<?php

class CampaignCategory {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function all(): array {
        try {
            return $this->db->fetchAll(
                "SELECT id, name, slug, description, color_hex, icon FROM campaign_categories ORDER BY name ASC"
            );
        } catch (\Throwable $e) {
            if (class_exists('Logger')) {
                Logger::error('Failed to fetch campaign categories', ['exception' => $e->getMessage()]);
            }
            return [];
        }
    }

    public function mapBySlug(): array {
        $rows = $this->all();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = $row;
        }
        return $map;
    }

    public function findBySlug(string $slug): ?array {
        return $this->db->fetch(
            "SELECT id, name, slug FROM campaign_categories WHERE slug = ?",
            [$slug]
        ) ?: null;
    }
}
