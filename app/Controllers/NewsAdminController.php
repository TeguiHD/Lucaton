<?php

class NewsAdminController {
    private NewsArticle $articles;
    private NewsCategory $categories;

    public function __construct() {
        $this->articles = new NewsArticle();
        $this->categories = new NewsCategory();
    }

    public function index() {
        $status = $_GET['status'] ?? '';
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $filters = [];
        if ($status !== '') {
            $filters['status'] = $status;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $result = $this->getArticlesForAdmin($filters, $page, 12);
        $articles = $result['data'];
        $pagination = $result['pagination'];
        $page_title = 'Gestión de noticias';
        $meta_description = 'Administra y publica noticias para la comunidad de Lucatón.';
        $current_page = 'admin-news';

        include VIEWS_PATH . '/admin/news/index.php';
    }

    public function create() {
        $page_title = 'Nueva noticia';
        $meta_description = 'Redacta una nueva noticia para la comunidad de Lucatón.';
        $current_page = 'admin-news';
        $categories = $this->categories->all();
        $article = null;
        $errors = [];
        $old = [
            'title' => '',
            'summary' => '',
            'content' => '',
            'status' => 'draft',
            'category_id' => '',
            'category_name' => '',
            'meta_title' => '',
            'meta_description' => ''
        ];

        include VIEWS_PATH . '/admin/news/create.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('admin/news');
        }

        if (!SessionHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('error', 'Token de seguridad inválido.');
            Router::redirect('admin/news');
        }

        $data = $this->sanitizeInput($_POST);
        $errors = $this->validate($data);

        $coverPath = null;
        try {
            $coverPath = $this->handleUpload($_FILES['cover_image'] ?? null);
            if ($coverPath) {
                $data['cover_image'] = $coverPath;
            }
        } catch (Exception $e) {
            $errors['cover_image'] = $e->getMessage();
        }

        $gallery = [];
        try {
            $gallery = $this->handleGalleryUploads($_FILES['gallery_images'] ?? null, $_POST['gallery_captions'] ?? [], $_POST['gallery_sort_order'] ?? []);
            $data['gallery'] = $gallery;
        } catch (Exception $e) {
            $errors['gallery'] = $e->getMessage();
        }

        if (!empty($errors)) {
            $categories = $this->categories->all();
            $page_title = 'Nueva noticia';
            $meta_description = 'Redacta una nueva noticia para la comunidad de Lucatón.';
            $current_page = 'admin-news';
            $article = null;
            $old = $data;
            include VIEWS_PATH . '/admin/news/create.php';
            return;
        }

        try {
            $data['author_id'] = SessionHelper::getUserId();
            $articleId = $this->articles->create($data);
            SessionHelper::setFlash('success', 'La noticia se publicó correctamente.');
            Router::redirect('admin/news');
        } catch (Exception $e) {
            Logger::error('Error creating news article', ['error' => $e->getMessage()]);
            $categories = $this->categories->all();
            $page_title = 'Nueva noticia';
            $article = null;
            $old = $data;
            $errors['general'] = 'Ocurrió un error al guardar la noticia. Intenta nuevamente.';
            include VIEWS_PATH . '/admin/news/create.php';
        }
    }

    public function edit($id) {
        $article = $this->articles->findById((int)$id);
        if (!$article) {
            SessionHelper::setFlash('error', 'La noticia no existe.');
            Router::redirect('admin/news');
        }

        $page_title = 'Editar noticia';
        $meta_description = 'Actualiza la información de la noticia seleccionada.';
        $current_page = 'admin-news';
        $categories = $this->categories->all();
        $errors = [];
        $old = $article;

        include VIEWS_PATH . '/admin/news/edit.php';
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('admin/news');
        }

        if (!SessionHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('error', 'Token de seguridad inválido.');
            Router::redirect('admin/news');
        }

        $article = $this->articles->findById((int)$id);
        if (!$article) {
            SessionHelper::setFlash('error', 'La noticia no existe.');
            Router::redirect('admin/news');
        }

        $data = $this->sanitizeInput($_POST);
        $errors = $this->validate($data, true);

        try {
            $coverPath = $this->handleUpload($_FILES['cover_image'] ?? null);
            if ($coverPath) {
                $data['cover_image'] = $coverPath;
            }
        } catch (Exception $e) {
            $errors['cover_image'] = $e->getMessage();
        }

        $data['existing_gallery'] = $_POST['existing_gallery'] ?? [];
        $removeIds = $_POST['remove_gallery'] ?? [];
        $data['remove_gallery_ids'] = array_map('intval', array_keys(array_filter($removeIds)));

        try {
            $data['gallery'] = $this->handleGalleryUploads($_FILES['gallery_images'] ?? null, $_POST['gallery_captions'] ?? [], $_POST['gallery_sort_order'] ?? []);
        } catch (Exception $e) {
            $errors['gallery'] = $e->getMessage();
        }

