<?php
/**
 * Modelo Campaign - Actualizado para la estructura modular
 */

class Campaign {
    private Database $db;
    private static $schemaCapabilities = null;
    private static $statusHistoryColumns = null;
    private static ?string $resolvedOwnerColumn = null;
    private string $ownerColumn = '';

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$schemaCapabilities === null) {
            self::$schemaCapabilities = [
                'owner_id' => $this->db->columnExists('campaigns', 'owner_id')
                    || $this->db->columnExists('campaigns', 'user_id'),
                'status' => $this->db->columnExists('campaigns', 'status'),
                'created_at' => $this->db->columnExists('campaigns', 'created_at'),
                'updated_at' => $this->db->columnExists('campaigns', 'updated_at'),
                'published_at' => $this->db->columnExists('campaigns', 'published_at'),
            ];
        }

        if (self::$resolvedOwnerColumn === null) {
            if ($this->db->columnExists('campaigns', 'owner_id')) {
                self::$resolvedOwnerColumn = 'owner_id';
            } elseif ($this->db->columnExists('campaigns', 'user_id')) {
                self::$resolvedOwnerColumn = 'user_id';
            } else {
                self::$resolvedOwnerColumn = '';
            }
        }

        $this->ownerColumn = self::$resolvedOwnerColumn;
    }

    private function supportsColumn(string $column): bool {
        if ($column === 'owner_id') {
            return $this->ownerColumn !== '';
        }

        return self::$schemaCapabilities[$column] ?? false;
    }

    public function create(array $data): int {
        $ownerId = $data['owner_id'] ?? $data['user_id'] ?? null;
        if (empty($ownerId)) {
            throw new Exception('owner_id requerido');
        }

        if (empty($data['category_id'])) {
            throw new Exception('category_id requerido');
        }

        if ($this->ownerColumn === '') {
            throw new Exception('La tabla campaigns no tiene una columna de propietario válida');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $campaignId = (int)$this->db->insert('campaigns', [
                $this->ownerColumn => $ownerId,
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
                'changed_by' => $ownerId,
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
        try {
            $row = $this->db->fetch(
                "SELECT c.*, cd.beneficiary_name, cd.beneficiary_type, cd.beneficiary_contact, cd.location_label,
                        cm.raised_amount, cm.donor_count, cat.name AS category_name, cat.slug AS category_slug
                 FROM campaigns c
                 LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
                 LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                 LEFT JOIN campaign_categories cat ON cat.id = c.category_id
                 WHERE c.id = ?",
                [$id]
            );
        } catch (Exception $exception) {
            if ($this->isModularTableMissing($exception)) {
                $row = $this->db->fetch('SELECT * FROM campaigns c WHERE c.id = ? LIMIT 1', [$id]);
                return $row ? $this->hydrateLegacyCampaignRow($row) : null;
            }

            throw $exception;
        }

        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array {
        try {
            $row = $this->db->fetch(
                "SELECT c.*, cd.beneficiary_name, cd.beneficiary_type, cd.beneficiary_contact, cd.location_label,
                        cm.raised_amount, cm.donor_count, cat.name AS category_name, cat.slug AS category_slug
                 FROM campaigns c
                 LEFT JOIN campaign_details cd ON cd.campaign_id = c.id
                 LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id
                 LEFT JOIN campaign_categories cat ON cat.id = c.category_id
                 WHERE c.slug = ?",
                [$slug]
            );
        } catch (Exception $exception) {
            if ($this->isModularTableMissing($exception)) {
                $row = $this->db->fetch('SELECT * FROM campaigns c WHERE c.slug = ? LIMIT 1', [$slug]);
                return $row ? $this->hydrateLegacyCampaignRow($row) : null;
            }

            throw $exception;
        }

        return $row ?: null;
    }

    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array {
        if (!$this->supportsColumn('owner_id')) {
            return [];
        }

        $ownerColumn = $this->ownerColumn;
        if ($ownerColumn === '') {
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
            WHERE c.{$ownerColumn} = ?
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

        if (!$this->supportsColumn('status')) {
            throw new Exception('El esquema actual no permite cambiar el estado de las campañas.');
        }

        $now = date('Y-m-d H:i:s');

        $updatePayload = [
            'status' => $status,
        ];

        if ($this->supportsColumn('updated_at')) {
            $updatePayload['updated_at'] = $now;
        }

        if ($this->supportsColumn('published_at')) {
            $updatePayload['published_at'] = $status === 'published'
                ? $now
                : ($campaign['published_at'] ?? null);
        }

        $this->db->beginTransaction();
        try {
            $this->db->update('campaigns', $updatePayload, 'id = ?', [$id]);

            $historyColumns = $this->getStatusHistoryColumns();
            if (!empty($historyColumns)) {
                $historyPayload = [];

                if (in_array('campaign_id', $historyColumns, true)) {
                    $historyPayload['campaign_id'] = $id;
                }
                if (in_array('previous_status', $historyColumns, true)) {
                    $historyPayload['previous_status'] = $campaign['status'] ?? null;
                }
                if (in_array('new_status', $historyColumns, true)) {
                    $historyPayload['new_status'] = $status;
                }
                if (in_array('changed_by', $historyColumns, true)) {
                    $historyPayload['changed_by'] = $moderatorId;
                }
                if (in_array('notes', $historyColumns, true)) {
                    $historyPayload['notes'] = $notes;
                }
                if (in_array('created_at', $historyColumns, true)) {
                    $historyPayload['created_at'] = $now;
                }

                if (!empty($historyPayload)) {
                    $this->db->insert('campaign_status_history', $historyPayload);
                }
            }

            $this->db->commit();

            if (in_array($status, ['completed', 'cancelled', 'archived'], true)) {
                try {
                    $notifier = new CampaignMilestoneNotifier();
                    $campaign['id'] = $id;
                    $campaign['status'] = $status;
                    $notifier->handleCampaignClosure($campaign, $status);
                } catch (Exception $e) {
                    Logger::error('Failed to handle campaign closure milestone', [
                        'campaign_id' => $id,
                        'status' => $status,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function getStatusHistoryColumns(): array
    {
        if (self::$statusHistoryColumns !== null) {
            return self::$statusHistoryColumns;
        }

        if (!$this->db->tableExists('campaign_status_history')) {
            self::$statusHistoryColumns = [];
            return self::$statusHistoryColumns;
        }

        try {
            $columns = $this->db->fetchAll('SHOW COLUMNS FROM campaign_status_history');
        } catch (Exception $exception) {
            Logger::warning('No se pudo inspeccionar la tabla campaign_status_history', [
                'error' => $exception->getMessage()
            ]);
            self::$statusHistoryColumns = [];
            return self::$statusHistoryColumns;
        }

        $names = [];
        foreach ($columns as $column) {
            if (isset($column['Field'])) {
                $names[] = $column['Field'];
            }
        }

        self::$statusHistoryColumns = $names;

        return self::$statusHistoryColumns;
    }

    private function isModularTableMissing(Exception $exception): bool
    {
        $message = $exception->getMessage();
        if (!str_contains($message, 'Base table or view not found')) {
            return false;
        }

        return str_contains($message, 'campaign_details')
            || str_contains($message, 'campaign_metrics')
            || str_contains($message, 'campaign_categories');
    }

    private function hydrateLegacyCampaignRow(array $row): array
    {
        if ($this->ownerColumn !== '' && !isset($row['owner_id']) && isset($row[$this->ownerColumn])) {
            $row['owner_id'] = $row[$this->ownerColumn];
        }

        if (!isset($row['raised_amount'])) {
            $row['raised_amount'] = (float)($row['current_amount'] ?? 0);
        }

        if (!isset($row['donor_count'])) {
            $row['donor_count'] = (int)($row['donation_count'] ?? 0);
        }

        if (!isset($row['category_name'])) {
            if (isset($row['category'])) {
                $row['category_name'] = $row['category'];
            } elseif (isset($row['category_slug'])) {
                $row['category_name'] = $row['category_slug'];
            }
        }

        if (!isset($row['category_slug']) && isset($row['category'])) {
            $row['category_slug'] = $row['category'];
        }

        if (!isset($row['beneficiary_name']) && isset($row['beneficiary'])) {
            $row['beneficiary_name'] = $row['beneficiary'];
        }

        return $row;
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
