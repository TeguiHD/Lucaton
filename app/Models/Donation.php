<?php
/**
 * Modelo Donation - Gestión de donaciones
 * Rediseñado para operar con la nueva estructura modular de campañas
 */

class Donation {
    private const STATUS_VALUES = ['pending', 'processing', 'completed', 'failed', 'refunded'];

    private Database $db;
    private static $schemaCapabilities = null;
    private static $campaignSchemaCapabilities = null;
    private static ?bool $hasCampaignMetricsTable = null;
    private static array $userIdentityCache = [];
    private string $supporterColumn = '';
    private ?string $legacySupporterColumn = null;
    private ?string $anonymousColumn = null;
    private ?string $campaignOwnerColumn = null;
    private bool $usersTableExists = false;
    private bool $usersHaveUsername = false;
    private bool $usersHaveSlug = false;

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$schemaCapabilities === null) {
            self::$schemaCapabilities = [
                'supporter_id' => $this->db->columnExists('donations', 'supporter_id'),
                'user_id' => $this->db->columnExists('donations', 'user_id'),
                'status' => $this->db->columnExists('donations', 'status'),
                'created_at' => $this->db->columnExists('donations', 'created_at'),
                'updated_at' => $this->db->columnExists('donations', 'updated_at'),
                'payment_method' => $this->db->columnExists('donations', 'payment_method'),
                'processed_at' => $this->db->columnExists('donations', 'processed_at'),
                'payment_provider' => $this->db->columnExists('donations', 'payment_provider'),
                'payment_reference' => $this->db->columnExists('donations', 'payment_reference'),
                'transaction_id' => $this->db->columnExists('donations', 'transaction_id'),
                'metadata' => $this->db->columnExists('donations', 'metadata'),
                'supporter_name' => $this->db->columnExists('donations', 'supporter_name'),
                'supporter_email' => $this->db->columnExists('donations', 'supporter_email'),
                'currency' => $this->db->columnExists('donations', 'currency'),
                'is_anonymous' => $this->db->columnExists('donations', 'is_anonymous'),
                'message' => $this->db->columnExists('donations', 'message'),
                'donor_ip' => $this->db->columnExists('donations', 'donor_ip'),
                'donor_name' => $this->db->columnExists('donations', 'donor_name'),
                'donor_email' => $this->db->columnExists('donations', 'donor_email'),
                'anonymous' => $this->db->columnExists('donations', 'anonymous'),
            ];
        }

        if (self::$campaignSchemaCapabilities === null) {
            self::$campaignSchemaCapabilities = [
                'raised_amount' => $this->db->columnExists('campaigns', 'raised_amount'),
                'current_amount' => $this->db->columnExists('campaigns', 'current_amount'),
                'donor_count' => $this->db->columnExists('campaigns', 'donor_count'),
                'supporters_count' => $this->db->columnExists('campaigns', 'supporters_count'),
                'donation_count' => $this->db->columnExists('campaigns', 'donation_count'),
                'status' => $this->db->columnExists('campaigns', 'status'),
                'goal_amount' => $this->db->columnExists('campaigns', 'goal_amount'),
                'progress' => $this->db->columnExists('campaigns', 'progress'),
                'percentage_raised' => $this->db->columnExists('campaigns', 'percentage_raised'),
                'updated_at' => $this->db->columnExists('campaigns', 'updated_at'),
                'owner_id' => $this->db->columnExists('campaigns', 'owner_id'),
                'user_id' => $this->db->columnExists('campaigns', 'user_id'),
                'cover_image_url' => $this->db->columnExists('campaigns', 'cover_image_url'),
                'featured_image_url' => $this->db->columnExists('campaigns', 'featured_image_url'),
            ];
        }

        if (self::$hasCampaignMetricsTable === null) {
            try {
                self::$hasCampaignMetricsTable = $this->db->tableExists('campaign_metrics');
            } catch (Throwable $exception) {
                Logger::warning('No se pudo comprobar la tabla campaign_metrics', [
                    'error' => $exception->getMessage()
                ]);
                self::$hasCampaignMetricsTable = false;
            }
        }

        if (self::$schemaCapabilities['supporter_id']) {
            $this->supporterColumn = 'supporter_id';
            if (self::$schemaCapabilities['user_id']) {
                $this->legacySupporterColumn = 'user_id';
            }
        } elseif (self::$schemaCapabilities['user_id']) {
            $this->supporterColumn = 'user_id';
            if (self::$schemaCapabilities['supporter_id']) {
                $this->legacySupporterColumn = 'supporter_id';
            }
        }

        if ($this->supportsColumn('is_anonymous')) {
            $this->anonymousColumn = 'is_anonymous';
        } elseif ($this->supportsColumn('anonymous')) {
            $this->anonymousColumn = 'anonymous';
        }

        if ($this->campaignSupports('owner_id')) {
            $this->campaignOwnerColumn = 'owner_id';
        } elseif ($this->campaignSupports('user_id')) {
            $this->campaignOwnerColumn = 'user_id';
        }

        try {
            $this->usersTableExists = $this->db->tableExists('users');
            if ($this->usersTableExists) {
                $this->usersHaveUsername = $this->db->columnExists('users', 'username');
                $this->usersHaveSlug = $this->db->columnExists('users', 'slug');
            }
        } catch (Throwable $exception) {
            $this->usersTableExists = false;
            $this->usersHaveUsername = false;
            $this->usersHaveSlug = false;
            Logger::warning('No se pudo determinar el esquema de usuarios para donaciones', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function supportsColumn(string $column): bool {
        return self::$schemaCapabilities[$column] ?? false;
    }

    private function campaignSupports(string $column): bool {
        return self::$campaignSchemaCapabilities[$column] ?? false;
    }

    private function hasCampaignMetrics(): bool {
        if (self::$hasCampaignMetricsTable === null) {
            try {
                self::$hasCampaignMetricsTable = $this->db->tableExists('campaign_metrics');
            } catch (Throwable $exception) {
                Logger::warning('No se pudo comprobar la tabla campaign_metrics', [
                    'error' => $exception->getMessage()
                ]);
                self::$hasCampaignMetricsTable = false;
            }
        }

        return self::$hasCampaignMetricsTable === true;
    }

    private function resolveDonorIdentity(array $payload): array
    {
        $name = trim((string)($payload['donor_name'] ?? ''));
        $email = trim((string)($payload['donor_email'] ?? ''));
        $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;

        if (($name === '' || $email === '') && $userId > 0) {
            try {
                $userModel = new User();
                $user = $userModel->findById($userId);
            } catch (Throwable $exception) {
                $user = null;
                Logger::warning('No se pudo obtener la identidad del usuario para la donación', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
            }

            if (is_array($user)) {
                if ($email === '' && !empty($user['email'])) {
                    $email = trim((string)$user['email']);
                }

                if ($name === '') {
                    $name = $this->resolveUserDisplayName($user);
                }
            }
        }

        if ($name === '' && $email !== '') {
            $name = trim((string)strtok($email, '@'));
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }

    private function resolveUserDisplayName(array $user): string
    {
        $firstName = trim((string)($user['first_name'] ?? ''));
        $lastName = trim((string)($user['last_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return trim($firstName . ' ' . $lastName);
        }

        $candidates = [
            $user['name'] ?? null,
            $user['nombre'] ?? null,
            $user['display_name'] ?? null,
            $user['username'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        if (!empty($user['email'])) {
            return trim((string)strtok($user['email'], '@'));
        }

        return 'Colaborador';
    }

    /**
     * Crear una nueva donación y dejarla en estado pending
     */
    public function create(array $data): int {
        $identity = $this->resolveDonorIdentity($data);
        $data['donor_name'] = $identity['name'];
        $data['donor_email'] = $identity['email'];

        $this->validateDonationData($data);

        $campaign = $this->db->fetch(
            "SELECT id, status, end_date FROM campaigns WHERE id = ?",
            [$data['campaign_id']]
        );

        if (!$campaign || !in_array($campaign['status'], ['published', 'paused'], true)) {
            throw new Exception('La campaña no está disponible para recibir donaciones');
        }

        if (!empty($campaign['end_date']) && strtotime($campaign['end_date']) < time()) {
            throw new Exception('La campaña ya finalizó');
        }

        $donationData = [
            'campaign_id' => $data['campaign_id'],
            'amount' => $data['amount'],
        ];

        if ($this->supportsColumn('payment_method')) {
            $donationData['payment_method'] = $data['payment_method'];
        }

        if ($this->supporterColumn !== '') {
            $donationData[$this->supporterColumn] = $data['user_id'] ?? null;
        }
        if ($this->legacySupporterColumn !== null) {
            $donationData[$this->legacySupporterColumn] = $data['user_id'] ?? null;
        }

        $donorName = $identity['name'] !== '' ? $identity['name'] : null;
        if ($this->supportsColumn('supporter_name')) {
            $donationData['supporter_name'] = $donorName;
        }
        if ($this->supportsColumn('donor_name')) {
            $donationData['donor_name'] = $donorName;
        }

        $donorEmail = $identity['email'] !== '' ? strtolower($identity['email']) : null;
        if ($this->supportsColumn('supporter_email')) {
            $donationData['supporter_email'] = $donorEmail;
        }
        if ($this->supportsColumn('donor_email')) {
            $donationData['donor_email'] = $donorEmail;
        }

        if ($this->supportsColumn('currency')) {
            $donationData['currency'] = $data['currency'] ?? 'CLP';
        }

        if ($this->supportsColumn('payment_provider')) {
            $donationData['payment_provider'] = $data['payment_provider'] ?? null;
        }

        if ($this->supportsColumn('payment_reference')) {
            $donationData['payment_reference'] = $data['transaction_id'] ?? null;
        }

        if ($this->supportsColumn('transaction_id')) {
            $donationData['transaction_id'] = $data['transaction_id'] ?? null;
        }

        if ($this->supportsColumn('status')) {
            $donationData['status'] = 'pending';
        }

        $anonymousColumn = $this->getAnonymousColumn();
        if ($anonymousColumn !== null) {
            $donationData[$anonymousColumn] = !empty($data['is_anonymous']) ? 1 : 0;
        }

        if ($this->supportsColumn('message')) {
            $donationData['message'] = $data['message'] ?? null;
        }

        if ($this->supportsColumn('metadata')) {
            $donationData['metadata'] = !empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null;
        }

        if ($this->supportsColumn('donor_ip')) {
            $donationData['donor_ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
        }

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
        $supporterJoinColumn = $this->supporterColumn;
        $hasSupporter = $supporterJoinColumn !== '';

        $select = 'SELECT d.*, c.title AS campaign_title';
        $joinUsers = '';
        if ($hasSupporter) {
            $select .= ', u.username, u.first_name, u.last_name';
            $joinUsers = ' LEFT JOIN users u ON d.' . $supporterJoinColumn . ' = u.id';
        }

        $sql = $select . "
             FROM donations d
             JOIN campaigns c ON d.campaign_id = c.id" . $joinUsers . "
             WHERE d.id = ?";

        $row = $this->db->fetch($sql, [$id]) ?: null;
        if ($row === null) {
            return null;
        }

        return $this->normalizeDonationRow($row);
    }

    /**
     * Donaciones realizadas por un usuario autenticado
     */
    public function findByUserId(int $userId, int $limit = 10, int $offset = 0, array $filters = [], ?string $email = null): array {
        $emailHint = $email ?? ($filters['email'] ?? null);
        $userMatch = $this->buildUserMatchCondition($userId, 'd', is_string($emailHint) ? $emailHint : null);
        if ($userMatch === null) {
            return [];
        }

        $hasCreatedAt = $this->supportsColumn('created_at');
        $hasStatus = $this->supportsColumn('status');

        $conditions = [$userMatch['clause']];
        $params = $userMatch['params'];

        $statusFilter = strtolower((string)($filters['status'] ?? ''));
        if ($hasStatus && in_array($statusFilter, self::STATUS_VALUES, true)) {
            $conditions[] = 'd.status = ?';
            $params[] = $statusFilter;
        }

        $searchTerm = trim((string)($filters['search'] ?? ''));
        if ($searchTerm !== '') {
            $conditions[] = '(c.title LIKE ? OR d.message LIKE ?)';
            $like = '%' . $searchTerm . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orderHint = strtolower((string)($filters['order'] ?? 'recent'));
        $orderColumn = $hasCreatedAt ? 'd.created_at' : 'd.id';
        if ($orderHint === 'amount_desc') {
            $orderBy = 'd.amount DESC, ' . $orderColumn . ' DESC';
        } elseif ($orderHint === 'amount_asc') {
            $orderBy = 'd.amount ASC, ' . $orderColumn . ' DESC';
        } else {
            $orderBy = $orderColumn . ' DESC';
        }

        $whereClause = implode(' AND ', $conditions);

        $queryParams = $params;
        $queryParams[] = $limit;
        $queryParams[] = $offset;

        $campaignSelect = [
            'c.title AS campaign_title',
            'c.slug AS campaign_slug'
        ];

        if ($this->campaignOwnerColumn !== null) {
            $campaignSelect[] = 'c.' . $this->campaignOwnerColumn . ' AS campaign_owner_id';
        }
        if ($this->campaignSupports('cover_image_url')) {
            $campaignSelect[] = 'c.cover_image_url';
        } elseif ($this->campaignSupports('featured_image_url')) {
            $campaignSelect[] = 'c.featured_image_url AS cover_image_url';
        } else {
            $campaignSelect[] = 'NULL AS cover_image_url';
        }

        $ownerJoin = '';
        if ($this->campaignOwnerColumn !== null && $this->usersTableExists && $this->usersHaveUsername) {
            $campaignSelect[] = 'owners.username AS campaign_owner_username';
            if ($this->usersHaveSlug) {
                $campaignSelect[] = 'owners.slug AS campaign_owner_slug';
            }

            $ownerJoin = ' LEFT JOIN users owners ON owners.id = c.' . $this->campaignOwnerColumn;
        }

        $sql = sprintf(
            "SELECT d.*, %s
             FROM donations d
             JOIN campaigns c ON d.campaign_id = c.id
             %s
             WHERE %s
             ORDER BY %s
             LIMIT ? OFFSET ?",
            implode(', ', $campaignSelect),
            $ownerJoin,
            $whereClause,
            $orderBy
        );

        $rows = $this->db->fetchAll($sql, $queryParams);

        return $this->normalizeDonationRows($rows);
    }

    /**
     * Donaciones asociadas a una campaña con soporte para filtros de visibilidad
     *
     * @param int $campaignId
     * @param int $limit
     * @param int $offset
     * @param mixed $options
     * @return array
     */
    public function findByCampaignId(int $campaignId, int $limit = 10, int $offset = 0, $options = []): array {
        $supporterJoinColumn = $this->supporterColumn;
        $hasSupporter = $supporterJoinColumn !== '';

        $includeAnonymous = true;
        $visibility = 'all';

        if (is_bool($options)) {
            $includeAnonymous = $options;
            $visibility = $includeAnonymous ? 'all' : 'public';
        } elseif (is_array($options)) {
            if (array_key_exists('include_anonymous', $options)) {
                $includeAnonymous = (bool)$options['include_anonymous'];
            }
            if (isset($options['visibility'])) {
                $visibilityCandidate = strtolower((string)$options['visibility']);
                if (in_array($visibilityCandidate, ['all', 'public', 'anonymous'], true)) {
                    $visibility = $visibilityCandidate;
                }
            }
        }

        if (!$includeAnonymous && $visibility === 'all') {
            $visibility = 'public';
        }

        $anonymousColumn = $this->getAnonymousColumn();
        $supportsAnonymous = $anonymousColumn !== null;
        if (!$supportsAnonymous && $visibility === 'anonymous') {
            return [];
        }

        $sql = 'SELECT d.*';
        if ($hasSupporter) {
            $sql .= ', u.username, u.first_name, u.last_name';
        }
        $sql .= "
                FROM donations d";

        if ($hasSupporter) {
            $sql .= "
                LEFT JOIN users u ON d." . $supporterJoinColumn . " = u.id";
        }

        $sql .= "
                WHERE d.campaign_id = ?";

        $params = [$campaignId];

        if ($this->supportsColumn('status')) {
            $sql .= " AND d.status = 'completed'";
        }

        if ($supportsAnonymous) {
            if ($visibility === 'public') {
                $sql .= " AND d." . $anonymousColumn . " = 0";
            } elseif ($visibility === 'anonymous') {
                $sql .= " AND d." . $anonymousColumn . " = 1";
            }
        }

        $sql .= " ORDER BY d." . ($this->supportsColumn('created_at') ? 'created_at' : 'id') . " DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);

        return $this->normalizeDonationRows($rows);
    }

    public function countByCampaignId(int $campaignId, $options = []): int {
        $visibility = 'all';

        if (is_bool($options)) {
            $visibility = $options ? 'all' : 'public';
        } elseif (is_array($options) && isset($options['visibility'])) {
            $candidate = strtolower((string)$options['visibility']);
            if (in_array($candidate, ['all', 'public', 'anonymous'], true)) {
                $visibility = $candidate;
            }
        }

        $anonymousColumn = $this->getAnonymousColumn();
        $supportsAnonymous = $anonymousColumn !== null;

        $sql = 'SELECT COUNT(*) AS total FROM donations WHERE campaign_id = ?';
        $params = [$campaignId];

        if ($this->supportsColumn('status')) {
            $sql .= " AND status = 'completed'";
        }

        if (!$supportsAnonymous && $visibility === 'anonymous') {
            return 0;
        }

        if ($supportsAnonymous) {
            if ($visibility === 'public') {
                $sql .= " AND " . $anonymousColumn . " = 0";
            } elseif ($visibility === 'anonymous') {
                $sql .= " AND " . $anonymousColumn . " = 1";
            }
        }

        $row = $this->db->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Actualizar estado de donación
     */
    public function updateStatus(int $id, string $status, ?string $paymentReference = null, ?string $provider = null): bool {
        if (!in_array($status, self::STATUS_VALUES, true)) {
            throw new Exception('Estado de donación inválido');
        }

        $updateData = [];

        if ($this->supportsColumn('status')) {
            $updateData['status'] = $status;
        }

        if ($this->supportsColumn('updated_at')) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($paymentReference) {
            if ($this->supportsColumn('payment_reference')) {
                $updateData['payment_reference'] = $paymentReference;
            }
            if ($this->supportsColumn('transaction_id')) {
                $updateData['transaction_id'] = $paymentReference;
            }
        }

        if ($provider && $this->supportsColumn('payment_provider')) {
            $updateData['payment_provider'] = $provider;
        }

        if ($status === 'completed' && $this->supportsColumn('processed_at')) {
            $updateData['processed_at'] = date('Y-m-d H:i:s');
        }

        if (!empty($updateData)) {
            $this->db->update('donations', $updateData, 'id = ?', [$id]);
        }

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
                if (!empty($paymentResult['requires_review'])) {
                    $this->updateStatus(
                        $donationId,
                        'processing',
                        $paymentResult['transaction_id'] ?? null,
                        $paymentResult['provider'] ?? null
                    );

                    return [
                        'success' => true,
                        'transaction_id' => $paymentResult['transaction_id'],
                        'message' => 'Donación pendiente de revisión manual',
                        'requires_review' => true,
                        'summary' => $paymentResult['summary'] ?? null,
                    ];
                }

                $this->updateStatus($donationId, 'completed', $paymentResult['transaction_id'], $paymentResult['provider'] ?? null);
                $this->sendDonationNotifications($donationId);

                return [
                    'success' => true,
                    'transaction_id' => $paymentResult['transaction_id'],
                    'message' => 'Donación procesada exitosamente',
                    'requires_review' => false,
                    'summary' => $paymentResult['summary'] ?? null,
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
            throw new Exception('Debes iniciar sesión para realizar un aporte.');
        }

        if (empty($data['donor_email']) || !filter_var($data['donor_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('No se pudo validar el correo asociado al aporte.');
        }

        if (empty($data['donor_name'])) {
            throw new Exception('No se pudo identificar el nombre del aportante.');
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

        $campaignId = (int)$donation['campaign_id'];

        if (!$this->hasCampaignMetrics()) {
            $totals = $this->calculateCampaignDonationTotals($campaignId);
            $this->updateCampaignAggregateFields($campaignId, $totals);
            if (!$isRefund) {
                $this->finalizeCampaignIfEligible($campaignId, $totals['total_amount']);
            }
            return;
        }

        if (!$this->ensureCampaignMetricsRowExists($campaignId)) {
            $totals = $this->calculateCampaignDonationTotals($campaignId);
            $this->updateCampaignAggregateFields($campaignId, $totals);
            if (!$isRefund) {
                $this->finalizeCampaignIfEligible($campaignId, $totals['total_amount']);
            }
            return;
        }

        $amount = (float)$donation['amount'];
        $operator = $isRefund ? -1 : 1;

        try {
            $affectedRows = $this->db->execute(
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
                    $campaignId
                ]
            );

            if ($affectedRows === 0) {
                $this->recalculateCampaignMetrics($campaignId, $isRefund);
            }
        } catch (Throwable $exception) {
            Logger::warning('No se pudo actualizar campaign_metrics tras una donación', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
            $this->recalculateCampaignMetrics($campaignId, $isRefund);
        }

        $this->syncCampaignProgress($campaignId, $isRefund);
    }

    private function ensureCampaignMetricsRowExists(int $campaignId): bool
    {
        if ($campaignId <= 0) {
            return false;
        }

        try {
            $row = $this->db->fetch(
                "SELECT campaign_id FROM campaign_metrics WHERE campaign_id = ? LIMIT 1",
                [$campaignId]
            );

            if ($row) {
                return true;
            }

            $this->db->insert('campaign_metrics', ['campaign_id' => $campaignId]);
            return true;
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'duplicate entry') !== false) {
                return true;
            }

            Logger::warning('No se pudo asegurar la fila en campaign_metrics', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);

            return false;
        }
    }

    private function recalculateCampaignMetrics(int $campaignId, bool $isRefund): void
    {
        if ($campaignId <= 0 || !$this->hasCampaignMetrics()) {
            return;
        }

        $totals = $this->calculateCampaignDonationTotals($campaignId);
        $average = $totals['donation_count'] > 0
            ? round($totals['total_amount'] / $totals['donation_count'], 2)
            : 0.0;

        $updatePayload = [
            'raised_amount' => $totals['total_amount'],
            'donor_count' => max(0, $totals['donation_count']),
            'average_donation' => $average,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($totals['last_donation_at'])) {
            $updatePayload['last_donation_at'] = $totals['last_donation_at'];
        } elseif (!$isRefund) {
            $updatePayload['last_donation_at'] = date('Y-m-d H:i:s');
        }

        try {
            $this->db->update('campaign_metrics', $updatePayload, 'campaign_id = ?', [$campaignId]);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo recalcular campaign_metrics tras una donación', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function syncCampaignProgress(int $campaignId, bool $isRefund): void
    {
        if ($campaignId <= 0) {
            return;
        }

        if (!$this->hasCampaignMetrics()) {
            return;
        }

        $snapshot = $this->fetchCampaignSnapshot($campaignId);
        if (!$snapshot) {
            return;
        }

        $totals = $this->calculateCampaignDonationTotals($campaignId);
        $amountForProgress = (float)($totals['total_amount'] ?? 0.0);
        $donorTotal = (int)($totals['donation_count'] ?? 0);

        $metricsRaised = (float)($snapshot['metrics_raised'] ?? 0.0);
        $metricsDonors = (int)($snapshot['metrics_donors'] ?? 0);

        if ($this->hasCampaignMetrics()
            && (abs($metricsRaised - $amountForProgress) > 0.01 || $metricsDonors !== $donorTotal)
        ) {
            $this->recalculateCampaignMetrics($campaignId, $isRefund);
            $snapshot = $this->fetchCampaignSnapshot($campaignId) ?? $snapshot;
            $metricsRaised = (float)($snapshot['metrics_raised'] ?? $metricsRaised);
            $metricsDonors = (int)($snapshot['metrics_donors'] ?? $metricsDonors);
        }

        $raised = $amountForProgress;
        if ($raised <= 0 && $metricsRaised > 0) {
            $raised = $metricsRaised;
        }

        $donors = $donorTotal;
        if ($donors <= 0 && $metricsDonors > 0) {
            $donors = $metricsDonors;
        }

        $goal = (float)($snapshot['goal_amount'] ?? 0);
        if ($goal <= 0 && $this->campaignSupports('goal_amount')) {
            $goal = $this->getCampaignGoalAmount($campaignId);
        }

        $currentStatus = $snapshot['status'] ?? null;

        $updatePayload = [];
        if ($this->campaignSupports('raised_amount')) {
            $updatePayload['raised_amount'] = $raised;
        }
        if ($this->campaignSupports('current_amount')) {
            $updatePayload['current_amount'] = $raised;
        }
        if ($this->campaignSupports('donor_count')) {
            $updatePayload['donor_count'] = max(0, $donors);
        }
        if ($this->campaignSupports('supporters_count')) {
            $updatePayload['supporters_count'] = max(0, $donors);
        }
        if ($this->campaignSupports('donation_count')) {
            $updatePayload['donation_count'] = max(0, $donorTotal);
        }

        $progress = 0.0;
        if ($goal > 0) {
            $progress = min(100, round(($amountForProgress / $goal) * 100, 2));
        }

        if ($this->campaignSupports('progress')) {
            $updatePayload['progress'] = $progress;
        }
        if ($this->campaignSupports('percentage_raised')) {
            $updatePayload['percentage_raised'] = $progress;
        }

        if ($this->campaignSupports('updated_at') && !empty($updatePayload)) {
            $updatePayload['updated_at'] = date('Y-m-d H:i:s');
        }

        if (!empty($updatePayload)) {
            try {
                $this->db->update('campaigns', $updatePayload, 'id = ?', [$campaignId]);
            } catch (Throwable $exception) {
                Logger::warning('No se pudo actualizar los totales agregados de la campaña', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
            }
        }

        if ($isRefund) {
            return;
        }

        $this->finalizeCampaignIfEligible($campaignId, $raised, $currentStatus, $goal);
    }

    private function fetchCampaignSnapshot(int $campaignId): ?array
    {
        $selectParts = ['c.id AS campaign_id'];

        if ($this->campaignSupports('status')) {
            $selectParts[] = 'c.status AS status';
        } else {
            $selectParts[] = 'NULL AS status';
        }

        if ($this->campaignSupports('goal_amount')) {
            $selectParts[] = 'c.goal_amount AS goal_amount';
        } else {
            $selectParts[] = 'NULL AS goal_amount';
        }

        if ($this->campaignSupports('raised_amount')) {
            $selectParts[] = 'c.raised_amount AS legacy_raised';
        } elseif ($this->campaignSupports('current_amount')) {
            $selectParts[] = 'c.current_amount AS legacy_raised';
        } else {
            $selectParts[] = 'NULL AS legacy_raised';
        }

        if ($this->campaignSupports('donor_count')) {
            $selectParts[] = 'c.donor_count AS legacy_donors';
        } elseif ($this->campaignSupports('supporters_count')) {
            $selectParts[] = 'c.supporters_count AS legacy_donors';
        } elseif ($this->campaignSupports('donation_count')) {
            $selectParts[] = 'c.donation_count AS legacy_donors';
        } else {
            $selectParts[] = 'NULL AS legacy_donors';
        }

        if ($this->hasCampaignMetrics()) {
            $selectParts[] = 'cm.raised_amount AS metrics_raised';
            $selectParts[] = 'cm.donor_count AS metrics_donors';
        } else {
            $selectParts[] = 'NULL AS metrics_raised';
            $selectParts[] = 'NULL AS metrics_donors';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM campaigns c';

        if ($this->hasCampaignMetrics()) {
            $sql .= ' LEFT JOIN campaign_metrics cm ON cm.campaign_id = c.id';
        }

        $sql .= ' WHERE c.id = ? LIMIT 1';

        try {
            return $this->db->fetch($sql, [$campaignId]) ?: null;
        } catch (Throwable $exception) {
            Logger::warning('No se pudo obtener el estado de la campaña para sincronizar el progreso', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Simula procesamiento de pago
     */
    private function simulatePaymentProcessing(array $donation, array $paymentData): array {
        $provider = $paymentData['provider'] ?? 'sandbox';
        $method = $paymentData['method'] ?? ($donation['payment_method'] ?? 'manual');
        $details = $paymentData['details'] ?? [];
        $validated = (bool)($details['validated'] ?? true);
        $requiresReview = !empty($details['requires_review']);

        if (!$validated) {
            return [
                'success' => false,
                'error' => 'Información de pago incompleta para la simulación.'
            ];
        }

        $transactionId = 'SIM-' . strtoupper(substr($method, 0, 2)) . '-' . strtoupper(bin2hex(random_bytes(4)));
        $summary = $details['summary'] ?? null;

        if ($summary === null && isset($details['card']['brand'], $details['card']['last4'])) {
            $summary = sprintf(
                'Tarjeta %s terminada en %s',
                strtoupper($details['card']['brand']),
                $details['card']['last4']
            );
        } elseif ($summary === null && isset($details['transfer']['bank'], $details['transfer']['reference'])) {
            $summary = sprintf(
                'Transferencia %s · Ref %s',
                $details['transfer']['bank'],
                strtoupper($details['transfer']['reference'])
            );
        } elseif ($summary === null && isset($details['wallet']['provider'], $details['wallet']['email'])) {
            $summary = sprintf(
                'Pago %s (%s)',
                ucfirst($details['wallet']['provider']),
                $details['wallet']['email']
            );
        } elseif ($summary === null && isset($details['webpay']['bank'], $details['webpay']['rut'])) {
            $summary = sprintf(
                'Webpay %s · %s',
                $details['webpay']['bank'],
                $details['webpay']['rut']
            );
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'provider' => $provider,
            'summary' => $summary,
            'requires_review' => $requiresReview,
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
        $columns = $this->collectUserColumns();
        if (empty($columns)) {
            return [];
        }

        $selects = [];
        foreach ($columns as $column) {
            $condition = "campaign_id = ? AND {$column} IS NOT NULL";
            if ($this->supportsColumn('status')) {
                $condition .= " AND status = 'completed'";
            }
            $selects[] = "SELECT DISTINCT {$column} AS supporter_id FROM donations WHERE {$condition}";
        }

        $sql = implode(' UNION ', $selects);
        $params = array_fill(0, count($columns), $campaignId);

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

    public function countByUserId(int $userId, array $filters = [], ?string $email = null): int
    {
        $emailHint = $email ?? ($filters['email'] ?? null);
        $userMatch = $this->buildUserMatchCondition($userId, 'd', is_string($emailHint) ? $emailHint : null);
        if ($userMatch === null) {
            return 0;
        }

        $conditions = [$userMatch['clause']];
        $params = $userMatch['params'];

        $hasStatus = $this->supportsColumn('status');
        $statusFilter = strtolower((string)($filters['status'] ?? ''));
        if ($hasStatus && in_array($statusFilter, self::STATUS_VALUES, true)) {
            $conditions[] = 'd.status = ?';
            $params[] = $statusFilter;
        }

        $searchTerm = trim((string)($filters['search'] ?? ''));
        if ($searchTerm !== '') {
            $conditions[] = '(c.title LIKE ? OR d.message LIKE ?)';
            $like = '%' . $searchTerm . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereClause = implode(' AND ', $conditions);

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM donations d
             JOIN campaigns c ON d.campaign_id = c.id
             WHERE {$whereClause}",
            $params
        );

        return (int)($row['total'] ?? 0);
    }

    public function getStatusCountsForUser(int $userId, ?string $email = null): array
    {
        if (!$this->supportsColumn('status')) {
            return [];
        }

        $userMatch = $this->buildUserMatchCondition($userId, '', $email);
        if ($userMatch === null) {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM donations
             WHERE {$userMatch['clause']}
             GROUP BY status",
            $userMatch['params']
        ) ?: [];

        $data = [];
        $total = 0;
        foreach ($rows as $row) {
            $status = strtolower((string)($row['status'] ?? ''));
            $count = (int)($row['total'] ?? 0);
            if ($status !== '') {
                $data[$status] = $count;
                $total += $count;
            }
        }

        $data['all'] = $total;

        return $data;
    }

    public function getUserDonationSummary(int $userId, ?string $email = null): array
    {
        $defaults = [
            'total_donations' => 0,
            'total_amount' => 0.0,
            'completed_donations' => 0,
            'completed_amount' => 0.0,
            'average_completed' => 0.0,
            'last_donation_at' => null,
        ];

        $userMatch = $this->buildUserMatchCondition($userId, '', $email);
        if ($userMatch === null) {
            return $defaults;
        }

        $selectParts = [
            'COUNT(*) AS total_donations',
            'COALESCE(SUM(amount), 0) AS total_amount',
        ];

        if ($this->supportsColumn('status')) {
            $selectParts[] = "COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_donations";
            $selectParts[] = "COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS completed_amount";
        } else {
            $selectParts[] = 'COUNT(*) AS completed_donations';
            $selectParts[] = 'COALESCE(SUM(amount), 0) AS completed_amount';
        }

        if ($this->supportsColumn('created_at')) {
            $selectParts[] = 'MAX(created_at) AS last_donation_at';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM donations WHERE ' . $userMatch['clause'];
        $row = $this->db->fetch($sql, $userMatch['params']) ?: [];

        $summary = [
            'total_donations' => (int)($row['total_donations'] ?? 0),
            'total_amount' => (float)($row['total_amount'] ?? 0),
            'completed_donations' => (int)($row['completed_donations'] ?? 0),
            'completed_amount' => (float)($row['completed_amount'] ?? ($row['total_amount'] ?? 0)),
            'average_completed' => 0.0,
            'last_donation_at' => $row['last_donation_at'] ?? null,
        ];

        if ($summary['completed_donations'] > 0) {
            $summary['average_completed'] = round($summary['completed_amount'] / $summary['completed_donations'], 2);
        }

        return $summary;
    }

    private function calculateCampaignDonationTotals(int $campaignId): array
    {
        $statusCondition = '';
        if ($this->supportsColumn('status')) {
            $statusCondition = " AND status = 'completed'";
        }

        $selectParts = [
            'COALESCE(SUM(amount), 0) AS total_amount',
            'COUNT(*) AS donation_count',
        ];

        if ($this->supportsColumn('created_at')) {
            $selectParts[] = 'MAX(created_at) AS last_donation_at';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts)
            . ' FROM donations WHERE campaign_id = ?' . $statusCondition;

        try {
            $row = $this->db->fetch($sql, [$campaignId]) ?: [];
        } catch (Throwable $exception) {
            Logger::warning('No se pudieron calcular los totales de donaciones', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);

            return [
                'total_amount' => 0.0,
                'donation_count' => 0,
                'last_donation_at' => null,
            ];
        }

        return [
            'total_amount' => (float)($row['total_amount'] ?? 0),
            'donation_count' => (int)($row['donation_count'] ?? 0),
            'last_donation_at' => $this->supportsColumn('created_at')
                ? ($row['last_donation_at'] ?? null)
                : null,
        ];
    }

    private function updateCampaignAggregateFields(int $campaignId, array $totals): void
    {
        $updatePayload = [];

        if ($this->campaignSupports('raised_amount')) {
            $updatePayload['raised_amount'] = $totals['total_amount'];
        }

        if ($this->campaignSupports('current_amount')) {
            $updatePayload['current_amount'] = $totals['total_amount'];
        }

        if ($this->campaignSupports('donor_count')) {
            $updatePayload['donor_count'] = max(0, $totals['donation_count']);
        }

        if ($this->campaignSupports('supporters_count')) {
            $updatePayload['supporters_count'] = max(0, $totals['donation_count']);
        }

        if ($this->campaignSupports('donation_count')) {
            $updatePayload['donation_count'] = max(0, $totals['donation_count']);
        }

        $shouldUpdateProgress = $this->campaignSupports('progress') || $this->campaignSupports('percentage_raised');
        if ($shouldUpdateProgress) {
            $goalAmount = $this->getCampaignGoalAmount($campaignId);
            $progress = 0.0;
            if ($goalAmount > 0) {
                $progress = min(100, round(($totals['total_amount'] / $goalAmount) * 100, 2));
            }

            if ($this->campaignSupports('progress')) {
                $updatePayload['progress'] = $progress;
            }

            if ($this->campaignSupports('percentage_raised')) {
                $updatePayload['percentage_raised'] = $progress;
            }
        }

        if ($this->campaignSupports('updated_at') && !empty($updatePayload)) {
            $updatePayload['updated_at'] = date('Y-m-d H:i:s');
        }

        if (empty($updatePayload)) {
            return;
        }

        try {
            $this->db->update('campaigns', $updatePayload, 'id = ?', [$campaignId]);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo actualizar la campaña sin métricas', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function getCampaignGoalAmount(int $campaignId): float
    {
        if ($campaignId <= 0 || !$this->campaignSupports('goal_amount')) {
            return 0.0;
        }

        try {
            $row = $this->db->fetch(
                'SELECT goal_amount FROM campaigns WHERE id = ? LIMIT 1',
                [$campaignId]
            );
        } catch (Throwable $exception) {
            Logger::warning('No se pudo obtener la meta de la campaña', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
            return 0.0;
        }

        return isset($row['goal_amount']) ? (float)$row['goal_amount'] : 0.0;
    }

    private function finalizeCampaignIfEligible(int $campaignId, float $raisedAmount, ?string $currentStatus = null, ?float $goal = null): void
    {
        if (!$this->campaignSupports('status')) {
            return;
        }

        if ($goal === null || $currentStatus === null) {
            if (!$this->campaignSupports('goal_amount')) {
                return;
            }

            try {
                $campaignRow = $this->db->fetch(
                    "SELECT goal_amount, status FROM campaigns WHERE id = ?",
                    [$campaignId]
                );
            } catch (Throwable $exception) {
                Logger::warning('No se pudo obtener la campaña para validar el objetivo', [
                    'campaign_id' => $campaignId,
                    'error' => $exception->getMessage()
                ]);
                return;
            }

            if (!$campaignRow) {
                return;
            }

            $goal = isset($campaignRow['goal_amount']) ? (float)$campaignRow['goal_amount'] : 0.0;
            $currentStatus = $campaignRow['status'] ?? null;
        }

        if ($goal <= 0 || $raisedAmount < $goal || $currentStatus === 'completed') {
            return;
        }

        try {
            $campaignModel = new Campaign();
            $campaignModel->changeStatus($campaignId, 'completed', null, 'Meta financiera alcanzada automáticamente');
            $campaignModel->markFundingMilestone($campaignId, ['mark_funded' => true]);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo finalizar automáticamente la campaña tras alcanzar la meta', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function getAnonymousColumn(): ?string
    {
        return $this->anonymousColumn;
    }

    private function normalizeDonationRows(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $rows[$index] = $this->normalizeDonationRow($row);
        }

        return $rows;
    }

    private function normalizeDonationRow(array $row): array
    {
        $hasSupporterName = array_key_exists('supporter_name', $row) && trim((string)$row['supporter_name']) !== '';
        $hasDonorName = array_key_exists('donor_name', $row) && trim((string)$row['donor_name']) !== '';

        if (!$hasSupporterName && $hasDonorName) {
            $row['supporter_name'] = $row['donor_name'];
        }

        if (!array_key_exists('donor_name', $row) && array_key_exists('supporter_name', $row)) {
            $row['donor_name'] = $row['supporter_name'];
        }

        $hasSupporterEmail = array_key_exists('supporter_email', $row) && trim((string)$row['supporter_email']) !== '';
        $hasDonorEmail = array_key_exists('donor_email', $row) && trim((string)$row['donor_email']) !== '';

        if (!$hasSupporterEmail && $hasDonorEmail) {
            $row['supporter_email'] = $row['donor_email'];
        }

        if (!array_key_exists('donor_email', $row) && array_key_exists('supporter_email', $row)) {
            $row['donor_email'] = $row['supporter_email'];
        }

        if (array_key_exists('is_anonymous', $row)) {
            $row['is_anonymous'] = (bool)((int)$row['is_anonymous']);
        } else {
            $anonymousColumn = $this->getAnonymousColumn();
            if ($anonymousColumn !== null && array_key_exists($anonymousColumn, $row)) {
                $row['is_anonymous'] = (bool)((int)$row[$anonymousColumn]);
            } else {
                $row['is_anonymous'] = false;
            }
        }

        $anonymousColumn = $this->getAnonymousColumn();
        if ($anonymousColumn !== null && $anonymousColumn !== 'is_anonymous') {
            $row[$anonymousColumn] = $row['is_anonymous'] ? 1 : 0;
        }

        if (array_key_exists('payment_reference', $row)) {
            $paymentReference = $row['payment_reference'];
            if (!array_key_exists('transaction_id', $row) || (empty($row['transaction_id']) && !empty($paymentReference))) {
                $row['transaction_id'] = $paymentReference;
            }
        }

        return $row;
    }

    public function getSupporterColumn(): ?string
    {
        return $this->supporterColumn !== '' ? $this->supporterColumn : null;
    }

    public function hasAnonymousColumn(): bool
    {
        return $this->getAnonymousColumn() !== null;
    }

    private function resolveUserEmail(int $userId): ?string
    {
        if (array_key_exists($userId, self::$userIdentityCache)) {
            return self::$userIdentityCache[$userId];
        }

        try {
            $userModel = new User();
            $user = $userModel->findById($userId);
            if (is_array($user) && !empty($user['email'])) {
                $email = strtolower(trim((string)$user['email']));
                if ($email !== '') {
                    self::$userIdentityCache[$userId] = $email;
                    return $email;
                }
            }
        } catch (Throwable $exception) {
            Logger::warning('No se pudo obtener el correo del usuario para filtrar donaciones', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
        }

        self::$userIdentityCache[$userId] = null;
        return null;
    }

    private function collectUserColumns(): array
    {
        $columns = [];
        if ($this->supporterColumn !== '') {
            $columns[] = $this->supporterColumn;
        }
        if ($this->legacySupporterColumn !== null) {
            $columns[] = $this->legacySupporterColumn;
        }

        return array_values(array_unique(array_filter($columns, static fn ($value) => $value !== '')));
    }

    private function buildUserMatchCondition(int $userId, string $alias = '', ?string $email = null): ?array
    {
        $columns = $this->collectUserColumns();
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        $groups = [];
        $params = [];

        if (!empty($columns)) {
            $idClauses = [];
            foreach ($columns as $column) {
                $idClauses[] = $prefix . $column . ' = ?';
                $params[] = $userId;
            }

            if ($idClauses !== []) {
                $groups[] = count($idClauses) === 1
                    ? $idClauses[0]
                    : '(' . implode(' OR ', $idClauses) . ')';
            }
        }

        if ($email === null) {
            $email = $this->resolveUserEmail($userId);
        } else {
            $email = strtolower(trim($email));
            if ($email === '') {
                $email = null;
            }
        }

        if ($email !== null) {
            $emailClauses = [];
            if ($this->supportsColumn('supporter_email')) {
                $emailClauses[] = 'LOWER(' . $prefix . 'supporter_email) = ?';
            }
            if ($this->supportsColumn('donor_email')) {
                $emailClauses[] = 'LOWER(' . $prefix . 'donor_email) = ?';
            }

            if ($emailClauses !== []) {
                $groups[] = count($emailClauses) === 1
                    ? $emailClauses[0]
                    : '(' . implode(' OR ', $emailClauses) . ')';

                $emailParamCount = count($emailClauses);
                for ($i = 0; $i < $emailParamCount; $i++) {
                    $params[] = $email;
                }
            }
        }

        if ($groups === []) {
            return null;
        }

        $clause = count($groups) === 1
            ? $groups[0]
            : '(' . implode(' OR ', $groups) . ')';

        return [
            'clause' => $clause,
            'params' => $params,
        ];
    }
}
