<?php
class Notification {
    private Database $db;
    private User $userModel;
    private ?NewsArticle $newsModel = null;
    private array $newsArticleCache = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
    }

    /**
     * Crear una notificación y asignarla a usuarios
     *
     * @param array $data [title, message, type, created_by, audience, user_ids, meta]
     */
    public function create(array $data): int {
        $title = trim($data['title'] ?? '');
        $message = trim($data['message'] ?? '');
        $type = $this->normalizeType($data['type'] ?? 'info');
        $audience = ($data['audience'] ?? 'all') === 'users' ? 'users' : 'all';
        $createdBy = $data['created_by'] ?? null;
        $meta = isset($data['meta']) ? json_encode($data['meta']) : null;
        $userIds = $data['user_ids'] ?? [];

        if ($title === '' || $message === '') {
            throw new Exception('El título y el mensaje son obligatorios.');
        }

        $recipients = $this->resolveRecipients($audience, $userIds);
        if (empty($recipients)) {
            throw new Exception('Debe existir al menos un destinatario.');
        }

        try {
            $this->db->beginTransaction();

            $notificationId = $this->db->insert('notifications', [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'audience' => $audience,
                'created_by' => $createdBy,
                'meta' => $meta
            ]);

            $this->storeRecipients($notificationId, $recipients);

            $this->db->commit();
            return (int)$notificationId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Generar una notificación del sistema (sin creador asociado)
     */
    public function createSystem(array $data): int {
        $data['created_by'] = $data['created_by'] ?? null;
        $data['type'] = $data['type'] ?? 'system';
        return $this->create($data);
    }

    /**
     * Obtener notificaciones para un usuario
     */
    public function getForUser(int $userId, int $limit = 10, int $offset = 0): array {
        $result = $this->paginateForUser($userId, $limit, $offset);
        return $result['items'];
    }

    /**
     * Obtener notificaciones con soporte de paginación.
     */
    public function paginateForUser(int $userId, int $limit = 10, int $offset = 0): array {
        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $limitPlusOne = $limit + 1;

        $rows = $this->db->fetchAll(
            "SELECT n.id, n.title, n.message, n.type, n.created_at, n.meta,
                    nu.is_read, nu.read_at
             FROM notification_user nu
             INNER JOIN notifications n ON n.id = nu.notification_id
             WHERE nu.user_id = ?
             ORDER BY n.created_at DESC
             LIMIT {$limitPlusOne} OFFSET {$offset}",
            [$userId]
        ) ?: [];

        $hasMore = false;
        if (count($rows) > $limit) {
            $hasMore = true;
            array_pop($rows);
        }

        $items = array_map([$this, 'formatNotificationRow'], $rows);

        return [
            'items' => $items,
            'has_more' => $hasMore,
            'next_offset' => $offset + count($items)
        ];
    }

    /**
     * Obtener las notificaciones más recientes (admin)
     */
    public function recent(int $limit = 20): array {
        $limit = max(1, min($limit, 100));
        $rows = $this->db->fetchAll(
            "SELECT n.*, 
                    (SELECT COUNT(*) FROM notification_user nu WHERE nu.notification_id = n.id) AS recipients,
                    (SELECT COUNT(*) FROM notification_user nu WHERE nu.notification_id = n.id AND nu.is_read = 0) AS unread
             FROM notifications n
             ORDER BY n.created_at DESC
             LIMIT {$limit}"
        );

        if (!$rows) {
            return [];
        }

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'audience' => $row['audience'],
                'created_by' => $row['created_by'],
                'meta' => $this->decodeMeta($row['meta']),
                'created_at' => $row['created_at'],
                'recipients' => (int)($row['recipients'] ?? 0),
                'unread' => (int)($row['unread'] ?? 0)
            ];
        }, $rows);
    }

    /**
     * Listado paginado de notificaciones para la consola de administración
     */
    public function paginateAdmin(int $page = 1, int $perPage = 20, array $filters = []): array {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if (!empty($filters['type']) && in_array($filters['type'], ['info', 'success', 'warning', 'error', 'system'], true)) {
            $conditions[] = 'n.type = ?';
            $params[] = strtolower($filters['type']);
        }

        if (!empty($filters['audience']) && in_array($filters['audience'], ['all', 'users'], true)) {
            $conditions[] = 'n.audience = ?';
            $params[] = $filters['audience'];
        }

        if (!empty($filters['query'])) {
            $query = '%' . $filters['query'] . '%';
            $conditions[] = '(n.title LIKE ? OR n.message LIKE ?)';
            $params[] = $query;
            $params[] = $query;
        }

        if (!empty($filters['with_news'])) {
            $conditions[] = "n.meta IS NOT NULL AND n.meta <> ''";
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1=1';

        $countRow = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM notifications n WHERE {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT n.*, 
                       (SELECT COUNT(*) FROM notification_user nu WHERE nu.notification_id = n.id) AS recipients,
                       (SELECT COUNT(*) FROM notification_user nu WHERE nu.notification_id = n.id AND nu.is_read = 0) AS unread,
                       u.first_name, u.last_name, u.email
                FROM notifications n
                LEFT JOIN users u ON u.id = n.created_by
                WHERE {$where}
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?";

        $rows = $this->db->fetchAll($sql, array_merge($params, [$perPage, $offset])) ?: [];

        $items = array_map(function ($row) {
            $creatorName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($creatorName === '') {
                $creatorName = $row['email'] ?? null;
            }

            return [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'audience' => $row['audience'],
                'created_by' => $row['created_by'],
                'creator_name' => $creatorName,
                'meta' => $this->decodeMeta($row['meta']),
                'created_at' => $row['created_at'],
                'recipients' => (int)($row['recipients'] ?? 0),
                'unread' => (int)($row['unread'] ?? 0)
            ];
        }, $rows);

        $totalPages = max(1, (int)ceil($total / $perPage));

        return [
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }

    public function hasMilestoneNotification(int $campaignId, string $milestone): bool {
        $pattern = '%"campaign_id":' . $campaignId . '%';
        $rows = $this->db->fetchAll(
            "SELECT meta FROM notifications WHERE meta IS NOT NULL AND meta <> '' AND meta LIKE ? ORDER BY id DESC LIMIT 20",
            [$pattern]
        ) ?: [];

        foreach ($rows as $row) {
            $meta = $this->decodeMeta($row['meta']);
            if (!is_array($meta)) {
                continue;
            }

            if ((int)($meta['campaign_id'] ?? 0) === $campaignId && ($meta['milestone'] ?? '') === $milestone) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contar notificaciones no leídas
     */
    public function countUnread(int $userId): int {
        $row = $this->db->fetch(
            "SELECT COUNT(*) as total FROM notification_user WHERE user_id = ? AND is_read = 0",
            [$userId]
        );

        if (!$row) {
            return 0;
        }

        return (int)($row['total'] ?? 0);
    }

    /**
     * Marcar notificaciones como leídas
     */
    public function markAsRead(int $userId, ?array $notificationIds = null): int {
        $params = [$userId];
        $sql = "UPDATE notification_user SET is_read = 1, read_at = NOW() WHERE user_id = ?";

        if (!empty($notificationIds)) {
            $ids = $this->filterIds($notificationIds);
            if (empty($ids)) {
                return 0;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND notification_id IN ({$placeholders})";
            $params = array_merge($params, $ids);
        }

        $sql .= " AND is_read = 0";

        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Eliminar notificaciones para un usuario (tabla pivote).
     */
    public function deleteForUser(int $userId, array $notificationIds): int {
        $ids = $this->filterIds($notificationIds);
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$userId], $ids);

        $stmt = $this->db->query(
            "DELETE FROM notification_user WHERE user_id = ? AND notification_id IN ({$placeholders})",
            $params
        );

        return $stmt->rowCount();
    }

    private function resolveRecipients(string $audience, array $userIds): array {
        if ($audience === 'all') {
            return $this->userModel->getAllActiveIds();
        }

        return $this->filterIds($userIds);
    }

    private function storeRecipients(int $notificationId, array $userIds): void {
        foreach ($userIds as $userId) {
            $this->db->insert('notification_user', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'is_read' => 0
            ]);
        }
    }

    private function filterIds(array $ids): array {
        $sanitized = [];
        foreach ($ids as $id) {
            $intId = (int)$id;
            if ($intId > 0) {
                $sanitized[$intId] = $intId;
            }
        }
        return array_values($sanitized);
    }

    private function normalizeType(string $type): string {
        $allowed = ['info', 'success', 'warning', 'error', 'system'];
        $type = strtolower($type);
        if (!in_array($type, $allowed, true)) {
            return 'info';
        }
        return $type;
    }

    private function formatNotificationRow(array $row): array {
        $meta = $this->decodeMeta($row['meta'] ?? null);
        $meta = $this->hydrateMeta($meta);

        return [
            'id' => (int)($row['id'] ?? 0),
            'title' => $row['title'] ?? '',
            'message' => $row['message'] ?? '',
            'type' => $row['type'] ?? 'info',
            'created_at' => $row['created_at'] ?? '',
            'is_read' => (bool)($row['is_read'] ?? false),
            'read_at' => $row['read_at'] ?? null,
            'meta' => $meta
        ];
    }

    private function decodeMeta($meta) {
        if ($meta === null || $meta === '') {
            return null;
        }

        if (is_array($meta)) {
            return $meta;
        }

        if (defined('JSON_THROW_ON_ERROR')) {
            try {
                $decoded = json_decode($meta, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        $decoded = json_decode($meta, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function hydrateMeta(?array $meta): ?array
    {
        if (empty($meta) || !isset($meta['news_article_id'])) {
            return $meta;
        }

        $articleId = (int)$meta['news_article_id'];
        if ($articleId <= 0) {
            return $meta;
        }

        $article = $this->findNewsArticleById($articleId);
        if ($article === null || empty($article['slug'])) {
            return $meta;
        }

        $meta['news_article'] = [
            'id' => (int)($article['id'] ?? 0),
            'title' => (string)($article['title'] ?? ''),
            'slug' => (string)($article['slug'] ?? ''),
            'summary' => (string)($article['summary'] ?? ''),
        ];

        if (!empty($article['category_slug'])) {
            $meta['news_article']['category_slug'] = (string)$article['category_slug'];
        }

        $url = isset($meta['url']) && is_string($meta['url']) && $meta['url'] !== ''
            ? $meta['url']
            : Router::url('noticias/' . $article['slug']);

        $meta['url'] = $url;
        if (empty($meta['link_label'])) {
            $meta['link_label'] = 'Ver noticia';
        }

        return $meta;
    }

    private function findNewsArticleById(int $articleId): ?array
    {
        if ($articleId <= 0) {
            return null;
        }

        if (array_key_exists($articleId, $this->newsArticleCache)) {
            return $this->newsArticleCache[$articleId];
        }

        if ($this->newsModel === null) {
            if (!class_exists('NewsArticle')) {
                $this->newsArticleCache[$articleId] = null;
                return null;
            }

            $this->newsModel = new NewsArticle();
        }

        try {
            $article = $this->newsModel->findById($articleId);
        } catch (Throwable $exception) {
            if (class_exists('Logger')) {
                Logger::warning('No se pudo hidratar la noticia asociada a la notificación', [
                    'article_id' => $articleId,
                    'error' => $exception->getMessage(),
                ]);
            }
            $article = null;
        }

        if (is_array($article) && isset($article['status']) && $article['status'] !== 'published') {
            $article = null;
        }

        $this->newsArticleCache[$articleId] = $article ?: null;

        return $this->newsArticleCache[$articleId];
    }
}
