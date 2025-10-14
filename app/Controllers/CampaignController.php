<?php
class CampaignController {
    private Database $db;
    private Donation $donations;
    private CampaignCategory $categoryRepository;
    private array $categoryMap = [];
    private array $fallbackCategories = [
        'tecnologia' => 'Tecnología',
        'arte' => 'Arte',
        'musica' => 'Música',
        'cine' => 'Cine',
        'juegos' => 'Juegos',
        'educacion' => 'Educación',
        'salud' => 'Salud',
        'medio_ambiente' => 'Medio Ambiente'
    ];
    private bool $hasModularSchema = false;
    private array $campaignColumns = [];
    private array $detailsColumns = [];
    private array $metricsColumns = [];
    private array $categoryColumns = [];
    private bool $hasVisibilityColumn = false;
    private string $ownerColumn = 'owner_id';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->donations = new Donation();
        $this->categoryRepository = new CampaignCategory();
        $this->categoryMap = $this->categoryRepository->mapBySlug();
        $this->detectSchema();
    }

    public function show($usernameOrIdentifier, $identifier = null) {
        (new Campaign())->closeExpiredCampaigns();

        if ($identifier === null) {
            $campaign = $this->fetchCampaign($usernameOrIdentifier, true);

            if ($campaign) {
                $publicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
                if ($publicPath !== null) {
                    $legacyUrl = Router::url('campana/' . $usernameOrIdentifier);
                    $targetUrl = Router::url($publicPath);
                    if ($legacyUrl !== $targetUrl) {
                        Router::redirect($publicPath);
                    }
                }
            }
        } else {
            $campaign = $this->fetchCampaign($identifier, true, (string)$usernameOrIdentifier);
        }

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $status = $campaign['status'] ?? 'draft';
        $visibility = strtolower((string)($campaign['visibility'] ?? 'public'));
        $publicStatuses = $this->getPublicStatuses();
        $isPublicStatus = in_array($status, $publicStatuses, true);
        $isPublicVisibility = $visibility !== 'private';
        $canPreview = $this->canPreviewCampaign($campaign);

        if ((!$isPublicStatus || !$isPublicVisibility) && !$canPreview) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $preview_mode = (!$isPublicStatus || !$isPublicVisibility) && $canPreview;
        $preview_notice = $preview_mode ? $this->previewNoticeForStatus($status, $campaign) : null;

        $current_page = 'campaigns';
        $currentUserId = SessionHelper::getUserId();
        $ownerId = $campaign['owner_id'] ?? $campaign['user_id'] ?? null;
        $isCampaignOwner = $currentUserId !== null
            && $ownerId !== null
            && (int)$ownerId === (int)$currentUserId;
        $stats = $this->buildCampaignStats($campaign);
        $campaignGoalReached = ($stats['goal_amount'] ?? 0) > 0
            && ($stats['raised_amount'] ?? 0) >= ($stats['goal_amount'] ?? 0);
        $campaignEndTimestamp = null;
        $rawEndDate = trim((string)($campaign['end_date'] ?? ''));
        if ($rawEndDate !== '') {
            $parsedEnd = strtotime($rawEndDate);
            if ($parsedEnd === false) {
                $dateOnly = DateTime::createFromFormat('Y-m-d', $rawEndDate);
                if ($dateOnly instanceof DateTime) {
                    $parsedEnd = $dateOnly->getTimestamp();
                }
            }
            if ($parsedEnd !== false) {
                $campaignEndTimestamp = $parsedEnd;
            }
        }
        $campaignTimeOver = $campaignEndTimestamp !== null && $campaignEndTimestamp < time();
        $campaignFinalized = ($campaign['status'] ?? '') === 'completed'
            || $campaignGoalReached
            || $campaignTimeOver;
        $finalUpdateAlreadyPosted = !empty($campaign['funding_celebrated_at']);
        $allowFinalUpdate = $isCampaignOwner && $campaignFinalized && !$finalUpdateAlreadyPosted;

        $canManageUpdates = $isCampaignOwner && (!$campaignFinalized || $allowFinalUpdate);
        $finalUpdateAllowed = $allowFinalUpdate;
        $campaignFinalLocked = $isCampaignOwner && $campaignFinalized && !$allowFinalUpdate;

        $recent_supporters = $this->donations->findByCampaignId($campaign['id'], 10, 0, true);

        $mediaService = new CampaignMediaUploadService();
        $campaignImageUrl = null;
        $galleryMedia = [];

        try {
            $mediaManifest = $mediaService->readManifest((int)$campaign['id']);
        } catch (\Throwable $exception) {
            $mediaManifest = [];
        }

        $coverCandidate = $mediaManifest['cover_image']
            ?? $campaign['image_url']
            ?? $campaign['cover_image_url']
            ?? null;
        $campaignImageUrl = CampaignMediaUploadService::normalizePublicUrl($coverCandidate)
            ?? ($campaign['image_url'] ?? null);

        $galleryMedia = array_values(array_filter(array_map(static function ($item) {
            if (!is_array($item) || empty($item['url'])) {
                return null;
            }

            $normalizedUrl = CampaignMediaUploadService::normalizePublicUrl($item['url']) ?? $item['url'];
            return [
                'url' => $normalizedUrl,
                'caption' => isset($item['caption']) && $item['caption'] !== '' ? $item['caption'] : null,
            ];
        }, $mediaManifest['gallery'] ?? [])));

        $campaignUpdates = $this->fetchCampaignUpdates((int)$campaign['id'], $isCampaignOwner);
        $creatorProfileData = $this->buildCreatorProfile($campaign);

        $donationFormErrors = $_SESSION['donation_form_errors'][$campaign['id']] ?? [];
        $donationFormOld = $_SESSION['donation_form_old'][$campaign['id']] ?? [];
        unset($_SESSION['donation_form_errors'][$campaign['id']], $_SESSION['donation_form_old'][$campaign['id']]);

        $updateFormErrors = $_SESSION['campaign_update_errors'] ?? [];
        $updateFormOld = $_SESSION['campaign_update_old'] ?? [];
        unset($_SESSION['campaign_update_errors'], $_SESSION['campaign_update_old']);

        $celebrationOverlay = $this->buildCampaignCelebrationPayload($campaign, $stats, $isCampaignOwner);

        include VIEWS_PATH . '/public/campaign-detail.php';
    }

    public function showCreatorProfile($username) {
        $username = trim((string)$username);
        if ($username === '') {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $userModel = new User();
        $campaignModel = new Campaign();

        try {
            $userRecord = $userModel->findByUsername($username);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo recuperar el perfil público del usuario', [
                'username' => $username,
                'error' => $exception->getMessage(),
            ]);
            $userRecord = null;
        }

        if (!$userRecord) {
            $legacyCampaign = $this->fetchCampaign($username, true);
            if ($legacyCampaign && isset($legacyCampaign['public_path'])) {
                Router::redirect($legacyCampaign['public_path']);
            }

            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $userId = (int)($userRecord['id'] ?? 0);
        $rawCampaigns = [];
        if ($userId > 0) {
            try {
                $rawCampaigns = $campaignModel->findByUserId($userId, 30, 0);
            } catch (Throwable $exception) {
                Logger::warning('No se pudieron recuperar las campañas públicas del usuario', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
                $rawCampaigns = [];
            }
        }

        $publicStatuses = $this->getPublicStatuses();
        $campaigns = [];
        foreach ($rawCampaigns as $row) {
            $status = strtolower((string)($row['status'] ?? ''));
            $visibility = strtolower((string)($row['visibility'] ?? 'public'));

            if ($visibility === 'private') {
                continue;
            }

            if ($status !== '' && !in_array($status, $publicStatuses, true)) {
                continue;
            }

            $row['username'] = $userRecord['username'] ?? ($row['username'] ?? null);
            $row['owner_username'] = $userRecord['username'] ?? ($row['owner_username'] ?? null);
            $row['first_name'] = $userRecord['first_name'] ?? ($row['first_name'] ?? null);
            $row['last_name'] = $userRecord['last_name'] ?? ($row['last_name'] ?? null);
            $row['avatar_url'] = $userRecord['avatar_url'] ?? ($row['avatar_url'] ?? null);

            $campaigns[] = CampaignPresenter::present($row);
        }

        usort($campaigns, static function (array $a, array $b) {
            $left = $a['created_at'] ?? $a['start_date'] ?? null;
            $right = $b['created_at'] ?? $b['start_date'] ?? null;

            if ($left === $right) {
                return 0;
            }

            if ($left === null) {
                return 1;
            }

            if ($right === null) {
                return -1;
            }

            return strcmp((string)$right, (string)$left);
        });

        $totalRaised = array_reduce($campaigns, static function (float $carry, array $campaign) {
            return $carry + (float)($campaign['raised_amount'] ?? 0.0);
        }, 0.0);

        $totalSupporters = array_reduce($campaigns, static function (int $carry, array $campaign) {
            return $carry + (int)($campaign['donor_count'] ?? 0);
        }, 0);

        $normalizedAvatar = SessionHelper::normalizeAvatarUrl($userRecord['avatar_url'] ?? null)
            ?? APP_URL . '/public/assets/images/avatars/default.jpg';
        $displayName = trim(($userRecord['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? ''));
        if ($displayName === '') {
            $displayName = $userRecord['username'] ?? 'Campañista';
        }

        $profile = [
            'id' => $userId,
            'username' => $userRecord['username'] ?? '',
            'name' => $displayName,
            'bio' => $userRecord['bio'] ?? null,
            'location' => $userRecord['location'] ?? null,
            'avatar' => $normalizedAvatar,
            'joined_at' => $userRecord['created_at'] ?? null,
            'campaign_count' => count($campaigns),
            'total_raised' => $totalRaised,
            'total_supporters' => $totalSupporters,
        ];

        $profileBreadcrumbUrl = $profile['username'] !== ''
            ? Router::url('campana/' . rawurlencode($profile['username']))
            : null;

        $breadcrumbs = [
            ['name' => 'Inicio', 'href' => Router::url('/')],
            ['name' => 'Campañas', 'href' => Router::url('campanas')],
            ['name' => '@' . ($profile['username'] ?: 'campanista'), 'href' => $profileBreadcrumbUrl],
        ];

        $page_title = 'Campañas de ' . $profile['name'] . ' - Lucatón';
        $page_description = 'Revisa las campañas públicas creadas por ' . $profile['name'] . ' en Lucatón.';

        include VIEWS_PATH . '/public/campaign-owner.php';
    }

    public function index() {
        (new Campaign())->closeExpiredCampaigns();
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'category' => trim($_GET['category'] ?? ''),
            'status' => isset($_GET['status']) ? trim($_GET['status']) : ''
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $sort = $_GET['sort'] ?? 'recent';
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $campaigns = $this->fetchCampaigns($filters, $perPage, $offset, $sort);
        $totalCampaigns = $this->countCampaigns($filters);
        $totalPages = max(1, (int)ceil($totalCampaigns / $perPage));

        $categories = $this->getCategories();
        $statuses = $this->getStatuses();
        $sort_options = [
            'recent' => 'Más recientes',
            'goal_amount' => 'Meta más alta',
            'ending_soon' => 'Terminan pronto'
        ];

        $current_page = 'campaigns';
        include VIEWS_PATH . '/public/campaigns.php';
    }

    public function myCampaigns() {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        (new Campaign())->closeExpiredCampaigns();

        $current_page = 'my_campaigns';

        $userId = SessionHelper::getUserId();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $campaigns = [];
        $totalCampaigns = 0;

        if ($this->db->tableExists('campaigns')) {
            $campaignModel = new Campaign();
            $rawCampaigns = $campaignModel->findByUserId($userId, $perPage, $offset);
            $campaigns = array_map(static function (array $row) {
                $presented = CampaignPresenter::present($row);
                return array_merge($row, $presented);
            }, $rawCampaigns);

            $ownerColumn = $this->ownerColumn;
            if (in_array($ownerColumn, $this->campaignColumns, true)) {
                $countResult = $this->db->fetch(
                    "SELECT COUNT(*) AS total FROM campaigns WHERE {$ownerColumn} = ?",
                    [$userId]
                );
                $totalCampaigns = (int)($countResult['total'] ?? 0);
            }
        }

        $totalPages = max(1, (int)ceil($totalCampaigns / $perPage));
        $hasMore = $page < $totalPages;

        $campaignAppeals = [];
        if (!empty($campaigns)) {
            $campaignIds = array_filter(array_map(static fn ($campaign) => (int)($campaign['id'] ?? 0), $campaigns));
            if (!empty($campaignIds)) {
                $appealModel = new CampaignAppeal();
                $campaignAppeals = $appealModel->getLatestForCampaigns($campaignIds, $userId);
            }
        }

        $appealFormErrors = $_SESSION['campaign_appeal_errors'] ?? [];
        $appealFormOld = $_SESSION['campaign_appeal_old'] ?? [];
        unset($_SESSION['campaign_appeal_errors'], $_SESSION['campaign_appeal_old']);

        include VIEWS_PATH . '/user/mis-campanas.php';
    }

    public function create() {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $page_title = 'Crear campaña';
        $categories = $this->getCategories();
        $draft_media = $this->getDraftMedia((int)SessionHelper::getUserId());
        $userProfile = [];
        $currentUserId = (int)(SessionHelper::getUserId() ?? 0);
        if ($currentUserId > 0) {
            try {
                $userModel = new User();
                $userProfile = $userModel->findById($currentUserId) ?: [];
            } catch (Throwable $exception) {
                Logger::warning('No se pudo recuperar el perfil del usuario para prefijar la creación de campañas', [
                    'user_id' => $currentUserId,
                    'error' => $exception->getMessage()
                ]);
                $userProfile = [];
            }
        }
        include VIEWS_PATH . '/pages/campaign-create.php';
    }

    public function store() {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $data = $this->sanitizeCampaignInput($_POST);
        $ownerId = (int)SessionHelper::getUserId();
        $mediaService = new CampaignMediaUploadService();
        $currentUser = SessionHelper::getUser();
        $isOwnerAdmin = ($currentUser['role'] ?? 'user') === 'admin';

        $draftMedia = $this->getDraftMedia($ownerId);
        $uploadErrors = [];
        $draftMedia = $this->synchronizeDraftMedia($data, $_FILES, $mediaService, $ownerId, $draftMedia, $uploadErrors);
        $this->setDraftMedia($ownerId, $draftMedia);

        if (!empty($uploadErrors)) {
            return $this->campaignFormError($uploadErrors, $data);
        }

        $data['featured_image_url'] = $draftMedia['cover'] ?? null;

        $errors = $this->validateCampaignInput($data);
        if (empty($data['featured_image_url'])) {
            $errors['featured_image'] = 'Sube una imagen principal para tu campaña.';
        }

        if (!empty($errors)) {
            return $this->campaignFormError($errors, $data);
        }

        $category = null;
        if ($this->hasModularSchema) {
            $category = $this->categoryRepository->findBySlug($data['category_slug']);
            if (!$category) {
                return $this->campaignFormError(['category' => 'Selecciona una categoría válida.'], $data);
            }
        } elseif (!isset($this->fallbackCategories[$data['category_slug']])) {
            return $this->campaignFormError(['category' => 'Selecciona una categoría válida.'], $data);
        }

        $slug = $this->generateUniqueSlug($data['title']);
        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();

        try {
            if ($this->hasModularSchema) {
                $campaignId = (int)$this->db->insert('campaigns', [
                    'owner_id' => $ownerId,
                    'category_id' => $category['id'],
                    'title' => $data['title'],
                    'slug' => $slug,
                    'summary' => $data['short_description'],
                    'story' => $data['description'],
                    'goal_amount' => $data['goal_amount'],
                    'currency' => 'CLP',
                    'status' => 'under_review',
                    'visibility' => 'private',
                    'start_date' => date('Y-m-d'),
                    'end_date' => $data['end_date'],
                    'cover_image_url' => $data['featured_image_url'] ?: null,
                    'video_url' => $data['video_url'] ?: null,
                    'ai_assisted' => $data['ai_generated'] ? 1 : 0,
                    'featured' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                $this->db->insert('campaign_details', [
                    'campaign_id' => $campaignId,
                    'beneficiary_type' => $data['beneficiary_type'],
                    'beneficiary_name' => $data['beneficiary_name'],
                    'beneficiary_contact' => $data['beneficiary_contact'] ?: null,
                    'location_label' => $data['location'] ?: null,
                    'impact_summary' => null,
                    'transparency_plan' => null,
                    'support_channels' => null,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                $this->db->insert('campaign_metrics', [
                    'campaign_id' => $campaignId,
                    'raised_amount' => 0,
                    'donor_count' => 0,
                    'follower_count' => 0,
                    'share_count' => 0,
                    'view_count' => 0,
                    'average_donation' => 0,
                    'last_donation_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                $historyNotes = $isOwnerAdmin
                    ? 'Creada por un administrador y requiere revisión de otro administrador.'
                    : 'Campaña creada y enviada a revisión inicial';

                $this->db->insert('campaign_status_history', [
                    'campaign_id' => $campaignId,
                    'previous_status' => null,
                    'new_status' => 'under_review',
                    'changed_by' => $ownerId,
                    'notes' => $historyNotes,
                    'created_at' => $now
                ]);
            } else {
                $campaignId = $this->createLegacyCampaign($ownerId, $data, $slug, $now, $isOwnerAdmin);
            }

            $promotion = $mediaService->promoteDraftMedia($ownerId, $campaignId, $draftMedia);
            $finalCoverUrl = $promotion['cover'] ?? $data['featured_image_url'];
            if ($finalCoverUrl && $finalCoverUrl !== $data['featured_image_url']) {
                $data['featured_image_url'] = $finalCoverUrl;

                $coverUpdate = [];
                if (in_array('cover_image_url', $this->campaignColumns, true)) {
                    $coverUpdate['cover_image_url'] = $finalCoverUrl;
                }
                if (in_array('image_url', $this->campaignColumns, true)) {
                    $coverUpdate['image_url'] = $finalCoverUrl;
                }

                if (!empty($coverUpdate)) {
                    $this->db->update('campaigns', $coverUpdate, 'id = ?', [$campaignId]);
                }
            }

            $galleryMedia = $promotion['gallery'];
            $attachmentMedia = $promotion['attachments'];

            try {
                $mediaService->persistManifest($campaignId, [
                    'cover_image' => $data['featured_image_url'] ?: null,
                    'gallery' => $galleryMedia,
                    'attachments' => $attachmentMedia,
                    'requires_admin_peer_review' => $isOwnerAdmin,
                    'requested_by_admin_id' => $isOwnerAdmin ? $ownerId : null,
                ]);
            } catch (RuntimeException $e) {
                $this->db->rollback();
                return $this->campaignFormError(['supporting_files' => 'Guardamos tu campaña pero no pudimos registrar los archivos adjuntos. Intenta nuevamente.'], $data);
            }

            $this->db->commit();
            $this->clearDraftMedia($ownerId);
            $this->syncOwnerProfileWithCampaignData($ownerId, $data);

            try {
                $lifecycleMailer = new CampaignLifecycleMailer();
                $lifecycleMailer->campaignCreated($campaignId, [
                    'campaign' => [
                        'id' => $campaignId,
                        'title' => $data['title'],
                        'slug' => $slug,
                        'goal_amount' => $data['goal_amount'],
                        'currency' => 'CLP',
                        'end_date' => $data['end_date']
                    ],
                    'owner_id' => $ownerId,
                    'is_admin_owner' => $isOwnerAdmin
                ]);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo preparar el correo de creación de campaña', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
            }

            $successMessage = $isOwnerAdmin
                ? 'Tu campaña fue creada y figura como privada hasta que otro administrador la apruebe.'
                : 'Tu campaña fue creada y está en revisión. Te avisaremos cuando esté publicada.';
            SessionHelper::setFlash('success', $successMessage);
            $this->respondAfterStore(Router::url('panel'));
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('Error storing campaign', [
                'error' => $e->getMessage(),
                'user_id' => $ownerId
            ]);

            $this->campaignFormError(['general' => 'No pudimos guardar la campaña. Intenta nuevamente.'], $data, 500);
        }
    }

    public function edit($id) {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $campaign = $this->fetchCampaign($id, true);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $ownerId = (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0);
        $currentUserId = (int)(SessionHelper::getUserId() ?? 0);
        $isAdmin = SessionHelper::userHasRole('admin');

        if ($ownerId !== $currentUserId && !$isAdmin) {
            http_response_code(403);
            include VIEWS_PATH . '/errors/403.php';
            return;
        }

        $allowedStatuses = ['draft', 'under_review', 'cancelled'];
        $status = strtolower((string)($campaign['status'] ?? 'draft'));
        if (!$isAdmin && !in_array($status, $allowedStatuses, true)) {
            SessionHelper::setFlash('info', 'Las campañas publicadas o completadas no se pueden editar. Crea una actualización o contacta al equipo de Lucatón.');
            Router::redirect('mis-campanas');
            return;
        }

        $campaignId = (int)$campaign['id'];
        $categories = $this->getCategories();
        $mediaService = new CampaignMediaUploadService();
        $mediaManifest = $mediaService->readManifest($campaignId);

        $formErrors = $_SESSION['campaign_edit_errors'][$campaignId] ?? [];
        $formOld = $_SESSION['campaign_edit_old'][$campaignId] ?? [];
        unset($_SESSION['campaign_edit_errors'][$campaignId], $_SESSION['campaign_edit_old'][$campaignId]);

        $page_title = 'Editar campaña · ' . $campaign['title'];

        include VIEWS_PATH . '/pages/campaign-edit.php';
    }

    public function update($id) {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $campaignId = (int)$id;
        $campaign = $this->fetchCampaign($campaignId, true);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $ownerId = (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0);
        $currentUserId = (int)(SessionHelper::getUserId() ?? 0);
        $isAdmin = SessionHelper::userHasRole('admin');

        if ($ownerId !== $currentUserId && !$isAdmin) {
            http_response_code(403);
            include VIEWS_PATH . '/errors/403.php';
            return;
        }

        $allowedStatuses = ['draft', 'under_review', 'cancelled'];
        $status = strtolower((string)($campaign['status'] ?? 'draft'));
        if (!$isAdmin && !in_array($status, $allowedStatuses, true)) {
            SessionHelper::setFlash('info', 'Las campañas publicadas o completadas no se pueden editar. Crea una actualización o contacta al equipo de Lucatón.');
            Router::redirect('mis-campanas');
            return;
        }

        $data = $this->sanitizeCampaignInput($_POST);

        $errors = $this->validateCampaignInput($data);
        if (isset($errors['end_date']) && !empty($campaign['end_date']) && $campaign['end_date'] === $data['end_date']) {
            unset($errors['end_date']);
        }

        if (!empty($errors)) {
            return $this->campaignEditError($campaignId, $errors, $data);
        }

        $categoryId = $campaign['category_id'] ?? null;
        if ($this->hasModularSchema) {
            $category = $this->categoryRepository->findBySlug($data['category_slug']);
            if (!$category) {
                return $this->campaignEditError($campaignId, ['category' => 'Selecciona una categoría válida.'], $data);
            }
            $categoryId = (int)$category['id'];
        }

        $mediaService = new CampaignMediaUploadService();
        $existingCover = $campaign['cover_image_url'] ?? null;
        $manifest = $mediaService->readManifest($campaignId);

        $newCoverUrl = null;
        $galleryMedia = [];
        $attachmentMedia = [];

        if (!empty($_FILES['featured_image']) && ($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $newCoverUrl = $mediaService->storeCoverImage($_FILES['featured_image'], $ownerId);
            } catch (RuntimeException $e) {
                return $this->campaignEditError($campaignId, ['featured_image' => $e->getMessage()], $data);
            }
        }

        if (!empty($_FILES['gallery_images']) && ($_FILES['gallery_images']['error'][0] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $galleryMedia = $mediaService->storeGalleryImages($_FILES['gallery_images'], $campaignId, $ownerId);
            } catch (RuntimeException $e) {
                if ($newCoverUrl) {
                    $mediaService->deletePublicUrl($newCoverUrl);
                }
                return $this->campaignEditError($campaignId, ['gallery_images' => $e->getMessage()], $data);
            }
        }

        if (!empty($_FILES['supporting_files']) && ($_FILES['supporting_files']['error'][0] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $attachmentMedia = $mediaService->storeSupportingFiles($_FILES['supporting_files'], $campaignId, $ownerId);
            } catch (RuntimeException $e) {
                if ($newCoverUrl) {
                    $mediaService->deletePublicUrl($newCoverUrl);
                }
                foreach ($galleryMedia as $item) {
                    $mediaService->deletePublicUrl($item['url'] ?? null);
                }
                return $this->campaignEditError($campaignId, ['supporting_files' => $e->getMessage()], $data);
            }
        }

        $updateData = [
            'title' => $data['title'],
            'summary' => $data['short_description'],
            'story' => $data['description'],
            'goal_amount' => $data['goal_amount'],
            'end_date' => $data['end_date'],
            'video_url' => $data['video_url'] ?: null,
            'ai_assisted' => $data['ai_generated'] ? 1 : 0,
            'beneficiary_name' => $data['beneficiary_name'],
            'beneficiary_type' => $data['beneficiary_type'],
            'beneficiary_contact' => $data['beneficiary_contact'] ?: null,
            'location' => $data['location'] ?: null,
        ];

        if ($this->hasModularSchema) {
            $updateData['category_id'] = $categoryId;
        }

        if ($newCoverUrl) {
            $updateData['cover_image_url'] = $newCoverUrl;
        }

        try {
            if ($this->hasModularSchema) {
                $campaignModel = new Campaign();
                $campaignModel->update($campaignId, $updateData);
            } else {
                $this->updateLegacyCampaignRecord($campaignId, $updateData, $data, $campaign, $newCoverUrl);
            }

            $manifestUpdate = [];
            if ($newCoverUrl) {
                $manifestUpdate['cover_image'] = $newCoverUrl;
            }
            if (!empty($galleryMedia)) {
                $manifestUpdate['gallery'] = array_merge($manifest['gallery'] ?? [], $galleryMedia);
            }
            if (!empty($attachmentMedia)) {
                $manifestUpdate['attachments'] = array_merge($manifest['attachments'] ?? [], $attachmentMedia);
            }

            if (!empty($manifestUpdate)) {
                $mediaService->persistManifest($campaignId, $manifestUpdate);
            }

            if ($newCoverUrl && $existingCover && $existingCover !== $newCoverUrl) {
                $mediaService->deletePublicUrl($existingCover);
            }

            SessionHelper::setFlash('success', 'Actualizamos tu campaña correctamente.');
            Logger::audit('campaign_updated', $currentUserId, ['campaign_id' => $campaignId]);

            $slug = $campaign['slug'] ?? $campaignId;
            $ownerUsername = $campaign['owner_username']
                ?? ($campaign['username'] ?? null)
                ?? (SessionHelper::getUser()['username'] ?? null);

            $publicPath = null;
            if ($ownerUsername !== null && $slug !== null) {
                $publicPath = 'campana/' . rawurlencode((string)$ownerUsername) . '/' . rawurlencode((string)$slug);
            }

            Router::redirect($publicPath ?? ('/campana/' . $slug));
        } catch (Exception $exception) {
            Logger::error('Failed to update campaign', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);

            if ($newCoverUrl) {
                $mediaService->deletePublicUrl($newCoverUrl);
            }
            foreach ($galleryMedia as $item) {
                $mediaService->deletePublicUrl($item['url'] ?? null);
            }

            return $this->campaignEditError(
                $campaignId,
                ['general' => 'No pudimos guardar los cambios de la campaña. Intenta nuevamente.'],
                $data,
                500
            );
        }
    }

    public function appeal($id) {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $campaignId = (int)$id;
        $campaign = $this->fetchCampaign($campaignId, true);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $userId = (int)SessionHelper::getUserId();
        $ownerId = (int)($campaign['owner_id'] ?? $campaign['user_id'] ?? 0);
        if ($ownerId !== $userId && !SessionHelper::userHasRole('admin')) {
            http_response_code(403);
            include VIEWS_PATH . '/errors/403.php';
            return;
        }

        $reason = trim($_POST['reason'] ?? '');
        $additionalEvidence = trim($_POST['additional_evidence'] ?? '');
        $errors = [];

        if (strlen($reason) < 30) {
            $errors['reason'] = 'Describe con más detalle por qué solicitas la revisión (mínimo 30 caracteres).';
        }

        $status = strtolower((string)($campaign['status'] ?? ''));
        if (!in_array($status, ['cancelled', 'paused'], true)) {
            $errors['general'] = 'Solo puedes apelar campañas rechazadas o pausadas.';
        }

        $appealModel = new CampaignAppeal();
        if ($appealModel->userHasPending($campaignId, $userId)) {
            $errors['general'] = 'Ya tienes una apelación en curso para esta campaña. Espera la respuesta del equipo.';
        }

        $old = [
            'reason' => $reason,
            'additional_evidence' => $additionalEvidence,
        ];

        if (!empty($errors)) {
            return $this->campaignAppealError($campaignId, $errors, $old);
        }

        try {
            $appealModel->create([
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'reason' => $reason,
                'additional_evidence' => $additionalEvidence !== '' ? $additionalEvidence : null,
            ]);

            SessionHelper::setFlash('success', 'Recibimos tu apelación. El equipo académico la revisará a la brevedad.');
            Logger::audit('campaign_appeal_created', $userId, [
                'campaign_id' => $campaignId,
            ]);
        } catch (Exception $exception) {
            Logger::error('Failed to create campaign appeal', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);

            return $this->campaignAppealError(
                $campaignId,
                ['general' => 'No pudimos registrar tu apelación. Intenta nuevamente.'],
                $old,
                500
            );
        }

        Router::redirect('/mis-campanas');
    }

    private function fetchCampaigns(array $filters, int $limit, int $offset, string $sort): array {
        if ($this->hasModularSchema) {
            return $this->fetchCampaignsModular($filters, $limit, $offset, $sort);
        }

        return $this->fetchCampaignsLegacy($filters, $limit, $offset, $sort);
    }

    private function fetchCampaignsModular(array $filters, int $limit, int $offset, string $sort): array {
        $where = ["c.visibility = 'public'"];
        $params = [];

        $publicStatuses = $this->getPublicStatuses();
        $resolvedStatuses = $this->resolveStatusFilter($filters['status'] ?? '');

        if (is_array($resolvedStatuses) && !empty($resolvedStatuses)) {
            $resolvedStatuses = array_values(array_intersect($resolvedStatuses, $publicStatuses));
        }

        if (!empty($resolvedStatuses)) {
            $placeholders = implode(',', array_fill(0, count($resolvedStatuses), '?'));
            $where[] = "c.status IN ({$placeholders})";
            $params = array_merge($params, $resolvedStatuses);
        } else {
            $placeholders = implode(',', array_fill(0, count($publicStatuses), '?'));
            $where[] = "c.status IN ({$placeholders})";
            $params = array_merge($params, $publicStatuses);
        }

        if (!empty($filters['category'])) {
            $categoryId = $this->categoryMap[$filters['category']]['id'] ?? null;
            if ($categoryId) {
                $where[] = 'c.category_id = ?';
                $params[] = $categoryId;
            }
        }

        if (!empty($filters['search'])) {
            $where[] = '(c.title LIKE ? OR c.summary LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $orderBy = $this->buildModularOrderByExpression($sort);

        $sql = "SELECT 
                    c.id,
                    c.slug,
                    c.title,
                    c.summary,
                    c.story,
                    c.goal_amount,
                    c.status,
                    c.start_date,
                    c.end_date,
                    c.cover_image_url,
                    c.featured_image_url,
                    c.visibility,
                    c.video_url,
                    c.featured,
                    c.ai_assisted,
                    c.owner_id AS user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.avatar_url,
                    cat.name AS category_name,
                    cat.slug AS category_slug,
                    cd.beneficiary_name,
                    cd.beneficiary_type,
                    cd.beneficiary_contact,
                    cd.location_label,
                    cm.raised_amount,
                    cm.donor_count,
                    cm.share_count,
                    cm.view_count,
                    cm.average_donation
                FROM campaigns c
                JOIN users u ON c.owner_id = u.id
                JOIN campaign_categories cat ON c.category_id = cat.id
                LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
                LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);

        return array_map([$this, 'transformCampaignCard'], $rows);
    }

    private function countCampaigns(array $filters): int {
        if ($this->hasModularSchema) {
            return $this->countCampaignsModular($filters);
        }

        return $this->countCampaignsLegacy($filters);
    }

    private function countCampaignsModular(array $filters): int {
        $where = ["visibility = 'public'"];
        $params = [];

        $publicStatuses = $this->getPublicStatuses();
        $resolvedStatuses = $this->resolveStatusFilter($filters['status'] ?? '');

        if (is_array($resolvedStatuses) && !empty($resolvedStatuses)) {
            $resolvedStatuses = array_values(array_intersect($resolvedStatuses, $publicStatuses));
        }

        if (!empty($resolvedStatuses)) {
            $placeholders = implode(',', array_fill(0, count($resolvedStatuses), '?'));
            $where[] = "status IN ({$placeholders})";
            $params = array_merge($params, $resolvedStatuses);
        } else {
            $placeholders = implode(',', array_fill(0, count($publicStatuses), '?'));
            $where[] = "status IN ({$placeholders})";
            $params = array_merge($params, $publicStatuses);
        }

        if (!empty($filters['category'])) {
            $categoryId = $this->categoryMap[$filters['category']]['id'] ?? null;
            if ($categoryId) {
                $where[] = 'category_id = ?';
                $params[] = $categoryId;
            }
        }

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE ? OR summary LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql = 'SELECT COUNT(*) AS total FROM campaigns WHERE ' . implode(' AND ', $where);
        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    private function fetchCampaign($identifier, bool $includeDraft = false, ?string $ownerUsername = null): ?array {
        if ($this->hasModularSchema) {
            return $this->fetchCampaignModular($identifier, $includeDraft, $ownerUsername);
        }

        return $this->fetchCampaignLegacy($identifier, $includeDraft, $ownerUsername);
    }

    private function fetchCampaignModular($identifier, bool $includeDraft = false, ?string $ownerUsername = null): ?array {
        $params = [];
        if (is_numeric($identifier)) {
            $where = 'c.id = ?';
            $params[] = (int)$identifier;
        } else {
            $where = 'c.slug = ?';
            $params[] = $identifier;
        }

        if ($ownerUsername !== null && $ownerUsername !== '') {
            $where .= ' AND u.username = ?';
            $params[] = $ownerUsername;
        }

        if (!$includeDraft) {
            $placeholders = implode(',', array_fill(0, count($this->getPublicStatuses()), '?'));
            $where .= " AND c.status IN ({$placeholders})";
            $params = array_merge($params, $this->getPublicStatuses());
        }

        $sql = "SELECT 
                    c.id,
                    c.slug,
                    c.title,
                    c.summary,
                    c.story,
                    c.goal_amount,
                    c.status,
                    c.start_date,
                    c.end_date,
                    c.cover_image_url,
                    c.featured_image_url,
                    c.visibility,
                    c.video_url,
                    c.featured,
                    c.ai_assisted,
                    c.owner_id AS user_id,
                    c.category_id,
                    c.created_at,
                    c.updated_at,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.avatar_url,
                    cat.name AS category_name,
                    cat.slug AS category_slug,
                    cd.beneficiary_name,
                    cd.beneficiary_type,
                    cd.beneficiary_contact,
                    cd.location_label,
                    cm.raised_amount,
                    cm.donor_count,
                    cm.share_count,
                    cm.view_count,
                    cm.average_donation,
                    cm.last_donation_at
                FROM campaigns c
                JOIN users u ON c.owner_id = u.id
                JOIN campaign_categories cat ON c.category_id = cat.id
                LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
                LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                WHERE {$where}
                LIMIT 1";

        $campaign = $this->db->fetch($sql, $params);

        if (!$campaign) {
            return null;
        }

        $presented = $this->transformCampaignCard($campaign);
        if ($ownerUsername !== null && $ownerUsername !== '' && isset($presented['owner_username'])) {
            if (strcasecmp((string)$presented['owner_username'], $ownerUsername) !== 0) {
                return null;
            }
        }

        return $presented;
    }

    private function transformCampaignCard(array $row): array {
        return CampaignPresenter::present($row);
    }

    private function buildCampaignStats(array $campaign): array {
        return [
            'goal_amount' => (float)($campaign['goal_amount'] ?? 0),
            'raised_amount' => (float)($campaign['raised_amount'] ?? 0),
            'progress' => (float)($campaign['progress'] ?? 0),
            'days_left' => $campaign['days_left'] ?? null,
            'donors' => (int)($campaign['donor_count'] ?? 0),
            'funded_at' => $campaign['funded_at'] ?? null,
        ];
    }

    private function fetchCampaignUpdates(int $campaignId, bool $includeOwnerView = false): array
    {
        if (!$this->db->tableExists('campaign_updates')) {
            return [];
        }

        static $campaignUpdateModel = null;
        if ($campaignUpdateModel === null) {
            $campaignUpdateModel = new CampaignUpdate();
        }

        try {
            $rawUpdates = $includeOwnerView
                ? $campaignUpdateModel->listForOwner($campaignId, 20)
                : $campaignUpdateModel->listPublicByCampaign($campaignId, 12);
        } catch (Throwable $exception) {
            Logger::warning('No se pudieron obtener las actualizaciones de la campaña', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
            return [];
        }

        return array_map(static function (array $update) use ($includeOwnerView) {
            $publishedAt = $update['published_at'] ?? $update['created_at'] ?? null;

            return [
                'id' => $update['id'] ?? null,
                'title' => $update['title'] ?? null,
                'body' => $update['body'] ?? '',
                'media' => $update['media'] ?? [],
                'heart_count' => (int)($update['heart_count'] ?? 0),
                'status' => $update['status'] ?? 'published',
                'visibility' => $update['visibility'] ?? 'public',
                'published_at' => $publishedAt,
                'created_at' => $update['created_at'] ?? null,
                'can_manage' => $includeOwnerView,
            ];
        }, $rawUpdates);
    }

    private function buildCampaignCelebrationPayload(array $campaign, array $stats, bool $canManageCampaign): ?array
    {
        if (!$canManageCampaign) {
            return null;
        }

        $progress = (float)($stats['progress'] ?? 0.0);
        if ($progress < 100.0) {
            return null;
        }

        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) {
            return null;
        }

        if (!empty($campaign['funding_celebrated_at'])) {
            return null;
        }

        $sessionKey = 'campaign_celebrations_shown';
        $celebrated = $_SESSION[$sessionKey] ?? [];
        if (isset($celebrated[$campaignId])) {
            return null;
        }

        $celebrated[$campaignId] = time();
        $_SESSION[$sessionKey] = $celebrated;

        $goal = (float)($stats['goal_amount'] ?? ($campaign['goal_amount'] ?? 0));
        $raised = (float)($stats['raised_amount'] ?? ($campaign['raised_amount'] ?? 0));
        $currency = $campaign['currency'] ?? 'CLP';

        $formatCurrency = static function (float $amount, string $currency): string {
            return sprintf('%s %s', strtoupper($currency), number_format($amount, 0, ',', '.'));
        };

        $manageUrl = null;
        if (!empty($campaign['id'])) {
            $manageUrl = Router::url('campana/' . $campaign['id'] . '/editar');
        }

        $campaignPublicPath = $campaign['public_path'] ?? CampaignPresenter::buildPublicPath($campaign);
        $publicUrl = $campaignPublicPath ? Router::url($campaignPublicPath) : Router::url('campana/' . ($campaign['slug'] ?? $campaign['id']));

        try {
            (new Campaign())->markFundingMilestone($campaignId, ['mark_celebrated' => true]);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo registrar la celebración de meta alcanzada', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
        }

        return [
            'campaign_title' => $campaign['title'] ?? 'Tu campaña',
            'progress' => $progress,
            'raised_amount' => $formatCurrency($raised, $currency),
            'goal_amount' => $formatCurrency($goal, $currency),
            'public_url' => $publicUrl,
            'manage_url' => $manageUrl,
        ];
    }

    private function buildCreatorProfile(array $campaign): array
    {
        $avatarCandidate = $campaign['owner_avatar'] ?? ($campaign['creator_avatar'] ?? null);

        return [
            'name' => $campaign['creator_name'] ?? $campaign['owner_name'] ?? 'Campañista',
            'username' => $campaign['username'] ?? null,
            'avatar' => CampaignMediaUploadService::normalizePublicUrl($avatarCandidate),
            'verified' => true,
            'campaign_verified' => in_array($campaign['status'] ?? 'draft', ['published', 'completed'], true),
            'joined_at' => $campaign['owner_joined_at'] ?? null,
        ];
    }

    private function getPublicStatuses(): array {
        return ['published', 'completed'];
    }

    private function canPreviewCampaign(array $campaign): bool {
        if (SessionHelper::userHasRole('admin')) {
            return true;
        }

        if (!SessionHelper::isAuthenticated()) {
            return false;
        }

        $ownerId = $campaign['owner_id'] ?? $campaign['user_id'] ?? null;
        if ($ownerId === null) {
            return false;
        }

        return (int)$ownerId === (int)SessionHelper::getUserId();
    }

    private function previewNoticeForStatus(string $status, array $campaign): array
    {
        $status = strtolower($status);

        $notices = [
            'draft' => [
                'title' => 'Tu campaña está en borrador',
                'message' => 'Completa los datos pendientes y envíala a revisión para que el equipo académico pueda evaluarla.',
                'tone' => 'info'
            ],
            'under_review' => [
                'title' => 'Campaña en revisión',
                'message' => 'Solo tú y el equipo de Lucatón pueden ver esta campaña mientras revisamos los antecedentes presentados.',
                'tone' => 'warning'
            ],
            'cancelled' => [
                'title' => 'Campaña rechazada',
                'message' => 'Revisa las observaciones del equipo, ajusta la información necesaria y contáctanos para volver a enviarla a revisión.',
                'tone' => 'error'
            ],
            'paused' => [
                'title' => 'Campaña pausada',
                'message' => 'Esta campaña no es visible públicamente. Puedes actualizarla y solicitar su reactivación cuando estés listo.',
                'tone' => 'warning'
            ],
        ];

        return $notices[$status] ?? [
            'title' => 'Vista privada de campaña',
            'message' => 'Esta campaña no está publicada. Solo los creadores y administradores pueden verla por ahora.',
            'tone' => 'info'
        ];
    }

    private function getCategories(): array {
        if (!empty($this->categoryMap)) {
            $categories = ['' => 'Todas las categorías'];
            foreach ($this->categoryMap as $slug => $data) {
                $categories[$slug] = $data['name'];
            }
            return $categories;
        }

        return ['' => 'Todas las categorías'] + $this->fallbackCategories;
    }

    private function getStatuses(): array {
        if ($this->hasModularSchema) {
            return [
                '' => 'Todas las campañas',
                'live' => 'En curso',
                'finalized' => 'Finalizadas'
            ];
        }

        return [
            '' => 'Todas las campañas',
            'live' => 'En curso',
            'finalized' => 'Finalizadas'
        ];
    }

    private function resolveStatusFilter(?string $filter): ?array
    {
        $filter = is_string($filter) ? trim($filter) : '';
        if ($filter === '') {
            return null;
        }

        $map = [
            'live' => ['published'],
            'finalized' => ['completed', 'archived'],
        ];

        if (isset($map[$filter])) {
            return $map[$filter];
        }

        $publicStatuses = $this->getPublicStatuses();
        if (in_array($filter, $publicStatuses, true)) {
            return [$filter];
        }

        return null;
    }

    private function formatCategoryLabel(?string $slug): string {
        if ($slug === null || $slug === '') {
            return 'Causa social';
        }
        if (isset($this->categoryMap[$slug])) {
            return $this->categoryMap[$slug]['name'];
        }
        if (isset($this->fallbackCategories[$slug])) {
            return $this->fallbackCategories[$slug];
        }
        return ucfirst(str_replace('-', ' ', $slug));
    }

    private function sanitizeCampaignInput(array $input): array {
        $categorySlug = trim($input['category'] ?? '');

        $rawGoal = preg_replace('/[^0-9]/', '', (string)($input['goal_amount'] ?? ''));
        $goalAmount = $rawGoal !== '' ? (float)$rawGoal : 0.0;

        $beneficiaryPhone = preg_replace('/[^0-9+]/', '', trim($input['beneficiary_phone'] ?? ''));
        $beneficiaryEmail = trim($input['beneficiary_email'] ?? '');

        $contactText = trim($input['beneficiary_contact_text'] ?? '');

        $contactParts = [];
        if ($beneficiaryPhone !== '') {
            $contactParts[] = 'Tel: ' . $beneficiaryPhone;
        }
        if ($beneficiaryEmail !== '') {
            $contactParts[] = 'Email: ' . $beneficiaryEmail;
        }

        $beneficiaryContact = $contactText !== ''
            ? $contactText
            : implode(' | ', $contactParts);

        $existingGallery = array_filter(array_map('trim', $input['existing_gallery'] ?? []));
        $removeGallery = array_filter(array_map('trim', $input['remove_gallery'] ?? []));
        $existingAttachments = array_filter(array_map('trim', $input['existing_attachments'] ?? []));
        $removeAttachments = array_filter(array_map('trim', $input['remove_attachments'] ?? []));

        $rawEndInput = $input['end_date'] ?? null;
        $rawEndDatePart = trim((string)($input['end_date_date'] ?? ''));
        $rawEndTimePart = trim((string)($input['end_date_time'] ?? ''));

        if (($rawEndInput === null || trim((string)$rawEndInput) === '') && $rawEndDatePart !== '') {
            $rawEndInput = $rawEndDatePart . 'T' . ($rawEndTimePart !== '' ? $rawEndTimePart : '12:00');
        }

        $normalizedEnd = $this->normalizeEndDateInput($rawEndInput);

        return [
            'title' => trim($input['title'] ?? ''),
            'short_description' => trim($input['short_description'] ?? ''),
            'description' => trim($input['description'] ?? ''),
            'goal_amount' => $goalAmount,
            'goal_amount_input' => $rawGoal,
            'end_date' => $normalizedEnd['storage'],
            'end_date_input' => $normalizedEnd['input'],
            'category' => $categorySlug,
            'category_slug' => $categorySlug,
            'location' => trim($input['location'] ?? ''),
            'beneficiary_type' => trim($input['beneficiary_type'] ?? 'individual'),
            'beneficiary_name' => trim($input['beneficiary_name'] ?? ''),
            'beneficiary_phone' => $beneficiaryPhone,
            'beneficiary_email' => $beneficiaryEmail,
            'beneficiary_contact_text' => $contactText,
            'beneficiary_contact' => $beneficiaryContact,
            'featured_image_url' => null,
            'featured_image_existing' => trim($input['featured_image_existing'] ?? ''),
            'remove_existing_cover' => isset($input['remove_existing_cover']) && $input['remove_existing_cover'] === '1',
            'existing_gallery' => $existingGallery,
            'remove_gallery' => $removeGallery,
            'existing_attachments' => is_array($existingAttachments) ? $existingAttachments : [],
            'remove_attachments' => $removeAttachments,
            'video_url' => trim($input['video_url'] ?? ''),
            'ai_generated' => isset($input['ai_generated']) && $input['ai_generated'] === '1'
        ];
    }

    private function normalizeEndDateInput($value): array
    {
        $raw = is_string($value) ? trim($value) : ($value ?? '');

        if ($raw === '') {
            return [
                'storage' => null,
                'input' => '',
            ];
        }

        $candidate = str_replace('/', '-', $raw);
        $timestamp = strtotime($candidate);

        if ($timestamp === false) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidate) === 1) {
                $dateOnly = DateTime::createFromFormat('Y-m-d', $candidate);
                if ($dateOnly instanceof DateTime) {
                    $dateOnly->setTime(23, 59, 59);
                    $timestamp = $dateOnly->getTimestamp();
                }
            }

            if ($timestamp === false) {
                return [
                    'storage' => null,
                    'input' => $raw,
                ];
            }
        }

        return [
            'storage' => date('Y-m-d H:i:s', $timestamp),
            'input' => date('Y-m-d\TH:i', $timestamp),
        ];
    }

    private function validateCampaignInput(array $data): array {
        $errors = [];

        $titleLength = mb_strlen($data['title'], 'UTF-8');
        if ($titleLength < 10) {
            $errors['title'] = 'El título debe tener al menos 10 caracteres.';
        } elseif ($titleLength > 120) {
            $errors['title'] = 'El título no puede superar los 120 caracteres.';
        }

        if (mb_strlen($data['short_description'], 'UTF-8') < 30) {
            $errors['short_description'] = 'La descripción breve debe tener al menos 30 caracteres.';
        }

        if (mb_strlen($data['description'], 'UTF-8') < 100) {
            $errors['description'] = 'Cuéntanos más detalles de la campaña (mínimo 100 caracteres).';
        }

        if ($data['goal_amount'] <= 0) {
            $errors['goal_amount'] = 'Define una meta económica válida.';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'Define una fecha y hora de término válida.';
        } else {
            $endTimestamp = strtotime($data['end_date']);
            if ($endTimestamp === false) {
                $errors['end_date'] = 'La fecha de término ingresada no es válida.';
            } elseif ($endTimestamp <= time()) {
                $errors['end_date'] = 'La fecha de término debe ser futura (considera la hora).';
            }
        }

        $hasCategory = isset($this->categoryMap[$data['category_slug']])
            || isset($this->fallbackCategories[$data['category_slug']]);
        if (!$data['category_slug'] || !$hasCategory) {
            $errors['category'] = 'Selecciona una categoría válida para tu campaña.';
        }

        if (empty($data['beneficiary_name'])) {
            $errors['beneficiary_name'] = 'Indica quién será beneficiario directo de la campaña.';
        }

        $hasContactText = isset($data['beneficiary_contact_text']) && trim($data['beneficiary_contact_text']) !== '';
        if (!$hasContactText && empty($data['beneficiary_phone']) && empty($data['beneficiary_email'])) {
            $errors['beneficiary_contact'] = 'Agrega al menos un teléfono o correo del beneficiario. Esta información solo la verá el equipo del proyecto.';
        }

        if ($hasContactText && strlen(trim($data['beneficiary_contact_text'])) < 10) {
            $errors['beneficiary_contact'] = 'El contacto del beneficiario debe incluir la información necesaria para validar la campaña.';
        }

        if (!empty($data['beneficiary_phone']) && !preg_match('/^\+?[0-9]{7,15}$/', $data['beneficiary_phone'])) {
            $errors['beneficiary_phone'] = 'Ingresa un teléfono válido (incluye código de país si aplica).';
        }

        if (!empty($data['beneficiary_email']) && !filter_var($data['beneficiary_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['beneficiary_email'] = 'Ingresa un correo válido para el beneficiario.';
        }

        return $errors;
    }

    private function campaignFormError(array $errors, array $oldInput, int $status = 422) {
        if ($this->isJsonRequest()) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        SessionHelper::setFlash('error', $errors['general'] ?? 'Corrige los campos indicados.');
        $_SESSION['old_campaign_form'] = $oldInput;
        $_SESSION['campaign_form_errors'] = $errors;
        Router::redirect('/campana/crear');
    }

    private function getDraftMedia(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'cover' => null,
                'gallery' => [],
                'attachments' => [],
            ];
        }

        $stored = $_SESSION['campaign_draft_media'][$userId] ?? [];

        $gallery = [];
        foreach ($stored['gallery'] ?? [] as $item) {
            if (is_array($item) && !empty($item['url'])) {
                $gallery[] = $item;
            }
        }

        $attachments = [];
        foreach ($stored['attachments'] ?? [] as $item) {
            if (is_array($item) && !empty($item['path'])) {
                $attachments[$item['path']] = $item;
            } elseif (is_string($item)) {
                $attachments[$item] = ['path' => $item];
            }
        }

        return [
            'cover' => $stored['cover'] ?? null,
            'gallery' => $gallery,
            'attachments' => array_values($attachments),
        ];
    }

    private function setDraftMedia(int $userId, array $media): void
    {
        if ($userId <= 0) {
            return;
        }

        $normalizedAttachments = [];
        foreach ($media['attachments'] ?? [] as $attachment) {
            if (is_array($attachment) && !empty($attachment['path'])) {
                $normalizedAttachments[] = $attachment;
            }
        }

        $_SESSION['campaign_draft_media'][$userId] = [
            'cover' => $media['cover'] ?? null,
            'gallery' => array_values($media['gallery'] ?? []),
            'attachments' => $normalizedAttachments,
        ];
    }

    private function clearDraftMedia(int $userId): void
    {
        unset($_SESSION['campaign_draft_media'][$userId]);
    }

    private function isAllowedPublicMediaPath(string $path, int $userId): bool
    {
        return strpos($path, CampaignMediaUploadService::PUBLIC_BASE_DIR . '/') === 0
            || strpos($path, CampaignMediaUploadService::DRAFT_PUBLIC_DIR . '/' . $userId . '/') === 0;
    }

    private function isAllowedPrivateMediaPath(string $path, int $userId): bool
    {
        return strpos($path, CampaignMediaUploadService::PRIVATE_BASE_DIR . '/') === 0
            || strpos($path, CampaignMediaUploadService::DRAFT_PRIVATE_DIR . '/' . $userId . '/') === 0;
    }

    private function synchronizeDraftMedia(array $data, array $files, CampaignMediaUploadService $mediaService, int $userId, array $draftMedia, array &$uploadErrors): array
    {
        $uploadErrors = [];

        // Cover removal and persistence
        if (!empty($data['remove_existing_cover'])) {
            if (!empty($draftMedia['cover'])) {
                $mediaService->deleteDraftAsset($draftMedia['cover']);
            }
            $draftMedia['cover'] = null;
        }

        if (!empty($data['featured_image_existing']) && $this->isAllowedPublicMediaPath($data['featured_image_existing'], $userId)) {
            $draftMedia['cover'] = $data['featured_image_existing'];
        }

        if (!empty($files['featured_image']) && ($files['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $draftMedia['cover'] = $mediaService->storeDraftCover($files['featured_image'], $userId);
            } catch (RuntimeException $exception) {
                $uploadErrors['featured_image'] = $exception->getMessage();
            }
        }

        // Gallery management
        $existingGallery = array_filter($data['existing_gallery'] ?? [], function ($url) use ($userId) {
            return $this->isAllowedPublicMediaPath($url, $userId);
        });

        $draftMedia['gallery'] = array_values(array_filter($draftMedia['gallery'], function ($item) use ($existingGallery) {
            return in_array($item['url'], $existingGallery, true);
        }));

        foreach ($data['remove_gallery'] ?? [] as $removeUrl) {
            if ($this->isAllowedPublicMediaPath($removeUrl, $userId)) {
                $mediaService->deleteDraftAsset($removeUrl);
                $draftMedia['gallery'] = array_values(array_filter($draftMedia['gallery'], function ($item) use ($removeUrl) {
                    return $item['url'] !== $removeUrl;
                }));
            }
        }

        if (!empty($files['gallery_images']) && isset($files['gallery_images']['error']) && is_array($files['gallery_images']['error']) && !($this->allErrorsAreNoFile($files['gallery_images']['error']))) {
            try {
                $newGallery = $mediaService->storeDraftGalleryImages($files['gallery_images'], $userId, count($draftMedia['gallery']));
                $draftMedia['gallery'] = array_merge($draftMedia['gallery'], $newGallery);
            } catch (RuntimeException $exception) {
                $uploadErrors['gallery_images'] = $exception->getMessage();
            }
        }

        // Attachments management
        $existingAttachments = array_filter($data['existing_attachments'] ?? [], function ($path) use ($userId) {
            return $this->isAllowedPrivateMediaPath($path, $userId);
        });

        $draftMedia['attachments'] = array_filter($draftMedia['attachments'], function ($item) use ($existingAttachments) {
            $path = is_array($item) ? ($item['path'] ?? null) : null;
            return $path && in_array($path, $existingAttachments, true);
        });

        foreach ($data['remove_attachments'] ?? [] as $removePath) {
            if ($this->isAllowedPrivateMediaPath($removePath, $userId)) {
                $mediaService->deleteDraftAsset($removePath);
                $draftMedia['attachments'] = array_filter($draftMedia['attachments'], function ($item) use ($removePath) {
                    $path = is_array($item) ? ($item['path'] ?? null) : null;
                    return $path !== $removePath;
                });
            }
        }

        if (!empty($files['supporting_files']) && isset($files['supporting_files']['error']) && is_array($files['supporting_files']['error']) && !($this->allErrorsAreNoFile($files['supporting_files']['error']))) {
            try {
                $newAttachments = $mediaService->storeDraftAttachments($files['supporting_files'], $userId, count($draftMedia['attachments']));
                foreach ($newAttachments as $attachment) {
                    $draftMedia['attachments'][] = $attachment;
                }
            } catch (RuntimeException $exception) {
                $uploadErrors['supporting_files'] = $exception->getMessage();
            }
        }

        // Normalize attachments to associative array keyed by path
        $normalizedAttachments = [];
        foreach ($draftMedia['attachments'] as $attachment) {
            if (is_array($attachment) && !empty($attachment['path'])) {
                $normalizedAttachments[$attachment['path']] = $attachment;
            }
        }
        $draftMedia['attachments'] = array_values($normalizedAttachments);

        return $draftMedia;
    }

    private function allErrorsAreNoFile(array $errors): bool
    {
        foreach ($errors as $errorCode) {
            if ((int)$errorCode !== UPLOAD_ERR_NO_FILE) {
                return false;
            }
        }
        return true;
    }

    private function campaignEditError(int $campaignId, array $errors, array $oldInput, int $status = 422) {
        if ($this->isJsonRequest()) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        SessionHelper::setFlash('error', $errors['general'] ?? 'Corrige los campos marcados para actualizar tu campaña.');
        $_SESSION['campaign_edit_old'][$campaignId] = $oldInput;
        $_SESSION['campaign_edit_errors'][$campaignId] = $errors;
        Router::redirect('/campana/' . $campaignId . '/editar');
    }

    private function campaignAppealError(int $campaignId, array $errors, array $oldInput, int $status = 422) {
        if ($this->isJsonRequest()) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        SessionHelper::setFlash('error', $errors['general'] ?? 'Revisa la información para enviar tu apelación.');
        $_SESSION['campaign_appeal_old'][$campaignId] = $oldInput;
        $_SESSION['campaign_appeal_errors'][$campaignId] = $errors;
        Router::redirect('/mis-campanas#campaign-' . $campaignId);
    }

    private function respondAfterStore(string $redirect): void {
        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit;
        }

        header('Location: ' . $redirect, true, 302);
        exit;
    }

    private function syncOwnerProfileWithCampaignData(int $ownerId, array $campaignData): void
    {
        if ($ownerId <= 0) {
            return;
        }

        try {
            $userModel = new User();
        } catch (Throwable $exception) {
            Logger::warning('No se pudo inicializar el modelo de usuario para sincronizar datos del perfil.', [
                'user_id' => $ownerId,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        try {
            $userRecord = $userModel->findById($ownerId);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo cargar el perfil para sincronizar datos desde la campaña.', [
                'user_id' => $ownerId,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        if (!$userRecord) {
            return;
        }

        $updates = [];

        $currentPhone = trim((string)($userRecord['phone'] ?? ''));
        if ($currentPhone === '' && !empty($campaignData['beneficiary_phone'])) {
            $updates['phone'] = $campaignData['beneficiary_phone'];
        }

        $currentLocation = trim((string)($userRecord['location'] ?? ''));
        if ($currentLocation === '' && !empty($campaignData['location'])) {
            $updates['location'] = $campaignData['location'];
        }

        if (empty($updates)) {
            return;
        }

        try {
            $userModel->updateProfile($ownerId, $updates);
            $refreshedUser = $userModel->findById($ownerId);
            if (is_array($refreshedUser)) {
                SessionHelper::updateUserProfile($refreshedUser);
            }
        } catch (Throwable $exception) {
            Logger::warning('Fallo la sincronización de datos del perfil tras crear campaña.', [
                'user_id' => $ownerId,
                'updates' => array_keys($updates),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function generateUniqueSlug(string $title): string {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        if ($base === '') {
            $base = 'campana-' . bin2hex(random_bytes(2));
        }

        if (!in_array('slug', $this->campaignColumns, true)) {
            return $base;
        }

        $slug = $base;
        $counter = 1;

        while (true) {
            try {
                $existing = $this->db->fetch('SELECT id FROM campaigns WHERE slug = ?', [$slug]);
            } catch (Exception $e) {
                break;
            }

            if (!$existing) {
                break;
            }

            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function isJsonRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }

    private function detectSchema(): void {
        $this->campaignColumns = $this->getTableColumns('campaigns');
        $this->detailsColumns = $this->getTableColumns('campaign_details');
        $this->metricsColumns = $this->getTableColumns('campaign_metrics');
        $this->categoryColumns = $this->getTableColumns('campaign_categories');

        $this->hasVisibilityColumn = in_array('visibility', $this->campaignColumns, true);
        if (in_array('owner_id', $this->campaignColumns, true)) {
            $this->ownerColumn = 'owner_id';
        } elseif (in_array('user_id', $this->campaignColumns, true)) {
            $this->ownerColumn = 'user_id';
        }

        $requiredCampaign = ['summary', 'story', 'category_id', 'owner_id', 'visibility'];
        $requiredDetails = ['campaign_id', 'beneficiary_name', 'beneficiary_type', 'location_label'];
        $requiredMetrics = ['campaign_id', 'raised_amount', 'donor_count', 'share_count', 'view_count'];
        $requiredCategory = ['id', 'slug', 'name'];

        $this->hasModularSchema =
            $this->hasColumns($requiredCampaign, $this->campaignColumns) &&
            $this->hasColumns($requiredDetails, $this->detailsColumns) &&
            $this->hasColumns($requiredMetrics, $this->metricsColumns) &&
            $this->hasColumns($requiredCategory, $this->categoryColumns);
    }

    private function fetchCampaignsLegacy(array $filters, int $limit, int $offset, string $sort): array {
        $where = ['1 = 1'];
        $params = [];

        if ($this->hasVisibilityColumn) {
            $where[] = "c.visibility = 'public'";
        }

        if (in_array('status', $this->campaignColumns, true)) {
            $publicStatuses = $this->getPublicStatuses();
            $resolvedStatuses = $this->resolveStatusFilter($filters['status'] ?? '');

            if (is_array($resolvedStatuses) && !empty($resolvedStatuses)) {
                $resolvedStatuses = array_values(array_intersect($resolvedStatuses, $publicStatuses));
            }

            if (!empty($resolvedStatuses)) {
                $placeholders = implode(',', array_fill(0, count($resolvedStatuses), '?'));
                $where[] = 'c.status IN (' . $placeholders . ')';
                $params = array_merge($params, $resolvedStatuses);
            } else {
                $placeholders = implode(',', array_fill(0, count($publicStatuses), '?'));
                $where[] = 'c.status IN (' . $placeholders . ')';
                $params = array_merge($params, $publicStatuses);
            }
        }

        if (!empty($filters['category'])) {
            $categorySlug = $filters['category'];
            if (in_array('category_slug', $this->campaignColumns, true)) {
                $where[] = 'c.category_slug = ?';
                $params[] = $categorySlug;
            } elseif (in_array('category', $this->campaignColumns, true)) {
                $where[] = 'c.category = ?';
                $params[] = $categorySlug;
            }
        }

        if (!empty($filters['search'])) {
            $searchable = [];
            if (in_array('title', $this->campaignColumns, true)) {
                $searchable[] = 'c.title LIKE ?';
            }
            if (in_array('summary', $this->campaignColumns, true)) {
                $searchable[] = 'c.summary LIKE ?';
            } elseif (in_array('short_description', $this->campaignColumns, true)) {
                $searchable[] = 'c.short_description LIKE ?';
            }
            if (in_array('description', $this->campaignColumns, true)) {
                $searchable[] = 'c.description LIKE ?';
            }

            if (!empty($searchable)) {
                $where[] = '(' . implode(' OR ', $searchable) . ')';
                $searchTerm = '%' . $filters['search'] . '%';
                foreach ($searchable as $_) {
                    $params[] = $searchTerm;
                }
            }
        }

        $orderBy = $this->buildLegacyOrderByExpression($sort);

        $select = 'c.*';
        $joins = '';
        if (in_array($this->ownerColumn, $this->campaignColumns, true)) {
            $select .= ', u.first_name, u.last_name, u.username';
            $joins = "LEFT JOIN users u ON u.id = c.{$this->ownerColumn}";
        }

        $sql = "SELECT {$select}
                FROM campaigns c
                {$joins}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            Logger::warning('Falling back to minimal campaign list', ['error' => $e->getMessage()]);
            $rows = [];
        }

        return array_map([$this, 'transformLegacyRow'], $rows);
    }

    private function countCampaignsLegacy(array $filters): int {
        $where = ['1 = 1'];
        $params = [];

        if ($this->hasVisibilityColumn) {
            $where[] = "visibility = 'public'";
        }

        if (in_array('status', $this->campaignColumns, true)) {
            $publicStatuses = $this->getPublicStatuses();
            $resolvedStatuses = $this->resolveStatusFilter($filters['status'] ?? '');

            if (is_array($resolvedStatuses) && !empty($resolvedStatuses)) {
                $resolvedStatuses = array_values(array_intersect($resolvedStatuses, $publicStatuses));
            }

            if (!empty($resolvedStatuses)) {
                $placeholders = implode(',', array_fill(0, count($resolvedStatuses), '?'));
                $where[] = 'status IN (' . $placeholders . ')';
                $params = array_merge($params, $resolvedStatuses);
            } else {
                $placeholders = implode(',', array_fill(0, count($publicStatuses), '?'));
                $where[] = 'status IN (' . $placeholders . ')';
                $params = array_merge($params, $publicStatuses);
            }
        }

        if (!empty($filters['category'])) {
            $categorySlug = $filters['category'];
            if (in_array('category_slug', $this->campaignColumns, true)) {
                $where[] = 'category_slug = ?';
                $params[] = $categorySlug;
            } elseif (in_array('category', $this->campaignColumns, true)) {
                $where[] = 'category = ?';
                $params[] = $categorySlug;
            }
        }

        if (!empty($filters['search'])) {
            $searchable = [];
            if (in_array('title', $this->campaignColumns, true)) {
                $searchable[] = 'title LIKE ?';
            }
            if (in_array('summary', $this->campaignColumns, true)) {
                $searchable[] = 'summary LIKE ?';
            } elseif (in_array('short_description', $this->campaignColumns, true)) {
                $searchable[] = 'short_description LIKE ?';
            }
            if (in_array('description', $this->campaignColumns, true)) {
                $searchable[] = 'description LIKE ?';
            }

            if (!empty($searchable)) {
                $where[] = '(' . implode(' OR ', $searchable) . ')';
                $searchTerm = '%' . $filters['search'] . '%';
                foreach ($searchable as $_) {
                    $params[] = $searchTerm;
                }
            }
        }

        $sql = 'SELECT COUNT(*) AS total FROM campaigns WHERE ' . implode(' AND ', $where);

        try {
            $result = $this->db->fetch($sql, $params);
        } catch (Exception $e) {
            Logger::warning('Failed counting campaigns on legacy schema', ['error' => $e->getMessage()]);
            return 0;
        }

        return (int)($result['total'] ?? 0);
    }

    private function buildModularOrderByExpression(string $sort): string
    {
        $clauses = [
            "CASE WHEN c.featured = 1 THEN 0 ELSE 1 END ASC",
            "CASE WHEN c.status IN ('published','active') THEN 0 ELSE 1 END ASC",
            "CASE WHEN c.end_date IS NULL THEN 999 WHEN c.end_date < CURRENT_DATE THEN 999 ELSE DATEDIFF(c.end_date, CURRENT_DATE) END ASC",
            "CASE WHEN c.goal_amount <= 0 THEN 1 ELSE COALESCE(cm.raised_amount / NULLIF(c.goal_amount, 0), 0) END ASC",
            "COALESCE(cm.last_donation_at, '1970-01-01') DESC",
            "(COALESCE(cm.share_count,0) + COALESCE(cm.view_count,0) + COALESCE(cm.donor_count,0)) DESC"
        ];

        switch ($sort) {
            case 'goal_amount':
                $clauses[] = 'c.goal_amount DESC';
                break;
            case 'ending_soon':
                $clauses[] = "CASE WHEN c.end_date IS NULL THEN 999 WHEN c.end_date < CURRENT_DATE THEN 999 ELSE DATEDIFF(c.end_date, CURRENT_DATE) END ASC";
                break;
            default:
                $clauses[] = 'c.created_at DESC';
                break;
        }

        if ($sort !== 'recent') {
            $clauses[] = 'c.created_at DESC';
        }

        $clauses[] = 'c.id DESC';

        $clauses = array_unique(array_filter($clauses));

        return implode(', ', $clauses) ?: 'c.created_at DESC, c.id DESC';
    }

    private function buildLegacyOrderByExpression(string $sort): string
    {
        $clauses = [];

        if ($this->hasCampaignColumn('featured')) {
            $clauses[] = 'CASE WHEN COALESCE(c.featured, 0) = 1 THEN 0 ELSE 1 END ASC';
        }

        if ($this->hasCampaignColumn('status')) {
            $clauses[] = "CASE WHEN c.status IN ('published','active') THEN 0 ELSE 1 END ASC";
        }

        if ($this->hasCampaignColumn('end_date')) {
            $clauses[] = "CASE WHEN c.end_date IS NULL THEN 999 WHEN c.end_date < CURRENT_DATE THEN 999 ELSE DATEDIFF(c.end_date, CURRENT_DATE) END ASC";
        }

        if ($this->hasCampaignColumn('goal_amount') && ($this->hasCampaignColumn('raised_amount') || $this->hasCampaignColumn('current_amount'))) {
            $amountColumn = $this->hasCampaignColumn('raised_amount') ? 'raised_amount' : 'current_amount';
            $clauses[] = "CASE WHEN c.goal_amount <= 0 THEN 1 ELSE COALESCE(c.{$amountColumn} / NULLIF(c.goal_amount, 0), 0) END ASC";
        }

        if ($this->hasCampaignColumn('view_count')) {
            $clauses[] = 'COALESCE(c.view_count, 0) DESC';
        }

        switch ($sort) {
            case 'goal_amount':
                if ($this->hasCampaignColumn('goal_amount')) {
                    $clauses[] = 'c.goal_amount DESC';
                }
                break;
            case 'ending_soon':
                if ($this->hasCampaignColumn('end_date')) {
                    $clauses[] = "CASE WHEN c.end_date IS NULL THEN 999 WHEN c.end_date < CURRENT_DATE THEN 999 ELSE DATEDIFF(c.end_date, CURRENT_DATE) END ASC";
                }
                break;
            default:
                if ($this->hasCampaignColumn('created_at')) {
                    $clauses[] = 'c.created_at DESC';
                }
                break;
        }

        if ($this->hasCampaignColumn('created_at') && $sort !== 'recent') {
            $clauses[] = 'c.created_at DESC';
        }

        $clauses[] = 'c.id DESC';

        $clauses = array_unique(array_filter($clauses));

        if (empty($clauses)) {
            return 'c.id DESC';
        }

        return implode(', ', $clauses);
    }

    private function hasCampaignColumn(string $column): bool
    {
        return in_array($column, $this->campaignColumns, true);
    }

    private function fetchCampaignLegacy($identifier, bool $includeDraft = false, ?string $ownerUsername = null): ?array {
        $conditions = [];
        $params = [];

        if (is_numeric($identifier)) {
            $conditions[] = 'c.id = ?';
            $params[] = (int)$identifier;
        } else {
            if (in_array('slug', $this->campaignColumns, true)) {
                $conditions[] = 'c.slug = ?';
                $params[] = $identifier;
            } else {
                $conditions[] = 'c.id = ?';
                $params[] = (int)$identifier;
            }
        }

        if (!$includeDraft && in_array('status', $this->campaignColumns, true)) {
            $publicStatuses = $this->getPublicStatuses();
            $placeholders = implode(',', array_fill(0, count($publicStatuses), '?'));
            $conditions[] = 'c.status IN (' . $placeholders . ')';
            $params = array_merge($params, $publicStatuses);
        }

        if (!$includeDraft && $this->hasVisibilityColumn) {
            $conditions[] = "c.visibility <> 'private'";
        }

        $select = 'c.*';
        $joins = '';
        if (in_array($this->ownerColumn, $this->campaignColumns, true)) {
            $select .= ', u.first_name, u.last_name, u.username, u.avatar_url';
            $joins = "LEFT JOIN users u ON u.id = c.{$this->ownerColumn}";
            if ($ownerUsername !== null && $ownerUsername !== '') {
                $conditions[] = 'u.username = ?';
                $params[] = $ownerUsername;
            }
        }

        $sql = "SELECT {$select}
                FROM campaigns c
                {$joins}
                WHERE " . implode(' AND ', $conditions) . "
                LIMIT 1";

        try {
            $campaign = $this->db->fetch($sql, $params);
        } catch (Exception $e) {
            Logger::warning('Legacy campaign fetch failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (!$campaign) {
            return null;
        }

        $presented = $this->transformLegacyRow($campaign);
        if ($ownerUsername !== null && $ownerUsername !== '' && isset($presented['owner_username'])) {
            if (strcasecmp((string)$presented['owner_username'], $ownerUsername) !== 0) {
                return null;
            }
        }

        return $presented;
    }

    private function createLegacyCampaign(int $ownerId, array $data, string $slug, string $now, bool $requiresPeerReview = false): int {
        $payload = ['title' => $data['title']];

        if (in_array($this->ownerColumn, $this->campaignColumns, true)) {
            $payload[$this->ownerColumn] = $ownerId;
        }

        if (in_array('slug', $this->campaignColumns, true)) {
            $payload['slug'] = $slug;
        }

        if (in_array('summary', $this->campaignColumns, true)) {
            $payload['summary'] = $data['short_description'];
        }
        if (in_array('short_description', $this->campaignColumns, true)) {
            $payload['short_description'] = $data['short_description'];
        }
        if (in_array('description', $this->campaignColumns, true)) {
            $payload['description'] = $data['description'];
        }

        if (in_array('goal_amount', $this->campaignColumns, true)) {
            $payload['goal_amount'] = $data['goal_amount'];
        }
        if (in_array('current_amount', $this->campaignColumns, true)) {
            $payload['current_amount'] = 0;
        }
        if (in_array('donation_count', $this->campaignColumns, true)) {
            $payload['donation_count'] = 0;
        }

        $categorySlug = $data['category_slug'] ?: 'otras-causas';
        $categoryLabel = $this->fallbackCategories[$categorySlug] ?? ucfirst(str_replace('-', ' ', $categorySlug));
        if (in_array('category_slug', $this->campaignColumns, true)) {
            $payload['category_slug'] = $categorySlug;
        }
        if (in_array('category', $this->campaignColumns, true)) {
            $payload['category'] = $categoryLabel;
        }

        if (in_array('status', $this->campaignColumns, true)) {
            $payload['status'] = 'under_review';
        }
        if ($this->hasVisibilityColumn) {
            $payload['visibility'] = 'private';
        }

        if (in_array('location', $this->campaignColumns, true)) {
            $payload['location'] = $data['location'];
        }
        if (in_array('beneficiary_name', $this->campaignColumns, true)) {
            $payload['beneficiary_name'] = $data['beneficiary_name'];
        }
        if (in_array('beneficiary_contact', $this->campaignColumns, true)) {
            $payload['beneficiary_contact'] = $data['beneficiary_contact'] ?: null;
        }

        if (in_array('image_url', $this->campaignColumns, true)) {
            $payload['image_url'] = $data['featured_image_url'] ?: null;
        }
        if (in_array('cover_image_url', $this->campaignColumns, true)) {
            $payload['cover_image_url'] = $data['featured_image_url'] ?: null;
        }
        if (in_array('video_url', $this->campaignColumns, true)) {
            $payload['video_url'] = $data['video_url'] ?: null;
        }

        if (in_array('end_date', $this->campaignColumns, true)) {
            $payload['end_date'] = $data['end_date'];
        }
        if (in_array('start_date', $this->campaignColumns, true)) {
            $payload['start_date'] = date('Y-m-d');
        }

        if (in_array('ai_generated', $this->campaignColumns, true)) {
            $payload['ai_generated'] = $data['ai_generated'] ? 1 : 0;
        } elseif (in_array('ai_assisted', $this->campaignColumns, true)) {
            $payload['ai_assisted'] = $data['ai_generated'] ? 1 : 0;
        }

        if (in_array('created_at', $this->campaignColumns, true)) {
            $payload['created_at'] = $now;
        }
        if (in_array('updated_at', $this->campaignColumns, true)) {
            $payload['updated_at'] = $now;
        }

        $insertId = $this->db->insert('campaigns', $payload);

        if ($this->db->tableExists('campaign_status_history')) {
            $historyNotes = $requiresPeerReview
                ? 'Creada por un administrador y requiere revisión de otro administrador.'
                : 'Campaña creada y enviada a revisión inicial';

            $this->db->insert('campaign_status_history', [
                'campaign_id' => $insertId,
                'previous_status' => null,
                'new_status' => 'under_review',
                'changed_by' => $ownerId,
                'notes' => $historyNotes,
                'created_at' => $now,
            ]);
        }

        return (int)$insertId;
    }

    private function updateLegacyCampaignRecord(int $campaignId, array $updateData, array $data, array $existing, ?string $newCoverUrl): void {
        $payload = [];

        if (in_array('title', $this->campaignColumns, true)) {
            $payload['title'] = $updateData['title'];
        }
        if (in_array('summary', $this->campaignColumns, true)) {
            $payload['summary'] = $updateData['summary'];
        }
        if (in_array('short_description', $this->campaignColumns, true)) {
            $payload['short_description'] = $updateData['summary'];
        }
        if (in_array('story', $this->campaignColumns, true)) {
            $payload['story'] = $updateData['story'];
        }
        if (in_array('description', $this->campaignColumns, true)) {
            $payload['description'] = $updateData['story'];
        }

        if (in_array('goal_amount', $this->campaignColumns, true)) {
            $payload['goal_amount'] = $updateData['goal_amount'];
        }

        $categorySlug = $data['category_slug'] ?: ($existing['category_slug'] ?? $existing['category'] ?? 'otras-causas');
        $categoryLabel = $this->fallbackCategories[$categorySlug] ?? ucfirst(str_replace('-', ' ', $categorySlug));
        if (in_array('category_slug', $this->campaignColumns, true)) {
            $payload['category_slug'] = $categorySlug;
        }
        if (in_array('category', $this->campaignColumns, true)) {
            $payload['category'] = $categoryLabel;
        }

        if (in_array('end_date', $this->campaignColumns, true)) {
            $payload['end_date'] = $updateData['end_date'];
        }

        if (in_array('location', $this->campaignColumns, true)) {
            $payload['location'] = $updateData['location'];
        }
        if (in_array('beneficiary_name', $this->campaignColumns, true)) {
            $payload['beneficiary_name'] = $updateData['beneficiary_name'];
        }
        if (in_array('beneficiary_contact', $this->campaignColumns, true)) {
            $payload['beneficiary_contact'] = $updateData['beneficiary_contact'];
        }

        if ($newCoverUrl) {
            if (in_array('cover_image_url', $this->campaignColumns, true)) {
                $payload['cover_image_url'] = $newCoverUrl;
            }
            if (in_array('image_url', $this->campaignColumns, true)) {
                $payload['image_url'] = $newCoverUrl;
            }
        }

        if (in_array('video_url', $this->campaignColumns, true)) {
            $payload['video_url'] = $updateData['video_url'];
        }

        if (in_array('ai_generated', $this->campaignColumns, true)) {
            $payload['ai_generated'] = $updateData['ai_assisted'];
        } elseif (in_array('ai_assisted', $this->campaignColumns, true)) {
            $payload['ai_assisted'] = $updateData['ai_assisted'];
        }

        if (!empty($payload)) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('campaigns', $payload, 'id = ?', [$campaignId]);
        }
    }

    private function transformLegacyRow(array $row): array {
        if (!isset($row['summary'])) {
            if (isset($row['short_description'])) {
                $row['summary'] = $row['short_description'];
            } elseif (isset($row['description'])) {
                $row['summary'] = $row['description'];
            } else {
                $row['summary'] = '';
            }
        }

        if (!isset($row['story'])) {
            $row['story'] = $row['description'] ?? $row['summary'];
        }

        if (!isset($row['raised_amount'])) {
            if (isset($row['current_amount'])) {
                $row['raised_amount'] = (float)$row['current_amount'];
            } else {
                $row['raised_amount'] = 0.0;
            }
        }

        if (!isset($row['cover_image_url'])) {
            $row['cover_image_url'] = $row['featured_image_url']
                ?? $row['featured_image']
                ?? $row['banner_image_url']
                ?? $row['banner_url']
                ?? $row['image_url']
                ?? $row['image_path']
                ?? $row['image']
                ?? null;
        }

        if (!isset($row['image_url']) && isset($row['cover_image_url'])) {
            $row['image_url'] = $row['cover_image_url'];
        }

        if (!isset($row['category_name']) && isset($row['category'])) {
            $row['category_name'] = $row['category'];
        }

        if (!isset($row['category_slug']) && isset($row['category'])) {
            $row['category_slug'] = $row['category'];
        }

        if (!isset($row['owner_id']) && isset($row[$this->ownerColumn])) {
            $row['owner_id'] = $row[$this->ownerColumn];
        }

        if (!isset($row['location_label']) && isset($row['location'])) {
            $row['location_label'] = $row['location'];
        }

        if (isset($row['first_name']) || isset($row['last_name']) || isset($row['username'])) {
            $row['first_name'] = $row['first_name'] ?? null;
            $row['last_name'] = $row['last_name'] ?? null;
            $row['username'] = $row['username'] ?? null;
        }

        return CampaignPresenter::present($row);
    }

    private function getTableColumns(string $table): array {
        try {
            $columns = $this->db->fetchAll(sprintf('SHOW COLUMNS FROM `%s`', $table));
        } catch (Exception $e) {
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

    private function hasColumns(array $required, array $available): bool {
        foreach ($required as $column) {
            if (!in_array($column, $available, true)) {
                return false;
            }
        }

        return true;
    }

}
