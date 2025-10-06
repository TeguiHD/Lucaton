<?php

class CampaignUpdate
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $campaignId, int $authorId, array $data): int
    {
        $title = trim((string)($data['title'] ?? ''));
        $body = $this->sanitizeBody($data['body'] ?? '');
        if ($body === '') {
            throw new InvalidArgumentException('El contenido de la actualización es obligatorio.');
        }

        $status = $this->sanitizeStatus($data['status'] ?? 'published');
        $visibility = $this->sanitizeVisibility($data['visibility'] ?? 'public');
        $now = date('Y-m-d H:i:s');

        $payload = [
            'campaign_id' => $campaignId,
            'author_id' => $authorId,
            'title' => $title !== '' ? $title : null,
            'body' => $body,
            'media' => $this->encodeMedia($data['media'] ?? null),
            'status' => $status,
            'visibility' => $visibility,
            'published_at' => $status === 'published' ? ($data['published_at'] ?? $now) : null,
            'created_at' => $now,
            'updated_at' => $now
        ];

        return (int)$this->db->insert('campaign_updates', $payload);
    }

    public function listPublicByCampaign(int $campaignId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        $sql = "SELECT cu.*, u.first_name, u.last_name, u.username, u.avatar_url
                FROM campaign_updates cu
                LEFT JOIN users u ON u.id = cu.author_id
                WHERE cu.campaign_id = ?
                  AND cu.status = 'published'
                  AND cu.visibility = 'public'
                ORDER BY COALESCE(cu.published_at, cu.created_at) DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$campaignId, $limit]) ?: [];

        return array_map([$this, 'mapRow'], $rows);
    }

    public function listForOwner(int $campaignId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));

        $sql = "SELECT cu.*, u.first_name, u.last_name, u.username, u.avatar_url
                FROM campaign_updates cu
                LEFT JOIN users u ON u.id = cu.author_id
                WHERE cu.campaign_id = ?
                ORDER BY COALESCE(cu.published_at, cu.created_at) DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$campaignId, $limit]) ?: [];

        return array_map([$this, 'mapRow'], $rows);
    }

    private function sanitizeBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        // Normalizar saltos de línea y remover etiquetas peligrosas.
        $body = preg_replace("/\r\n|\r|\n/", "\n", $body);
        $body = strip_tags($body);
        $body = preg_replace('/\s{3,}/', '  ', $body);

        return mb_substr($body, 0, 5000);
    }

    private function sanitizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['draft', 'scheduled', 'published', 'archived'];
        return in_array($status, $allowed, true) ? $status : 'published';
    }

    private function sanitizeVisibility(string $visibility): string
    {
        $visibility = strtolower(trim($visibility));
        $allowed = ['public', 'supporters', 'private'];
        return in_array($visibility, $allowed, true) ? $visibility : 'public';
    }

    private function encodeMedia($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $normalized = array_values(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    return ['url' => trim($item)];
                }
                if (!is_array($item)) {
                    return null;
                }

                $url = trim((string)($item['url'] ?? ''));
                if ($url === '') {
                    return null;
                }

                $caption = trim((string)($item['caption'] ?? ''));
                return [
                    'url' => $url,
                    'caption' => $caption !== '' ? $caption : null
                ];
            }, $value)));

            if (empty($normalized)) {
                return null;
            }

            return json_encode($normalized, JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    private function mapRow(array $row): array
    {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['campaign_id'] = (int)($row['campaign_id'] ?? 0);
        $row['author_id'] = (int)($row['author_id'] ?? 0);
        $row['heart_count'] = (int)($row['heart_count'] ?? 0);

        if (!empty($row['media'])) {
            $decoded = json_decode($row['media'], true);
            $row['media'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['media'] = [];
        }

        $row['author_name'] = $this->buildAuthorName($row);
        $row['author_avatar'] = SessionHelper::normalizeAvatarUrl($row['avatar_url'] ?? null);

        return $row;
    }

    private function buildAuthorName(array $row): string
    {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        if (!empty($row['username'])) {
            return (string)$row['username'];
        }

        return 'Equipo de campaña';
    }
}
