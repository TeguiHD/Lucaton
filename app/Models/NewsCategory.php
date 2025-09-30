<?php

class NewsCategory {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function all(): array {
        try {
            return $this->db->fetchAll(
                "SELECT id, name, slug, description FROM news_categories ORDER BY name ASC"
            );
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to list news categories', ['error' => $e->getMessage()]);
            }
            return array();
        }
    }

    public function allWithCounts(string $status = 'published'): array {
        try {
            return $this->db->fetchAll(
                "SELECT c.id, c.name, c.slug, COUNT(a.id) as article_count
                 FROM news_categories c
                 LEFT JOIN news_articles a ON a.category_id = c.id AND a.status = ?
                 GROUP BY c.id
                 ORDER BY c.name ASC",
                [$status]
            );
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to list news categories with counts', ['error' => $e->getMessage()]);
            }
            return array();
        }
    }

    public function findBySlug(string $slug): ?array {
        try {
            return $this->db->fetch(
                "SELECT * FROM news_categories WHERE slug = ? LIMIT 1",
                [$slug]
            ) ?: null;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to find news category by slug', ['slug' => $slug, 'error' => $e->getMessage()]);
            }
            return null;
        }
    }

    public function findById(int $id): ?array {
        try {
            return $this->db->fetch(
                "SELECT * FROM news_categories WHERE id = ? LIMIT 1",
                [$id]
            ) ?: null;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning('Failed to find news category by id', ['id' => $id, 'error' => $e->getMessage()]);
            }
            return null;
        }
    }

    public function firstOrCreateByName(string $name): array {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('El nombre de la categoría no puede estar vacío');
        }

        $existing = $this->db->fetch(
            "SELECT * FROM news_categories WHERE name = ? LIMIT 1",
            [$name]
        );

        if ($existing) {
            return $existing;
        }

        $slug = self::slugify($name);
        $slug = $this->ensureUniqueSlug($slug);

        $id = $this->db->insert('news_categories', [
            'name' => $name,
            'slug' => $slug
        ]);

        return $this->findById((int)$id);
    }

    private function ensureUniqueSlug(string $baseSlug, ?int $excludeId = null): string {
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $params = [$slug];
            $sql = "SELECT id FROM news_categories WHERE slug = ?";

            if ($excludeId !== null) {
                $sql .= " AND id <> ?";
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
        return $text ?: 'categoria';
    }
}
