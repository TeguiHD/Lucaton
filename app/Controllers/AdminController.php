<?php

class AdminController {
    private Database $db;
    private Campaign $campaigns;
    private User $users;
    private Donation $donations;
    private Notification $notifications;
    private AuditLogReader $auditReader;
    private array $campaignColumns = [];
    private ?string $campaignOwnerColumn = null;
    private bool $campaignHasStatus = false;
    private bool $campaignHasVisibility = false;
    private bool $campaignHasCreatedAt = false;
    private bool $campaignHasApprovedFields = false;
    private ?int $pendingCampaignsCache = null;
    private ?int $aiPendingModerationCache = null;
    private bool $campaignHasAiAssisted = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->campaigns = new Campaign();
        $this->users = new User();
        $this->donations = new Donation();
        $this->notifications = new Notification();
        $this->auditReader = new AuditLogReader();
        $this->detectCampaignSchema();
    }

    public function dashboard(): void {
        $this->ensureAdmin();

        $metrics = $this->buildDashboardMetrics();
        $reviewQueue = $this->fetchCampaignReviewQueue(5);
        $recentUsers = $this->fetchRecentUsers(4);
        $recentNotifications = $this->notifications->recent(5);

        $page_title = 'Panel de Administración';
        $current_page = 'admin-dashboard';
        $pending_campaigns_count = $metrics['pending_campaigns'];
        $ai_pending_count = $metrics['awaiting_peer_review'];

        include VIEWS_PATH . '/admin/dashboard.php';
    }

    public function campaigns(): void {
        $this->ensureAdmin();

        $filter = $_GET['filter'] ?? 'pending';
        $search = trim($_GET['search'] ?? '');

        $campaigns = $this->fetchCampaignsForModeration($filter, $search, 25);
        $pending_campaigns_count = $this->getPendingCampaignCount();

        $page_title = 'Moderación de Campañas';
        $current_page = 'admin-campaigns';
        $filters = [
            'filter' => $filter,
            'search' => $search,
        ];

        include VIEWS_PATH . '/admin/campaigns.php';
    }

    public function showCampaign($id): void
    {
        $this->ensureAdmin();

        $campaignId = (int)$id;
        if ($campaignId <= 0) {
            SessionHelper::setFlash('error', 'Campaña inválida.');
            Router::redirect('/admin/campanas');
            return;
        }

        $campaign = $this->campaigns->findById($campaignId);
        if (!$campaign) {
            SessionHelper::setFlash('error', 'No encontramos la campaña solicitada.');
            Router::redirect('/admin/campanas');
            return;
        }

        $presented = CampaignPresenter::present($campaign);
        $campaignView = array_merge($campaign, $presented);

        $mediaService = new CampaignMediaUploadService();
        $mediaManifest = $mediaService->readManifest($campaignId);

        $coverCandidate = $mediaManifest['cover_image'] ?? ($campaignView['cover_image_url'] ?? $campaignView['image_url'] ?? null);
        $coverImageUrl = CampaignMediaUploadService::normalizePublicUrl($coverCandidate)
            ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg';

        $galleryMedia = array_values(array_filter(array_map(static function ($item) {
            if (!is_array($item) || empty($item['url'])) {
                return null;
            }

            $item['url'] = CampaignMediaUploadService::normalizePublicUrl($item['url']) ?? $item['url'];
            return $item;
        }, $mediaManifest['gallery'] ?? [])));

        $previewableMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

        $attachments = array_values(array_filter(array_map(function ($item) use ($campaignId, $previewableMimes) {
            if (!is_array($item) || empty($item['path'])) {
                return null;
            }

            $relativePath = $item['path'];
            $encoded = rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=');
            $token = $this->signDocumentPath($campaignId, $relativePath);
            $downloadUrl = Router::url('admin/campana/' . $campaignId . '/documento', [
                'file' => $encoded,
                'token' => $token,
            ]);

            $previewUrl = null;
            $mime = $item['mime'] ?? null;
            if ($mime && in_array(strtolower($mime), $previewableMimes, true)) {
                $previewUrl = Router::url('admin/campana/' . $campaignId . '/documento', [
                    'file' => $encoded,
                    'token' => $token,
                    'mode' => 'inline',
                ]);
            }

            return [
                'filename' => $item['filename'] ?? basename($relativePath),
                'size' => (int)($item['size'] ?? 0),
                'mime' => $mime ?? 'application/octet-stream',
                'url' => $downloadUrl,
                'preview_url' => $previewUrl,
                'path' => $relativePath,
            ];
        }, $mediaManifest['attachments'] ?? [])));

        $videoUrl = trim((string)($campaignView['video_url'] ?? ''));
        $videoEmbed = $this->resolveYoutubeEmbed($videoUrl);

        $ownerProfile = null;
        $ownerId = $campaignView['owner_id'] ?? ($campaign['owner_id'] ?? null);
        if ($ownerId) {
            $ownerProfile = $this->users->findById((int)$ownerId);
            if ($ownerProfile) {
                $fullName = trim(($ownerProfile['first_name'] ?? '') . ' ' . ($ownerProfile['last_name'] ?? ''));
                if ($fullName === '' && !empty($ownerProfile['name'])) {
                    $fullName = $ownerProfile['name'];
                }
                if ($fullName === '') {
                    $fullName = $campaignView['owner_name'] ?? 'Campañista';
                }

                $ownerProfile['display_name'] = $fullName;
                $ownerProfile['avatar_url'] = SessionHelper::normalizeAvatarUrl($ownerProfile['avatar_url'] ?? null);
            }
        }

        $page_title = 'Revisión de campaña · ' . ($campaignView['title'] ?? 'Campaña');
        $current_page = 'admin-campaigns';
        $pending_campaigns_count = $this->getPendingCampaignCount();

        include VIEWS_PATH . '/admin/campaign-show.php';
    }

    public function downloadCampaignDocument($id): void
    {
        $this->ensureAdmin();

        $campaignId = (int)$id;
        $fileKey = $_GET['file'] ?? '';
        $token = $_GET['token'] ?? '';
        $mode = $_GET['mode'] ?? 'download';

        if ($campaignId <= 0 || $fileKey === '' || $token === '') {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $normalizedKey = strtr($fileKey, '-_', '+/');
        $padding = strlen($normalizedKey) % 4;
        if ($padding !== 0) {
            $normalizedKey .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalizedKey, true);
        if ($decoded === false) {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $relativePath = '/' . ltrim($decoded, '/');
        if (!$this->verifyDocumentSignature($campaignId, $relativePath, $token)) {
            http_response_code(403);
            echo 'No autorizado.';
            return;
        }

        $expectedPrefix = '/storage/private/campaigns/' . $campaignId . '/documents/';
        if (strpos($relativePath, $expectedPrefix) !== 0) {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $absolute = rtrim(ROOT_PATH, '/') . $relativePath;
        if (!is_file($absolute)) {
            http_response_code(404);
            echo 'Documento no disponible.';
            return;
        }

        $filename = basename($absolute);
        $mime = function_exists('mime_content_type') ? mime_content_type($absolute) : null;
        if (!$mime) {
            $mime = 'application/octet-stream';
        }

        $previewableMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $serveInline = ($mode === 'inline') && in_array(strtolower($mime), $previewableMimes, true);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($absolute));
        header('X-Content-Type-Options: nosniff');

        if ($serveInline) {
            header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        }

        readfile($absolute);
        exit;
    }

    public function aiModeration(): void {
        $this->ensureAdmin();

        $requestFilters = [
            'estado' => strtolower(trim($_GET['estado'] ?? 'pendientes')),
            'accion' => strtolower(trim($_GET['accion'] ?? 'pendientes')),
        ];

        $aiData = $this->buildAiModerationData($requestFilters);
        $aiSummary = $aiData['summary'];
        if (isset($aiSummary['needs_attention_total'])) {
            $this->aiPendingModerationCache = $aiSummary['needs_attention_total'];
        }

        $page_title = 'Moderación IA';
        $current_page = 'admin-ai';
        $pending_campaigns_count = $this->getPendingCampaignCount();
        $ai_pending_count = $this->getAiPendingCount();

        $aiSupported = $aiData['supported'];
        $aiStatusCounts = $aiData['status_counts'];
        $aiProviderCounts = $aiData['provider_counts'];
        $aiModeCounts = $aiData['mode_counts'];
        $aiGenerations = $aiData['generations'];
        $aiPolicyEvents = $aiData['policy_events'];
        $aiFilters = $aiData['filters'];
        $aiStatusMeta = $aiData['status_meta'];
        $aiPolicyMeta = $aiData['policy_meta'];

        include VIEWS_PATH . '/admin/ai-moderation.php';
    }

    public function auditLogs(): void {
        $this->ensureAdmin();

        $requestFilters = [
            'accion' => trim($_GET['accion'] ?? ''),
            'usuario' => isset($_GET['usuario']) ? (int)$_GET['usuario'] : null,
            'rol' => strtolower(trim($_GET['rol'] ?? '')),
            'ip' => trim($_GET['ip'] ?? ''),
            'limite' => isset($_GET['limite']) ? (int)$_GET['limite'] : 50,
        ];

        $auditData = $this->buildAuditOverview($requestFilters);

        $page_title = 'Auditoría';
        $current_page = 'admin-audit';
        $pending_campaigns_count = $this->getPendingCampaignCount();
        $ai_pending_count = $this->getAiPendingCount();

        $auditEvents = $auditData['events'];
        $auditSummary = $auditData['summary'];
        $auditFilters = $auditData['filters'];
        $auditUsers = $auditData['users'];
        $auditLimit = $auditData['limit'];

        include VIEWS_PATH . '/admin/audit.php';
    }

    public function statistics(): void {
        $this->ensureAdmin();

        $statistics = $this->buildStatisticsData();

        $page_title = 'Estadísticas';
        $current_page = 'admin-stats';
        $pending_campaigns_count = $this->getPendingCampaignCount();
        $ai_pending_count = $this->getAiPendingCount();

        include VIEWS_PATH . '/admin/statistics.php';
    }

    public function users(): void {
        $this->ensureAdmin();

        $role = $_GET['role'] ?? 'all';
        $search = trim($_GET['search'] ?? '');

        $users = $this->filterUsers($role, $search);
        $userStats = $this->buildUserStats();
        $pending_campaigns_count = $this->getPendingCampaignCount();

        $page_title = 'Gestión de Usuarios';
        $current_page = 'admin-users';
        $filters = [
            'role' => $role,
            'search' => $search,
        ];

        include VIEWS_PATH . '/admin/users.php';
    }

    public function showUser($id): void {
        $this->ensureAdmin();

        $userId = (int)$id;
        if ($userId <= 0) {
            SessionHelper::setFlash('error', 'Usuario inválido.');
            Router::redirect('/admin/usuarios');
        }

        $user = $this->users->findById($userId);
        if (!$user) {
            SessionHelper::setFlash('error', 'No encontramos a la persona seleccionada.');
            Router::redirect('/admin/usuarios');
        }

        $campaignStats = $this->fetchUserCampaignStats($userId);
        $donationStats = $this->fetchUserDonationStats($userId);

        $recentCampaigns = $this->campaigns->findByUserId($userId, 5);
        $recentDonations = $this->canQueryUserDonations() ? $this->donations->findByUserId($userId, 5) : [];
        $recentAuditEvents = $this->auditReader->getRecentEventsForUser($userId, 8);

        $page_title = 'Perfil de ' . ($user['first_name'] ?? $user['email'] ?? 'Usuario');
        $current_page = 'admin-users';
        $pending_campaigns_count = $this->getPendingCampaignCount();

        include VIEWS_PATH . '/admin/user-show.php';
    }

    public function resetUserPassword($id): void {
        $this->ensureAdmin();

        if (!SessionHelper::isSuperAdmin()) {
            SessionHelper::setFlash('error', 'No tienes permisos suficientes para restablecer contraseñas.');
            Router::redirect('/admin/usuarios');
        }

        $targetId = (int)$id;
        if ($targetId <= 0) {
            SessionHelper::setFlash('error', 'Usuario inválido.');
            Router::redirect('/admin/usuarios');
        }

        if ($targetId === (int)SessionHelper::getUserId()) {
            SessionHelper::setFlash('warning', 'Utiliza tu perfil para cambiar tu propia contraseña.');
            Router::redirect('/admin/usuarios');
        }

        $target = $this->users->findById($targetId);
        if (!$target) {
            SessionHelper::setFlash('error', 'No pudimos encontrar a la persona seleccionada.');
            Router::redirect('/admin/usuarios');
        }

        $targetRole = strtolower($target['role'] ?? 'user');
        if ($targetRole === 'superadmin') {
            SessionHelper::setFlash('error', 'No puedes restablecer la contraseña de otro superadministrador desde esta sección.');
            Router::redirect('/admin/usuarios');
        }

        try {
            $temporaryPassword = $this->generateTemporaryPassword();
            $this->users->changePassword($targetId, $temporaryPassword);
            Logger::audit('admin_reset_user_password', $targetId, [
                'actor_id' => SessionHelper::getUserId(),
            ]);

            SessionHelper::setFlash('success', 'Contraseña restablecida. Nueva clave temporal: ' . $temporaryPassword);
        } catch (Exception $exception) {
            Logger::error('Failed to reset user password as superadmin', [
                'actor_id' => SessionHelper::getUserId(),
                'target_id' => $targetId,
                'error' => $exception->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos restablecer la contraseña. Intenta nuevamente.');
        }

        Router::redirect('/admin/usuarios');
    }

    public function updateUserRole($id): void {
        $this->ensureAdmin();

        if (!SessionHelper::isSuperAdmin()) {
            SessionHelper::setFlash('error', 'No tienes permisos suficientes para modificar roles.');
            Router::redirect('/admin/usuarios');
        }

        $targetId = (int)$id;
        if ($targetId <= 0) {
            SessionHelper::setFlash('error', 'Usuario inválido.');
            Router::redirect('/admin/usuarios');
        }

        if ($targetId === (int)SessionHelper::getUserId()) {
            SessionHelper::setFlash('warning', 'No puedes modificar tu propio rol desde esta pantalla.');
            Router::redirect('/admin/usuarios');
        }

        $target = $this->users->findById($targetId);
        if (!$target) {
            SessionHelper::setFlash('error', 'No pudimos encontrar al usuario seleccionado.');
            Router::redirect('/admin/usuarios');
        }

        $currentRole = strtolower($target['role'] ?? 'user');
        if ($currentRole === 'superadmin') {
            SessionHelper::setFlash('error', 'No es posible modificar el rol de otro superadministrador.');
            Router::redirect('/admin/usuarios');
        }

        $newRole = strtolower(trim($_POST['role'] ?? ''));
        $allowedRoles = ['user', 'admin'];
        if (!in_array($newRole, $allowedRoles, true)) {
            SessionHelper::setFlash('error', 'Rol seleccionado no permitido.');
            Router::redirect('/admin/usuarios');
        }

        try {
            if ($currentRole === $newRole) {
                SessionHelper::setFlash('info', 'El usuario ya tiene el rol seleccionado.');
            } else {
                $this->users->assignRole($targetId, $newRole, SessionHelper::getUserId());
                SessionHelper::setFlash('success', 'El rol se actualizó correctamente.');
            }
        } catch (Exception $exception) {
            Logger::error('Failed to update user role as superadmin', [
                'actor_id' => SessionHelper::getUserId(),
                'target_id' => $targetId,
                'requested_role' => $newRole,
                'error' => $exception->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos actualizar el rol. Intenta nuevamente.');
        }

        Router::redirect('/admin/usuarios');
    }

    public function approveCampaign($id): void {
        $this->ensureAdmin();

        $campaignId = (int)$id;
        $campaign = $this->campaigns->findById($campaignId);

        if (!$campaign) {
            SessionHelper::setFlash('error', 'No encontramos la campaña indicada.');
            Router::redirect('/admin/campanas');
            return;
        }

        $currentAdmin = SessionHelper::getUserId();
        $ownerId = $campaign['owner_id'] ?? $campaign['user_id'] ?? null;

        if ($ownerId !== null && (int)$ownerId === (int)$currentAdmin) {
            SessionHelper::setFlash('warning', 'Otro administrador debe aprobar las campañas que creas.');
            Router::redirect('/admin/campanas?filter=pending');
            return;
        }

        if ($this->campaignHasStatus && !in_array($campaign['status'], ['under_review', 'paused'], true)) {
            SessionHelper::setFlash('info', 'La campaña ya no está pendiente de aprobación.');
            Router::redirect('/admin/campanas');
            return;
        }

        try {
            $this->campaigns->changeStatus($campaignId, 'published', $currentAdmin, 'Aprobada por administración');
            $this->applyApprovalMetadata($campaignId, [
                'visibility' => 'public',
                'approved_by' => $currentAdmin,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
            SessionHelper::setFlash('success', 'Campaña aprobada y publicada correctamente.');

            try {
                $lifecycleMailer = new CampaignLifecycleMailer();
                $campaignContext = $campaign;
                $campaignContext['status'] = 'published';
                $campaignContext['visibility'] = 'public';
                $lifecycleMailer->campaignApproved($campaignId, [
                    'campaign' => $campaignContext,
                    'owner_id' => (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0)
                ]);
            } catch (Throwable $mailerException) {
                Logger::warning('No se pudo preparar el correo de aprobación de campaña', [
                    'campaign_id' => $campaignId,
                    'error' => $mailerException->getMessage()
                ]);
            }
        } catch (Exception $exception) {
            Logger::error('Error approving campaign', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos aprobar la campaña. Intenta nuevamente.');
        }

        Router::redirect('/admin/campanas');
    }

    public function rejectCampaign($id): void {
        $this->ensureAdmin();

        $campaignId = (int)$id;
        $campaign = $this->campaigns->findById($campaignId);

        if (!$campaign) {
            SessionHelper::setFlash('error', 'No encontramos la campaña indicada.');
            Router::redirect('/admin/campanas');
        }

        $notes = trim($_POST['notes'] ?? '');
        if ($notes === '') {
            SessionHelper::setFlash('error', 'Debes ingresar un motivo para rechazar la campaña.');
            Router::redirect('/admin/campana/' . $campaignId . '#reject-form');
        }

        $notes = mb_substr($notes, 0, 500);

        try {
            $this->campaigns->changeStatus($campaignId, 'cancelled', SessionHelper::getUserId(), $notes);
            $this->applyApprovalMetadata($campaignId, [
                'visibility' => 'private',
                'approved_by' => null,
                'approved_at' => null,
            ]);
            SessionHelper::setFlash('success', 'La campaña fue rechazada y permanece privada.');

            try {
                $lifecycleMailer = new CampaignLifecycleMailer();
                $campaignContext = $campaign;
                $campaignContext['status'] = 'cancelled';
                $campaignContext['visibility'] = 'private';
                $lifecycleMailer->campaignRejected($campaignId, $notes, [
                    'campaign' => $campaignContext,
                    'owner_id' => (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0)
                ]);
            } catch (Throwable $mailerException) {
                Logger::warning('No se pudo preparar el correo de rechazo de campaña', [
                    'campaign_id' => $campaignId,
                    'error' => $mailerException->getMessage()
                ]);
            }
        } catch (Exception $exception) {
            Logger::error('Error rejecting campaign', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);
            SessionHelper::setFlash('error', 'No pudimos rechazar la campaña. Intenta nuevamente.');
        }

        Router::redirect('/admin/campanas');
    }

    private function ensureAdmin(): void {
        if (!SessionHelper::userHasRole('admin')) {
            http_response_code(403);
            if (file_exists(VIEWS_PATH . '/errors/403.php')) {
                include VIEWS_PATH . '/errors/403.php';
            }
            exit;
        }
    }

    private function buildDashboardMetrics(): array {
        $metrics = [
            'pending_campaigns' => $this->getPendingCampaignCount(),
            'active_campaigns' => 0,
            'private_campaigns' => 0,
            'awaiting_peer_review' => 0,
            'total_raised' => 0.0,
            'completed_donations' => 0,
            'total_users' => 0,
            'new_users_30_days' => 0,
            'admin_users' => 0,
        ];

        if ($this->db->tableExists('campaigns')) {
            if ($this->campaignHasStatus) {
                $metrics['active_campaigns'] = $this->countCampaignsByStatus(['published', 'active']);
                $metrics['awaiting_peer_review'] = $this->countPeerReviewQueue();
            }

            if ($this->campaignHasVisibility) {
                $metrics['private_campaigns'] = $this->countCampaignsByVisibility('private');
            }
        }

        if ($this->db->tableExists('donations')) {
            $where = '';
            if ($this->db->columnExists('donations', 'status')) {
                $where = "WHERE status = 'completed'";
            }

            $row = $this->db->fetch("SELECT COALESCE(SUM(amount), 0) AS total_amount, COUNT(*) AS total_count FROM donations {$where}");
            if ($row) {
                $metrics['total_raised'] = (float)$row['total_amount'];
                $metrics['completed_donations'] = (int)$row['total_count'];
            }
        }

        if ($this->db->tableExists('users')) {
            $totalRow = $this->db->fetch('SELECT COUNT(*) AS total FROM users');
            $metrics['total_users'] = (int)($totalRow['total'] ?? 0);

            if ($this->db->columnExists('users', 'role_id')) {
                $adminRow = $this->db->fetch(
                    "SELECT COUNT(*) AS total
                     FROM users u
                     INNER JOIN roles r ON r.id = u.role_id
                     WHERE r.slug = 'admin'"
                );
            } else {
                $adminRow = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
            }
            $metrics['admin_users'] = (int)($adminRow['total'] ?? 0);

            if ($this->db->columnExists('users', 'created_at')) {
                $recentRow = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $metrics['new_users_30_days'] = (int)($recentRow['total'] ?? 0);
            }
        }

        return $metrics;
    }

    private function buildAiModerationData(array $input): array {
        $normalizedFilters = [
            'estado' => strtolower($input['estado'] ?? 'pendientes'),
            'accion' => strtolower($input['accion'] ?? 'pendientes'),
        ];

        $stateOptions = [
            'pendientes' => ['pending', 'moderated'],
            'moderadas' => ['moderated'],
            'fallidas' => ['failed'],
            'rechazadas' => ['rejected'],
            'completadas' => ['completed'],
            'todas' => null,
        ];

        if (!array_key_exists($normalizedFilters['estado'], $stateOptions)) {
            $normalizedFilters['estado'] = 'pendientes';
        }

        $policyOptions = [
            'pendientes' => ['flagged', 'blocked'],
            'bloqueadas' => ['blocked'],
            'permitidas' => ['allowed'],
            'revisadas' => ['reviewed'],
            'todas' => null,
        ];

        if (!array_key_exists($normalizedFilters['accion'], $policyOptions)) {
            $normalizedFilters['accion'] = 'pendientes';
        }

        $statusMeta = [
            'pending' => [
                'label' => 'Pendiente',
                'description' => 'Requiere revisión manual',
                'badge_class' => 'bg-amber-100 text-amber-800',
            ],
            'moderated' => [
                'label' => 'Moderado',
                'description' => 'Revisión automática aplicada',
                'badge_class' => 'bg-blue-100 text-blue-800',
            ],
            'completed' => [
                'label' => 'Completado',
                'description' => 'Generación entregada al usuario',
                'badge_class' => 'bg-emerald-100 text-emerald-700',
            ],
            'failed' => [
                'label' => 'Falló',
                'description' => 'El proveedor devolvió error',
                'badge_class' => 'bg-rose-100 text-rose-700',
            ],
            'rejected' => [
                'label' => 'Rechazado',
                'description' => 'Bloqueado por políticas de seguridad',
                'badge_class' => 'bg-red-100 text-red-800',
            ],
            'unknown' => [
                'label' => 'Sin estado',
                'description' => 'Estructura previa a migraciones recientes',
                'badge_class' => 'bg-gray-100 text-gray-700',
            ],
        ];

        $policyMeta = [
            'allowed' => [
                'label' => 'Permitido',
                'badge_class' => 'bg-emerald-100 text-emerald-700',
            ],
            'blocked' => [
                'label' => 'Bloqueado',
                'badge_class' => 'bg-red-100 text-red-800',
            ],
            'flagged' => [
                'label' => 'Marcado',
                'badge_class' => 'bg-amber-100 text-amber-800',
            ],
            'reviewed' => [
                'label' => 'Revisado',
                'badge_class' => 'bg-blue-100 text-blue-800',
            ],
        ];

        $supported = $this->db->tableExists('ai_generations');
        $policySupported = $this->db->tableExists('ai_policy_logs');

        if (!$supported) {
            return [
                'supported' => false,
                'status_counts' => [],
                'provider_counts' => [],
                'mode_counts' => [],
                'generations' => [],
                'policy_events' => [],
                'summary' => [
                    'total_generations' => 0,
                    'needs_attention_total' => 0,
                    'flagged_events' => 0,
                    'avg_latency' => null,
                    'avg_cost' => null,
                    'last_24h' => 0,
                ],
                'filters' => $normalizedFilters,
                'status_meta' => $statusMeta,
                'policy_meta' => $policyMeta,
            ];
        }

        $hasStatusColumn = $this->db->columnExists('ai_generations', 'status');
        $hasModeColumn = $this->db->columnExists('ai_generations', 'mode');
        $hasProviderColumn = $this->db->columnExists('ai_generations', 'provider');
        $hasModelColumn = $this->db->columnExists('ai_generations', 'model_used');
        $hasTokensInputColumn = $this->db->columnExists('ai_generations', 'tokens_input');
        $hasTokensOutputColumn = $this->db->columnExists('ai_generations', 'tokens_output');
        $hasCostColumn = $this->db->columnExists('ai_generations', 'cost_estimate');
        $hasLatencyColumn = $this->db->columnExists('ai_generations', 'latency_ms');
        $hasCreatedAtColumn = $this->db->columnExists('ai_generations', 'created_at');

        if (!$hasStatusColumn) {
            $normalizedFilters['estado'] = 'todas';
        }

        $summary = [
            'total_generations' => 0,
            'needs_attention_total' => 0,
            'flagged_events' => 0,
            'avg_latency' => null,
            'avg_cost' => null,
            'last_24h' => 0,
        ];

        $statusCounts = [];
        if ($hasStatusColumn) {
            foreach ($this->db->fetchAll("SELECT status, COUNT(*) AS total FROM ai_generations GROUP BY status") as $row) {
                $status = $row['status'] ?? 'pending';
                $statusCounts[$status] = (int)($row['total'] ?? 0);
            }

            $orderedStatusCounts = [];
            foreach (array_keys($statusMeta) as $statusKey) {
                if (array_key_exists($statusKey, $statusCounts)) {
                    $orderedStatusCounts[$statusKey] = $statusCounts[$statusKey];
                }
            }
            foreach ($statusCounts as $key => $value) {
                if (!array_key_exists($key, $orderedStatusCounts)) {
                    $orderedStatusCounts[$key] = $value;
                }
            }
            $statusCounts = $orderedStatusCounts;
        } else {
            $row = $this->db->fetch("SELECT COUNT(*) AS total FROM ai_generations");
            $statusCounts['unknown'] = (int)($row['total'] ?? 0);
            ksort($statusCounts);
        }

        $summary['total_generations'] = array_sum($statusCounts);

        if ($hasStatusColumn) {
            $needsAttentionStatuses = ['pending', 'moderated'];
            foreach ($needsAttentionStatuses as $status) {
                if (isset($statusCounts[$status])) {
                    $summary['needs_attention_total'] += $statusCounts[$status];
                }
            }
        }

        $providerCounts = [];
        if ($hasProviderColumn) {
            foreach ($this->db->fetchAll("SELECT provider, COUNT(*) AS total FROM ai_generations GROUP BY provider ORDER BY total DESC") as $row) {
                $provider = $row['provider'] ?? 'openai';
                $providerCounts[$provider] = (int)($row['total'] ?? 0);
            }
        } elseif ($summary['total_generations'] > 0) {
            $providerCounts['sin_dato'] = $summary['total_generations'];
        }

        $modeCounts = [];
        if ($hasModeColumn) {
            foreach ($this->db->fetchAll("SELECT mode, COUNT(*) AS total FROM ai_generations GROUP BY mode ORDER BY total DESC") as $row) {
                $mode = $row['mode'] ?? 'text';
                $modeCounts[$mode] = (int)($row['total'] ?? 0);
            }
        } elseif ($summary['total_generations'] > 0) {
            $modeCounts['text'] = $summary['total_generations'];
        }

        $avgSelect = [];
        if ($hasLatencyColumn) {
            $avgSelect[] = 'AVG(latency_ms) AS avg_latency';
        }
        if ($hasCostColumn) {
            $avgSelect[] = 'AVG(cost_estimate) AS avg_cost';
        }

        if (!empty($avgSelect)) {
            $avgRow = $this->db->fetch('SELECT ' . implode(', ', $avgSelect) . ' FROM ai_generations');
            if ($avgRow) {
                if (isset($avgRow['avg_latency']) && $avgRow['avg_latency'] !== null) {
                    $summary['avg_latency'] = round((float)$avgRow['avg_latency'], 2);
                }
                if (isset($avgRow['avg_cost']) && $avgRow['avg_cost'] !== null) {
                    $summary['avg_cost'] = round((float)$avgRow['avg_cost'], 5);
                }
            }
        }

        if ($hasCreatedAtColumn) {
            $recentRow = $this->db->fetch(
                "SELECT COUNT(*) AS total FROM ai_generations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $summary['last_24h'] = (int)($recentRow['total'] ?? 0);
        }

        if ($policySupported) {
            $flaggedRow = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM ai_policy_logs
                 WHERE action IN ('flagged','blocked')"
            );
            $summary['flagged_events'] = (int)($flaggedRow['total'] ?? 0);

            $pendingReviewRow = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM ai_policy_logs
                 WHERE action IN ('flagged','blocked')
                   AND (reviewed_at IS NULL OR reviewed_at = '0000-00-00 00:00:00')"
            );
            $summary['needs_attention_total'] += (int)($pendingReviewRow['total'] ?? 0);
        }

        $generationLimit = 25;
        $generationConditions = [];
        $generationParams = [];
        $stateFilter = $hasStatusColumn ? $stateOptions[$normalizedFilters['estado']] : null;
        if ($hasStatusColumn && is_array($stateFilter) && !empty($stateFilter)) {
            $placeholders = implode(',', array_fill(0, count($stateFilter), '?'));
            $generationConditions[] = "g.status IN ({$placeholders})";
            $generationParams = array_merge($generationParams, $stateFilter);
        }

        $generationWhere = '';
        if (!empty($generationConditions)) {
            $generationWhere = 'WHERE ' . implode(' AND ', $generationConditions);
        }

        $orderColumn = $hasCreatedAtColumn ? 'g.created_at' : 'g.id';
        $generationRows = $this->db->fetchAll(
            "SELECT g.*, u.first_name, u.last_name, u.email, u.username
             FROM ai_generations g
             LEFT JOIN users u ON u.id = g.user_id
             {$generationWhere}
             ORDER BY {$orderColumn} DESC
             LIMIT {$generationLimit}",
            $generationParams
        );

        $generations = array_map(static function (array $row) use ($statusMeta, $hasModeColumn, $hasProviderColumn, $hasTokensInputColumn, $hasTokensOutputColumn, $hasCostColumn, $hasLatencyColumn) {
            $moderation = null;
            if (!empty($row['moderation_result'])) {
                $decoded = json_decode($row['moderation_result'], true);
                if (is_array($decoded)) {
                    $moderation = $decoded;
                }
            }

            $prompt = $row['prompt'] ?? '';
            $promptExcerpt = $prompt;
            if (mb_strlen($promptExcerpt) > 160) {
                $promptExcerpt = mb_substr($promptExcerpt, 0, 157) . '…';
            }

            $userName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($userName === '') {
                $userName = $row['email'] ?? 'Usuario';
            }

            $status = $row['status'] ?? 'pending';

            return [
                'id' => (int)$row['id'],
                'status' => $status,
                'status_meta' => $statusMeta[$status] ?? [
                    'label' => ucfirst($status),
                    'description' => '',
                    'badge_class' => 'bg-gray-100 text-gray-700',
                ],
                'mode' => $hasModeColumn ? ($row['mode'] ?? 'text') : 'text',
                'provider' => $hasProviderColumn ? ($row['provider'] ?? 'openai') : 'openai',
                'model_used' => $row['model_used'] ?? '',
                'prompt_excerpt' => $promptExcerpt,
                'prompt_length' => mb_strlen($prompt),
                'user_name' => $userName,
                'user_email' => $row['email'] ?? '',
                'username' => $row['username'] ?? '',
                'context_type' => $row['context_entity_type'] ?? 'standalone',
                'context_id' => $row['context_entity_id'] ?? null,
                'tokens_input' => ($hasTokensInputColumn && isset($row['tokens_input'])) ? (int)$row['tokens_input'] : null,
                'tokens_output' => ($hasTokensOutputColumn && isset($row['tokens_output'])) ? (int)$row['tokens_output'] : null,
                'cost_estimate' => ($hasCostColumn && isset($row['cost_estimate'])) ? (float)$row['cost_estimate'] : null,
                'latency_ms' => ($hasLatencyColumn && isset($row['latency_ms'])) ? (int)$row['latency_ms'] : null,
                'error_message' => $row['error_message'] ?? null,
                'moderation_result' => $moderation,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }, $generationRows ?: []);

        $policyEvents = [];
        if ($policySupported) {
            $policyLimit = 20;
            $policyConditions = [];
            $policyParams = [];
            $policyFilter = $policyOptions[$normalizedFilters['accion']];
            if (is_array($policyFilter) && !empty($policyFilter)) {
                $placeholders = implode(',', array_fill(0, count($policyFilter), '?'));
                $policyConditions[] = "p.action IN ({$placeholders})";
                $policyParams = array_merge($policyParams, $policyFilter);
            }

            $policyWhere = '';
            if (!empty($policyConditions)) {
                $policyWhere = 'WHERE ' . implode(' AND ', $policyConditions);
            }

            $policySelectParts = ['p.*'];
            if ($hasStatusColumn) {
                $policySelectParts[] = 'g.status AS generation_status';
            }
            if ($hasModeColumn) {
                $policySelectParts[] = 'g.mode AS generation_mode';
            }
            if ($hasProviderColumn) {
                $policySelectParts[] = 'g.provider AS generation_provider';
            }
            if ($hasModelColumn) {
                $policySelectParts[] = 'g.model_used AS generation_model';
            }
            if ($hasCreatedAtColumn) {
                $policySelectParts[] = 'g.created_at AS generation_created_at';
            }
            $policySelectParts[] = 'u.email';
            $policySelectParts[] = 'u.first_name';
            $policySelectParts[] = 'u.last_name';
            $policySelectParts[] = 'reviewer.email AS reviewer_email';
            $policySelectParts[] = 'reviewer.first_name AS reviewer_first_name';
            $policySelectParts[] = 'reviewer.last_name AS reviewer_last_name';

            $policyRows = $this->db->fetchAll(
                'SELECT ' . implode(', ', $policySelectParts) . "
                 FROM ai_policy_logs p
                 LEFT JOIN ai_generations g ON g.id = p.ai_generation_id
                 LEFT JOIN users u ON u.id = p.user_id
                 LEFT JOIN users reviewer ON reviewer.id = p.reviewer_id
                 {$policyWhere}
                 ORDER BY p.created_at DESC
                 LIMIT {$policyLimit}",
                $policyParams
            );

            $policyEvents = array_map(static function (array $row) use ($policyMeta, $statusMeta) {
                $reason = $row['reason'] ?? '';
                if ($reason === '' && !empty($row['flagged_content'])) {
                    $reason = 'Contenido marcado para revisión';
                }

                $eventAction = $row['action'] ?? 'flagged';
                $actionMeta = $policyMeta[$eventAction] ?? [
                    'label' => ucfirst($eventAction),
                    'badge_class' => 'bg-gray-100 text-gray-700',
                ];

                $userName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($userName === '') {
                    $userName = $row['email'] ?? 'Usuario';
                }

                $reviewerName = trim(($row['reviewer_first_name'] ?? '') . ' ' . ($row['reviewer_last_name'] ?? ''));
                if ($reviewerName === '') {
                    $reviewerName = $row['reviewer_email'] ?? null;
                }

                $flaggedContent = $row['flagged_content'] ?? null;
                if ($flaggedContent !== null) {
                    $jsonDecoded = json_decode($flaggedContent, true);
                    if (is_array($jsonDecoded)) {
                        $flaggedContent = $jsonDecoded;
                    }
                }

                $moderationStatus = $row['generation_status'] ?? null;

                return [
                    'id' => (int)$row['id'],
                    'action' => $eventAction,
                    'action_meta' => $actionMeta,
                    'policy_type' => $row['policy_type'] ?? 'moderation',
                    'reason' => $reason,
                    'confidence_score' => isset($row['confidence_score']) ? (float)$row['confidence_score'] : null,
                    'flagged_content' => $flaggedContent,
                    'user_name' => $userName,
                    'user_email' => $row['email'] ?? '',
                    'ai_generation_id' => isset($row['ai_generation_id']) ? (int)$row['ai_generation_id'] : null,
                    'generation_status' => $moderationStatus,
                    'generation_status_meta' => $moderationStatus ? ($statusMeta[$moderationStatus] ?? null) : null,
                    'generation_provider' => $row['generation_provider'] ?? null,
                    'generation_mode' => $row['generation_mode'] ?? null,
                    'generation_model' => $row['generation_model'] ?? null,
                    'reviewer_name' => $reviewerName,
                    'reviewed_at' => $row['reviewed_at'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                ];
            }, $policyRows ?: []);
        }

        $this->aiPendingModerationCache = $summary['needs_attention_total'];

        return [
            'supported' => true,
            'status_counts' => $statusCounts,
            'provider_counts' => $providerCounts,
            'mode_counts' => $modeCounts,
            'generations' => $generations,
            'policy_events' => $policyEvents,
            'summary' => $summary,
            'filters' => $normalizedFilters,
            'status_meta' => $statusMeta,
            'policy_meta' => $policyMeta,
        ];
    }

    private function buildAuditOverview(array $input): array {
        $limit = isset($input['limite']) ? (int)$input['limite'] : 50;
        if ($limit < 10) {
            $limit = 10;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $filters = [
            'accion' => trim($input['accion'] ?? ''),
            'usuario' => isset($input['usuario']) && (int)$input['usuario'] > 0 ? (int)$input['usuario'] : null,
            'rol' => strtolower(trim($input['rol'] ?? '')),
            'ip' => trim($input['ip'] ?? ''),
        ];

        $summary = [
            'supported' => $this->db->tableExists('audit_events'),
            'total' => 0,
            'last_24h' => 0,
            'admin_actions' => 0,
            'distinct_users' => 0,
            'top_actions' => [],
            'sources' => [
                'database' => false,
                'log_files' => true,
            ],
        ];

        $events = [];
        $hasRoleId = $this->db->columnExists('users', 'role_id');
        $joins = '';
        $selectRole = '';
        if ($hasRoleId) {
            $selectRole = ', r.slug AS role_slug, r.name AS role_name, r.is_admin';
            $joins = "LEFT JOIN users u ON u.id = e.user_id
                      LEFT JOIN roles r ON r.id = u.role_id";
        } elseif ($this->db->columnExists('users', 'role')) {
            $selectRole = ', u.role';
            $joins = "LEFT JOIN users u ON u.id = e.user_id";
        } elseif ($this->db->tableExists('users')) {
            $joins = "LEFT JOIN users u ON u.id = e.user_id";
        }

        if ($summary['supported']) {
            $summary['sources']['database'] = true;

            $totalRow = $this->db->fetch("SELECT COUNT(*) AS total FROM audit_events");
            $summary['total'] = (int)($totalRow['total'] ?? 0);

            $recentRow = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM audit_events
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $summary['last_24h'] = (int)($recentRow['total'] ?? 0);

            $distinctRow = $this->db->fetch(
                "SELECT COUNT(DISTINCT user_id) AS total
                 FROM audit_events
                 WHERE user_id IS NOT NULL"
            );
            $summary['distinct_users'] = (int)($distinctRow['total'] ?? 0);

            if ($hasRoleId) {
                $adminRow = $this->db->fetch(
                    "SELECT COUNT(*) AS total
                     FROM audit_events e
                     INNER JOIN users u ON u.id = e.user_id
                     INNER JOIN roles r ON r.id = u.role_id
                     WHERE r.is_admin = 1"
                );
                $summary['admin_actions'] = (int)($adminRow['total'] ?? 0);
            } elseif ($this->db->columnExists('users', 'role')) {
                $adminRow = $this->db->fetch(
                    "SELECT COUNT(*) AS total
                     FROM audit_events e
                     INNER JOIN users u ON u.id = e.user_id
                     WHERE u.role IN ('admin','superadmin')"
                );
                $summary['admin_actions'] = (int)($adminRow['total'] ?? 0);
            }

            $summary['top_actions'] = $this->db->fetchAll(
                "SELECT action, COUNT(*) AS total
                 FROM audit_events
                 GROUP BY action
                 ORDER BY total DESC
                 LIMIT 5"
            ) ?: [];

            $conditions = [];
            $params = [];

            if ($filters['accion'] !== '') {
                $conditions[] = 'e.action LIKE ?';
                $params[] = '%' . $filters['accion'] . '%';
            }

            if ($filters['usuario'] !== null) {
                $conditions[] = 'e.user_id = ?';
                $params[] = $filters['usuario'];
            }

            if ($filters['ip'] !== '') {
                $conditions[] = 'e.ip_address LIKE ?';
                $params[] = '%' . $filters['ip'] . '%';
            }

            if ($filters['rol'] !== '' && $joins !== '') {
                if ($hasRoleId) {
                    $conditions[] = 'r.slug = ?';
                    $params[] = $filters['rol'];
                } else {
                    $conditions[] = 'u.role = ?';
                    $params[] = $filters['rol'];
                }
            }

            $where = '';
            if (!empty($conditions)) {
                $where = 'WHERE ' . implode(' AND ', $conditions);
            }

            $dbRows = $this->db->fetchAll(
                "SELECT e.*, u.first_name, u.last_name, u.email{$selectRole}
                 FROM audit_events e
                 {$joins}
                 {$where}
                 ORDER BY e.created_at DESC
                 LIMIT {$limit}",
                $params
            );

            foreach ($dbRows as $row) {
                $roleSlug = $row['role_slug'] ?? $row['role'] ?? null;

                $context = null;
                if (!empty($row['context'])) {
                    $decodedContext = json_decode($row['context'], true);
                    if (is_array($decodedContext)) {
                        $context = $decodedContext;
                    }
                }

                $metadata = null;
                if (!empty($row['metadata'])) {
                    $decodedMetadata = json_decode($row['metadata'], true);
                    if (is_array($decodedMetadata)) {
                        $metadata = $decodedMetadata;
                    }
                }

                $userName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($userName === '') {
                    $userName = $row['email'] ?? null;
                }

                $events[] = [
                    'source' => 'database',
                    'occurred_at' => $row['created_at'] ?? null,
                    'action' => $row['action'] ?? '',
                    'entity_type' => $row['entity_type'] ?? '',
                    'entity_id' => isset($row['entity_id']) ? (int)$row['entity_id'] : null,
                    'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
                    'user_name' => $userName,
                    'user_email' => $row['email'] ?? null,
                    'role' => $roleSlug,
                    'ip' => $row['ip_address'] ?? null,
                    'user_agent' => $row['user_agent'] ?? null,
                    'context' => $context,
                    'metadata' => $metadata,
                ];
            }
        }

        $logReaderLimit = min($limit, 100);
        $logFilters = [];
        if ($filters['usuario'] !== null) {
            $logFilters['user_id'] = $filters['usuario'];
        }
        if ($filters['accion'] !== '') {
            $logFilters['action'] = $filters['accion'];
        }
        if ($filters['ip'] !== '') {
            $logFilters['ip'] = $filters['ip'];
        }

        $logEvents = $this->auditReader->getRecentEvents($logReaderLimit, $logFilters);

        if (!$summary['supported']) {
            $summary['total'] = count($logEvents);

            $threshold = time() - 86400;
            $recentCount = 0;
            $distinctUsers = [];
            $actionTotals = [];

            foreach ($logEvents as $logEvent) {
                if (!empty($logEvent['timestamp']) && (int)$logEvent['timestamp'] >= $threshold) {
                    $recentCount++;
                }

                $actorId = $logEvent['actor_id'] ?? null;
                if ($actorId !== null) {
                    $distinctUsers[$actorId] = true;
                }

                $targetId = $logEvent['details']['user_id'] ?? ($logEvent['target_id'] ?? null);
                if ($targetId !== null) {
                    $distinctUsers[$targetId] = true;
                }

                $actionKey = trim((string)($logEvent['action'] ?? 'evento'));
                if (!isset($actionTotals[$actionKey])) {
                    $actionTotals[$actionKey] = 0;
                }
                $actionTotals[$actionKey]++;
            }

            $summary['last_24h'] = $recentCount;
            $summary['distinct_users'] = count($distinctUsers);

            arsort($actionTotals);
            $summary['top_actions'] = [];
            foreach (array_slice($actionTotals, 0, 5, true) as $action => $total) {
                $summary['top_actions'][] = [
                    'action' => $action,
                    'total' => $total,
                ];
            }
        }

        foreach ($logEvents as $event) {
            $occurredAt = date('Y-m-d H:i:s', (int)$event['timestamp']);
            $events[] = [
                'source' => 'log',
                'occurred_at' => $occurredAt,
                'action' => $event['action'] ?? '',
                'entity_type' => $event['details']['entity_type'] ?? ($event['details']['scope'] ?? 'event'),
                'entity_id' => isset($event['details']['entity_id']) ? (int)$event['details']['entity_id'] : null,
                'user_id' => isset($event['details']['actor_id']) ? (int)$event['details']['actor_id'] : ($event['actor_id'] ?? null),
                'user_name' => $event['details']['actor_name'] ?? null,
                'user_email' => $event['details']['actor_email'] ?? null,
                'role' => null,
                'ip' => $event['ip'] ?? null,
                'user_agent' => $event['user_agent'] ?? null,
                'context' => $event['details'] ?? [],
                'metadata' => null,
            ];
        }

        usort($events, static function (array $a, array $b) {
            return strcmp($b['occurred_at'] ?? '', $a['occurred_at'] ?? '');
        });

        $events = array_slice($events, 0, $limit);

        $userOptions = array_map(static function (array $user) {
            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if ($name === '') {
                $name = $user['email'] ?? ('Usuario #' . $user['id']);
            }

            return [
                'id' => (int)$user['id'],
                'label' => $name . (!empty($user['email']) ? ' (' . $user['email'] . ')' : ''),
            ];
        }, array_slice($this->users->getAllUsers(), 0, 200));

        return [
            'events' => $events,
            'summary' => $summary,
            'filters' => array_merge($filters, ['limite' => $limit]),
            'users' => $userOptions,
            'limit' => $limit,
        ];
    }

    private function buildStatisticsData(): array {
        $supported = $this->db->tableExists('campaigns');

        $campaignStats = [
            'total' => 0,
            'publicadas' => 0,
            'en_revision' => 0,
            'ai' => 0,
            'manual' => 0,
            'ai_percentage' => 0.0,
            'nuevas_30_dias' => 0,
            'meta_promedio' => null,
        ];

        $donationStats = [
            'total_donaciones' => 0,
            'monto_total' => 0.0,
            'promedio_donacion' => null,
            'conversion_rate' => null,
            'ratio_visitantes_donantes' => null,
            'donaciones_30_dias' => 0,
            'monto_30_dias' => 0.0,
        ];

        $engagementStats = [
            'total_visitantes' => 0,
            'total_donantes' => 0,
            'visitas_30_dias' => 0,
        ];

        $categoryLeaders = [];
        $aiByCategory = [];

        if (!$supported) {
            return [
                'supported' => false,
                'campaign' => $campaignStats,
                'donations' => $donationStats,
                'engagement' => $engagementStats,
                'category_leaders' => $categoryLeaders,
                'ai_by_category' => $aiByCategory,
            ];
        }

        $campaignStats['total'] = (int)($this->db->fetch("SELECT COUNT(*) AS total FROM campaigns")['total'] ?? 0);

        if ($this->campaignHasStatus) {
            $campaignStats['publicadas'] = $this->countCampaignsByStatus(['published', 'active']);
            $campaignStats['en_revision'] = $this->countCampaignsByStatus(['under_review']);
        }

        if ($this->campaignHasAiAssisted) {
            $aiRow = $this->db->fetch("SELECT COUNT(*) AS total FROM campaigns WHERE ai_assisted = 1");
            $campaignStats['ai'] = (int)($aiRow['total'] ?? 0);
            $campaignStats['manual'] = max(0, $campaignStats['total'] - $campaignStats['ai']);
            if ($campaignStats['total'] > 0) {
                $campaignStats['ai_percentage'] = round(($campaignStats['ai'] / $campaignStats['total']) * 100, 1);
            }
        } else {
            $campaignStats['manual'] = $campaignStats['total'];
        }

        if ($this->campaignHasCreatedAt) {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM campaigns
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $campaignStats['nuevas_30_dias'] = (int)($row['total'] ?? 0);
        }

        if ($this->db->columnExists('campaigns', 'goal_amount')) {
            $row = $this->db->fetch("SELECT AVG(goal_amount) AS promedio FROM campaigns WHERE goal_amount > 0");
            if ($row && $row['promedio'] !== null) {
                $campaignStats['meta_promedio'] = round((float)$row['promedio'], 2);
            }
        }

        if ($this->db->tableExists('campaign_metrics')) {
            $metricsRow = $this->db->fetch(
                "SELECT
                    SUM(view_count) AS views,
                    SUM(donor_count) AS donors,
                    SUM(raised_amount) AS raised,
                    SUM(share_count) AS shares
                 FROM campaign_metrics"
            );

            if ($metricsRow) {
                $engagementStats['total_visitantes'] = (int)($metricsRow['views'] ?? 0);
                $engagementStats['total_donantes'] = (int)($metricsRow['donors'] ?? 0);
                $donationStats['monto_total'] = (float)($metricsRow['raised'] ?? 0);
            }

            $recentMetricsRow = $this->db->fetch(
                "SELECT
                    SUM(view_count) AS views,
                    SUM(donor_count) AS donors,
                    SUM(raised_amount) AS raised
                 FROM campaign_metrics
                 WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );

            if ($recentMetricsRow) {
                $engagementStats['visitas_30_dias'] = (int)($recentMetricsRow['views'] ?? 0);
                $donationStats['donaciones_30_dias'] = (int)($recentMetricsRow['donors'] ?? 0);
                $donationStats['monto_30_dias'] = (float)($recentMetricsRow['raised'] ?? 0);
            }
        }

        if ($this->db->tableExists('donations')) {
            $where = '';
            if ($this->db->columnExists('donations', 'status')) {
                $where = "WHERE status = 'completed'";
            }

            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total, COALESCE(SUM(amount), 0) AS montos
                 FROM donations
                 {$where}"
            );

            if ($row) {
                $donationStats['total_donaciones'] = (int)($row['total'] ?? 0);
                $donationStats['monto_total'] = (float)($row['montos'] ?? $donationStats['monto_total']);
                if ($donationStats['total_donaciones'] > 0) {
                    $donationStats['promedio_donacion'] = round($donationStats['monto_total'] / $donationStats['total_donaciones'], 2);
                }
            }

            if ($this->db->columnExists('donations', 'created_at')) {
                $recentRow = $this->db->fetch(
                    "SELECT COUNT(*) AS total, COALESCE(SUM(amount), 0) AS montos
                     FROM donations
                     {$where}
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                );
                if ($recentRow) {
                    $donationStats['donaciones_30_dias'] = (int)($recentRow['total'] ?? $donationStats['donaciones_30_dias']);
                    $donationStats['monto_30_dias'] = (float)($recentRow['montos'] ?? $donationStats['monto_30_dias']);
                }
            }
        }

        if ($engagementStats['total_visitantes'] > 0 && $engagementStats['total_donantes'] > 0) {
            $donationStats['conversion_rate'] = round(($engagementStats['total_donantes'] / $engagementStats['total_visitantes']) * 100, 2);
            $donationStats['ratio_visitantes_donantes'] = round($engagementStats['total_visitantes'] / max(1, $engagementStats['total_donantes']), 2);
        }

        if ($this->db->tableExists('campaign_categories') && $this->db->tableExists('campaign_metrics')) {
            $categoryRows = $this->db->fetchAll(
                "SELECT cat.name, cat.slug,
                        SUM(cm.raised_amount) AS raised,
                        SUM(cm.donor_count) AS donors,
                        SUM(cm.view_count) AS views
                 FROM campaigns c
                 INNER JOIN campaign_categories cat ON cat.id = c.category_id
                 INNER JOIN campaign_metrics cm ON cm.campaign_id = c.id
                 GROUP BY cat.id, cat.name, cat.slug
                 ORDER BY raised DESC
                 LIMIT 6"
            );

            $categoryLeaders = array_map(static function (array $row) {
                return [
                    'name' => $row['name'] ?? 'Categoría',
                    'slug' => $row['slug'] ?? null,
                    'raised' => round((float)($row['raised'] ?? 0), 2),
                    'donors' => (int)($row['donors'] ?? 0),
                    'views' => (int)($row['views'] ?? 0),
                ];
            }, $categoryRows ?: []);

            if ($this->campaignHasAiAssisted) {
                $aiCategoryRows = $this->db->fetchAll(
                    "SELECT cat.name,
                            SUM(cm.raised_amount) AS raised,
                            COUNT(*) AS total_campaigns
                     FROM campaigns c
                     INNER JOIN campaign_categories cat ON cat.id = c.category_id
                     INNER JOIN campaign_metrics cm ON cm.campaign_id = c.id
                     WHERE c.ai_assisted = 1
                     GROUP BY cat.id, cat.name
                     ORDER BY raised DESC"
                );

                $aiByCategory = array_map(static function (array $row) {
                    return [
                        'name' => $row['name'] ?? 'Categoría',
                        'raised' => round((float)($row['raised'] ?? 0), 2),
                        'campaigns' => (int)($row['total_campaigns'] ?? 0),
                    ];
                }, $aiCategoryRows ?: []);
            }
        }

        return [
            'supported' => true,
            'campaign' => $campaignStats,
            'donations' => $donationStats,
            'engagement' => $engagementStats,
            'category_leaders' => $categoryLeaders,
            'ai_by_category' => $aiByCategory,
        ];
    }

    private function fetchCampaignReviewQueue(int $limit = 5): array {
        if (!$this->db->tableExists('campaigns')) {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $conditions = [];
        $params = [];

        if ($this->campaignHasStatus) {
            $conditions[] = "c.status = 'under_review'";
        }

        if ($this->campaignHasVisibility) {
            $conditions[] = "c.visibility <> 'archived'";
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $order = $this->campaignHasCreatedAt ? 'c.created_at DESC' : 'c.id DESC';

        $ownerJoin = '';
        $ownerSelect = '';
        if ($this->campaignOwnerColumn) {
            if ($this->db->columnExists('users', 'role_id')) {
                $ownerJoin = "LEFT JOIN users u ON u.id = c.{$this->campaignOwnerColumn} " .
                             "LEFT JOIN roles r ON r.id = u.role_id";
                $ownerSelect = ', u.first_name, u.last_name, u.email, r.slug AS owner_role';
            } else {
                $ownerJoin = "LEFT JOIN users u ON u.id = c.{$this->campaignOwnerColumn}";
                $ownerSelect = ', u.first_name, u.last_name, u.email, u.role AS owner_role';
            }
        }

        $rows = $this->db->fetchAll(
            "SELECT c.*{$ownerSelect}
             FROM campaigns c
             {$ownerJoin}
             {$where}
             ORDER BY {$order}
             LIMIT {$limit}"
        );

        if (!$rows) {
            return [];
        }

        return array_map(function (array $row) {
            $presented = CampaignPresenter::present($row);
            $presented['owner_email'] = $row['email'] ?? null;
            $presented['owner_role'] = $row['owner_role'] ?? 'user';
            $presented['submitted_at'] = $row['created_at'] ?? ($row['updated_at'] ?? null);
            $presented['requires_peer_review'] = ($presented['status'] === 'under_review') && (($presented['visibility'] ?? '') === 'private');
            return $presented;
        }, $rows);
    }

    private function fetchCampaignsForModeration(string $filter, string $search, int $limit): array {
        if (!$this->db->tableExists('campaigns')) {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $conditions = [];
        $params = [];

        switch ($filter) {
            case 'pending':
                if ($this->campaignHasStatus) {
                    $conditions[] = "c.status = 'under_review'";
                }
                break;
            case 'published':
                if ($this->campaignHasStatus) {
                    $conditions[] = "c.status IN ('published','active')";
                }
                break;
            case 'paused':
                if ($this->campaignHasStatus) {
                    $conditions[] = "c.status = 'paused'";
                }
                break;
            case 'private':
                if ($this->campaignHasVisibility) {
                    $conditions[] = "c.visibility = 'private'";
                }
                break;
            case 'public':
                if ($this->campaignHasVisibility) {
                    $conditions[] = "c.visibility = 'public'";
                }
                break;
            default:
                break;
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $searchable = ['title'];
            if (in_array('slug', $this->campaignColumns, true)) {
                $searchable[] = 'slug';
            }
            if (in_array('summary', $this->campaignColumns, true)) {
                $searchable[] = 'summary';
            } elseif (in_array('short_description', $this->campaignColumns, true)) {
                $searchable[] = 'short_description';
            }
            $parts = [];
            foreach ($searchable as $column) {
                $parts[] = "c.{$column} LIKE ?";
                $params[] = $like;
            }
            if (!empty($parts)) {
                $conditions[] = '(' . implode(' OR ', $parts) . ')';
            }
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $order = $this->campaignHasCreatedAt ? 'c.created_at DESC' : 'c.id DESC';

        $select = 'c.*';
        $joins = '';
        if ($this->campaignOwnerColumn) {
            if ($this->db->columnExists('users', 'role_id')) {
                $select .= ', u.first_name, u.last_name, u.email, r.slug AS owner_role';
                $joins = "LEFT JOIN users u ON u.id = c.{$this->campaignOwnerColumn} " .
                         "LEFT JOIN roles r ON r.id = u.role_id";
            } else {
                $select .= ', u.first_name, u.last_name, u.email, u.role AS owner_role';
                $joins = "LEFT JOIN users u ON u.id = c.{$this->campaignOwnerColumn}";
            }
        }

        $sql = "SELECT {$select}
                FROM campaigns c
                {$joins}
                {$where}
                ORDER BY {$order}
                LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, $params);

        if (!$rows) {
            return [];
        }

        return array_map(function (array $row) {
            $presented = CampaignPresenter::present($row);
            $presented['owner_email'] = $row['email'] ?? null;
            $presented['owner_role'] = $row['owner_role'] ?? 'user';
            $presented['submitted_at'] = $row['created_at'] ?? ($row['updated_at'] ?? null);
            $presented['status_meta'] = CampaignPresenter::statusMeta($presented['status']);
            return $presented;
        }, $rows);
    }

    private function fetchRecentUsers(int $limit): array {
        if (!$this->db->tableExists('users')) {
            return [];
        }

        $limit = max(1, min($limit, 12));
        $order = $this->db->columnExists('users', 'created_at') ? 'created_at DESC' : 'id DESC';

        if (!$this->db->columnExists('users', 'role_id')) {
            $rows = $this->db->fetchAll(
                "SELECT id, first_name, last_name, email, role, created_at
                 FROM users
                 ORDER BY {$order}
                 LIMIT {$limit}"
            );

            if (!$rows) {
                return [];
            }

            return array_map(static function (array $row) {
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($name === '') {
                    $name = $row['email'] ?? 'Usuario';
                }

                return [
                    'id' => (int)$row['id'],
                    'name' => $name,
                    'email' => $row['email'] ?? '',
                    'role' => strtolower($row['role'] ?? 'user'),
                    'role_name' => ucfirst(strtolower($row['role'] ?? 'user')),
                    'created_at' => $row['created_at'] ?? null,
                ];
            }, $rows);
        }

        $rows = $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, r.slug AS role, r.name AS role_name, u.created_at
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             ORDER BY {$order}
             LIMIT {$limit}"
        );

        if (!$rows) {
            return [];
        }

        return array_map(static function (array $row) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($name === '') {
                $name = $row['email'] ?? 'Usuario';
            }

            return [
                'id' => (int)$row['id'],
                'name' => $name,
                'email' => $row['email'] ?? '',
                'role' => $row['role'] ?? 'user',
                'role_name' => $row['role_name'] ?? null,
                'created_at' => $row['created_at'] ?? null,
            ];
        }, $rows);
    }

    private function filterUsers(string $role, string $search): array {
        $users = $this->users->getAllUsers();

        if ($role !== 'all') {
            $role = strtolower($role);
            $users = array_filter($users, static function (array $user) use ($role) {
                return strtolower($user['role'] ?? 'user') === $role;
            });
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $users = array_filter($users, static function (array $user) use ($needle) {
                $haystacks = [
                    mb_strtolower($user['username'] ?? ''),
                    mb_strtolower($user['email'] ?? ''),
                    mb_strtolower(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))),
                ];
                foreach ($haystacks as $value) {
                    if ($value !== '' && strpos($value, $needle) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        return array_values($users);
    }

    private function fetchUserCampaignStats(int $userId): array {
        $stats = [
            'total' => 0,
            'published' => 0,
            'completed' => 0,
        ];

        if (!$this->db->tableExists('campaigns')) {
            return $stats;
        }

        $ownerColumn = $this->campaignOwnerColumn;
        if (!$ownerColumn) {
            if ($this->db->columnExists('campaigns', 'owner_id')) {
                $ownerColumn = 'owner_id';
            } elseif ($this->db->columnExists('campaigns', 'user_id')) {
                $ownerColumn = 'user_id';
            }
        }

        if (!$ownerColumn) {
            return $stats;
        }

        if ($this->campaignHasStatus) {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
                 FROM campaigns
                 WHERE {$ownerColumn} = ?",
                [$userId]
            );
            $stats['total'] = (int)($row['total'] ?? 0);
            $stats['published'] = (int)($row['published'] ?? 0);
            $stats['completed'] = (int)($row['completed'] ?? 0);
        } else {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total FROM campaigns WHERE {$ownerColumn} = ?",
                [$userId]
            );
            $stats['total'] = (int)($row['total'] ?? 0);
        }

        return $stats;
    }

    private function fetchUserDonationStats(int $userId): array {
        $stats = [
            'total' => 0,
            'completed' => 0,
            'sum' => 0.0,
        ];

        if (!$this->canQueryUserDonations()) {
            return $stats;
        }

        $where = "supporter_id = ?";
        $params = [$userId];
        if ($this->db->columnExists('donations', 'status')) {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS sum_completed,
                        SUM(amount) AS sum_all
                 FROM donations
                 WHERE {$where}",
                $params
            );
            $stats['total'] = (int)($row['total'] ?? 0);
            $stats['completed'] = (int)($row['completed'] ?? 0);
            $stats['sum'] = (float)($row['sum_completed'] ?? $row['sum_all'] ?? 0);
        } else {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total, SUM(amount) AS sum_all FROM donations WHERE {$where}",
                $params
            );
            $stats['total'] = (int)($row['total'] ?? 0);
            $stats['sum'] = (float)($row['sum_all'] ?? 0);
            $stats['completed'] = $stats['total'];
        }

        return $stats;
    }

    private function canQueryUserDonations(): bool {
        return $this->db->tableExists('donations') && $this->db->columnExists('donations', 'supporter_id');
    }

    private function generateTemporaryPassword(int $length = 16): string {
        $bytes = random_bytes(max(8, $length));
        $password = substr(rtrim(strtr(base64_encode($bytes), '+/', 'AB'), '='), 0, $length);

        $hasUpper = (bool)preg_match('/[A-Z]/', $password);
        $hasLower = (bool)preg_match('/[a-z]/', $password);
        $hasDigit = (bool)preg_match('/[0-9]/', $password);

        if ($hasUpper && $hasLower && $hasDigit) {
            return $password;
        }

        $appendix = 'A1a';
        if (!$hasUpper) {
            $appendix[0] = chr(random_int(65, 90));
        }
        if (!$hasDigit) {
            $appendix[1] = (string)random_int(0, 9);
        }
        if (!$hasLower) {
            $appendix[2] = chr(random_int(97, 122));
        }

        $password = substr($password, 0, $length - 3) . $appendix;
        return $password;
    }

    private function buildUserStats(): array {
        if (!$this->db->tableExists('users')) {
            return [
                'total' => 0,
                'admins' => 0,
                'superadmins' => 0,
                'active' => 0,
                'pending' => 0,
            ];
        }

        $stats = [
            'total' => 0,
            'admins' => 0,
            'superadmins' => 0,
            'active' => 0,
            'pending' => 0,
        ];

        $row = $this->db->fetch('SELECT COUNT(*) AS total FROM users');
        $stats['total'] = (int)($row['total'] ?? 0);

        if ($this->db->columnExists('users', 'role_id')) {
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'admin'"
            );
            $super = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'superadmin'"
            );
        } else {
            $row = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
            $super = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE role = 'superadmin'");
        }
        $stats['admins'] = (int)($row['total'] ?? 0);
        $stats['superadmins'] = (int)($super['total'] ?? 0);

        if ($this->db->columnExists('users', 'status')) {
            $active = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE status = 'active'");
            $pending = $this->db->fetch("SELECT COUNT(*) AS total FROM users WHERE status IN ('pending','pending_verification')");
            $stats['active'] = (int)($active['total'] ?? 0);
            $stats['pending'] = (int)($pending['total'] ?? 0);
        }

        return $stats;
    }

    private function getAiPendingCount(): int {
        if ($this->aiPendingModerationCache !== null) {
            return $this->aiPendingModerationCache;
        }

        if (!$this->db->tableExists('ai_generations')) {
            $this->aiPendingModerationCache = 0;
            return 0;
        }

        $needsAttentionStatuses = ['pending', 'moderated'];
        $placeholders = implode(',', array_fill(0, count($needsAttentionStatuses), '?'));
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM ai_generations
             WHERE status IN ({$placeholders})",
            $needsAttentionStatuses
        );

        $count = (int)($row['total'] ?? 0);

        if ($this->db->tableExists('ai_policy_logs')) {
            $policyRow = $this->db->fetch(
                "SELECT COUNT(*) AS total
                 FROM ai_policy_logs
                 WHERE action IN ('flagged','blocked')
                   AND (reviewed_at IS NULL OR reviewed_at = '0000-00-00 00:00:00')"
            );
            $count += (int)($policyRow['total'] ?? 0);
        }

        $this->aiPendingModerationCache = $count;
        return $count;
    }

    private function getPendingCampaignCount(): int {
        if ($this->pendingCampaignsCache !== null) {
            return $this->pendingCampaignsCache;
        }

        if (!$this->db->tableExists('campaigns')) {
            $this->pendingCampaignsCache = 0;
            return 0;
        }

        if ($this->campaignHasStatus) {
            $row = $this->db->fetch("SELECT COUNT(*) AS total FROM campaigns WHERE status = 'under_review'");
            $this->pendingCampaignsCache = (int)($row['total'] ?? 0);
            return $this->pendingCampaignsCache;
        }

        $this->pendingCampaignsCache = 0;
        return 0;
    }

    private function countCampaignsByStatus(array $statuses): int {
        if (!$this->campaignHasStatus) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM campaigns WHERE status IN ({$placeholders})",
            $statuses
        );

        return (int)($row['total'] ?? 0);
    }

    private function countCampaignsByVisibility(string $visibility): int {
        if (!$this->campaignHasVisibility) {
            return 0;
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM campaigns WHERE visibility = ?",
            [$visibility]
        );

        return (int)($row['total'] ?? 0);
    }

    private function countPeerReviewQueue(): int {
        if (!$this->campaignHasStatus) {
            return 0;
        }

        $conditions = ["status = 'under_review'"];
        $params = [];

        if ($this->campaignHasVisibility) {
            $conditions[] = "visibility = 'private'";
        }

        $where = implode(' AND ', $conditions);
        $row = $this->db->fetch("SELECT COUNT(*) AS total FROM campaigns WHERE {$where}", $params);

        return (int)($row['total'] ?? 0);
    }

    private function applyApprovalMetadata(int $campaignId, array $data): void {
        $update = [];

        if ($this->campaignHasVisibility && array_key_exists('visibility', $data)) {
            $update['visibility'] = $data['visibility'];
        }

        if ($this->campaignHasApprovedFields) {
            if (array_key_exists('approved_by', $data)) {
                $update['approved_by'] = $data['approved_by'];
            }
            if (array_key_exists('approved_at', $data)) {
                $update['approved_at'] = $data['approved_at'];
            }
        }

        if (!empty($update)) {
            $update['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('campaigns', $update, 'id = ?', [$campaignId]);
        }
    }

    private function signDocumentPath(int $campaignId, string $relativePath): string
    {
        $key = ROLE_SIGNATURE_KEY ?: (SESSION_SIGNATURE_KEY ?: 'lucaton-signature');
        return hash_hmac('sha256', $campaignId . '|' . $relativePath, $key);
    }

    private function verifyDocumentSignature(int $campaignId, string $relativePath, string $signature): bool
    {
        $expected = $this->signDocumentPath($campaignId, $relativePath);
        return hash_equals($expected, $signature);
    }

    private function resolveYoutubeEmbed(string $url): ?array
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return null;
        }

        $patterns = [
            '/youtu\.be\/([^?&#\/]+)/i',
            '/v=([^&]+)/i',
            '/embed\/([^?&#\/]+)/i',
            '/shorts\/([^?&#\/]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $trimmed, $matches)) {
                $videoId = $matches[1];
                return [
                    'id' => $videoId,
                    'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $videoId,
                    'watch_url' => 'https://www.youtube.com/watch?v=' . $videoId,
                    'thumbnail_url' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
                ];
            }
        }

        return null;
    }

    private function detectCampaignSchema(): void {
        if (!$this->db->tableExists('campaigns')) {
            return;
        }

        $this->campaignColumns = $this->getTableColumns('campaigns');
        $this->campaignHasStatus = in_array('status', $this->campaignColumns, true);
        $this->campaignHasVisibility = in_array('visibility', $this->campaignColumns, true);
        $this->campaignHasCreatedAt = in_array('created_at', $this->campaignColumns, true);
        $this->campaignHasApprovedFields = in_array('approved_by', $this->campaignColumns, true) && in_array('approved_at', $this->campaignColumns, true);
        $this->campaignHasAiAssisted = in_array('ai_assisted', $this->campaignColumns, true);

        if (in_array('owner_id', $this->campaignColumns, true)) {
            $this->campaignOwnerColumn = 'owner_id';
        } elseif (in_array('user_id', $this->campaignColumns, true)) {
            $this->campaignOwnerColumn = 'user_id';
        }
    }

    private function getTableColumns(string $table): array {
        try {
            $columns = $this->db->fetchAll(sprintf('SHOW COLUMNS FROM `%s`', $table));
        } catch (Exception $exception) {
            Logger::warning('Unable to inspect table columns', [
                'table' => $table,
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        $names = [];
        foreach ($columns as $column) {
            if (isset($column['Field'])) {
                $names[] = $column['Field'];
            }
        }

        return $names;
    }
}
