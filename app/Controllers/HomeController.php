<?php
class HomeController {
    private Database $db;
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
        $this->categoryRepository = new CampaignCategory();
        $this->categoryMap = $this->categoryRepository->mapBySlug();
        $this->detectSchema();
    }

    public function index() {
        $current_page = 'home';
        (new Campaign())->closeExpiredCampaigns();
        $featured_campaigns = $this->fetchFeaturedCampaigns();
        $impact_stats = $this->fetchImpactStats();
        $top_categories = $this->fetchTopCategories();
        $success_stories = $this->fetchSuccessStories();
        $category_options = $this->getCategoryOptions();
        $recent_campaigns = $this->fetchRecentCampaigns();
        $urgent_campaigns = $this->filterUrgentCampaigns($recent_campaigns);
        $donor_samples = $this->fetchDonorSamples();
        $testimonial_showcase = $this->buildTestimonialShowcase(
            $this->fetchCreatorFeedbackTestimonials()
        );
        $can_submit_creator_feedback = false;

        if (SessionHelper::isAuthenticated()) {
            $userId = SessionHelper::getUserId();
            if ($userId !== null) {
                try {
                    $campaignModel = new Campaign();
                    $hasCampaign = !empty($campaignModel->findByUserId((int)$userId, 1, 0));
                } catch (Exception $e) {
                    Logger::debug('Unable to verify creator campaigns', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                    $hasCampaign = false;
                }

                $role = SessionHelper::getUserRole();
                $can_submit_creator_feedback = $hasCampaign
                    || in_array($role, ['admin', 'superadmin'], true);
            }
        }
        include VIEWS_PATH . '/public/index.php';
    }

    private function fetchFeaturedCampaigns(): array {
        if ($this->hasModularSchema) {
            try {
                return $this->fetchFeaturedCampaignsModular();
            } catch (Exception $e) {
                Logger::warning('Failed to fetch featured campaigns (modular)', ['error' => $e->getMessage()]);
            }
        }

        return $this->fetchFeaturedCampaignsLegacy();
    }

    private function fetchRecentCampaigns(): array {
        if ($this->hasModularSchema) {
            try {
                return $this->fetchRecentCampaignsModular();
            } catch (Exception $e) {
                Logger::warning('Failed to fetch recent campaigns (modular)', ['error' => $e->getMessage()]);
            }
        }

        return $this->fetchRecentCampaignsLegacy();
    }

    private function filterUrgentCampaigns(array $campaigns): array {
        $urgent = array_filter($campaigns, function ($campaign) {
            $daysLeft = $campaign['days_left'] ?? null;
            $status = $campaign['status'] ?? 'draft';
            return in_array($status, ['published', 'active'], true) && $daysLeft !== null && $daysLeft <= 5;
        });

        usort($urgent, function ($a, $b) {
            return ($a['days_left'] ?? PHP_INT_MAX) <=> ($b['days_left'] ?? PHP_INT_MAX);
        });

        return array_slice($urgent, 0, 3);
    }

    private function fetchDonorSamples(): array {
        try {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT supporter_name 
                 FROM donations
                 WHERE status = 'completed'
                 AND is_anonymous = 0
                 AND supporter_name IS NOT NULL
                 AND supporter_name <> ''
                 ORDER BY RAND()
                 LIMIT 12"
            );

            $names = array_map(fn ($row) => $row['supporter_name'], $rows);

            if (!empty($names)) {
                return $names;
            }
        } catch (Exception $e) {
            Logger::warning('Failed to fetch donor samples', ['error' => $e->getMessage()]);
        }

        return [
            'María P.',
            'Ricardo L.',
            'Camila V.',
            'Javiera S.',
            'Diego A.',
            'Valentina R.',
            'Tomás I.',
            'Sofía G.',
            'Matías H.',
        ];
    }

    private function fetchImpactStats(): array {
        if ($this->hasModularSchema) {
            try {
                return $this->fetchImpactStatsModular();
            } catch (Exception $e) {
                Logger::warning('Failed to fetch impact stats (modular)', ['error' => $e->getMessage()]);
            }
        }

        return $this->fetchImpactStatsLegacy();
    }

    private function fetchTopCategories(): array {
        if ($this->hasModularSchema) {
            try {
                return $this->fetchTopCategoriesModular();
            } catch (Exception $e) {
                Logger::warning('Failed to fetch category summary (modular)', ['error' => $e->getMessage()]);
            }
        }

        return $this->fetchTopCategoriesLegacy();
    }

    private function fetchSuccessStories(): array {
        if ($this->hasModularSchema) {
            try {
                return $this->fetchSuccessStoriesModular();
            } catch (Exception $e) {
                Logger::warning('Failed to fetch success stories (modular)', ['error' => $e->getMessage()]);
            }
        }

        return $this->fetchSuccessStoriesLegacy();
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

    private function fetchFeaturedCampaignsModular(): array {
        $rows = $this->db->fetchAll(
            "SELECT 
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
                c.featured,
                cm.raised_amount,
                cm.donor_count,
                cat.name AS category_name,
                cat.slug AS category_slug,
                u.first_name,
                u.last_name,
                u.username
             FROM campaigns c
             JOIN campaign_metrics cm ON cm.campaign_id = c.id
             JOIN campaign_categories cat ON cat.id = c.category_id
             JOIN users u ON u.id = c.owner_id
             WHERE c.status = 'published' AND c.visibility = 'public'
             ORDER BY c.featured DESC, cm.raised_amount DESC, c.end_date ASC
             LIMIT 6"
        );

        $campaigns = array_map([$this, 'mapCampaignRow'], $rows);

        $campaigns = array_filter($campaigns, function ($item) {
            $progress = (int)($item['progress'] ?? 0);
            return $progress >= 70 && $progress < 100;
        });

        usort($campaigns, function ($a, $b) {
            return ($b['progress'] ?? 0) <=> ($a['progress'] ?? 0);
        });

        return array_slice($campaigns, 0, 3);
    }

    private function fetchFeaturedCampaignsLegacy(): array {
        $where = ['1 = 1'];
        if ($this->hasVisibilityColumn) {
            $where[] = "c.visibility = 'public'";
        }
        if (in_array('status', $this->campaignColumns, true)) {
            $where[] = "c.status IN ('published','completed')";
        }

        $order = 'c.id DESC';
        if (in_array('featured', $this->campaignColumns, true)) {
            $order = 'c.featured DESC';
        } elseif (in_array('created_at', $this->campaignColumns, true)) {
            $order = 'c.created_at DESC';
        }

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
                ORDER BY {$order}
                LIMIT 6";

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (Exception $e) {
            Logger::warning('Failed legacy featured query', ['error' => $e->getMessage()]);
            return [];
        }

        $campaigns = array_map([$this, 'normalizeLegacyRow'], $rows);

        $campaigns = array_filter($campaigns, function ($item) {
            $progress = (int)($item['progress'] ?? 0);
            return $progress >= 70 && $progress < 100;
        });

        usort($campaigns, function ($a, $b) {
            return ($b['progress'] ?? 0) <=> ($a['progress'] ?? 0);
        });

        return array_slice($campaigns, 0, 3);
    }

    private function fetchRecentCampaignsModular(): array {
        $rows = $this->db->fetchAll(
            "SELECT 
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
                cm.raised_amount,
                cm.donor_count,
                cat.name AS category_name,
                cat.slug AS category_slug,
                u.first_name,
                u.last_name,
                u.username
             FROM campaigns c
             JOIN campaign_metrics cm ON cm.campaign_id = c.id
             JOIN campaign_categories cat ON cat.id = c.category_id
             JOIN users u ON u.id = c.owner_id
             WHERE c.status IN ('published','completed') AND c.visibility = 'public'
             ORDER BY c.created_at DESC
             LIMIT 6"
        );

        return array_map([$this, 'mapCampaignRow'], $rows);
    }

    private function fetchRecentCampaignsLegacy(): array {
        $where = ['1 = 1'];
        if ($this->hasVisibilityColumn) {
            $where[] = "c.visibility = 'public'";
        }
        if (in_array('status', $this->campaignColumns, true)) {
            $where[] = "c.status IN ('published','completed')";
        }

        $order = in_array('created_at', $this->campaignColumns, true) ? 'c.created_at DESC' : 'c.id DESC';

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
                ORDER BY {$order}
                LIMIT 6";

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (Exception $e) {
            Logger::warning('Failed legacy recent query', ['error' => $e->getMessage()]);
            return [];
        }

        return array_map([$this, 'normalizeLegacyRow'], $rows);
    }

    private function fetchImpactStatsModular(): array {
        $campaignStats = $this->db->fetch(
            "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS active
             FROM campaigns
             WHERE visibility = 'public'"
        ) ?: ['total' => 0, 'completed' => 0, 'active' => 0];

        $donationStats = $this->db->fetch(
            "SELECT COALESCE(SUM(amount),0) AS total_amount, COUNT(*) AS total_donations
             FROM donations WHERE status = 'completed'"
        ) ?: ['total_amount' => 0, 'total_donations' => 0];

        $communities = $this->db->fetch(
            "SELECT COUNT(DISTINCT cd.location_label) AS total
             FROM campaign_details cd
             JOIN campaigns c ON cd.campaign_id = c.id
             WHERE cd.location_label IS NOT NULL AND cd.location_label <> ''"
        ) ?: ['total' => 0];

        $userTotals = ['total' => 0];
        try {
            $userTotals = $this->db->fetch("SELECT COUNT(*) AS total FROM users");
        } catch (Exception $e) {
            Logger::warning('Failed modular user stats', ['error' => $e->getMessage()]);
        }

        return [
            'supporters' => (int)$donationStats['total_donations'],
            'raised' => (float)$donationStats['total_amount'],
            'active_campaigns' => (int)$campaignStats['active'],
            'hours' => (int)$communities['total'],
            'communities' => (int)$communities['total'],
            'registered_users' => (int)($userTotals['total'] ?? 0)
        ];
    }

    private function fetchImpactStatsLegacy(): array {
        $campaignStats = ['total' => 0, 'completed' => 0, 'active' => 0];
        try {
            $campaignStats = $this->db->fetch(
                "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status IN ('published','active') THEN 1 ELSE 0 END) AS active
                 FROM campaigns"
            ) ?: $campaignStats;
        } catch (Exception $e) {
            Logger::warning('Failed legacy campaign stats', ['error' => $e->getMessage()]);
        }

        $donationStats = ['total_amount' => 0, 'total_donations' => 0];
        try {
            $donationStats = $this->db->fetch(
                "SELECT COALESCE(SUM(amount),0) AS total_amount, COUNT(*) AS total_donations
                 FROM donations"
            ) ?: $donationStats;
        } catch (Exception $e) {
            Logger::warning('Failed legacy donation stats', ['error' => $e->getMessage()]);
        }

        $communitiesTotal = 0;
        $locationColumn = in_array('location_label', $this->campaignColumns, true) ? 'location_label' : (in_array('location', $this->campaignColumns, true) ? 'location' : null);
        if ($locationColumn !== null) {
            try {
                $row = $this->db->fetch("SELECT COUNT(DISTINCT {$locationColumn}) AS total FROM campaigns WHERE {$locationColumn} IS NOT NULL AND {$locationColumn} <> ''");
                $communitiesTotal = (int)($row['total'] ?? 0);
            } catch (Exception $e) {
                Logger::warning('Failed legacy communities stats', ['error' => $e->getMessage()]);
            }
        }

        $userTotals = ['total' => 0];
        try {
            $userTotals = $this->db->fetch("SELECT COUNT(*) AS total FROM users");
        } catch (Exception $e) {
            Logger::warning('Failed legacy user stats', ['error' => $e->getMessage()]);
        }

        return [
            'supporters' => (int)($donationStats['total_donations'] ?? 0),
            'raised' => (float)($donationStats['total_amount'] ?? 0),
            'active_campaigns' => (int)($campaignStats['active'] ?? 0),
            'hours' => $communitiesTotal,
            'communities' => $communitiesTotal,
            'registered_users' => (int)($userTotals['total'] ?? 0)
        ];
    }

    private function fetchTopCategoriesModular(): array {
        $rows = $this->db->fetchAll(
            "SELECT 
                cat.slug,
                cat.name,
                COUNT(*) AS total,
                SUM(CASE WHEN c.status = 'published' THEN 1 ELSE 0 END) AS active
             FROM campaigns c
             JOIN campaign_categories cat ON cat.id = c.category_id
             WHERE c.visibility = 'public'
             GROUP BY cat.id, cat.slug, cat.name
             ORDER BY total DESC
             LIMIT 6"
        );

        return array_map(function ($row) {
            return [
                'key' => $row['slug'],
                'label' => $row['name'],
                'total' => (int)$row['total'],
                'active' => (int)$row['active']
            ];
        }, $rows);
    }

    private function fetchTopCategoriesLegacy(): array {
        if (in_array('category', $this->campaignColumns, true)) {
            try {
                $rows = $this->db->fetchAll(
                    "SELECT category AS cat, COUNT(*) AS total
                     FROM campaigns
                     WHERE category IS NOT NULL AND category <> ''
                     GROUP BY category
                     ORDER BY total DESC
                     LIMIT 6"
                );

                return array_map(function ($row) {
                    $label = $this->fallbackCategories[$row['cat']] ?? ucfirst(str_replace('_', ' ', $row['cat']));
                    return [
                        'key' => $row['cat'],
                        'label' => $label,
                        'total' => (int)$row['total'],
                        'active' => (int)$row['total']
                    ];
                }, $rows);
            } catch (Exception $e) {
                Logger::warning('Failed legacy top categories', ['error' => $e->getMessage()]);
            }
        }

        return [];
    }

    private function fetchSuccessStoriesModular(): array {
        $rows = $this->db->fetchAll(
            "SELECT 
                c.id,
                c.slug,
                c.title,
                c.story,
                c.cover_image_url,
                c.featured_image_url,
                c.goal_amount,
                c.currency,
                cm.raised_amount,
                u.first_name,
                u.last_name,
                u.username
             FROM campaigns c
             JOIN campaign_metrics cm ON cm.campaign_id = c.id
             JOIN users u ON c.owner_id = u.id
             WHERE c.status = 'completed'
               AND c.visibility = 'public'
               AND c.goal_amount IS NOT NULL
               AND c.goal_amount > 0
               AND cm.raised_amount >= (c.goal_amount * 0.9)
             ORDER BY (cm.raised_amount / NULLIF(c.goal_amount, 0)) DESC, c.updated_at DESC
             LIMIT 3"
        );

        return array_map(function ($row) {
            $presented = CampaignPresenter::present($row);
            $creator = $presented['owner_name'] ?? 'Campañista';
            $goal = (float)($presented['goal_amount'] ?? 0);
            $raised = (float)($presented['raised_amount'] ?? 0);
            $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100, 1)) : 0;

            return [
                'title' => $presented['title'],
                'excerpt' => substr($presented['story'] ?? '', 0, 220),
                'raised_amount' => $raised,
                'goal_amount' => $goal,
                'progress' => $progress,
                'currency' => $presented['currency'] ?? 'CLP',
                'creator' => $creator,
                'image_url' => $presented['image_url'] ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg',
                'slug' => $presented['slug'] ?? $row['slug']
            ];
        }, $rows);
    }

    private function fetchSuccessStoriesLegacy(): array {
        $where = [];
        if (in_array('status', $this->campaignColumns, true)) {
            $where[] = "status = 'completed'";
        }
        if (in_array('visibility', $this->campaignColumns, true)) {
            $where[] = "visibility = 'public'";
        }
        $where[] = 'goal_amount IS NOT NULL';
        $where[] = 'goal_amount > 0';
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $orderColumn = in_array('updated_at', $this->campaignColumns, true) ? 'updated_at' : 'id';
        $sql = "SELECT * FROM campaigns {$whereClause} ORDER BY {$orderColumn} DESC";

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (Exception $e) {
            Logger::warning('Failed legacy success stories', ['error' => $e->getMessage()]);
            return [];
        }

        $stories = [];
        foreach ($rows as $row) {
            $presented = $this->normalizeLegacyRow($row);
            $goal = (float)($presented['goal_amount'] ?? 0);
            $raised = (float)($presented['raised_amount'] ?? 0);
            if ($goal <= 0) {
                continue;
            }
            if ($raised < ($goal * 0.9)) {
                continue;
            }

            $progress = min(100, round(($raised / $goal) * 100, 1));

            $stories[] = [
                'title' => $presented['title'],
                'excerpt' => substr($presented['story'] ?? '', 0, 220),
                'raised_amount' => $raised,
                'goal_amount' => $goal,
                'progress' => $progress,
                'currency' => $presented['currency'] ?? 'CLP',
                'creator' => $presented['owner_name'] ?? 'Campañista',
                'image_url' => $presented['image_url'] ?? APP_URL . '/public/assets/images/campaigns/escuela-rural.svg',
                'slug' => $presented['slug'] ?? (string)$presented['id']
            ];
        }

        usort($stories, static function ($a, $b) {
            return ($b['progress'] ?? 0) <=> ($a['progress'] ?? 0);
        });

        return array_slice($stories, 0, 3);
    }

    private function fetchCreatorFeedbackTestimonials(int $limit = 40): array
    {
        $path = ROOT_PATH . '/storage/feedback/creator-feedback.jsonl';
        if (!is_readable($path)) {
            return [];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || empty($lines)) {
            return [];
        }

        $entries = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            $rating = isset($decoded['rating']) ? (float)$decoded['rating'] : null;
            $comment = isset($decoded['comment']) ? trim((string)$decoded['comment']) : '';
            if ($rating === null || $rating < 1 || $comment === '') {
                continue;
            }

            $decoded['rating'] = $rating;
            $decoded['comment'] = $comment;
            $entries[] = $decoded;
        }

        if (empty($entries)) {
            return [];
        }

        usort($entries, static function (array $a, array $b): int {
            $ratingDiff = ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            if ($ratingDiff !== 0) {
                return $ratingDiff;
            }

            $aDate = strtotime($a['created_at'] ?? '1970-01-01T00:00:00Z');
            $bDate = strtotime($b['created_at'] ?? '1970-01-01T00:00:00Z');

            return $bDate <=> $aDate;
        });

        return array_slice($entries, 0, max(1, $limit));
    }

    private function buildTestimonialShowcase(array $feedbackEntries): array
    {
        if (empty($feedbackEntries)) {
            return [
                'highlight' => null,
                'secondary' => [],
                'count' => 0,
                'average' => null,
                'distribution' => [],
            ];
        }

        $userIds = array_values(array_filter(array_unique(array_map(
            static fn ($entry) => (int)($entry['user_id'] ?? 0),
            $feedbackEntries
        )), static fn ($id) => $id > 0));

        $usersById = $this->fetchUsersByIds($userIds);

        $cards = [];
        $ratingSum = 0.0;
        $distribution = [];

        foreach ($feedbackEntries as $entry) {
            $rating = (float)($entry['rating'] ?? 0);
            $ratingSum += $rating;

            $bucket = (int)floor($rating);
            $distribution[$bucket] = ($distribution[$bucket] ?? 0) + 1;

            $userId = (int)($entry['user_id'] ?? 0);
            $userData = $usersById[$userId] ?? null;

            $name = trim((string)($entry['user_name'] ?? ''));
            if ($name === '' && $userData !== null) {
                $composed = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
                if ($composed !== '') {
                    $name = $composed;
                } elseif (!empty($userData['username'])) {
                    $name = $userData['username'];
                }
            }
            if ($name === '') {
                $name = 'Miembro de Lucatón';
            }

            $roleLabel = $this->mapRoleLabel($entry['user_role'] ?? ($userData['role'] ?? 'user'));

            $avatar = SessionHelper::normalizeAvatarUrl($userData['avatar_url'] ?? null);
            $quote = $this->truncateQuote($entry['comment'] ?? '');

            if ($quote === '') {
                continue;
            }

            $createdAt = $entry['created_at'] ?? null;
            $createdLabel = null;
            if ($createdAt) {
                try {
                    $createdLabel = (new DateTimeImmutable($createdAt))->format('d/m/Y');
                } catch (Exception $e) {
                    $createdLabel = null;
                }
            }

            $cards[] = [
                'quote' => $quote,
                'name' => $name,
                'role' => $roleLabel,
                'rating' => $rating,
                'rating_display' => number_format($rating, 1, ',', '.'),
                'avatar' => $avatar,
                'created_at' => $createdAt,
                'created_label' => $createdLabel,
            ];
        }

        if (empty($cards)) {
            return [
                'highlight' => null,
                'secondary' => [],
                'count' => 0,
                'average' => null,
                'distribution' => [],
            ];
        }

        usort($cards, static function (array $a, array $b): int {
            $ratingDiff = $b['rating'] <=> $a['rating'];
            if ($ratingDiff !== 0) {
                return $ratingDiff;
            }

            $aDate = strtotime($a['created_at'] ?? '1970-01-01T00:00:00Z');
            $bDate = strtotime($b['created_at'] ?? '1970-01-01T00:00:00Z');

            return $bDate <=> $aDate;
        });

        $topTier = array_filter($cards, static fn ($card) => $card['rating'] >= 4.7);
        $midTier = array_filter($cards, static fn ($card) => $card['rating'] >= 4.0 && $card['rating'] < 4.7);

        $selection = [];

        foreach ($topTier as $card) {
            $selection[] = $card;
            if (count($selection) >= 2) {
                break;
            }
        }

        foreach ($midTier as $card) {
            if (count($selection) >= 4) {
                break;
            }
            $selection[] = $card;
        }

        if (count($selection) < 4) {
            foreach ($cards as $card) {
                if (in_array($card, $selection, true)) {
                    continue;
                }
                $selection[] = $card;
                if (count($selection) >= 4) {
                    break;
                }
            }
        }

        $selection = array_slice($selection, 0, 4);

        $highlight = $selection[0] ?? null;
        $secondary = array_slice($selection, 1);

        $cardCount = count($cards);

        $distributionFormatted = [];
        krsort($distribution);
        foreach ($distribution as $bucket => $count) {
            $distributionFormatted[] = [
                'rating' => (int)$bucket,
                'count' => $count,
                'percentage' => $cardCount > 0 ? (int)round(($count / $cardCount) * 100) : 0
            ];
        }

        return [
            'highlight' => $highlight,
            'secondary' => $secondary,
            'count' => $cardCount,
            'average' => round($ratingSum / $cardCount, 1),
            'distribution' => $distributionFormatted,
        ];
    }

    private function fetchUsersByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $rows = $this->db->fetchAll(
                "SELECT id, first_name, last_name, username, avatar_url, role FROM users WHERE id IN ({$placeholders})",
                $ids
            );
        } catch (Exception $exception) {
            Logger::warning('Failed to fetch testimonial users', ['error' => $exception->getMessage()]);
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }

        return $map;
    }

    private function mapRoleLabel(?string $role): string
    {
        $normalized = strtolower(trim((string)$role));
        return match ($normalized) {
            'admin', 'superadmin' => 'Equipo Lucatón',
            'creator', 'user' => 'Creador verificado',
            default => 'Miembro de la comunidad',
        };
    }

    private function truncateQuote(string $quote, int $maxLength = 320): string
    {
        $trimmed = trim($quote);
        if ($trimmed === '') {
            return '';
        }

        if (mb_strlen($trimmed) <= $maxLength) {
            return $trimmed;
        }

        return rtrim(mb_substr($trimmed, 0, $maxLength - 1)) . '…';
    }

    private function normalizeLegacyRow(array $row): array {
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

    private function mapCampaignRow(array $row): array {
        return CampaignPresenter::present($row);
    }

    private function getCategoryOptions(): array {
        if (!empty($this->categoryMap)) {
            $options = [];
            foreach ($this->categoryMap as $slug => $data) {
                $options[$slug] = $data['name'];
            }
            return $options;
        }

        return $this->fallbackCategories;
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
