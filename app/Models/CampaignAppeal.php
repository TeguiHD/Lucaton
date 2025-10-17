<?php

class CampaignAppeal {
    private Database $db;
    private static ?bool $hasFilesTable = null;
    private const STATUSES = ['pending', 'under_review', 'approved', 'rejected', 'closed'];
    private static bool $campaignTableChecked = false;
    private static bool $campaignTableExists = false;
    private static array $campaignColumns = [];

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$hasFilesTable === null) {
            try {
                self::$hasFilesTable = $this->db->tableExists('campaign_appeal_files');
            } catch (Throwable $exception) {
                Logger::warning('No se pudo verificar la existencia de campaign_appeal_files', [
                    'error' => $exception->getMessage(),
                ]);
                self::$hasFilesTable = false;
            }
        }

        if (!self::$campaignTableChecked) {
            self::$campaignTableChecked = true;
            try {
                self::$campaignTableExists = $this->db->tableExists('campaigns');
                if (self::$campaignTableExists) {
                    $columns = $this->db->fetchAll('SHOW COLUMNS FROM campaigns');
                    if (is_array($columns)) {
                        foreach ($columns as $column) {
                            if (isset($column['Field'])) {
                                self::$campaignColumns[$column['Field']] = true;
                            }
                        }
                    }
                }
            } catch (Throwable $exception) {
                Logger::warning('No se pudo inspeccionar la tabla campaigns para apelaciones', [
                    'error' => $exception->getMessage(),
                ]);
                self::$campaignTableExists = false;
                self::$campaignColumns = [];
            }
        }
    }

    public function create(array $data): int {
        $payload = [
            'campaign_id' => (int)$data['campaign_id'],
            'user_id' => (int)$data['user_id'],
            'reason' => $data['reason'],
            'additional_evidence' => $data['additional_evidence'] ?? null,
            'status' => 'pending',
        ];

        return (int)$this->db->insert('campaign_appeals', $payload);
    }

    public function userHasPending(int $campaignId, int $userId): bool {
        $row = $this->db->fetch(
            "SELECT id FROM campaign_appeals
             WHERE campaign_id = ? AND user_id = ? AND status IN ('pending','under_review')
             ORDER BY created_at DESC LIMIT 1",
            [$campaignId, $userId]
        );

        return !empty($row);
    }

    public function getLatestForCampaigns(array $campaignIds, int $userId): array {
        if (empty($campaignIds)) {
            return [];
        }

        $campaignIds = array_values(array_unique(array_filter($campaignIds, static fn ($id) => $id > 0)));
        if (empty($campaignIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
        $params = array_merge($campaignIds, [$userId]);

        $rows = $this->db->fetchAll(
            "SELECT ca.*
             FROM campaign_appeals ca
             INNER JOIN (
                 SELECT MAX(id) AS latest_id
                 FROM campaign_appeals
                 WHERE campaign_id IN ($placeholders) AND user_id = ?
                 GROUP BY campaign_id
             ) latest ON latest.latest_id = ca.id",
            $params
        ) ?: [];

        $appealIds = array_map(static fn ($row) => (int)($row['id'] ?? 0), $rows);
        $filesByAppeal = $this->getFilesForAppeals($appealIds);

        $result = [];
        foreach ($rows as $row) {
            $appealId = (int)($row['id'] ?? 0);
            $row['files'] = $appealId > 0 ? ($filesByAppeal[$appealId] ?? []) : [];
            $result[(int)$row['campaign_id']] = $row;
        }

        return $result;
    }

    public function countByStatuses(array $statuses): int {
        $statuses = array_values(array_filter(array_map(static function ($status) {
            return strtolower(trim((string)$status));
        }, $statuses), static function ($status) {
            return in_array($status, self::STATUSES, true);
        }));

        if (empty($statuses)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM campaign_appeals WHERE status IN ({$placeholders})",
            $statuses
        );

        return (int)($row['total'] ?? 0);
    }

    public function paginateForAdmin(array $filters, int $perPage = 20, int $page = 1): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $conditions = ['1=1'];
        $params = [];

        $statusFilter = strtolower(trim((string)($filters['status'] ?? 'open')));
        if ($statusFilter === 'open') {
            $conditions[] = "ca.status IN ('pending','under_review')";
        } elseif ($statusFilter !== '' && $statusFilter !== 'all') {
            if (!in_array($statusFilter, self::STATUSES, true)) {
                $statusFilter = 'pending';
            }
            $conditions[] = 'ca.status = ?';
            $params[] = $statusFilter;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = '(c.title LIKE ? OR c.slug LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR ca.reason LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where = implode(' AND ', $conditions);

        $countRow = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM campaign_appeals ca
             LEFT JOIN campaigns c ON c.id = ca.campaign_id
             LEFT JOIN users u ON u.id = ca.user_id
             WHERE {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $filesSelect = $this->hasFilesTable()
            ? '(SELECT COUNT(*) FROM campaign_appeal_files f WHERE f.appeal_id = ca.id) AS files_count'
            : '0 AS files_count';

        $selectCampaignSlug = $this->campaignColumnExists('slug') ? 'c.slug AS campaign_slug' : 'NULL AS campaign_slug';
        $selectCampaignStatus = $this->campaignColumnExists('status') ? 'c.status AS campaign_status' : 'NULL AS campaign_status';
        $selectCampaignVisibility = $this->campaignColumnExists('visibility') ? 'c.visibility AS campaign_visibility' : 'NULL AS campaign_visibility';
        $selectCampaignFeatured = $this->campaignColumnExists('featured') ? 'c.featured AS campaign_featured' : 'NULL AS campaign_featured';
        $selectCampaignOwner = $this->campaignColumnExists('owner_id') ? 'c.owner_id AS campaign_owner_id' : ($this->campaignColumnExists('user_id') ? 'c.user_id AS campaign_owner_id' : 'NULL AS campaign_owner_id');

        $sql = "SELECT ca.*,
                       c.title AS campaign_title,
                       {$selectCampaignSlug},
                       {$selectCampaignStatus},
                       {$selectCampaignVisibility},
                       {$selectCampaignFeatured},
                       u.first_name AS user_first_name,
                       u.last_name AS user_last_name,
                       u.username AS user_username,
                       u.email AS user_email,
                       reviewer.first_name AS reviewer_first_name,
                       reviewer.last_name AS reviewer_last_name,
                       reviewer.username AS reviewer_username,
                       reviewer.email AS reviewer_email,
                       {$selectCampaignOwner},
                       {$filesSelect}
                FROM campaign_appeals ca
                LEFT JOIN campaigns c ON c.id = ca.campaign_id
                LEFT JOIN users u ON u.id = ca.user_id
                LEFT JOIN users reviewer ON reviewer.id = ca.reviewed_by
                WHERE {$where}
                ORDER BY ca.created_at DESC
                LIMIT ? OFFSET ?";

        $rows = $this->db->fetchAll(
            $sql,
            array_merge($params, [$perPage, $offset])
        ) ?: [];

        $items = array_map([$this, 'formatAdminRow'], $rows);

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }

    public function findForAdmin(int $appealId): ?array {
        if ($appealId <= 0) {
            return null;
        }

        $filesSelect = $this->hasFilesTable()
            ? '(SELECT COUNT(*) FROM campaign_appeal_files f WHERE f.appeal_id = ca.id) AS files_count'
            : '0 AS files_count';

        $selectCampaignSlug = $this->campaignColumnExists('slug') ? 'c.slug AS campaign_slug' : 'NULL AS campaign_slug';
        $selectCampaignStatus = $this->campaignColumnExists('status') ? 'c.status AS campaign_status' : 'NULL AS campaign_status';
        $selectCampaignVisibility = $this->campaignColumnExists('visibility') ? 'c.visibility AS campaign_visibility' : 'NULL AS campaign_visibility';
        $selectCampaignOwner = $this->campaignColumnExists('owner_id') ? 'c.owner_id AS campaign_owner_id' : ($this->campaignColumnExists('user_id') ? 'c.user_id AS campaign_owner_id' : 'NULL AS campaign_owner_id');

        $row = $this->db->fetch(
            "SELECT ca.*,
                    c.title AS campaign_title,
                    {$selectCampaignSlug},
                    {$selectCampaignStatus},
                    {$selectCampaignVisibility},
                    {$selectCampaignOwner},
                    u.first_name AS user_first_name,
                    u.last_name AS user_last_name,
                    u.username AS user_username,
                    u.email AS user_email,
                    reviewer.first_name AS reviewer_first_name,
                    reviewer.last_name AS reviewer_last_name,
                    reviewer.username AS reviewer_username,
                    reviewer.email AS reviewer_email,
                    {$filesSelect}
             FROM campaign_appeals ca
             LEFT JOIN campaigns c ON c.id = ca.campaign_id
             LEFT JOIN users u ON u.id = ca.user_id
             LEFT JOIN users reviewer ON reviewer.id = ca.reviewed_by
             WHERE ca.id = ?
             LIMIT 1",
            [$appealId]
        );

        if (!$row) {
            return null;
        }

        $formatted = $this->formatAdminRow($row);
        $files = $this->getFilesForAppeals([$appealId]);
        $formatted['files'] = $files[$appealId] ?? [];

        return $formatted;
    }

    public function updateStatus(int $appealId, string $status, array $data): bool {
        $status = strtolower(trim($status));
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Estado de apelación inválido.');
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'status' => $status,
            'updated_at' => $now,
        ];

        if (array_key_exists('admin_response', $data)) {
            $payload['admin_response'] = $data['admin_response'];
        }

        if (array_key_exists('reviewed_by', $data)) {
            $payload['reviewed_by'] = $data['reviewed_by'];
        }

        if (array_key_exists('reviewed_at', $data)) {
            $payload['reviewed_at'] = $data['reviewed_at'];
        } elseif (in_array($status, ['approved', 'rejected', 'closed'], true)) {
            $payload['reviewed_at'] = $now;
        } elseif ($status === 'under_review') {
            $payload['reviewed_at'] = null;
        }

        $result = $this->db->update('campaign_appeals', $payload, 'id = ?', [$appealId]);
        return $result >= 0;
    }

    public function findFile(int $appealId, int $fileId): ?array {
        if (!$this->hasFilesTable() || $appealId <= 0 || $fileId <= 0) {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT f.*, ca.campaign_id
             FROM campaign_appeal_files f
             INNER JOIN campaign_appeals ca ON ca.id = f.appeal_id
             WHERE f.id = ? AND f.appeal_id = ?
             LIMIT 1",
            [$fileId, $appealId]
        );

        if (!$row) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'appeal_id' => (int)$row['appeal_id'],
            'campaign_id' => (int)($row['campaign_id'] ?? 0),
            'path' => $row['storage_path'],
            'original_name' => $row['original_name'],
            'mime_type' => $row['mime_type'],
            'size_bytes' => (int)$row['size_bytes'],
            'uploaded_by' => (int)$row['uploaded_by'],
            'created_at' => $row['created_at'],
        ];
    }

    public static function statusMeta(string $status): array {
        $normalized = strtolower(trim($status));
        $map = [
            'pending' => [
                'label' => 'Pendiente',
                'badge' => 'bg-amber-100 text-amber-800 border border-amber-200',
                'description' => 'Esperando revisión del equipo',
            ],
            'under_review' => [
                'label' => 'En revisión',
                'badge' => 'bg-blue-100 text-blue-800 border border-blue-200',
                'description' => 'Asignada a un revisor',
            ],
            'approved' => [
                'label' => 'Aprobada',
                'badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                'description' => 'La campaña fue restituida',
            ],
            'rejected' => [
                'label' => 'Rechazada',
                'badge' => 'bg-rose-100 text-rose-700 border border-rose-200',
                'description' => 'Se mantiene el rechazo original',
            ],
            'closed' => [
                'label' => 'Cerrada',
                'badge' => 'bg-gray-200 text-gray-700 border border-gray-300',
                'description' => 'Caso concluido manualmente',
            ],
        ];

        return $map[$normalized] ?? [
            'label' => ucfirst($normalized ?: 'Desconocido'),
            'badge' => 'bg-gray-100 text-gray-700 border border-gray-200',
            'description' => 'Estado no clasificado',
        ];
    }

    /**
     * @param array<int, array{path:string,filename:string,mime:string,size:int,uploaded_by:int}> $files
     */
    public function attachEvidenceFiles(int $appealId, array $files): void {
        if (!$this->hasFilesTable() || empty($files)) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($files as $file) {
            if (empty($file['path']) || empty($file['filename'])) {
                continue;
            }

            try {
                $this->db->insert('campaign_appeal_files', [
                    'appeal_id' => $appealId,
                    'storage_path' => $file['path'],
                    'original_name' => $file['filename'],
                    'mime_type' => $file['mime'] ?? 'application/octet-stream',
                    'size_bytes' => (int)($file['size'] ?? 0),
                    'uploaded_by' => (int)($file['uploaded_by'] ?? 0),
                    'created_at' => $now,
                ]);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo adjuntar un archivo a la apelación', [
                    'appeal_id' => $appealId,
                    'path' => $file['path'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getFilesForAppeals(array $appealIds): array {
        if (!$this->hasFilesTable()) {
            return [];
        }

        $appealIds = array_values(array_unique(array_filter(array_map('intval', $appealIds))));
        if (empty($appealIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($appealIds), '?'));
        try {
            $rows = $this->db->fetchAll(
                "SELECT id, appeal_id, storage_path, original_name, mime_type, size_bytes, uploaded_by, created_at
                 FROM campaign_appeal_files
                 WHERE appeal_id IN ($placeholders)
                 ORDER BY id ASC",
                $appealIds
            );
        } catch (Throwable $exception) {
            Logger::warning('No se pudo obtener archivos de apelaciones', [
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $appealId = (int)($row['appeal_id'] ?? 0);
            if ($appealId <= 0) {
                continue;
            }

            $grouped[$appealId][] = [
                'id' => (int)($row['id'] ?? 0),
                'path' => $row['storage_path'] ?? null,
                'original_name' => $row['original_name'] ?? null,
                'mime_type' => $row['mime_type'] ?? null,
                'size_bytes' => (int)($row['size_bytes'] ?? 0),
                'uploaded_by' => (int)($row['uploaded_by'] ?? 0),
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        return $grouped;
    }

    private function formatAdminRow(array $row): array {
        $requesterName = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
        if ($requesterName === '') {
            $requesterName = $row['user_username'] ?? ($row['user_email'] ?? 'Solicitante');
        }

        $reviewerName = null;
        if (!empty($row['reviewer_first_name']) || !empty($row['reviewer_last_name'])) {
            $reviewerName = trim(($row['reviewer_first_name'] ?? '') . ' ' . ($row['reviewer_last_name'] ?? ''));
        } elseif (!empty($row['reviewer_username'])) {
            $reviewerName = $row['reviewer_username'];
        } elseif (!empty($row['reviewer_email'])) {
            $reviewerName = $row['reviewer_email'];
        }

        $campaignPath = null;
        if (!empty($row['campaign_slug']) && !empty($row['user_username'])) {
            $campaignPath = 'campana/' . rawurlencode((string)$row['user_username']) . '/' . rawurlencode((string)$row['campaign_slug']);
        }

        $formatted = $row;
        $formatted['requester_name'] = $requesterName;
        $formatted['requester_email'] = $row['user_email'] ?? null;
        $formatted['requester_username'] = $row['user_username'] ?? null;
        $formatted['reviewer_name'] = $reviewerName;
        $formatted['campaign_title'] = $row['campaign_title'] ?? ('Campaña #' . ($row['campaign_id'] ?? '—'));
        $formatted['campaign_path'] = $campaignPath;
        $formatted['files_count'] = isset($row['files_count']) ? (int)$row['files_count'] : 0;

        return $formatted;
    }

    private function hasFilesTable(): bool {
        return (bool)self::$hasFilesTable;
    }

    private function campaignColumnExists(string $column): bool {
        return self::$campaignTableExists && isset(self::$campaignColumns[$column]);
    }
}
