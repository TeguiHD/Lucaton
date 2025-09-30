<?php
/**
 * Bootstrap de configuración para Lucatón
 * Carga variables de entorno y configuraciones iniciales
 */

// Definir ROOT_PATH dinámicamente si no está definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Definir APP_PATH para la aplicación
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/app');
}

// Definir VIEWS_PATH para las vistas
if (!defined('VIEWS_PATH')) {
    define('VIEWS_PATH', ROOT_PATH . '/views');
}

// Cargar variables de entorno
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Saltar comentarios
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remover comillas si existen
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Función helper para obtener variables de entorno
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Configurar zona horaria
date_default_timezone_set(env('APP_TIMEZONE', 'America/Santiago'));

// Configurar base de datos
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'lucaton_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Configurar aplicación
define('APP_NAME', env('APP_NAME', 'Lucatón'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');
// Calcular APP_URL dinámicamente para la carpeta instalada y solo honrar .env cuando coincide
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$hostHeader = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($script)), '/.');
$computedAppUrl = $scheme . '://' . $hostHeader . ($basePath ? $basePath : '');

$envAppUrl = env('APP_URL');
$resolvedAppUrl = $computedAppUrl;

if (!empty($envAppUrl)) {
    $envScheme = parse_url($envAppUrl, PHP_URL_SCHEME) ?: $scheme;
    $envHost = parse_url($envAppUrl, PHP_URL_HOST) ?: '';
    $envPort = parse_url($envAppUrl, PHP_URL_PORT);
    $envPathRaw = parse_url($envAppUrl, PHP_URL_PATH);
    $envPath = $envPathRaw !== null ? rtrim($envPathRaw, '/') : '';

    $currentHost = parse_url($computedAppUrl, PHP_URL_HOST) ?: $hostHeader;
    $currentPort = parse_url($computedAppUrl, PHP_URL_PORT);
    if ($currentPort === null && preg_match('/:(\d+)$/', $hostHeader, $matches)) {
        $currentPort = (int)$matches[1];
    }

    $defaultEnvPort = $envScheme === 'https' ? 443 : 80;
    $defaultCurrentPort = $scheme === 'https' ? 443 : 80;
    $normalizedEnvPort = $envPort !== null ? (int)$envPort : $defaultEnvPort;
    $normalizedCurrentPort = $currentPort !== null ? (int)$currentPort : $defaultCurrentPort;

    $pathsMatch = ($envPath === '' && $basePath === '') || $envPath === $basePath;
    $hostsMatch = $envHost === '' || strcasecmp($envHost, $currentHost) === 0;
    $portsMatch = $normalizedEnvPort === $normalizedCurrentPort;

    if ($hostsMatch && $pathsMatch && $portsMatch) {
        $resolvedAppUrl = rtrim($envAppUrl, '/');
    }
}

define('APP_URL', $resolvedAppUrl);

// Información institucional del proyecto (prototipo académico)
define('PROJECT_OWNER_NAME', env('PROJECT_OWNER_NAME', 'Proyecto Lucatón — Tesis Universidad Bernardo O\'Higgins'));
define('PROJECT_OWNER_EMAIL', env('PROJECT_OWNER_EMAIL', 'nlopetegui@pregrado.ubo.cl'));
define('PROJECT_DISCLAIMER', env('PROJECT_DISCLAIMER', 'Prototipo académico sin fines comerciales. Los datos de contacto se proveen solo para fines universitarios.'));

// Cargar helper de base de datos// Incluir helpers
require_once ROOT_PATH . '/app/Helpers/Database.php';
require_once ROOT_PATH . '/app/Helpers/Logger.php';
require_once ROOT_PATH . '/app/Helpers/SessionHelper.php';
require_once ROOT_PATH . '/app/Helpers/Router.php';

// Incluir middleware
require_once ROOT_PATH . '/app/Middleware/AuthMiddleware.php';

