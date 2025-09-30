<?php
class NotificationAdminController {
    private Notification $notifications;
    private User $users;

    public function __construct() {
        $this->notifications = new Notification();
        $this->users = new User();
    }

    public function index() {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $page_title = 'Notificaciones';
        $current_page = 'admin-notifications';
        $notifications = $this->notifications->recent(25);
        $availableUsers = $this->users->getActiveUsersBasic();
        $old = $_SESSION['old_notification_form'] ?? [];
        unset($_SESSION['old_notification_form']);
        include VIEWS_PATH . '/admin/notifications.php';
    }

    public function store() {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = strtolower($_POST['type'] ?? 'info');
        $audience = $_POST['audience'] === 'users' ? 'users' : 'all';
        $userIds = $_POST['user_ids'] ?? [];

        if (is_string($userIds)) {
            $userIds = [$userIds];
        }

        $errors = [];
        if ($title === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if ($message === '') {
            $errors[] = 'El mensaje es obligatorio.';
        }
        $validTypes = ['info', 'success', 'warning', 'error', 'system'];
        if (!in_array($type, $validTypes, true)) {
            $errors[] = 'Tipo de notificación inválido.';
        }
        if ($audience === 'users') {
            $sanitizedIds = array_filter(array_map('intval', (array)$userIds));
            if (empty($sanitizedIds)) {
                $errors[] = 'Selecciona al menos un usuario destinatario.';
            }
            $userIds = $sanitizedIds;
        } else {
            $userIds = [];
        }

        $formData = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'audience' => $audience,
            'user_ids' => $userIds
        ];

        if (!empty($errors)) {
            $_SESSION['old_notification_form'] = $formData;
            SessionHelper::setFlash('error', implode(' ', $errors));
            Router::redirect('/admin/notificaciones');
        }

        try {
            $formData['created_by'] = SessionHelper::getUserId();
            $this->notifications->create($formData);
            SessionHelper::setFlash('success', 'Notificación enviada correctamente.');
        } catch (Exception $e) {
            $_SESSION['old_notification_form'] = $formData;
            SessionHelper::setFlash('error', 'No se pudo enviar la notificación: ' . $e->getMessage());
        }

        Router::redirect('/admin/notificaciones');
    }

    private function isAdmin(): bool {
        return SessionHelper::isAuthenticated() && SessionHelper::isAdmin();
    }
}
