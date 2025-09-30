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

    public function show($identifier) {
        $campaign = $this->fetchCampaign($identifier);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        $current_page = 'campaigns';
        $recent_supporters = $this->donations->findByCampaignId($campaign['id'], 10, 0, true);
        $stats = $this->buildCampaignStats($campaign);

        include VIEWS_PATH . '/public/campaign-detail.php';
    }

    public function index() {
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

        $current_page = 'my_campaigns';

        $userId = SessionHelper::getUserId();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $campaigns = [];
        $totalCampaigns = 0;

        if ($this->db->tableExists('campaigns')) {
            $campaignModel = new Campaign();
            $campaigns = $campaignModel->findByUserId($userId, $perPage, $offset);

            $countResult = $this->db->fetch(
                "SELECT COUNT(*) AS total FROM campaigns WHERE owner_id = ?",
                [$userId]
            );
            $totalCampaigns = (int)($countResult['total'] ?? 0);
        }

        $totalPages = max(1, (int)ceil($totalCampaigns / $perPage));
        $hasMore = $page < $totalPages;

        include VIEWS_PATH . '/user/mis-campanas.php';
    }

    public function create() {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $page_title = 'Crear campaña';
        $categories = $this->getCategories();
        include VIEWS_PATH . '/pages/campaign-create.php';
    }

    public function store() {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
        }

        $data = $this->sanitizeCampaignInput($_POST);
        $errors = $this->validateCampaignInput($data);

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

        $ownerId = SessionHelper::getUserId();
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
                    'visibility' => 'public',
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

                $this->db->insert('campaign_status_history', [
                    'campaign_id' => $campaignId,
                    'previous_status' => null,
                    'new_status' => 'under_review',
                    'changed_by' => $ownerId,
                    'notes' => 'Campaña creada y enviada a revisión inicial',
                    'created_at' => $now
                ]);
            } else {
                $campaignId = $this->createLegacyCampaign($ownerId, $data, $slug, $now);
            }

            $this->db->commit();

            SessionHelper::setFlash('success', 'Tu campaña fue creada y está en revisión. Te avisaremos cuando esté publicada.');
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
        $campaign = $this->fetchCampaign($id, true);

        if (!$campaign) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }

        if ($campaign['user_id'] !== SessionHelper::getUserId()) {
            http_response_code(403);
            include VIEWS_PATH . '/errors/403.php';
            return;
        }

        $page_title = 'Editar campaña · ' . htmlspecialchars($campaign['title']);
        $categories = $this->getCategories();
        include VIEWS_PATH . '/pages/campaign-edit.php';
    }

    public function update($id) {
        SessionHelper::setFlash('warning', 'La edición de campañas estará disponible pronto.');
        Router::redirect('/panel');
    }

    public function appeal($id) {
        SessionHelper::setFlash('warning', 'El sistema de apelaciones estará disponible pronto.');
        Router::redirect('/panel');
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

        if (!empty($filters['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
        } else {
            $where[] = "c.status = 'published'";
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

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        } else {
            $where[] = "status = 'published'";
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

    private function fetchCampaign($identifier, bool $includeDraft = false): ?array {
        if ($this->hasModularSchema) {
            return $this->fetchCampaignModular($identifier, $includeDraft);
        }

        return $this->fetchCampaignLegacy($identifier, $includeDraft);
    }

    private function fetchCampaignModular($identifier, bool $includeDraft = false): ?array {
        $params = [];
        if (is_numeric($identifier)) {
            $where = 'c.id = ?';
            $params[] = (int)$identifier;
        } else {
            $where = 'c.slug = ?';
            $params[] = $identifier;
        }

        if (!$includeDraft) {
            $where .= " AND c.status IN ('published','completed')";
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

        return $campaign ? $this->transformCampaignCard($campaign) : null;
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
            'donors' => (int)($campaign['donor_count'] ?? 0)
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
                '' => 'Todos los estados',
                'published' => 'Activas',
                'completed' => 'Completadas',
                'under_review' => 'En revisión',
                'paused' => 'Pausadas'
            ];
        }

        return [
            '' => 'Todos los estados',
            'active' => 'Activas',
            'funded' => 'Financiadas',
            'ended' => 'Finalizadas'
        ];
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

        return [
            'title' => trim($input['title'] ?? ''),
            'short_description' => trim($input['short_description'] ?? ''),
            'description' => trim($input['description'] ?? ''),
            'goal_amount' => (float)($input['goal_amount'] ?? 0),
            'end_date' => $input['end_date'] ?? null,
            'category' => $categorySlug,
            'category_slug' => $categorySlug,
            'location' => trim($input['location'] ?? ''),
            'beneficiary_type' => trim($input['beneficiary_type'] ?? 'individual'),
            'beneficiary_name' => trim($input['beneficiary_name'] ?? ''),
            'beneficiary_contact' => trim($input['beneficiary_contact'] ?? ''),
            'featured_image_url' => trim($input['featured_image_url'] ?? ''),
            'video_url' => trim($input['video_url'] ?? ''),
            'ai_generated' => isset($input['ai_generated']) && $input['ai_generated'] === '1'
        ];
    }

    private function validateCampaignInput(array $data): array {
        $errors = [];

        if (strlen($data['title']) < 10) {
            $errors['title'] = 'El título debe tener al menos 10 caracteres.';
        }

        if (strlen($data['short_description']) < 30) {
            $errors['short_description'] = 'La descripción breve debe tener al menos 30 caracteres.';
        }

        if (strlen($data['description']) < 100) {
            $errors['description'] = 'Cuéntanos más detalles de la campaña (mínimo 100 caracteres).';
        }

        if ($data['goal_amount'] <= 0) {
            $errors['goal_amount'] = 'Define una meta económica válida.';
        }

        if (!$data['end_date'] || strtotime($data['end_date']) <= time()) {
            $errors['end_date'] = 'La fecha de término debe ser futura.';
        }

        $hasCategory = isset($this->categoryMap[$data['category_slug']])
            || isset($this->fallbackCategories[$data['category_slug']]);
        if (!$data['category_slug'] || !$hasCategory) {
            $errors['category'] = 'Selecciona una categoría válida para tu campaña.';
        }

        if (empty($data['beneficiary_name'])) {
            $errors['beneficiary_name'] = 'Indica quién será beneficiario directo de la campaña.';
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
        Router::redirect('/campana/crear');
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

        if (!empty($filters['status']) && in_array('status', $this->campaignColumns, true)) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
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

        if (!empty($filters['status']) && in_array('status', $this->campaignColumns, true)) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
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

    private function fetchCampaignLegacy($identifier, bool $includeDraft = false): ?array {
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
            $conditions[] = "c.status IN ('published','active','completed','funded')";
        }

        if ($this->hasVisibilityColumn) {
            $conditions[] = "c.visibility <> 'private'";
        }

        $select = 'c.*';
        $joins = '';
        if (in_array($this->ownerColumn, $this->campaignColumns, true)) {
            $select .= ', u.first_name, u.last_name, u.username, u.avatar_url';
            $joins = "LEFT JOIN users u ON u.id = c.{$this->ownerColumn}";
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

        return $this->transformLegacyRow($campaign);
    }

    private function createLegacyCampaign(int $ownerId, array $data, string $slug, string $now): int {
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
            $payload['status'] = 'pending_review';
        }
        if ($this->hasVisibilityColumn) {
            $payload['visibility'] = 'public';
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
        return (int)$insertId;
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

        if (!isset($row['cover_image_url']) && isset($row['image_url'])) {
            $row['cover_image_url'] = $row['image_url'];
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
