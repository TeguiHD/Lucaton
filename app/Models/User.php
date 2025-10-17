<?php
/**
 * Modelo User - Gestión de usuarios y autenticación
 * Maneja registro, login, perfiles y seguridad
 */

class User {
    public const PASSWORD_RESET_THROTTLED = 'reset_throttled';
    private const PASSWORD_RESET_MIN_INTERVAL = 600; // 10 minutos
    private const PASSWORD_RESET_TOKEN_BYTES = 32;

    private $db;
    private static $schemaCapabilities = null;
    private static $roleCacheBySlug = null;
    private static $roleCacheById = null;
    private static $roleCacheNameBySlug = null;

    public function __construct() {
        $this->db = Database::getInstance();
        if (self::$schemaCapabilities === null) {
            self::$schemaCapabilities = [
                'failed_login_attempts' => $this->db->columnExists('users', 'failed_login_attempts'),
                'locked_until' => $this->db->columnExists('users', 'locked_until'),
                'last_login_at' => $this->db->columnExists('users', 'last_login_at'),
                'last_login_ip' => $this->db->columnExists('users', 'last_login_ip'),
                'status' => $this->db->columnExists('users', 'status'),
                'role_id' => $this->db->columnExists('users', 'role_id'),
                'role_signature' => $this->db->columnExists('users', 'role_signature'),
                'two_factor_secret' => $this->db->columnExists('users', 'two_factor_secret'),
                'location' => $this->db->columnExists('users', 'location'),
                'social_links' => $this->db->columnExists('users', 'social_links'),
                'pref_product_updates' => $this->db->columnExists('users', 'pref_product_updates'),
                'pref_campaign_tips' => $this->db->columnExists('users', 'pref_campaign_tips'),
                'pref_donation_alerts' => $this->db->columnExists('users', 'pref_donation_alerts'),
                'password_updated_at' => $this->db->columnExists('users', 'password_updated_at'),
                'password_reset_at' => $this->db->columnExists('users', 'password_reset_at'),
                'requested_first_name' => $this->db->columnExists('users', 'requested_first_name'),
                'requested_last_name' => $this->db->columnExists('users', 'requested_last_name'),
                'name_review_status' => $this->db->columnExists('users', 'name_review_status'),
                'name_review_notes' => $this->db->columnExists('users', 'name_review_notes'),
                'name_reviewed_at' => $this->db->columnExists('users', 'name_reviewed_at'),
            ];
        }
    }

    private function supportsColumn(string $column): bool {
        return self::$schemaCapabilities[$column] ?? false;
    }

    private function roleSigningKey(): string
    {
        return ROLE_SIGNATURE_KEY ?: SESSION_SIGNATURE_KEY;
    }

    private function normalizeRoleValue($role): string
    {
        $normalized = strtolower(trim((string)$role));
        return $normalized !== '' ? $normalized : 'user';
    }

    private function signRoleValue(int $userId, string $role): string
    {
        return hash_hmac('sha256', $userId . '|' . $this->normalizeRoleValue($role), $this->roleSigningKey());
    }