        if (!empty($errors)) {
            $categories = $this->categories->all();
            $page_title = 'Editar noticia';
            $meta_description = 'Actualiza la información de la noticia seleccionada.';
            $current_page = 'admin-news';
            $old = array_merge($article, $data);
            include VIEWS_PATH . '/admin/news/edit.php';
            return;
        }

        try {
            $this->articles->update((int)$id, $data);
            SessionHelper::setFlash('success', 'La noticia se actualizó correctamente.');
            Router::redirect('admin/news');
        } catch (Exception $e) {
            Logger::error('Error updating news article', ['error' => $e->getMessage(), 'article_id' => $id]);
            $categories = $this->categories->all();
            $page_title = 'Editar noticia';
            $errors['general'] = 'Ocurrió un error al guardar los cambios. Intenta nuevamente.';
            $old = array_merge($article, $data);
            include VIEWS_PATH . '/admin/news/edit.php';
        }
    }

    public function destroy($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('admin/news');
        }

        if (!SessionHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('error', 'Token de seguridad inválido.');
            Router::redirect('admin/news');
        }

        try {
            $this->articles->delete((int)$id);
            SessionHelper::setFlash('success', 'La noticia se eliminó correctamente.');
        } catch (Exception $e) {
            Logger::error('Error deleting news article', ['error' => $e->getMessage(), 'article_id' => $id]);
            SessionHelper::setFlash('error', 'No fue posible eliminar la noticia.');
        }

        Router::redirect('admin/news');
    }

    private function getArticlesForAdmin(array $filters, int $page, int $perPage): array {
        $conditions = array();
        $params = array();

        if (!empty($filters['status'])) {
            $conditions[] = 'na.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(na.title LIKE ? OR na.summary LIKE ? OR na.content LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1=1';

        $countSql = "SELECT COUNT(*) FROM news_articles na WHERE {$where}";
        $countRow = $this->db()->fetch($countSql, $params);
        $total = isset($countRow['COUNT(*)']) ? (int)$countRow['COUNT(*)'] : 0;

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT na.*, nc.name as category_name
                FROM news_articles na
                LEFT JOIN news_categories nc ON nc.id = na.category_id
                WHERE {$where}
                ORDER BY na.created_at DESC
                LIMIT ? OFFSET ?";

        $articles = $this->db()->fetchAll($sql, array_merge($params, array($perPage, $offset)));

        return array(
            'data' => $articles,
            'pagination' => array(
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            )
        );
    }

    private function sanitizeInput(array $input): array {
        return [
            'title' => trim($input['title'] ?? ''),
            'summary' => trim($input['summary'] ?? ''),
            'content' => $input['content_html'] ?? ($input['content'] ?? ''),
            'status' => $input['status'] ?? 'draft',
            'category_id' => $input['category_id'] ?? null,
            'category_name' => trim($input['category_name'] ?? ''),
            'published_at' => $input['published_at'] ?? null,
            'meta_title' => trim($input['meta_title'] ?? ''),
            'meta_description' => trim($input['meta_description'] ?? '')
        ];
    }

    private function validate(array $data, bool $isUpdate = false): array {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'El título es obligatorio.';
        }

        if (strlen($data['title']) > 200) {
            $errors['title'] = 'El título no debe superar los 200 caracteres.';
        }

        if (empty(strip_tags($data['content']))) {
            $errors['content'] = 'El contenido es obligatorio.';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'], true)) {
            $errors['status'] = 'Selecciona un estado válido.';
        }

        if (!empty($data['published_at']) && !strtotime($data['published_at'])) {
            $errors['published_at'] = 'Ingresa una fecha válida.';
        }

        if (!empty($data['meta_description']) && strlen($data['meta_description']) > 255) {
            $errors['meta_description'] = 'La meta descripción no debe superar los 255 caracteres.';
        }

        return $errors;
    }

    private function handleUpload(?array $file, string $folder = 'news'): ?string {
        if (!$file || empty($file['name'])) {
            return null;
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No fue posible subir la imagen.');
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new Exception('La imagen supera el tamaño permitido.');
        }

        $allowed = array_map('trim', explode(',', UPLOAD_ALLOWED_TYPES));
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            throw new Exception('Formato de archivo no permitido.');
        }

        $uploadDir = ROOT_PATH . '/public/storage/' . $folder;
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception('No fue posible crear el directorio de subida.');
            }
        }

        $filename = uniqid('news_', true) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Error al mover la imagen subida.');
        }

        return 'public/storage/' . $folder . '/' . $filename;
    }

    private function handleGalleryUploads(?array $files, array $captions, array $sortOrders): array {
        if (!$files || empty($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $items = [];
        foreach ($files['name'] as $index => $name) {
            $file = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0
            ];

            if ($file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
                continue;
            }

            $path = $this->handleUpload($file, 'news/gallery');
            $items[] = [
                'image_path' => $path,
                'caption' => trim($captions[$index] ?? ''),
                'sort_order' => isset($sortOrders[$index]) ? (int)$sortOrders[$index] : $index
            ];
        }

        return $items;
    }

    private function db(): Database {
        return Database::getInstance();
    }
}
