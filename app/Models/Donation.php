<?php
/**
 * Modelo Donation - Gestión de donaciones
 * Rediseñado para operar con la nueva estructura modular de campañas
 */

class Donation {
    private Database $db;
    private static $schemaCapabilities = null;

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$schemaCapabilities === null) {
            self::$schemaCapabilities = [
                'supporter_id' => $this->db->columnExists('donations', 'supporter_id'),
                'status' => $this->db->columnExists('donations', 'status'),
                'created_at' => $this->db->columnExists('donations', 'created_at'),
            ];
        }
    }

    private function supportsColumn(string $column): bool {
        return self::$schemaCapabilities[$column] ?? false;
    }

    /**
     * Crear una nueva donación y dejarla en estado pending
     */
    public function create(array $data): int {
        $this->validateDonationData($data);

        $campaign = $this->db->fetch(
            "SELECT id, status, end_date FROM campaigns WHERE id = ?",
            [$data['campaign_id']]
        );

        if (!$campaign || !in_array($campaign['status'], ['published', 'completed', 'paused'], true)) {
            throw new Exception('La campaña no está disponible para recibir donaciones');
        }

        if (!empty($campaign['end_date']) && strtotime($campaign['end_date']) < time()) {
            throw new Exception('La campaña ya finalizó');
        }

        $donationData = [
            'campaign_id' => $data['campaign_id'],
            'supporter_id' => $data['user_id'] ?? null,
            'supporter_name' => $data['donor_name'] ?? null,
            'supporter_email' => $data['donor_email'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'CLP',
            'payment_method' => $data['payment_method'],
            'payment_provider' => $data['payment_provider'] ?? null,
            'payment_reference' => $data['transaction_id'] ?? null,
            'status' => 'pending',
            'is_anonymous' => !empty($data['is_anonymous']),
            'message' => $data['message'] ?? null,
            'metadata' => !empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
            'donor_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        try {
            $donationId = (int)$this->db->insert('donations', $donationData);

            Logger::info('Donation created', [
                'donation_id' => $donationId,
                'campaign_id' => $data['campaign_id'],
                'amount' => $data['amount']
            ]);

            return $donationId;
        } catch (Exception $e) {
            Logger::error('Error creating donation', [
                'error' => $e->getMessage(),
                'campaign_id' => $data['campaign_id']
            ]);
            throw new Exception('Error al registrar la donación');
        }
    }

    /**
     * Obtener donación por ID
     */
    public function findById(int $id): ?array {
        $hasSupporter = $this->supportsColumn('supporter_id');

        $select = 'SELECT d.*, c.title AS campaign_title';
        $joinUsers = '';
        if ($hasSupporter) {
            $select .= ', u.username, u.first_name, u.last_name';
            $joinUsers = ' LEFT JOIN users u ON d.supporter_id = u.id';
        }

        $sql = $select . "
             FROM donations d
             JOIN campaigns c ON d.campaign_id = c.id" . $joinUsers . "
             WHERE d.id = ?";

        return $this->db->fetch($sql, [$id]) ?: null;
    }

    /**
     * Donaciones realizadas por un usuario autenticado
     */
    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array {
        if (!$this->supportsColumn('supporter_id')) {
            return [];
        }

        $hasCreatedAt = $this->supportsColumn('created_at');

        return $this->db->fetchAll(
            "SELECT d.*, c.title AS campaign_title, c.cover_image_url
             FROM donations d
             JOIN campaigns c ON d.campaign_id = c.id
             WHERE d.supporter_id = ?
             ORDER BY d." . ($hasCreatedAt ? 'created_at' : 'id') . " DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Donaciones asociadas a una campaña
     */
    public function findByCampaignId(int $campaignId, int $limit = 10, int $offset = 0, bool $includeAnonymous = true): array {
        $hasSupporter = $this->supportsColumn('supporter_id');

        $sql = 'SELECT d.*';
        if ($hasSupporter) {
            $sql .= ', u.username, u.first_name, u.last_name';
        }
        $sql .= "
                FROM donations d";

        if ($hasSupporter) {
            $sql .= "
                LEFT JOIN users u ON d.supporter_id = u.id";
        }

        $sql .= "
                WHERE d.campaign_id = ?";

        $params = [$campaignId];

        if ($this->supportsColumn('status')) {
            $sql .= " AND d.status = 'completed'";
        }

        if (!$includeAnonymous) {
            $sql .= " AND d.is_anonymous = 0";
        }

        $sql .= " ORDER BY d." . ($this->supportsColumn('created_at') ? 'created_at' : 'id') . " DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Actualizar estado de donación
     */
    public function updateStatus(int $id, string $status, ?string $paymentReference = null, ?string $provider = null): bool {
        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses, true)) {
            throw new Exception('Estado de donación inválido');
        }

        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($paymentReference) {
            $updateData['payment_reference'] = $paymentReference;
        }

        if ($provider) {
            $updateData['payment_provider'] = $provider;
        }

        if ($status === 'completed') {
            $updateData['processed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('donations', $updateData, 'id = ?', [$id]);

        if ($status === 'completed') {
            $this->updateCampaignMetrics($id, false);
        } elseif ($status === 'refunded') {
            $this->updateCampaignMetrics($id, true);
        }

        Logger::audit('donation_status_updated', SessionHelper::getUserId() ?? null, [
            'donation_id' => $id,
            'new_status' => $status
        ]);

        return true;
    }

    /**
     * Simulación de pago (placeholder)
     */
    public function processPayment(int $donationId, array $paymentData): array {
        $donation = $this->findById($donationId);

        if (!$donation || $donation['status'] !== 'pending') {
            throw new Exception('Donación no disponible para procesamiento');
        }

        try {
            $this->updateStatus($donationId, 'processing');

            $paymentResult = $this->simulatePaymentProcessing($donation, $paymentData);

            if ($paymentResult['success']) {
                $this->updateStatus($donationId, 'completed', $paymentResult['transaction_id'], $paymentResult['provider'] ?? null);
                $this->sendDonationNotifications($donationId);

                return [
                    'success' => true,
                    'transaction_id' => $paymentResult['transaction_id'],
                    'message' => 'Donación procesada exitosamente'
                ];
            }

            $this->updateStatus($donationId, 'failed');

            return [
                'success' => false,
                'error' => $paymentResult['error'],
                'message' => 'No fue posible completar el pago'
            ];
        } catch (Exception $e) {
            $this->updateStatus($donationId, 'failed');
            Logger::error('Payment processing failed', [
                'donation_id' => $donationId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Simulación de reembolso
     */
    public function refund(int $donationId): array {
        $donation = $this->findById($donationId);
        if (!$donation || $donation['status'] !== 'completed') {
            throw new Exception('La donación no es reembolsable');
        }

        $result = $this->simulateRefundProcessing($donation);

        if ($result['success']) {
            $this->updateStatus($donationId, 'refunded');
            Logger::info('Donation refunded', [
                'donation_id' => $donationId,
                'campaign_id' => $donation['campaign_id']
            ]);
        }

        return $result;
    }

    /**
     * Validaciones básicas de payload
     */
    private function validateDonationData(array $data): void {
        if (empty($data['campaign_id'])) {
            throw new Exception('Campaña requerida');
        }

        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Monto de donación inválido');
        }

        if ($data['amount'] < 1000) {
            throw new Exception('El monto mínimo es $1.000 CLP');
        }

        if ($data['amount'] > 20000000) {
            throw new Exception('El monto máximo permitido es $20.000.000 CLP');
        }

        $validPaymentMethods = ['credit_card', 'debit_card', 'bank_transfer', 'paypal', 'webpay', 'manual'];
        if (empty($data['payment_method']) || !in_array($data['payment_method'], $validPaymentMethods, true)) {
            throw new Exception('Método de pago no soportado');
        }

        if (empty($data['user_id'])) {
            if (empty($data['donor_name'])) {
                throw new Exception('Nombre del donante requerido');
            }

            if (empty($data['donor_email']) || !filter_var($data['donor_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ingresa un email válido');
            }
        }
    }

    /**
     * Actualizar métricas agregadas de la campaña
     */
    private function updateCampaignMetrics(int $donationId, bool $isRefund = false): void {
        $donation = $this->db->fetch(
            "SELECT campaign_id, amount FROM donations WHERE id = ?",
            [$donationId]
        );

        if (!$donation) {
            return;
        }

        $amount = (float)$donation['amount'];
        $operator = $isRefund ? -1 : 1;

        $this->db->execute(
            "UPDATE campaign_metrics
             SET raised_amount = GREATEST(0, raised_amount + (? * ?)),
                 donor_count = GREATEST(0, donor_count + ?),
                 average_donation = CASE
                     WHEN (donor_count + ?) > 0 THEN
                        ROUND(GREATEST(0, (raised_amount + (? * ?))) / (donor_count + ?), 2)
                     ELSE 0
                 END,
                 last_donation_at = CASE WHEN ? > 0 THEN NOW() ELSE last_donation_at END,
                 updated_at = NOW()
             WHERE campaign_id = ?",
            [
                $operator, $amount,
                $operator,
                $operator,
                $operator, $amount,
                $operator,
                $operator > 0 ? 1 : 0,
                $donation['campaign_id']
            ]
        );
    }

    /**
     * Simula procesamiento de pago
     */
    private function simulatePaymentProcessing(array $donation, array $paymentData): array {
        $success = rand(1, 100) <= 92; // 92% de éxito

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(6))),
                'provider' => $paymentData['provider'] ?? 'sandbox'
            ];
        }

        return [
            'success' => false,
            'error' => 'Payment declined by processor'
        ];
    }

    /**
     * Simula procesamiento de reembolso
     */
    private function simulateRefundProcessing(array $donation): array {
        $success = rand(1, 100) <= 95;

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'REF-' . strtoupper(bin2hex(random_bytes(6)))
            ];
        }

        return [
            'success' => false,
            'error' => 'Refund was rejected by the payment processor'
        ];
    }

    private function sendDonationNotifications(int $donationId): void {
        $donation = $this->findById($donationId);
        if (!$donation) {
            return;
        }

        try {
            $service = new CampaignMilestoneNotifier();
            $service->handleDonationEvent($donation);
        } catch (Exception $e) {
            Logger::error('Failed to dispatch donation milestone notifications', [
                'donation_id' => $donationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getSupporterUserIdsForCampaign(int $campaignId): array {
        if (!$this->supportsColumn('supporter_id')) {
            return [];
        }

        $sql = "SELECT DISTINCT supporter_id FROM donations WHERE campaign_id = ? AND supporter_id IS NOT NULL";

        $params = [$campaignId];
        if ($this->supportsColumn('status')) {
            $sql .= " AND status = 'completed'";
        }

        $rows = $this->db->fetchAll($sql, $params) ?: [];

        $ids = [];
        foreach ($rows as $row) {
            $id = (int)($row['supporter_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