    private function ensureRoleCache(): void
    {
        if (self::$roleCacheBySlug !== null && self::$roleCacheById !== null && self::$roleCacheNameBySlug !== null) {
            return;
        }

        self::$roleCacheBySlug = [];
        self::$roleCacheById = [];
        self::$roleCacheNameBySlug = [];

        if (!$this->db->tableExists('roles')) {
            return;
        }

        $rows = $this->db->fetchAll('SELECT id, slug, name FROM roles');
        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $slug = $this->normalizeRoleValue($row['slug'] ?? '');
            $id = (int)($row['id'] ?? 0);
            if ($slug === '' || $id <= 0) {
                continue;
            }
            self::$roleCacheBySlug[$slug] = $id;
            self::$roleCacheById[$id] = $slug;
            self::$roleCacheNameBySlug[$slug] = $row['name'] ?? ucfirst($slug);
        }
    }

    private function getRoleIdBySlug(string $slug): int
    {
        $this->ensureRoleCache();
        $normalized = $this->normalizeRoleValue($slug);

        if (!isset(self::$roleCacheBySlug[$normalized])) {
            self::$roleCacheBySlug = null;
            self::$roleCacheById = null;
            self::$roleCacheNameBySlug = null;
            $this->ensureRoleCache();
        }

        if (!isset(self::$roleCacheBySlug[$normalized])) {
            $normalized = 'user';
        }

        if (!isset(self::$roleCacheBySlug[$normalized])) {
            throw new Exception('El rol base no está configurado.');
        }

        return (int)self::$roleCacheBySlug[$normalized];
    }

    private function getRoleSlugById(int $roleId): string
    {
        $this->ensureRoleCache();
        if (!isset(self::$roleCacheById[$roleId])) {
            self::$roleCacheBySlug = null;
            self::$roleCacheById = null;
            self::$roleCacheNameBySlug = null;
            $this->ensureRoleCache();
        }

        return self::$roleCacheById[$roleId] ?? 'user';
    }

    private function ensureRoleSignature(?array $user): ?array
    {
        if (!$user || !$this->supportsColumn('role_signature')) {
            return $user;
        }

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return $user;
        }

        if (!isset($user['role']) && isset($user['role_id'])) {
            $user['role'] = $this->getRoleSlugById((int)$user['role_id']);
        }

        $currentRole = $this->normalizeRoleValue($user['role'] ?? 'user');
        $currentSignature = $user['role_signature'] ?? null;
        $expectedSignature = $this->signRoleValue($userId, $currentRole);

        if ($currentSignature === $expectedSignature) {
            return $user;
        }

        if ($currentSignature === null) {
            try {
                $this->db->update('users', ['role_signature' => $expectedSignature], 'id = ?', [$userId]);
                $user['role_signature'] = $expectedSignature;
            } catch (Exception $exception) {
                Logger::warning('No se pudo generar la firma de rol para el usuario.', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
            }

            return $user;
        }

        Logger::security('role_signature_mismatch', 'high', [
            'user_id' => $userId,
            'stored_role' => $currentRole,
        ]);

        $safeRole = 'user';
        $safeRoleId = $this->supportsColumn('role_id') ? $this->getRoleIdBySlug($safeRole) : null;
        $safeSignature = $this->signRoleValue($userId, $safeRole);

        $updatePayload = ['role_signature' => $safeSignature];
        if ($this->supportsColumn('role_id')) {
            $updatePayload['role_id'] = $safeRoleId;
        } else {
            $updatePayload['role'] = $safeRole;
        }

        try {
            $this->db->update('users', $updatePayload, 'id = ?', [$userId]);
            $user['role'] = $safeRole;
            if ($safeRoleId !== null) {
                $user['role_id'] = $safeRoleId;
            }
            $user['role_signature'] = $safeSignature;
            $user['role_name'] = self::$roleCacheNameBySlug[$safeRole] ?? ucfirst($safeRole);
        } catch (Exception $exception) {
            Logger::warning('No se pudo restablecer la firma de rol inválida.', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
        }

        return $user;
    }

    private function shouldGrantSuperadmin(?array $user): bool
    {
        if (!$user) {
            return false;
        }

        $email = strtolower(trim((string)($user['email'] ?? '')));
        if ($email === '') {
            return false;
        }

        if (env('APP_SUPERADMIN_AUTO_PROMOTE', 'false') !== 'true') {
            return false;
        }

        $seedList = env('SUPERADMIN_SEEDS_HASHED', '');
        if ($seedList === '') {
            return false;
        }

        $hashes = array_filter(array_map('trim', explode(',', $seedList)));
        if (!$hashes) {
            return false;
        }

        $algo = strtolower(env('SUPERADMIN_SEEDS_ALGO', 'sha256'));
        if (!in_array($algo, hash_algos(), true)) {
            Logger::warning('Algoritmo de hash inválido para SUPERADMIN_SEEDS_ALGO', [
                'algorithm' => $algo,
            ]);
            return false;
        }

        $secret = env('SUPERADMIN_SEEDS_SECRET', '');
        $computed = $secret !== ''
            ? hash_hmac($algo, $email, $secret)
            : hash($algo, $email);

        foreach ($hashes as $hash) {
            if ($hash !== '' && hash_equals($hash, $computed)) {
                return true;
            }
        }

        return false;
    }

    private function applyAdminOverrides(?array $user): ?array
    {
        if (!$user) {
            return $user;
        }

        $user = $this->ensureRoleSignature($user);

        if (!$this->shouldGrantSuperadmin($user)) {
            return $user;
        }

        $currentRole = $this->normalizeRoleValue($user['role'] ?? 'user');
        if ($currentRole === 'superadmin') {
            return $user;
        }

        $update = [];
        if ($this->supportsColumn('role_id')) {
            $superadminRoleId = $this->getRoleIdBySlug('superadmin');
            $update['role_id'] = $superadminRoleId;
        } else {
            $update['role'] = 'superadmin';
            $superadminRoleId = null;
        }

        if ($this->supportsColumn('role_signature')) {
            $update['role_signature'] = $this->signRoleValue((int)$user['id'], 'superadmin');
            $user['role_signature'] = $update['role_signature'];
        }
        if ($this->supportsColumn('status')) {
            $update['status'] = 'active';
            $user['status'] = 'active';
        }
        if ($this->supportsColumn('email_verification_token')) {
            $update['email_verification_token'] = null;
        }
        if ($this->supportsColumn('email_verified_at') && empty($user['email_verified_at'])) {
            $timestamp = date('Y-m-d H:i:s');
            $update['email_verified_at'] = $timestamp;
            $user['email_verified_at'] = $timestamp;
        }

        try {
            $this->db->update('users', $update, 'id = ?', [$user['id']]);
        } catch (Exception $e) {
            Logger::warning('No se pudo promover automáticamente a administrador', [
                'user_id' => $user['id'],
                'error' => $e->getMessage(),
            ]);
            return $user;
        }

        Logger::audit('superadmin_auto_promote', (int)$user['id'], [
            'email' => $user['email'] ?? null,
        ]);

        $user['role'] = 'superadmin';
        if ($superadminRoleId !== null) {
            $user['role_id'] = $superadminRoleId;
        }
        $user['role_name'] = self::$roleCacheNameBySlug['superadmin'] ?? 'Super Administrador';
        return $user;
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create($data) {
        // Validar datos requeridos
        $this->validateUserData($data);
        
        // Verificar que email y username no existan
        if ($this->emailExists($data['email'])) {
            throw new Exception('El email ya está registrado');
        }
        
        if ($this->usernameExists($data['username'])) {
            throw new Exception('El nombre de usuario ya está en uso');
        }
        
        // Preparar datos para inserción
        $status = $data['status'] ?? 'pending_verification';
        $roleSlug = $this->normalizeRoleValue($data['role'] ?? 'user');

        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'status' => $status,
            'email_verification_token' => $status === 'active' ? null : bin2hex(random_bytes(32)),
            'email_verified_at' => $status === 'active' ? date('Y-m-d H:i:s') : null
        ];

        if ($this->supportsColumn('role_id')) {
            $roleId = $this->getRoleIdBySlug($roleSlug);
            $userData['role_id'] = $roleId;
        } else {
            $userData['role'] = $roleSlug;
        }

        try {
            $userId = $this->db->insert('users', $userData);
            if ($this->supportsColumn('role_signature')) {
                $signature = $this->signRoleValue((int)$userId, $roleSlug);
                $this->db->update('users', ['role_signature' => $signature], 'id = ?', [$userId]);
            }
            
            // Log de auditoría
            Logger::audit('user_created', $userId, [
                'username' => $userData['username'],
                'email' => $userData['email']
            ]);
            
            return $userId;
            
        } catch (Exception $e) {
            Logger::error('Error creating user', [
                'error' => $e->getMessage(),
                'email' => $data['email']
            ]);
            throw new Exception('Error al crear usuario');
        }
    }
    
    /**
     * Autenticar usuario
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) {
            $this->logFailedLogin($email, 'user_not_found');
            throw new Exception('Credenciales inválidas');
        }
        
        // Verificar si la cuenta está bloqueada
        if ($this->supportsColumn('locked_until') && $this->isAccountLocked($user)) {
            $this->logFailedLogin($email, 'account_locked');
            throw new Exception('Cuenta bloqueada temporalmente. Inténtalo nuevamente en 15 minutos.');
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            if ($this->supportsColumn('failed_login_attempts')) {
                $this->incrementFailedAttempts($user['id']);
            }
            $this->logFailedLogin($email, 'invalid_password');
            throw new Exception('Credenciales inválidas');
        }

        // Verificar estado de la cuenta
        if ($this->supportsColumn('status') && $user['status'] !== 'active') {
            $this->logFailedLogin($email, 'account_inactive');
            throw new Exception('Cuenta no activa. Verifica tu email.');
        }

        // Login exitoso
        if ($this->supportsColumn('failed_login_attempts')) {
            $this->resetFailedAttempts($user['id']);
        }
        if ($this->supportsColumn('last_login_at') || $this->supportsColumn('last_login_ip')) {
            $this->updateLastLogin($user['id']);
        }

        $user = $this->applyAdminOverrides($user);

        Logger::audit('user_login', $user['id'], [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        return $user;
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email) {
        if ($this->supportsColumn('role_id')) {
            $user = $this->db->fetch(
                "SELECT u.*, r.slug AS role, r.name AS role_name
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.email = ?",
                [$email]
            );
        } else {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );
        }

        return $this->applyAdminOverrides($user);
    }
    
    /**
     * Buscar usuario por ID
     */
    public function findById($id) {
        if ($this->supportsColumn('role_id')) {
            $user = $this->db->fetch(
                "SELECT u.*, r.slug AS role, r.name AS role_name
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.id = ?",
                [$id]
            );
        } else {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$id]
            );
        }

        return $this->applyAdminOverrides($user);
    }
    
    /**
     * Buscar usuario por username
     */
    public function findByUsername($username) {
        if ($this->supportsColumn('role_id')) {
            $user = $this->db->fetch(
                "SELECT u.*, r.slug AS role, r.name AS role_name
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.username = ?",
                [$username]
            );
        } else {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE username = ?",
                [$username]
            );
        }

        return $this->applyAdminOverrides($user);
    }
    
    /**
     * Verificar si email existe
     */
    public function emailExists($email) {
        $result = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        return $result !== false;
    }
    
    /**
     * Verificar si username existe
     */
    public function usernameExists($username) {
        $result = $this->db->fetch(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );
        return $result !== false;
    }
    
    /**
     * Verificar email con token
     */
    public function verifyEmail($token) {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE email_verification_token = ? AND status = 'pending_verification'",
            [$token]
        );
        
        if (!$user) {
            throw new Exception('Token de verificación inválido');
        }
        
        $this->db->update('users', [
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_token' => null
        ], 'id = ?', [$user['id']]);
        
        Logger::audit('email_verified', $user['id']);
        
        return true;
    }
    
    /**
     * Generar token de recuperación de contraseña
     */
    public function generatePasswordResetToken($email) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            throw new Exception('Email no encontrado');
        }

        if ($this->supportsColumn('password_reset_at')) {
            $lastResetAt = $user['password_reset_at'] ?? null;
            if ($lastResetAt !== null) {
                $lastResetTimestamp = strtotime((string)$lastResetAt);
                if ($lastResetTimestamp !== false && (time() - $lastResetTimestamp) < self::PASSWORD_RESET_MIN_INTERVAL) {
                    Logger::notice('password_reset_throttled', [
                        'user_id' => $user['id'],
                        'email' => $user['email'] ?? null,
                    ]);
                    throw new RuntimeException(self::PASSWORD_RESET_THROTTLED);
                }
            }
        }
        
        $token = bin2hex(random_bytes(self::PASSWORD_RESET_TOKEN_BYTES));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $updateFields = [
            'password_reset_token' => $tokenHash,
            'password_reset_expires_at' => $expires
        ];

        if ($this->supportsColumn('password_reset_at')) {
            $updateFields['password_reset_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->update('users', $updateFields, 'id = ?', [$user['id']]);
        
        Logger::audit('password_reset_requested', $user['id']);
        
        return $token;
    }

    /**
     * Buscar usuario válido por token de recuperación
     */
    public function findByValidResetToken($token) {
        $tokenHash = hash('sha256', $token);
        $tokenCandidates = [$tokenHash];
        if ($tokenHash !== $token) {
            $tokenCandidates[] = $token;
        }
        $placeholders = implode(', ', array_fill(0, count($tokenCandidates), '?'));

        if ($this->supportsColumn('role_id')) {
            $user = $this->db->fetch(
                "SELECT u.*, r.slug AS role, r.name AS role_name
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.password_reset_token IN ($placeholders)
                   AND u.password_reset_expires_at > NOW()",
                $tokenCandidates
            );
        } else {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE password_reset_token IN ($placeholders) AND password_reset_expires_at > NOW()",
                $tokenCandidates
            );
        }

        return $this->applyAdminOverrides($user);
    }
    
    /**
     * Resetear contraseña con token
     */
    public function resetPassword($token, $newPassword) {
        $tokenHash = hash('sha256', $token);
        $tokenCandidates = [$tokenHash];
        if ($tokenHash !== $token) {
            $tokenCandidates[] = $token;
        }
        $placeholders = implode(', ', array_fill(0, count($tokenCandidates), '?'));

        $user = $this->db->fetch(
            "SELECT id FROM users WHERE password_reset_token IN ($placeholders) AND password_reset_expires_at > NOW()",
            $tokenCandidates
        );
        
        if (!$user) {
            throw new Exception('Token de recuperación inválido o expirado');
        }

        $updateData = [
            'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID),
            'password_reset_token' => null,
            'password_reset_expires_at' => null
        ];

        if ($this->supportsColumn('password_reset_at')) {
            $updateData['password_reset_at'] = date('Y-m-d H:i:s');
        }

        if ($this->supportsColumn('password_updated_at')) {
            $updateData['password_updated_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->update('users', $updateData, 'id = ?', [$user['id']]);
        
        Logger::audit('password_reset_completed', $user['id']);
        
        return true;
    }
    
    /**
     * Actualizar perfil de usuario
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['phone', 'bio', 'avatar_url'];

        if ($this->supportsColumn('location')) {
            $allowedFields[] = 'location';
        }

        $updateData = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if ($this->supportsColumn('social_links') && isset($data['social_links'])) {
            $updateData['social_links'] = empty($data['social_links'])
                ? null
                : json_encode($data['social_links'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (empty($updateData)) {
            throw new Exception('No hay datos para actualizar');
        }

        $this->db->update('users', $updateData, 'id = ?', [$userId]);

        Logger::audit('profile_updated', $userId, array_keys($updateData));

        return true;
    }

    public function requestNameChange(int $userId, string $firstName, string $lastName): void {
        if (!$this->supportsColumn('requested_first_name') || !$this->supportsColumn('name_review_status')) {
            throw new Exception('La solicitud de cambio de nombre no está disponible.');
        }

        $update = [
            'requested_first_name' => $firstName,
            'requested_last_name' => $lastName,
            'name_review_status' => 'pending',
        ];

        if ($this->supportsColumn('name_review_notes')) {
            $update['name_review_notes'] = null;
        }

        if ($this->supportsColumn('name_reviewed_at')) {
            $update['name_reviewed_at'] = null;
        }

        $this->db->update('users', $update, 'id = ?', [$userId]);

        Logger::audit('user_name_change_requested', $userId, [
            'requested_first_name' => $firstName,
            'requested_last_name' => $lastName,
        ]);
    }

    public function updatePreferences(int $userId, array $preferences): void {
        $fields = [];

        if ($this->supportsColumn('pref_product_updates') && array_key_exists('product_updates', $preferences)) {
            $fields['pref_product_updates'] = (int)$preferences['product_updates'];
        }

        if ($this->supportsColumn('pref_campaign_tips') && array_key_exists('campaign_tips', $preferences)) {
            $fields['pref_campaign_tips'] = (int)$preferences['campaign_tips'];
        }

        if ($this->supportsColumn('pref_donation_alerts') && array_key_exists('donation_alerts', $preferences)) {
            $fields['pref_donation_alerts'] = (int)$preferences['donation_alerts'];
        }

        if (empty($fields)) {
            throw new Exception('No hay preferencias para actualizar.');
        }

        $this->db->update('users', $fields, 'id = ?', [$userId]);

        Logger::audit('user_preferences_updated', $userId, $fields);
    }

    public function updateTwoFactorStatus(int $userId, bool $enabled, ?string $secret = null): void {
        $fields = ['two_factor_enabled' => $enabled ? 1 : 0];

        if ($this->supportsColumn('two_factor_secret')) {
            $fields['two_factor_secret'] = $enabled ? $secret : null;
        }

        $this->db->update('users', $fields, 'id = ?', [$userId]);

        Logger::audit('user_two_factor_updated', $userId, ['enabled' => $enabled]);
    }

    public function changePassword(int $userId, string $newPassword): void {
        $update = [
            'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID)
        ];

        if ($this->supportsColumn('password_updated_at')) {
            $update['password_updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('users', $update, 'id = ?', [$userId]);

        Logger::audit('user_password_changed', $userId);
    }

    public function assignRole(int $userId, string $roleSlug, ?int $actorId = null): void {
        $userId = (int)$userId;
        if ($userId <= 0) {
            throw new Exception('Usuario inválido');
        }

        $roleSlug = $this->normalizeRoleValue($roleSlug);
        $allowed = ['user', 'admin', 'superadmin'];
        if (!in_array($roleSlug, $allowed, true)) {
            throw new Exception('Rol no permitido');
        }

        $current = $this->findById($userId);
        if (!$current) {
            throw new Exception('Usuario no encontrado');
        }

        $currentRole = $this->normalizeRoleValue($current['role'] ?? 'user');
        if ($currentRole === $roleSlug) {
            return;
        }

        $update = [];
        if ($this->supportsColumn('role_id')) {
            $update['role_id'] = $this->getRoleIdBySlug($roleSlug);
        } else {
            $update['role'] = $roleSlug;
        }

        if ($this->supportsColumn('role_signature')) {
            $update['role_signature'] = $this->signRoleValue($userId, $roleSlug);
        }

        $this->db->update('users', $update, 'id = ?', [$userId]);

        Logger::audit('user_role_changed', $userId, [
            'previous_role' => $currentRole,
            'new_role' => $roleSlug,
            'actor_id' => $actorId,
        ]);
    }
    
    /**
     * Validar datos de usuario
     */
    private function validateUserData($data) {
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo {$field} es requerido");
            }
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        if (strlen($data['password']) < 8) {
            throw new Exception('La contraseña debe tener al menos 8 caracteres');
        }
        
        if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            throw new Exception('El username debe tener entre 3 y 50 caracteres');
        }
    }
    
    /**
     * Verificar si la cuenta está bloqueada
     */
    private function isAccountLocked($user) {
        if (!$this->supportsColumn('locked_until')) {
            return false;
        }

        $lockedUntilRaw = $user['locked_until'] ?? null;
        if (!$lockedUntilRaw) {
            return false;
        }

        $lockedUntil = strtotime($lockedUntilRaw);
        if ($lockedUntil === false) {
            return false;
        }

        if ($lockedUntil > time()) {
            return true;
        }

        if ($this->supportsColumn('failed_login_attempts') && !empty($user['id']) && (int)($user['failed_login_attempts'] ?? 0) > 0) {
            $this->resetFailedAttempts((int)$user['id']);
        }

        return false;
    }
    
    /**
     * Incrementar intentos fallidos
     */
    private function incrementFailedAttempts($userId) {
        $this->db->query(
            "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?",
            [$userId]
        );

        $user = $this->findById($userId);
        if (!$user) {
            return;
        }

        $attempts = (int)($user['failed_login_attempts'] ?? 0);

        if ($attempts < 5) {
            return;
        }

        $updates = [];

        if ($this->supportsColumn('locked_until')) {
            $updates['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }

        if ($this->supportsColumn('failed_login_attempts')) {
            $updates['failed_login_attempts'] = 5;
        }

        if (!empty($updates)) {
            $this->db->update('users', $updates, 'id = ?', [$userId]);
        }

        Logger::security('account_locked', 'high', [
            'user_id' => $userId,
            'attempts' => $attempts,
            'lock_duration_minutes' => 15
        ]);
    }
    
    /**
     * Resetear intentos fallidos
     */
    private function resetFailedAttempts($userId) {
        $update = [];
        if ($this->supportsColumn('failed_login_attempts')) {
            $update['failed_login_attempts'] = 0;
        }
        if ($this->supportsColumn('locked_until')) {
            $update['locked_until'] = null;
        }

        if (!empty($update)) {
            $this->db->update('users', $update, 'id = ?', [$userId]);
        }
    }
    
    /**
     * Actualizar último login
     */
    private function updateLastLogin($userId) {
        $update = [];
        if ($this->supportsColumn('last_login_at')) {
            $update['last_login_at'] = date('Y-m-d H:i:s');
        }
        if ($this->supportsColumn('last_login_ip')) {
            $update['last_login_ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        if (!empty($update)) {
            $this->db->update('users', $update, 'id = ?', [$userId]);
        }
    }
    
    /**
     * Log de intento de login fallido
     */
    private function logFailedLogin($email, $reason) {
        Logger::security('failed_login', 'medium', [
            'email' => $email,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Obtener todos los IDs de usuarios activos
     */
    public function getAllActiveIds(): array {
        $sql = "SELECT id FROM users";
        if ($this->supportsColumn('status')) {
            $sql .= " WHERE status = 'active'";
        }

        $rows = $this->db->fetchAll($sql);

        if (!$rows) {
            return [];
        }

        return array_map(static function ($row) {
            return (int)($row['id'] ?? 0);
        }, $rows);
    }

    /**
     * Obtener usuarios activos con datos básicos (para selects)
     */
    public function getActiveUsersBasic(): array {
        if (!$this->supportsColumn('role_id')) {
            $rows = $this->db->fetchAll(
                "SELECT id, first_name, last_name, email, role
                 FROM users
                 WHERE status = 'active'
                 ORDER BY first_name, last_name"
            );

            if (!$rows) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int)$row['id'],
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                    'email' => $row['email'] ?? '',
                    'role' => $this->normalizeRoleValue($row['role'] ?? 'user'),
                    'role_name' => ucfirst($this->normalizeRoleValue($row['role'] ?? 'user')),
                ];
            }, $rows);
        }

        $select = "u.id, u.first_name, u.last_name, u.email, r.slug AS role, r.name AS role_name";
        if ($this->supportsColumn('role_signature')) {
            $select .= ", u.role_signature";
        }

        $statusFilter = '';
        if ($this->supportsColumn('status')) {
            $statusFilter = "WHERE u.status = 'active'";
        }

        $rows = $this->db->fetchAll(
            "SELECT {$select}
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             {$statusFilter}
             ORDER BY u.first_name, u.last_name"
        );

        if (!$rows) {
            return [];
        }

        return array_map(function ($row) {
            if ($this->supportsColumn('role_signature')) {
                $row = $this->ensureRoleSignature($row);
            }

            return [
                'id' => (int)$row['id'],
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'email' => $row['email'] ?? '',
                'role' => $this->normalizeRoleValue($row['role'] ?? 'user'),
                'role_name' => $row['role_name'] ?? null,
            ];
        }, $rows);
    }

    /**
     * Obtener todos los usuarios para administración
     */
    public function getAllUsers(): array {
        if (!$this->supportsColumn('role_id')) {
            $columns = "id, username, email, first_name, last_name, role, created_at";

            if ($this->supportsColumn('status')) {
                $columns .= ", status";
            }

            if ($this->supportsColumn('last_login_at')) {
                $columns .= ", last_login_at";
            }

            $rows = $this->db->fetchAll("SELECT {$columns} FROM users ORDER BY created_at DESC");

            if (!$rows) {
                return [];
            }

            return array_map(function ($row) {
                $user = [
                    'id' => (int)$row['id'],
                    'username' => $row['username'] ?? '',
                    'email' => $row['email'] ?? '',
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                    'role' => $this->normalizeRoleValue($row['role'] ?? 'user'),
                    'role_name' => ucfirst($this->normalizeRoleValue($row['role'] ?? 'user')),
                    'created_at' => $row['created_at'] ?? ''
                ];

                if ($this->supportsColumn('status')) {
                    $user['status'] = $row['status'] ?? 'active';
                }

                if ($this->supportsColumn('last_login_at')) {
                    $user['last_login_at'] = $row['last_login_at'] ?? null;
                }

                return $user;
            }, $rows);
        }

        $select = "u.id, u.username, u.email, u.first_name, u.last_name, r.slug AS role, r.name AS role_name, u.created_at";

        if ($this->supportsColumn('role_signature')) {
            $select .= ", u.role_signature";
        }
        
        if ($this->supportsColumn('status')) {
            $select .= ", u.status";
        }
        
        if ($this->supportsColumn('last_login_at')) {
            $select .= ", u.last_login_at";
        }
        
        $sql = "SELECT {$select}
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                ORDER BY u.created_at DESC";
        
        $rows = $this->db->fetchAll($sql);

        if (!$rows) {
            return [];
        }

        return array_map(function ($row) {
            if ($this->supportsColumn('role_signature')) {
                $row = $this->ensureRoleSignature($row);
            }

            $user = [
                'id' => (int)$row['id'],
                'username' => $row['username'] ?? '',
                'email' => $row['email'] ?? '',
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'role' => $this->normalizeRoleValue($row['role'] ?? 'user'),
                'created_at' => $row['created_at'] ?? ''
            ];

            if (isset($row['role_name'])) {
                $user['role_name'] = $row['role_name'];
            }
            
            if ($this->supportsColumn('status')) {
                $user['status'] = $row['status'] ?? 'active';
            }
            
            if ($this->supportsColumn('last_login_at')) {
                $user['last_login_at'] = $row['last_login_at'] ?? null;
            }
            
            return $user;
        }, $rows);
    }
}
