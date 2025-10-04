<?php
class NotificationAdminController {
    private Notification $notifications;
    private User $users;
    private NewsArticle $news;

    public function __construct() {
        $this->notifications = new Notification();
        $this->users = new User();
        $this->news = new NewsArticle();
    }

    public function index() {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $page_title = 'Notificaciones';
        $current_page = 'admin-notifications';
        $notifications = $this->notifications->recent(25);
        $availableUsers = $this->users->getActiveUsersBasic();
        $newsResult = $this->news->getPublished([], 1, 50);
        $availableNews = $newsResult['data'] ?? [];
        $old = $_SESSION['old_notification_form'] ?? [];
        unset($_SESSION['old_notification_form']);
        include VIEWS_PATH . '/admin/notifications.php';
    }

    public function history() {
        if (!$this->isAdmin()) {
            Router::redirect('/');
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);

        $filters = [
            'query' => trim($_GET['q'] ?? ''),
            'type' => $_GET['type'] ?? '',
            'audience' => $_GET['audience'] ?? '',
            'with_news' => isset($_GET['with_news']) && $_GET['with_news'] !== ''
        ];

        if (!in_array($filters['type'], ['info', 'success', 'warning', 'error', 'system'], true)) {
            $filters['type'] = '';
        }

        if (!in_array($filters['audience'], ['all', 'users'], true)) {
            $filters['audience'] = '';
        }

        $result = $this->notifications->paginateAdmin($page, 20, $filters);

        $page_title = 'Historial de notificaciones';
        $current_page = 'admin-notifications-history';
        $notifications = $result['data'];
        $pagination = $result['pagination'];
        $activeFilters = $filters;

        include VIEWS_PATH . '/admin/notifications-history.php';
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
        $newsArticleId = $_POST['news_article_id'] ?? null;

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

        $selectedNewsId = null;
        if ($newsArticleId !== null && $newsArticleId !== '') {
            $selectedNewsId = (int)$newsArticleId;
            if ($selectedNewsId <= 0 || !$this->news->findById($selectedNewsId)) {
                $errors[] = 'La noticia seleccionada no es válida.';
                $selectedNewsId = null;
            }
        }

        $formData = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'audience' => $audience,
            'user_ids' => $userIds,
            'news_article_id' => $selectedNewsId
        ];

        if (!empty($errors)) {
            $_SESSION['old_notification_form'] = $formData;
            SessionHelper::setFlash('error', implode(' ', $errors));
            Router::redirect('/admin/notificaciones');
        }

        try {
            $formData['created_by'] = SessionHelper::getUserId();
            $meta = [];
            if ($selectedNewsId) {
                $meta['news_article_id'] = $selectedNewsId;
            }
            if (!empty($meta)) {
                $formData['meta'] = $meta;
            }
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
