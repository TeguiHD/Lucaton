<?php
/**
 * SessionHelper - Manejo seguro de sesiones para Lucatón
 * Implementa medidas de seguridad según requerimientos OWASP
 */

class SessionHelper {
    
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_name'] = self::resolveDisplayName($user);
        $_SESSION['user_role'] = $user['role'] ?? ($user['rol'] ?? 'user');
        $_SESSION['user_status'] = $user['status'] ?? 'active';
        $_SESSION['email_verified'] = !empty($user['email_verified_at']);
        $_SESSION['login_time'] = time();

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
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user',
            'status' => $_SESSION['user_status'] ?? 'active',
            'email_verified' => !empty($_SESSION['email_verified'])
        ];
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
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
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

    /**
     * Resolver un nombre amigable para mostrar en la interfaz
     */
    private static function resolveDisplayName($user) {
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
}
?>
