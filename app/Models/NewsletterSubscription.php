<?php

class NewsletterSubscription
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function subscribe(string $email, ?string $name = null): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Ingresa un correo válido.');
        }

        $name = $name !== null ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }

        $existing = $this->findByEmail($email);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $token = $existing['unsubscribe_token'];
            $status = $existing['status'];

            if ($status !== 'active' || !empty($existing['unsubscribed_at'])) {
                $token = $this->generateToken();
            }

            $this->db->update('newsletter_subscriptions', [
                'name' => $name ?? $existing['name'],
                'status' => 'active',
                'unsubscribe_token' => $token,
                'unsubscribed_at' => null,
                'updated_at' => $now
            ], 'id = ?', [$existing['id']]);

            return $this->findById((int)$existing['id']);
        }

        $token = $this->generateToken();
        $subscriptionId = $this->db->insert('newsletter_subscriptions', [
            'email' => $email,
            'name' => $name,
            'status' => 'active',
            'unsubscribe_token' => $token,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        return $this->findById((int)$subscriptionId);
    }

    public function unsubscribeByToken(string $token): ?array
    {
        $subscription = $this->findByToken($token);
        if (!$subscription) {
            return null;
        }

        if ($subscription['status'] === 'unsubscribed' && !empty($subscription['unsubscribed_at'])) {
            return $subscription;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->update('newsletter_subscriptions', [
            'status' => 'unsubscribed',
            'unsubscribed_at' => $now,
            'updated_at' => $now
        ], 'id = ?', [$subscription['id']]);

        return $this->findById((int)$subscription['id']);
    }

    public function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $row = $this->db->fetch(
            'SELECT * FROM newsletter_subscriptions WHERE unsubscribe_token = ? LIMIT 1',
            [$token]
        );

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->db->fetch(
            'SELECT * FROM newsletter_subscriptions WHERE id = ? LIMIT 1',
            [$id]
        );

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $row = $this->db->fetch(
            'SELECT * FROM newsletter_subscriptions WHERE email = ? LIMIT 1',
            [$email]
        );

        return $row ?: null;
    }

    public function getActiveSubscribers(int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        return $this->db->fetchAll(
            "SELECT * FROM newsletter_subscriptions
             WHERE status = 'active'
             ORDER BY created_at ASC
             LIMIT {$limit} OFFSET {$offset}",
            []
        ) ?: [];
    }

    public function countActive(): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM newsletter_subscriptions WHERE status = 'active'",
            []
        );

        return (int)($row['total'] ?? 0);
    }

    public function countTotal(): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS total FROM newsletter_subscriptions',
            []
        );

        return (int)($row['total'] ?? 0);
    }

    public function touchSent(int $subscriptionId): void
    {
        $this->db->update('newsletter_subscriptions', [
            'last_sent_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$subscriptionId]);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }
}
