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
