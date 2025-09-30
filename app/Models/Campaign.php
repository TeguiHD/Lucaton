<?php
/**
 * Modelo Campaign - Actualizado para la estructura modular
 */

class Campaign {
    private Database $db;
    private static $schemaCapabilities = null;

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$schemaCapabilities === null) {
            self::$schemaCapabilities = [
                'owner_id' => $this->db->columnExists('campaigns', 'owner_id'),
                'status' => $this->db->columnExists('campaigns', 'status'),
                'created_at' => $this->db->columnExists('campaigns', 'created_at'),
            ];
        }
    }

    private function supportsColumn(string $column): bool {
        return self::$schemaCapabilities[$column] ?? false;
    }

    public function create(array $data): int {
        if (empty($data['owner_id'])) {
            throw new Exception('owner_id requerido');
        }

        if (empty($data['category_id'])) {
            throw new Exception('category_id requerido');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $campaignId = (int)$this->db->insert('campaigns', [
                'owner_id' => $data['owner_id'],
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'story' => $data['story'],
                'goal_amount' => $data['goal_amount'],
                'currency' => $data['currency'] ?? 'CLP',
                'status' => $data['status'] ?? 'draft',
                'visibility' => $data['visibility'] ?? 'public',
                'start_date' => $data['start_date'] ?? date('Y-m-d'),
                'end_date' => $data['end_date'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'ai_assisted' => !empty($data['ai_assisted']),
                'featured' => !empty($data['featured']),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $this->db->insert('campaign_details', [
                'campaign_id' => $campaignId,
                'beneficiary_type' => $data['beneficiary_type'] ?? 'individual',
                'beneficiary_name' => $data['beneficiary_name'] ?? 'Beneficiario',
                'beneficiary_contact' => $data['beneficiary_contact'] ?? null,
                'location_label' => $data['location'] ?? null,
                'impact_summary' => $data['impact_summary'] ?? null,
                'transparency_plan' => $data['transparency_plan'] ?? null,
                'support_channels' => !empty($data['support_channels']) ? json_encode($data['support_channels']) : null,
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
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $this->db->insert('campaign_status_history', [
                'campaign_id' => $campaignId,
                'previous_status' => null,
                'new_status' => $data['status'] ?? 'draft',
                'changed_by' => $data['owner_id'],
                'notes' => $data['status_notes'] ?? 'Campaña creada',
                'created_at' => $now
            ]);

            $this->db->commit();
            return $campaignId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function findById(int $id): ?array {
        return $this->db->fetch(
            "SELECT c.*, cd.beneficiary_name, cd.beneficiary_type, cd.beneficiary_contact, cd.location_label,
                    cm.raised_amount, cm.donor_count, cat.name AS category_name, cat.slug AS category_slug
             FROM campaigns c
             LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
             LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
             LEFT JOIN campaign_categories cat ON cat.id = c.category_id
             WHERE c.id = ?",
            [$id]
        ) ?: null;
    }

    public function findBySlug(string $slug): ?array {
        return $this->db->fetch(
            "SELECT c.*, cd.beneficiary_name, cd.beneficiary_type, cd.beneficiary_contact, cd.location_label,
                    cm.raised_amount, cm.donor_count, cat.name AS category_name, cat.slug AS category_slug
             FROM campaigns c
             LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
             LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
             LEFT JOIN campaign_categories cat ON cat.id = c.category_id
             WHERE c.slug = ?",
            [$slug]
        ) ?: null;
    }

    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array {
        if (!$this->supportsColumn('owner_id')) {
            return [];
        }

        $hasMetricsTable = $this->db->tableExists('campaign_metrics');
        $hasCategoryTable = $this->db->tableExists('campaign_categories');

        $select = "SELECT c.*";
        $joins = " FROM campaigns c";

        if ($hasMetricsTable) {
            $select .= ", cm.raised_amount, cm.donor_count";
            $joins .= " LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id";
        }

        if ($hasCategoryTable) {
            $select .= ", cat.name AS category_name";
            $joins .= " LEFT JOIN campaign_categories cat ON cat.id = c.category_id";
        }

        $sql = $select . $joins . "
            WHERE c.owner_id = ?
            ORDER BY c." . ($this->supportsColumn('created_at') ? 'created_at' : 'id') . " DESC
            LIMIT ? OFFSET ?";

        $campaigns = $this->db->fetchAll($sql, [$userId, $limit, $offset]);

        if (!$hasMetricsTable) {
            foreach ($campaigns as &$campaign) {
                $campaign['raised_amount'] = $campaign['raised_amount'] ?? 0;
                $campaign['donor_count'] = $campaign['donor_count'] ?? 0;
            }
            unset($campaign);
        }

        return $campaigns;
    }

    public function getActiveCampaigns(int $limit = 20, int $offset = 0, ?int $categoryId = null): array {
        $where = ["c.status = 'published'", "c.visibility = 'public'"];
        $params = [];

        if ($categoryId) {
            $where[] = 'c.category_id = ?';
            $params[] = $categoryId;
        }

        $sql = "SELECT c.*, cm.raised_amount, cm.donor_count
                FROM campaigns c
                LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.end_date ASC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array {
        $term = '%' . $query . '%';
        return $this->db->fetchAll(
            "SELECT c.*, cm.raised_amount, cm.donor_count
             FROM campaigns c
             LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
             WHERE c.status = 'published' AND c.visibility = 'public'
               AND (c.title LIKE ? OR c.summary LIKE ?)
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            [$term, $term, $limit, $offset]
        );
    }

    public function update(int $id, array $data): bool {
        $allowed = [
            'title', 'summary', 'story', 'goal_amount', 'category_id', 'end_date',
            'cover_image_url', 'video_url', 'ai_assisted', 'featured'
        ];

        $update = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (empty($update)) {
            return false;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('campaigns', $update, 'id = ?', [$id]);

        if (isset($data['beneficiary_name']) || isset($data['beneficiary_contact']) || isset($data['location'])) {
            $detailUpdate = [];
            if (isset($data['beneficiary_name'])) {
                $detailUpdate['beneficiary_name'] = $data['beneficiary_name'];
            }
            if (isset($data['beneficiary_contact'])) {
                $detailUpdate['beneficiary_contact'] = $data['beneficiary_contact'];
            }
            if (isset($data['beneficiary_type'])) {
                $detailUpdate['beneficiary_type'] = $data['beneficiary_type'];
            }
            if (isset($data['location'])) {
                $detailUpdate['location_label'] = $data['location'];
            }

            if (!empty($detailUpdate)) {
                $detailUpdate['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('campaign_details', $detailUpdate, 'campaign_id = ?', [$id]);
            }
        }

        return true;
    }

    public function changeStatus(int $id, string $status, ?int $moderatorId = null, ?string $notes = null): bool {
        $valid = ['draft','under_review','published','paused','completed','cancelled','archived'];
        if (!in_array($status, $valid, true)) {
            throw new Exception('Estado inválido');
        }

        $campaign = $this->findById($id);
        if (!$campaign) {
            throw new Exception('Campaña no encontrada');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $this->db->update('campaigns', [
                'status' => $status,
                'updated_at' => $now,
                'published_at' => $status === 'published' ? $now : $campaign['published_at']
            ], 'id = ?', [$id]);

            $this->db->insert('campaign_status_history', [
                'campaign_id' => $id,
                'previous_status' => $campaign['status'],
                'new_status' => $status,
                'changed_by' => $moderatorId,
                'notes' => $notes,
                'created_at' => $now
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getStats(int $id): array {
        $campaign = $this->findById($id);
        if (!$campaign) {
            return [];
        }

        $goal = (float)$campaign['goal_amount'];
        $raised = (float)($campaign['raised_amount'] ?? 0);
        $progress = $goal > 0 ? min(100, round(($raised / $goal) * 100)) : 0;

        return [
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'progress' => $progress,
            'donors' => (int)($campaign['donor_count'] ?? 0),
            'average_donation' => (float)($campaign['average_donation'] ?? 0)
        ];
    }
}
