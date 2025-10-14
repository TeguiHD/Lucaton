<?php
/**
 * SessionHelper - Manejo seguro de sesiones para Lucatón
 * Implementa medidas de seguridad según requerimientos OWASP
 */

class SessionHelper {
    private const ROLE_REVALIDATE_SECONDS = 600;
    private const ROLE_HIERARCHY = [
        'user' => 10,
        'admin' => 50,
        'superadmin' => 100,
    ];

    private static array $lastSiteToasts = [];
    
    /**
     * Iniciar sesión segura
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar parámetros de sesión seguros
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            // Configurar nombre de sesión
            session_name(SESSION_NAME);
            
            // Configurar tiempo de vida
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            
            session_start();
            
            // Regenerar ID de sesión periódicamente
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
            
            // Generar token CSRF si no existe
            if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
                $_SESSION[CSRF_TOKEN_NAME] = self::generateCSRFToken();
            }
        }
    }
    
    /**
     * Generar token CSRF
     */
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Obtener token CSRF
     */
    public static function getCSRFToken() {
        return $_SESSION[CSRF_TOKEN_NAME] ?? '';
    }
    
    /**
     * Verificar token CSRF
     */
    public static function verifyCSRFToken($token) {
        $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Establecer datos de usuario en sesión
     */
    public static function setUser($user) {
        $userId = (int)($user['id'] ?? 0);
        $role = self::normalizeRole($user['role'] ?? ($user['rol'] ?? 'user'));

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_role_signature'] = self::signRole($userId, $role);
        $_SESSION['user_role_verified_at'] = time();
        $_SESSION['user_status'] = $user['status'] ?? 'active';
        $_SESSION['email_verified'] = !empty($user['email_verified_at']);
        $_SESSION['login_time'] = time();

        self::storeUserProfileFields($user);

        // Regenerar ID de sesión después del login
        session_regenerate_id(true);
    }
    
    /**
     * Obtener ID de usuario actual
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtener datos de usuario actual
     */
    public static function getUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $avatar = $_SESSION['user_avatar'] ?? null;

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'username' => $_SESSION['user_username'] ?? null,
            'name' => $_SESSION['user_name'] ?? '',
            'role' => self::getUserRole() ?? 'user',
            'status' => $_SESSION['user_status'] ?? 'active',
            'email_verified' => !empty($_SESSION['email_verified']),
            'avatar' => $avatar,
            'avatar_url' => $avatar,
        ];
    }

    public static function getUserRole(): ?string {
        if (!self::isAuthenticated()) {
            return null;
        }

        $sessionRole = $_SESSION['user_role'] ?? null;

        if ($sessionRole === null || !self::hasValidRoleSignature($sessionRole)) {
            return self::refreshUserRole();
        }

        $lastVerified = (int)($_SESSION['user_role_verified_at'] ?? 0);
        if ($lastVerified === 0 || (time() - $lastVerified) > self::ROLE_REVALIDATE_SECONDS) {
            return self::refreshUserRole();
        }

        return $sessionRole;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin() {
        return self::userHasRole('admin');
    }

    public static function isSuperAdmin(): bool {
        return self::userHasRole('superadmin');
    }
    
    /**
     * Cerrar sesión
     */
    public static function logout() {
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        // Destruir sesión
        session_destroy();
    }
    
    /**
     * Establecer mensaje flash
     */
    public static function setFlash($type, $message) {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        } elseif (!is_array($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [$_SESSION['flash'][$type]];
        }

        $_SESSION['flash'][$type][] = $message;
    }

    /**
     * Alias retrocompatible para middleware existentes
     */
    public static function setFlashMessage($type, $message) {
        self::setFlash($type, $message);
    }
    
    /**
     * Obtener y limpiar mensaje flash
     */
    public static function getFlash($type) {
        if (!isset($_SESSION['flash'][$type])) {
            return null;
        }

        $stored = $_SESSION['flash'][$type];

        if (is_array($stored)) {
            $message = array_shift($_SESSION['flash'][$type]);
            if (empty($_SESSION['flash'][$type])) {
                unset($_SESSION['flash'][$type]);
            }
        } else {
            $message = $stored;
            unset($_SESSION['flash'][$type]);
        }

        if (isset($_SESSION['flash']) && empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }

        return $message;
    }
    
    /**
     * Verificar si hay mensajes flash
     */
    public static function hasFlash($type = null) {
        if ($type !== null) {
            if (!isset($_SESSION['flash'][$type])) {
                return false;
            }

            $messages = $_SESSION['flash'][$type];
            return is_array($messages) ? !empty($messages) : $messages !== null && $messages !== '';
        }

        if (empty($_SESSION['flash'])) {
            return false;
        }

        foreach ($_SESSION['flash'] as $messages) {
            if (is_array($messages)) {
                if (!empty($messages)) {
                    return true;
                }
            } elseif ($messages !== null && $messages !== '') {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Obtener todos los mensajes flash y limpiarlos
     */
    public static function getAllFlash() {
        if (empty($_SESSION['flash'])) {
            return [];
        }

        $messages = $_SESSION['flash'];
        unset($_SESSION['flash']);

        foreach ($messages as $type => $value) {
            if (is_array($value)) {
                $messages[$type] = implode(' ', array_filter($value, static function ($item) {
                    return $item !== null && $item !== '';
                }));
            }
        }

        return array_filter($messages, static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private static function migrateFlashToToasts(): void
    {
        $flashMessages = $_SESSION['flash'] ?? [];

        foreach ($flashMessages as $type => $messages) {
            $normalized = in_array($type, ['success', 'error', 'warning', 'info', 'system'], true) ? $type : 'info';
            $messages = is_array($messages) ? $messages : [$messages];

            foreach ($messages as $message) {
                if ($message === null) {
                    continue;
                }

                $trimmed = trim((string)$message);
                if ($trimmed === '') {
                    continue;
                }

                self::pushSiteToast($normalized, $trimmed);
            }
        }

        $legacyKeys = ['success', 'error', 'warning', 'info', 'system'];
        foreach ($legacyKeys as $legacyType) {
            $sessionKey = 'flash_' . $legacyType;
            if (!isset($_SESSION[$sessionKey])) {
                continue;
            }

            $stored = $_SESSION[$sessionKey];
            $messages = is_array($stored) ? $stored : [$stored];

            foreach ($messages as $message) {
                if ($message === null) {
                    continue;
                }

                $trimmed = trim((string)$message);
                if ($trimmed === '') {
                    continue;
                }

                self::pushSiteToast($legacyType, $trimmed);
            }

            unset($_SESSION[$sessionKey]);
        }

        unset($_SESSION['flash']);
    }

    public static function pushSiteToast(string $type, string $message): void
    {
        if (!isset($_SESSION['site_toasts']) || !is_array($_SESSION['site_toasts'])) {
            $_SESSION['site_toasts'] = [];
        }

        $allowed = ['success', 'error', 'warning', 'info', 'system'];
        if (!in_array($type, $allowed, true)) {
            $type = 'info';
        }

        $message = trim($message);
        if ($message === '') {
            return;
        }

        $_SESSION['site_toasts'][] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time(),
        ];
    }

    public static function pullSiteToasts(): array
    {
        self::migrateFlashToToasts();

        if (!isset($_SESSION['site_toasts']) || !is_array($_SESSION['site_toasts'])) {
            self::$lastSiteToasts = [];
            return [];
        }

        $toasts = array_values(array_filter($_SESSION['site_toasts'], static function ($toast) {
            return is_array($toast) && !empty($toast['message']);
        }));

        unset($_SESSION['site_toasts']);

        self::$lastSiteToasts = $toasts;

        return $toasts;
    }

    public static function getLastSiteToasts(): array
    {
        return self::$lastSiteToasts;
    }

    /**
     * Verificar tiempo de sesión (para timeout)
     */
    public static function checkTimeout() {
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }
        return true;
    }
    
    /**
     * Rate limiting por sesión
     */
    public static function checkRateLimit($action, $limit, $window = 3600) {
        $key = "rate_limit_{$action}";
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // Limpiar intentos antiguos
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Verificar límite
        if (count($_SESSION[$key]) >= $limit) {
            return false;
        }
        
        // Registrar intento actual
        $_SESSION[$key][] = $now;
        return true;
    }

    /**
     * Verificar si la acción alcanzó el límite sin registrar un nuevo intento
     */
    public static function isRateLimited($action, $limit, $window = 3600) {
        $key = "rate_limit_{$action}";
        $now = time();

        if (!isset($_SESSION[$key])) {
            return false;
        }

        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        return count($_SESSION[$key]) >= $limit;
    }

    /**
     * Obtener intentos restantes para rate limiting
     */
    public static function getRemainingAttempts($action, $limit, $window = 3600) {
        $key = "rate_limit_{$action}";
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            return $limit;
        }
        
        // Limpiar intentos antiguos
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        return max(0, $limit - count($_SESSION[$key]));
    }

    public static function userHasRole(string $role): bool {
        if (!self::isAuthenticated()) {
            return false;
        }

        $expected = self::normalizeRole($role);
        $sessionRole = self::normalizeRole($_SESSION['user_role'] ?? null);

        if (self::roleSatisfies($sessionRole, $expected) && self::hasValidRoleSignature($sessionRole)) {
            $lastVerified = (int)($_SESSION['user_role_verified_at'] ?? 0);
            if ($lastVerified > 0 && (time() - $lastVerified) <= self::ROLE_REVALIDATE_SECONDS) {
                return true;
            }
        }

        $currentRole = self::refreshUserRole();
        return $currentRole !== null && self::roleSatisfies($currentRole, $expected);
    }

    private static function hasValidRoleSignature(string $role): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $signature = $_SESSION['user_role_signature'] ?? '';
        if ($signature === '') {
            return false;
        }

        $expected = self::signRole((int)$_SESSION['user_id'], self::normalizeRole($role));
        return hash_equals($expected, $signature);
    }

    private static function signRole(int $userId, string $role): string {
        $key = ROLE_SIGNATURE_KEY ?: (SESSION_SIGNATURE_KEY ?: 'lucaton-signature');
        return hash_hmac('sha256', $userId . '|' . $role, $key);
    }

    private static function refreshUserRole(): ?string {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        try {
            $userModel = new User();
            $record = $userModel->findById((int)$userId);
        } catch (Exception $exception) {
            Logger::warning('Unable to refresh user role', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }

        if (!$record) {
            self::logout();
            return null;
        }

        $role = self::normalizeRole($record['role'] ?? ($record['rol'] ?? 'user'));

        $_SESSION['user_role'] = $role;
        $_SESSION['user_role_signature'] = self::signRole((int)$userId, $role);
        $_SESSION['user_role_verified_at'] = time();

        self::storeUserProfileFields($record);
        if (isset($record['email_verified_at'])) {
            $_SESSION['email_verified'] = !empty($record['email_verified_at']);
        }

        return $role;
    }

    private static function normalizeRole($role): string {
        $normalized = strtolower(trim((string)$role));
        return $normalized !== '' ? $normalized : 'user';
    }

    private static function getRoleRank(string $role): int {
        $normalized = self::normalizeRole($role);
        return self::ROLE_HIERARCHY[$normalized] ?? 0;
    }

    private static function roleSatisfies(string $currentRole, string $requiredRole): bool {
        return self::getRoleRank($currentRole) >= self::getRoleRank($requiredRole);
    }

    /**
     * Refresca campos visibles del usuario autenticado sin regenerar la sesión.
     */
    public static function updateUserProfile(array $user): void {
        if (!self::isAuthenticated()) {
            return;
        }

        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        $payloadUserId = (int)($user['id'] ?? 0);

        if ($sessionUserId === 0 || $payloadUserId === 0 || $sessionUserId !== $payloadUserId) {
            return;
        }

        self::storeUserProfileFields($user);

        if (isset($user['email_verified_at'])) {
            $_SESSION['email_verified'] = !empty($user['email_verified_at']);
        }

        if (isset($user['status'])) {
            $_SESSION['user_status'] = $user['status'];
        }
    }

    /**
     * Resolver un nombre amigable para mostrar en la interfaz
     */
    private static function resolveDisplayName($user) {
        if (is_object($user)) {
            $user = get_object_vars($user);
        }

        if (!empty($user['first_name']) || !empty($user['last_name'])) {
            return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        }

        if (!empty($user['nombre'])) {
            return $user['nombre'];
        }

        if (!empty($user['username'])) {
            return $user['username'];
        }

        return $user['email'] ?? 'Usuario';
    }

    /**
     * Guarda en sesión los campos básicos del perfil (nombre, email, avatar, estado).
     */
    private static function storeUserProfileFields($user): void
    {
        if (is_object($user)) {
            $user = get_object_vars($user);
        }

        if (!is_array($user)) {
            return;
        }

        $email = $user['email'] ?? null;
        if ($email !== null) {
            $_SESSION['user_email'] = $email;
        } elseif (!isset($_SESSION['user_email'])) {
            $_SESSION['user_email'] = '';
        }

        if (isset($user['username']) && $user['username'] !== '') {
            $_SESSION['user_username'] = $user['username'];
        } elseif (!isset($_SESSION['user_username'])) {
            $_SESSION['user_username'] = null;
        }

        $_SESSION['user_name'] = self::resolveDisplayName($user);

        if (isset($user['status'])) {
            $_SESSION['user_status'] = $user['status'];
        }

        if (self::payloadHasAvatar($user)) {
            $avatarValue = self::extractAvatarValue($user);
            $normalizedAvatar = self::normalizeAvatarUrl($avatarValue);

            if ($normalizedAvatar !== null) {
                $_SESSION['user_avatar'] = $normalizedAvatar;
            } else {
                unset($_SESSION['user_avatar']);
            }
        }
    }

    /**
     * Normaliza una URL de avatar relativa o absoluta para alinearla al APP_URL actual.
     */
    public static function normalizeAvatarUrl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $avatar = trim($value);
        if ($avatar === '') {
            return null;
        }

        $normalizedAppUrl = rtrim(APP_URL, '/');
        $appBasePath = parse_url($normalizedAppUrl, PHP_URL_PATH) ?? '';
        $appBasePath = rtrim($appBasePath, '/');
        $appEndsWithPublic = $appBasePath !== '' && substr($appBasePath, -7) === '/public';

        $stripBasePath = static function (string $path) use ($appBasePath): string {
            $normalizedPath = '/' . ltrim($path, '/');

            if ($appBasePath !== '' && $appBasePath !== '/') {
                $baseWithSlash = $appBasePath[0] === '/' ? $appBasePath : '/' . $appBasePath;
                $baseWithSlash = rtrim($baseWithSlash, '/');

                if (strpos($normalizedPath, $baseWithSlash . '/') === 0) {
                    $normalizedPath = substr($normalizedPath, strlen($baseWithSlash));
                    if ($normalizedPath === '' || $normalizedPath[0] !== '/') {
                        $normalizedPath = '/' . ltrim($normalizedPath, '/');
                    }
                }
            }

            return $normalizedPath;
        };

        $buildAbsolute = static function (string $path) use ($normalizedAppUrl, $stripBasePath, $appEndsWithPublic): string {
            $normalizedPath = $stripBasePath($path);

            if (!$appEndsWithPublic && strpos($normalizedPath, '/storage/avatars/') === 0 && strpos($normalizedPath, '/public/storage/avatars/') !== 0) {
                $normalizedPath = '/public' . $normalizedPath;
            }

            return $normalizedAppUrl . $normalizedPath;
        };

        $managedPrefixes = ['/public/storage/avatars/', '/storage/avatars/'];

        if (preg_match('/^https?:\/\//i', $avatar)) {
            $avatarPath = parse_url($avatar, PHP_URL_PATH) ?: '';
            if ($avatarPath !== '') {
                $normalizedPath = $stripBasePath($avatarPath);
                foreach ($managedPrefixes as $prefix) {
                    if (strpos($normalizedPath, $prefix) === 0) {
                        return $buildAbsolute($normalizedPath);
                    }
                }
            }
            return $avatar;
        }

        if (strpos($avatar, '//') === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            $rebuilt = $scheme . $avatar;
            $avatarPath = parse_url($rebuilt, PHP_URL_PATH) ?: '';
            if ($avatarPath !== '') {
                $normalizedPath = $stripBasePath($avatarPath);
                foreach ($managedPrefixes as $prefix) {
                    if (strpos($normalizedPath, $prefix) === 0) {
                        return $buildAbsolute($normalizedPath);
                    }
                }
            }
            return $rebuilt;
        }

        return $buildAbsolute($avatar);
    }

    private static function payloadHasAvatar($user): bool
    {
        if (is_array($user)) {
            return array_key_exists('avatar_url', $user) || array_key_exists('avatar', $user);
        }

        if (is_object($user)) {
            return property_exists($user, 'avatar_url') || property_exists($user, 'avatar');
        }

        return false;
    }

    private static function extractAvatarValue($user): ?string
    {
        if (is_array($user)) {
            if (array_key_exists('avatar_url', $user)) {
                return $user['avatar_url'];
            }

            if (array_key_exists('avatar', $user)) {
                return $user['avatar'];
            }

            return null;
        }

        if (is_object($user)) {
            if (property_exists($user, 'avatar_url')) {
                return $user->avatar_url;
            }

            if (property_exists($user, 'avatar')) {
                return $user->avatar;
            }

            return null;
        }

        return null;
    }
}
?>
