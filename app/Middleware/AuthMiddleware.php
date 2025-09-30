<?php
/**
 * Middleware de Autenticación
 * Verifica que el usuario esté autenticado antes de acceder a rutas protegidas
 */

class AuthMiddleware {
    
    /**
     * Verificar autenticación del usuario
     */
    public static function requireAuth() {
        if (!SessionHelper::isAuthenticated()) {
            // Guardar la URL a la que intentaba acceder
            SessionHelper::setFlashMessage('error', 'Debes iniciar sesión para acceder a esta página');
            
            // Redirigir al login
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Verificar que el usuario NO esté autenticado (para páginas como login/register)
     */
    public static function requireGuest() {
        if (SessionHelper::isAuthenticated()) {
            // Si ya está autenticado, redirigir al dashboard
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Verificar que el usuario sea administrador
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!SessionHelper::isAdmin()) {
            SessionHelper::setFlashMessage('error', 'No tienes permisos para acceder a esta página');
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Verificar que el usuario sea el propietario del recurso o admin
     */
    public static function requireOwnerOrAdmin($resourceUserId) {
        self::requireAuth();
        
        $currentUserId = SessionHelper::getUserId();
        
        if ($currentUserId != $resourceUserId && !SessionHelper::isAdmin()) {
            SessionHelper::setFlashMessage('error', 'No tienes permisos para acceder a este recurso');
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Verificar verificación de email
     */
    public static function requireEmailVerified() {
        self::requireAuth();
        
        $user = SessionHelper::getUser();
        
        if (!$user['email_verified']) {
            SessionHelper::setFlashMessage('warning', 'Debes verificar tu email antes de continuar');
            header('Location: /auth/verify-email');
            exit;
        }
    }
    
    /**
     * Verificar límites de rate limiting
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $action . '_' . $ip;
        
        if (SessionHelper::isRateLimited($key, $maxAttempts, $timeWindow)) {
            http_response_code(429);
            SessionHelper::setFlashMessage('error', 'Demasiados intentos. Intenta de nuevo más tarde.');
            
            // Log del intento bloqueado
            Logger::warning('Rate limit exceeded', [
                'action' => $action,
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            header('Location: /');
            exit;
        }
    }
    
    /**
     * Middleware para verificar CSRF token
     */
    public static function verifyCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!SessionHelper::verifyCSRFToken($token)) {
                http_response_code(403);
                SessionHelper::setFlashMessage('error', 'Token de seguridad inválido');
                
                Logger::warning('CSRF token verification failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
                
                header('Location: /');
                exit;
            }
        }
    }
    
    /**
     * Verificar que la cuenta no esté bloqueada
     */
    public static function requireActiveAccount() {
        self::requireAuth();
        
        $user = SessionHelper::getUser();
        
        if ($user['status'] !== 'active') {
            $message = match($user['status']) {
                'suspended' => 'Tu cuenta ha sido suspendida. Contacta al soporte.',
                'banned' => 'Tu cuenta ha sido bloqueada permanentemente.',
                'pending' => 'Tu cuenta está pendiente de activación.',
                default => 'Tu cuenta no está activa.'
            };
            
            SessionHelper::setFlashMessage('error', $message);
            SessionHelper::logout();
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Verificar permisos específicos
     */
    public static function requirePermission($permission) {
        self::requireAuth();
        
        $user = SessionHelper::getUser();
        
        // Los admins tienen todos los permisos
        if ($user['role'] === 'admin') {
            return;
        }
        
        // Verificar permisos específicos según el rol
        $permissions = self::getRolePermissions($user['role']);
        
        if (!in_array($permission, $permissions)) {
            SessionHelper::setFlashMessage('error', 'No tienes permisos para realizar esta acción');
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Obtener permisos por rol
     */
    private static function getRolePermissions($role) {
        $permissions = [
            'user' => [
                'create_campaign',
                'edit_own_campaign',
                'delete_own_campaign',
                'make_donation',
                'view_own_donations'
            ],
            'moderator' => [
                'create_campaign',
                'edit_own_campaign',
                'delete_own_campaign',
                'make_donation',
                'view_own_donations',
                'moderate_campaigns',
                'view_reports',
                'suspend_users'
            ],
            'admin' => ['*'] // Todos los permisos
        ];
        
        return $permissions[$role] ?? [];
    }
    
    /**
     * Middleware para verificar límites de creación de campañas
     */
    public static function checkCampaignLimits() {
        self::requireAuth();
        
        $userId = SessionHelper::getUserId();
        $user = SessionHelper::getUser();
        
        // Los admins no tienen límites
        if ($user['role'] === 'admin') {
            return;
        }
        
        // Verificar límite de campañas activas
        $db = Database::getInstance();
        $activeCampaigns = $db->fetch(
            "SELECT COUNT(*) as count FROM campaigns 
             WHERE owner_id = ? AND status IN ('published', 'under_review')",
            [$userId]
        );
        
        $maxActiveCampaigns = $user['role'] === 'moderator' ? 10 : 3;
        
        if ($activeCampaigns['count'] >= $maxActiveCampaigns) {
            SessionHelper::setFlashMessage('error', 
                "No puedes tener más de {$maxActiveCampaigns} campañas activas al mismo tiempo");
            header('Location: /campaigns/my-campaigns');
            exit;
        }
    }
    
    /**
     * Verificar límites de donación
     */
    public static function checkDonationLimits($amount) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Verificar límite diario por IP
        $db = Database::getInstance();
        $dailyTotal = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM donations 
             WHERE donor_ip = ? AND DATE(created_at) = CURDATE()",
            [$ip]
        );
        
        $maxDailyAmount = 10000; // $10,000 por día por IP
        
        if (($dailyTotal['total'] + $amount) > $maxDailyAmount) {
            SessionHelper::setFlashMessage('error', 
                'Has alcanzado el límite diario de donaciones');
            header('Location: /');
            exit;
        }
        
        // Si está autenticado, verificar límites de usuario
        if (SessionHelper::isAuthenticated()) {
            $userId = SessionHelper::getUserId();
            
            $userDailyTotal = $db->fetch(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM donations 
                 WHERE supporter_id = ? AND DATE(created_at) = CURDATE()",
                [$userId]
            );
            
            $maxUserDailyAmount = 25000; // $25,000 por día por usuario
            
            if (($userDailyTotal['total'] + $amount) > $maxUserDailyAmount) {
                SessionHelper::setFlashMessage('error', 
                    'Has alcanzado tu límite diario de donaciones');
                header('Location: /');
                exit;
            }
        }
    }
    
    /**
     * Aplicar múltiples middlewares
     */
    public static function apply($middlewares) {
        foreach ($middlewares as $middleware => $params) {
            if (is_numeric($middleware)) {
                // Si no hay parámetros, el middleware está en $params
                $middleware = $params;
                $params = [];
            }
            
            switch ($middleware) {
                case 'auth':
                    self::requireAuth();
                    break;
                case 'guest':
                    self::requireGuest();
                    break;
                case 'admin':
                    self::requireAdmin();
                    break;
                case 'verified':
                    self::requireEmailVerified();
                    break;
                case 'active':
                    self::requireActiveAccount();
                    break;
                case 'csrf':
                    self::verifyCsrf();
                    break;
                case 'rate_limit':
                    $action = $params['action'] ?? 'default';
                    $max = $params['max'] ?? 5;
                    $window = $params['window'] ?? 300;
                    self::checkRateLimit($action, $max, $window);
                    break;
                case 'permission':
                    self::requirePermission($params['permission']);
                    break;
                case 'campaign_limits':
                    self::checkCampaignLimits();
                    break;
            }
        }
    }
}