// Incluir modelos
require_once ROOT_PATH . '/app/Models/User.php';
require_once ROOT_PATH . '/app/Models/Campaign.php';
require_once ROOT_PATH . '/app/Models/CampaignCategory.php';
require_once ROOT_PATH . '/app/Models/Donation.php';
require_once ROOT_PATH . '/app/Models/NewsArticle.php';
require_once ROOT_PATH . '/app/Models/NewsCategory.php';
require_once ROOT_PATH . '/app/Models/Notification.php';

// Incluir servicios
require_once ROOT_PATH . '/app/Services/AvatarUploadService.php';

// Incluir controladores
require_once ROOT_PATH . '/app/Controllers/HomeController.php';
require_once ROOT_PATH . '/app/Controllers/AuthController.php';
require_once ROOT_PATH . '/app/Controllers/CampaignController.php';
require_once ROOT_PATH . '/app/Controllers/DonationController.php';
require_once ROOT_PATH . '/app/Controllers/UserController.php';
require_once ROOT_PATH . '/app/Controllers/AdminController.php';
require_once ROOT_PATH . '/app/Controllers/NewsController.php';
require_once ROOT_PATH . '/app/Controllers/NewsAdminController.php';
require_once ROOT_PATH . '/app/Controllers/NotificationController.php';
require_once ROOT_PATH . '/app/Controllers/NotificationAdminController.php';

// Configurar archivos y uploads
define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 10485760)); // 10MB
define('UPLOAD_ALLOWED_TYPES', env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf'));
define('STORAGE_PUBLIC_PATH', env('STORAGE_PUBLIC_PATH', 'public/storage/uploads'));
define('STORAGE_PRIVATE_PATH', env('STORAGE_PRIVATE_PATH', 'storage/private'));
define('STORAGE_AI_PATH', env('STORAGE_AI_PATH', 'storage/ai_files'));

// Configurar IA
define('OPENAI_API_KEY', env('OPENAI_API_KEY', ''));
define('OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini'));
define('OPENAI_MAX_TOKENS', (int)env('OPENAI_MAX_TOKENS', 500));
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
define('GEMINI_MODEL', env('GEMINI_MODEL', 'gemini-1.5-flash'));

// Configurar límites de seguridad
define('RATE_LIMIT_LOGIN', (int)env('RATE_LIMIT_LOGIN', 5));
define('RATE_LIMIT_AI_REQUESTS', (int)env('RATE_LIMIT_AI_REQUESTS', 10));
define('RATE_LIMIT_WINDOW', (int)env('RATE_LIMIT_WINDOW', 3600));

// Configurar sesiones
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));
define('SESSION_NAME', env('SESSION_NAME', 'lucaton_session'));
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));

// Configurar logs
define('LOG_LEVEL', env('LOG_LEVEL', 'info'));
define('LOG_PATH', env('LOG_PATH', 'storage/logs'));
define('LOG_MAX_FILES', (int)env('LOG_MAX_FILES', 30));

// Configurar modo académico
define('RESEARCH_MODE', env('RESEARCH_MODE', 'false') === 'true');
define('METRICS_ENABLED', env('METRICS_ENABLED', 'false') === 'true');
define('AB_TESTING_ENABLED', env('AB_TESTING_ENABLED', 'false') === 'true');

// Autoloader simple para clases
spl_autoload_register(function ($class) {
    // Convertir namespace a ruta de archivo
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    // Buscar en subdirectorios comunes
    $directories = ['Controllers', 'Models', 'Middleware', 'Services', 'Helpers'];
    
    foreach ($directories as $dir) {
        $file = APP_PATH . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Configurar manejo de errores personalizado
if (!APP_DEBUG) {
    error_reporting(0);
    ini_set('display_errors', 0);
    
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Log del error
        error_log("Error: $message in $file on line $line");
        
        // Mostrar página de error genérica
        http_response_code(500);
        include VIEWS_PATH . '/errors/500.php';
        exit;
    });
}

// Configurar headers de seguridad básicos
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configurar PHP para mayor seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
?>
