<?php

class CampaignAppeal {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int {
        $payload = [
            'campaign_id' => (int)$data['campaign_id'],
            'user_id' => (int)$data['user_id'],
            'reason' => $data['reason'],
            'additional_evidence' => $data['additional_evidence'] ?? null,
            'status' => 'pending',
        ];

        return (int)$this->db->insert('campaign_appeals', $payload);
    }

    public function userHasPending(int $campaignId, int $userId): bool {
        $row = $this->db->fetch(
            "SELECT id FROM campaign_appeals
             WHERE campaign_id = ? AND user_id = ? AND status IN ('pending','under_review')
             ORDER BY created_at DESC LIMIT 1",
            [$campaignId, $userId]
        );

        return !empty($row);
    }

    public function getLatestForCampaigns(array $campaignIds, int $userId): array {
        if (empty($campaignIds)) {
            return [];
        }

        $campaignIds = array_values(array_unique(array_filter($campaignIds, static fn ($id) => $id > 0)));
        if (empty($campaignIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
        $params = array_merge($campaignIds, [$userId]);

        $rows = $this->db->fetchAll(
            "SELECT ca.*
             FROM campaign_appeals ca
             INNER JOIN (
                 SELECT MAX(id) AS latest_id
                 FROM campaign_appeals
                 WHERE campaign_id IN ($placeholders) AND user_id = ?
                 GROUP BY campaign_id
             ) latest ON latest.latest_id = ca.id",
            $params
        ) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['campaign_id']] = $row;
        }

        return $result;
    }
}

