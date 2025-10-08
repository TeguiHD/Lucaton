<?php

class NewsletterCampaign
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        $subject = trim($data['subject'] ?? '');
        $templateKey = trim($data['template_key'] ?? 'general_update');
        $message = trim($data['message'] ?? '');
        $ctaLabel = isset($data['cta_label']) ? trim($data['cta_label']) : null;
        $ctaUrl = isset($data['cta_url']) ? trim($data['cta_url']) : null;
        $previewPath = $data['preview_path'] ?? null;
        $recipientCount = (int)($data['recipient_count'] ?? 0);
        $createdBy = $data['created_by'] ?? null;

        if ($subject === '' || $message === '') {
            throw new Exception('Define un asunto y un mensaje para la campaña.');
        }

        $insertData = [
            'subject' => $subject,
            'template_key' => $templateKey,
            'message' => $message,
            'cta_label' => $ctaLabel !== '' ? $ctaLabel : null,
            'cta_url' => $ctaUrl !== '' ? $ctaUrl : null,
            'preview_path' => $previewPath,
            'recipient_count' => $recipientCount,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return (int)$this->db->insert('newsletter_campaigns', $insertData);
    }

    public function attachRecipients(int $campaignId, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $this->db->insert('newsletter_campaign_recipients', [
                'campaign_id' => $campaignId,
                'subscription_id' => $row['subscription_id'] ?? null,
                'email' => $row['email'],
                'status' => $row['status'] ?? 'queued',
                'preview_path' => $row['preview_path'] ?? null,
                'sent_at' => $row['sent_at'] ?? null,
                'error_message' => $row['error_message'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->db->update('newsletter_campaigns', [
            'recipient_count' => $this->countRecipients($campaignId)
        ], 'id = ?', [$campaignId]);
    }

    public function markRecipientStatus(int $recipientId, string $status, ?string $error = null): void
    {
        $allowed = ['queued', 'sent', 'failed'];
        if (!in_array($status, $allowed, true)) {
            $status = 'queued';
        }

        $update = [
            'status' => $status,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            'error_message' => $error
        ];

        $this->db->update('newsletter_campaign_recipients', $update, 'id = ?', [$recipientId]);
    }

    public function recentCampaigns(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $rows = $this->db->fetchAll(
            "SELECT nc.*, u.first_name, u.last_name, u.email
             FROM newsletter_campaigns nc
             LEFT JOIN users u ON u.id = nc.created_by
             ORDER BY nc.created_at DESC
             LIMIT {$limit}",
            []
        ) ?: [];

        return array_map(function ($row) {
            $author = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($author === '') {
                $author = $row['email'] ?? null;
            }

            return [
                'id' => (int)$row['id'],
                'subject' => $row['subject'],
                'template_key' => $row['template_key'],
                'message' => $row['message'],
                'cta_label' => $row['cta_label'],
                'cta_url' => $row['cta_url'],
                'preview_path' => $row['preview_path'],
                'recipient_count' => (int)($row['recipient_count'] ?? 0),
                'created_by' => $author,
                'created_at' => $row['created_at']
            ];
        }, $rows);
    }

    public function updatePreviewPath(int $campaignId, ?string $path): void
    {
        $this->db->update('newsletter_campaigns', ['preview_path' => $path], 'id = ?', [$campaignId]);
    }

    public function countRecipients(int $campaignId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS total FROM newsletter_campaign_recipients WHERE campaign_id = ?',
            [$campaignId]
        );

        return (int)($row['total'] ?? 0);
    }

    public function getRecipients(int $campaignId, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        return $this->db->fetchAll(
            "SELECT * FROM newsletter_campaign_recipients
             WHERE campaign_id = ?
             ORDER BY id ASC
             LIMIT {$limit} OFFSET {$offset}",
            [$campaignId]
        ) ?: [];
    }
}
