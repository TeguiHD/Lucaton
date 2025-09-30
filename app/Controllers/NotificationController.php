<?php
class NotificationController {
    private Notification $notifications;

    public function __construct() {
        $this->notifications = new Notification();
    }

    public function index() {
        if (!SessionHelper::isAuthenticated()) {
            $this->respondUnauthorized();
        }

        $userId = SessionHelper::getUserId();
        $items = $this->notifications->getForUser($userId, 20);
        $unread = $this->notifications->countUnread($userId);

        $payload = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'message' => $item['message'],
                'type' => $item['type'],
                'is_read' => $item['is_read'],
                'created_at' => $item['created_at'],
                'time_ago' => $this->humanDiff($item['created_at'])
            ];
        }, $items);

        $this->respondJson([
            'success' => true,
            'notifications' => $payload,
            'unread' => $unread
        ]);
    }

    public function markRead() {
        if (!SessionHelper::isAuthenticated()) {
            $this->respondUnauthorized();
        }

        $userId = SessionHelper::getUserId();
        $ids = $_POST['ids'] ?? [];
        if (is_string($ids)) {
            $ids = [$ids];
        }

        $updated = $this->notifications->markAsRead($userId, $ids);
        $unread = $this->notifications->countUnread($userId);

        $this->respondJson([
            'success' => true,
            'updated' => $updated,
            'unread' => $unread
        ]);
    }

    private function respondUnauthorized(): void {
        $this->respondJson([
            'success' => false,
            'message' => 'No autorizado.'
        ], 401);
    }

    private function respondJson(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function humanDiff(string $timestamp): string {
        $time = strtotime($timestamp);
        if (!$time) {
            return '';
        }

        $diff = time() - $time;
        if ($diff < 60) {
            return 'Hace unos segundos';
        }
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return 'Hace ' . $minutes . ' minuto' . ($minutes === 1 ? '' : 's');
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'Hace ' . $hours . ' hora' . ($hours === 1 ? '' : 's');
        }
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'Hace ' . $days . ' día' . ($days === 1 ? '' : 's');
        }

        return date('d/m/Y H:i', $time);
    }
}
