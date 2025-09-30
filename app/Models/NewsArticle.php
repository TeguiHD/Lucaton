<?php

class NewsArticle {
    private Database $db;
    private NewsCategory $categories;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->categories = new NewsCategory();
    }

    public function getPublished(array $filters = array(), int $page = 1, int $perPage = 9): array {
        $conditions = array("na.status = 'published'");
        $params = array();

        if (!empty($filters['category_slug'])) {
            $conditions[] = 'nc.slug = ?';
            $params[] = $filters['category_slug'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = '(na.title LIKE ? OR na.summary LIKE ? OR na.content LIKE ?)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(na.published_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(na.published_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $where = implode(' AND ', $conditions);

        try {
            $countSql = "SELECT COUNT(*) FROM news_articles na
                      LEFT JOIN news_categories nc ON nc.id = na.category_id
                      WHERE {$where}";
            $countRow = $this->db->fetch($countSql, $params);
            $total = isset($countRow['COUNT(*)']) ? (int)$countRow['COUNT(*)'] : 0;

            $totalPages = max(1, (int)ceil(max($total, 0) / $perPage));
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT na.*, nc.name as category_name, nc.slug as category_slug,
                           u.username, u.first_name, u.last_name
                    FROM news_articles na
                    LEFT JOIN news_categories nc ON nc.id = na.category_id
                    LEFT JOIN users u ON na.author_id = u.id
                    WHERE {$where}
                    ORDER BY COALESCE(na.published_at, na.created_at) DESC
                    LIMIT ? OFFSET ?";

            $articles = $this->db->fetchAll($sql, array_merge($params, array($perPage, $offset)));
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch published news', ['error' => $e->getMessage()]);
            }
            $total = 0;
            $totalPages = 1;
            $page = 1;
            $articles = array();
        }

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

    public function findBySlug(string $slug, bool $includeDraft = false): ?array {
        $conditions = ['na.slug = ?'];
        $params = [$slug];

        if (!$includeDraft) {
            $conditions[] = "na.status = 'published'";
        }

        $where = implode(' AND ', $conditions);

        try {
            $article = $this->db->fetch(
                "SELECT na.*, nc.name as category_name, nc.slug as category_slug,
                        u.username, u.first_name, u.last_name
                 FROM news_articles na
                 LEFT JOIN news_categories nc ON nc.id = na.category_id
                 LEFT JOIN users u ON na.author_id = u.id
                 WHERE {$where} LIMIT 1",
                $params
            );
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch news article by slug', ['slug' => $slug, 'error' => $e->getMessage()]);
            }
            return null;
        }

        if (!$article) {
            return null;
        }

        $article['gallery'] = $this->getGallery((int)$article['id']);
        return $article;
    }

    public function findById(int $id): ?array {
        try {
            $article = $this->db->fetch(
                "SELECT na.*, nc.name as category_name, nc.slug as category_slug,
                        u.username, u.first_name, u.last_name
                 FROM news_articles na
                 LEFT JOIN news_categories nc ON nc.id = na.category_id
                 LEFT JOIN users u ON na.author_id = u.id
                 WHERE na.id = ? LIMIT 1",
                [$id]
            );
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch news article by id', ['id' => $id, 'error' => $e->getMessage()]);
            }
            return null;
        }

        if (!$article) {
            return null;
        }

        $article['gallery'] = $this->getGallery((int)$article['id']);
        return $article;
    }

    public function getRecent(int $limit = 3, ?int $excludeId = null): array {
        $params = [];
        $sql = "SELECT na.*, nc.name as category_name, nc.slug as category_slug
                FROM news_articles na
                LEFT JOIN news_categories nc ON nc.id = na.category_id
                WHERE na.status = 'published'";
        if ($excludeId) {
            $sql .= ' AND na.id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY COALESCE(na.published_at, na.created_at) DESC LIMIT ?';
        $params[] = $limit;

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch recent news', ['error' => $e->getMessage()]);
            }
            return array();
        }
    }

    public function getRelated(int $articleId, ?int $categoryId, int $limit = 3): array {
        $params = [$articleId];
        $sql = "SELECT na.*, nc.name as category_name, nc.slug as category_slug
                FROM news_articles na
                LEFT JOIN news_categories nc ON nc.id = na.category_id
                WHERE na.status = 'published' AND na.id <> ?";
        if ($categoryId) {
            $sql .= ' AND na.category_id = ?';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY COALESCE(na.published_at, na.created_at) DESC LIMIT ?';
        $params[] = $limit;

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch related news', ['base_id' => $articleId, 'error' => $e->getMessage()]);
            }
            return array();
        }
    }

    public function create(array $data): int {
        $this->db->beginTransaction();
        try {
            $slug = $this->generateUniqueSlug($data['title']);
            $categoryId = $this->resolveCategoryId($data);
            $status = $data['status'] ?? 'draft';
            $publishedAt = $status === 'published'
                ? ($data['published_at'] ?? date('Y-m-d H:i:s'))
                : null;

            $articleId = $this->db->insert('news_articles', [
                'author_id' => $data['author_id'],
                'category_id' => $categoryId,
                'title' => $data['title'],
                'slug' => $slug,
                'summary' => $data['summary'] ?? null,
                'content' => $this->sanitizeContent($data['content'] ?? ''),
                'cover_image' => $data['cover_image'] ?? null,
                'status' => $status,
                'published_at' => $publishedAt,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null
            ]);

            $this->syncGallery((int)$articleId, $data['gallery'] ?? []);

            $this->db->commit();
            return (int)$articleId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        $article = $this->findById($id);
        if (!$article) {
            throw new Exception('Artículo no encontrado');
        }

        $this->db->beginTransaction();
        try {
            $updateData = [];

            if (!empty($data['title']) && $data['title'] !== $article['title']) {
                $updateData['title'] = $data['title'];
                $updateData['slug'] = $this->generateUniqueSlug($data['title'], $id);
            }

            if (isset($data['summary'])) {
                $updateData['summary'] = $data['summary'];
            }

            if (isset($data['content'])) {
                $updateData['content'] = $this->sanitizeContent($data['content']);
            }

            if (array_key_exists('cover_image', $data)) {
                $updateData['cover_image'] = $data['cover_image'];
            }

            if (!empty($data['category_id']) || !empty($data['category_name'])) {
                $updateData['category_id'] = $this->resolveCategoryId($data);
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
                if ($data['status'] === 'published' && empty($article['published_at'])) {
                    $updateData['published_at'] = $data['published_at'] ?? date('Y-m-d H:i:s');
                }
            }

            if (isset($data['meta_title'])) {
                $updateData['meta_title'] = $data['meta_title'];
            }

            if (isset($data['meta_description'])) {
                $updateData['meta_description'] = $data['meta_description'];
            }

            if (!empty($updateData)) {
                $this->db->update('news_articles', $updateData, 'id = ?', [$id]);
            }

            if (isset($data['existing_gallery'])) {
                $this->updateExistingGallery($id, $data['existing_gallery']);
            }

            if (!empty($data['remove_gallery_ids'])) {
                $this->deleteGalleryImages($id, $data['remove_gallery_ids']);
            }

            if (!empty($data['gallery'])) {
                $this->syncGallery($id, $data['gallery']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function delete(int $id): bool {
        return $this->db->delete('news_articles', 'id = ?', [$id]) > 0;
    }

    public function getGallery(int $articleId): array {
        try {
            return $this->db->fetchAll(
                "SELECT id, image_path, caption, sort_order
                 FROM news_images
                 WHERE article_id = ?
                 ORDER BY sort_order ASC, id ASC",
                [$articleId]
            );
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to fetch news gallery', ['article_id' => $articleId, 'error' => $e->getMessage()]);
            }
            return array();
        }
    }

    private function syncGallery(int $articleId, array $gallery): void {
        if (empty($gallery)) {
            return;
        }

        foreach ($gallery as $image) {
            if (empty($image['image_path'])) {
                continue;
            }

            $this->db->insert('news_images', [
                'article_id' => $articleId,
                'image_path' => $image['image_path'],
                'caption' => $image['caption'] ?? null,
                'sort_order' => $image['sort_order'] ?? 0
            ]);
        }
    }

    private function updateExistingGallery(int $articleId, array $existing): void {
        foreach ($existing as $imageId => $payload) {
            if (!empty($payload['remove'])) {
                $this->deleteGalleryImages($articleId, [$imageId]);
                continue;
            }

            $this->db->update('news_images', [
                'caption' => $payload['caption'] ?? null,
                'sort_order' => isset($payload['sort_order']) ? (int)$payload['sort_order'] : 0
            ], 'id = ? AND article_id = ?', [$imageId, $articleId]);
        }
    }

    private function deleteGalleryImages(int $articleId, array $ids): void {
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$articleId], $ids);
        $this->db->query(
            "DELETE FROM news_images WHERE article_id = ? AND id IN ($placeholders)",
            $params
        );
    }

    private function resolveCategoryId(array $data): ?int {
        if (!empty($data['category_id'])) {
            return (int)$data['category_id'];
        }

        if (!empty($data['category_name'])) {
            $category = $this->categories->firstOrCreateByName($data['category_name']);
            return (int)$category['id'];
        }

        return null;
    }

    public function generateUniqueSlug(string $title, ?int $excludeId = null): string {
        $baseSlug = self::slugify($title);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $params = [$slug];
            $sql = 'SELECT id FROM news_articles WHERE slug = ?';

            if ($excludeId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $excludeId;
            }

            $existing = $this->db->fetch($sql, $params);
            if (!$existing) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    public static function slugify(string $text): string {
        $text = strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'noticia';
    }

    private function sanitizeContent(string $html): string {
        if ($html === '') {
            return '';
        }

        $allowedTags = '<p><a><strong><em><u><ul><ol><li><blockquote><h2><h3><h4><figure><figcaption><img><br>'; 
        $clean = strip_tags($html, $allowedTags);
        $clean = preg_replace('/\son[a-z]+="[^"]*"/i', '', $clean);
        $clean = preg_replace('/style="[^"]*"/i', '', $clean);
        $clean = preg_replace('/data-[^=]+="[^"]*"/i', '', $clean);

        // Limitar atributos permitidos en enlaces e imágenes
        $clean = preg_replace_callback('/<a\s+([^>]+)>/i', function($matches) {
            $attrs = self::filterAttributes($matches[1], ['href', 'title', 'target', 'rel']);
            return '<a ' . $attrs . '>';
        }, $clean);

        $clean = preg_replace_callback('/<img\s+([^>]+)>/i', function($matches) {
            $attrs = self::filterAttributes($matches[1], ['src', 'alt']);
            return '<img ' . $attrs . ' />';
        }, $clean);

        return $clean;
    }

    private static function filterAttributes(string $attributeString, array $allowed): string {
        $result = [];
        preg_match_all('/([a-zA-Z0-9:_-]+)\s*=\s*"([^"]*)"/', $attributeString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = $match[2];

            if (!in_array($name, $allowed, true)) {
                continue;
            }

            if ($name === 'href' || $name === 'src') {
                if (!preg_match('/^(https?:|mailto:|tel:|\/)/i', $value)) {
                    continue;
                }
            }

            if ($name === 'target') {
                $value = '_blank';
            }

            if ($name === 'rel') {
                $value = 'noopener noreferrer';
            }

            $result[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return implode(' ', $result);
    }

    private function deleteGalleryImagesByArticle(int $articleId): void {
        $this->db->delete('news_images', 'article_id = ?', [$articleId]);
    }
}
