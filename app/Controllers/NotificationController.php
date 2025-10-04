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
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($limit <= 0) {
            $limit = 10;
        }

        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        if ($offset < 0) {
            $offset = 0;
        }

        if (isset($_GET['page'])) {
            $page = (int)$_GET['page'];
            if ($page > 1) {
                $offset = ($page - 1) * $limit;
            } elseif ($page === 1) {
                $offset = 0;
            }
        }

        $result = $this->notifications->paginateForUser($userId, $limit, $offset);
        $items = $result['items'];
        $unread = $this->notifications->countUnread($userId);

        $payload = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'message' => $item['message'],
                'type' => $item['type'],
                'is_read' => $item['is_read'],
                'created_at' => $item['created_at'],
                'time_ago' => $this->humanDiff($item['created_at']),
                'meta' => $item['meta'],
                'read_at' => $item['read_at']
            ];
        }, $items);

        $this->respondJson([
            'success' => true,
            'notifications' => $payload,
            'unread' => $unread,
            'has_more' => $result['has_more'],
            'next_offset' => $result['next_offset']
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

    public function delete() {
        if (!SessionHelper::isAuthenticated()) {
            $this->respondUnauthorized();
        }

        $userId = SessionHelper::getUserId();
        $ids = $_POST['ids'] ?? $_POST['id'] ?? [];
        if (is_string($ids)) {
            $ids = [$ids];
        }

        $deleted = $this->notifications->deleteForUser($userId, (array)$ids);
        $unread = $this->notifications->countUnread($userId);

        $this->respondJson([
            'success' => true,
            'deleted' => $deleted,
            'unread' => $unread
        ]);
    }

    public function summary() {
        if (!SessionHelper::isAuthenticated()) {
            $this->respondUnauthorized();
        }

        $userId = SessionHelper::getUserId();
        $unread = $this->notifications->countUnread($userId);

        $this->respondJson([
            'success' => true,
            'unread' => $unread
        ]);
    }

    public function history(): void
    {
        if (!SessionHelper::isAuthenticated()) {
            Router::redirect('/login');
            return;
        }

        $userId = (int)SessionHelper::getUserId();

        $limit = max(1, min((int)($_GET['limit'] ?? 20), 50));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $result = $this->notifications->paginateForUser($userId, $limit, $offset);
        $notifications = $result['items'];
        $hasMore = $result['has_more'];

        $selectedId = isset($_GET['n']) ? (int)$_GET['n'] : 0;
        $selectedNotification = null;

        foreach ($notifications as $index => $notification) {
            if ((int)$notification['id'] === $selectedId) {
                $selectedNotification = $notification;
                break;
            }
        }

        if ($selectedNotification === null && !empty($notifications)) {
            $selectedNotification = $notifications[0];
            $selectedId = (int)$selectedNotification['id'];
        }

        if ($selectedNotification && !$selectedNotification['is_read']) {
            $this->notifications->markAsRead($userId, [$selectedNotification['id']]);
            $selectedNotification['is_read'] = true;
            $selectedNotification['read_at'] = date('Y-m-d H:i:s');
            foreach ($notifications as &$item) {
                if ($item['id'] === $selectedNotification['id']) {
                    $item['is_read'] = true;
                    $item['read_at'] = $selectedNotification['read_at'];
                    break;
                }
            }
            unset($item);
        }

        $unreadCount = $this->notifications->countUnread($userId);

        $pagination = [
            'page' => $page,
            'limit' => $limit,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        ];

        $current_page = 'notifications';
        $page_title = 'Notificaciones';

        include VIEWS_PATH . '/user/notifications-history.php';
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
