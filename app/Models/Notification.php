<?php
class Notification {
    private Database $db;
    private User $userModel;

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
    public function getForUser(int $userId, int $limit = 10): array {
        $limit = max(1, min($limit, 50));

        $rows = $this->db->fetchAll(
            "SELECT n.id, n.title, n.message, n.type, n.created_at, nu.is_read, nu.read_at
             FROM notification_user nu
             INNER JOIN notifications n ON n.id = nu.notification_id
             WHERE nu.user_id = ?
             ORDER BY n.created_at DESC
             LIMIT {$limit}",
            [$userId]
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
                'created_at' => $row['created_at'],
                'is_read' => (bool)$row['is_read'],
                'read_at' => $row['read_at']
            ];
        }, $rows);
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
                'meta' => $row['meta'],
                'created_at' => $row['created_at'],
                'recipients' => (int)($row['recipients'] ?? 0),
                'unread' => (int)($row['unread'] ?? 0)
            ];
        }, $rows);
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
}
