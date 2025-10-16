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

// Para el servidor de desarrollo PHP integrado, no incluir el path del script
$isDevServer = isset($_SERVER['SERVER_SOFTWARE']) && 
               strpos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false;

if ($isDevServer) {
    // En servidor de desarrollo, usar solo el host sin path
    $basePath = '';
} else {
    // En servidor web normal, calcular el path base
    $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/.');
}

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

// Configuración básica de correo saliente
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@lucaton.local'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Lucatón'));

$assetVersionEnv = env('ASSET_VERSION');
if ($assetVersionEnv !== null && $assetVersionEnv !== '') {
    $assetVersion = $assetVersionEnv;
} else {
    $assetVersionSeed = '';
    foreach ([
        ROOT_PATH . '/public/assets/css/app.css',
        ROOT_PATH . '/public/assets/css/aliases.css',
        ROOT_PATH . '/public/assets/js/app.js',
    ] as $assetCandidate) {
        if (file_exists($assetCandidate)) {
            $assetVersionSeed .= (string)filemtime($assetCandidate);
        }
    }

    $assetVersion = $assetVersionSeed !== ''
        ? substr(hash('sha256', $assetVersionSeed), 0, 12)
        : (string)time();
}

define('ASSET_VERSION', $assetVersion);

// Cargar helper de base de datos// Incluir helpers
require_once ROOT_PATH . '/app/Helpers/Database.php';
require_once ROOT_PATH . '/app/Helpers/Logger.php';
require_once ROOT_PATH . '/app/Helpers/SessionHelper.php';
require_once ROOT_PATH . '/app/Helpers/Router.php';
require_once ROOT_PATH . '/app/Helpers/AssetHelper.php';

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
require_once ROOT_PATH . '/app/Models/CampaignAppeal.php';

// Incluir servicios
require_once ROOT_PATH . '/app/Services/AvatarUploadService.php';
require_once ROOT_PATH . '/app/Services/CampaignMediaUploadService.php';
require_once ROOT_PATH . '/app/Services/CampaignLifecycleMailer.php';
require_once ROOT_PATH . '/app/Services/CampaignMilestoneNotifier.php';
require_once ROOT_PATH . '/app/Services/AuditLogReader.php';
require_once ROOT_PATH . '/app/Services/SupportTicketStore.php';
require_once ROOT_PATH . '/app/Services/AITextService.php';

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
require_once ROOT_PATH . '/app/Controllers/SupportController.php';

// Configurar archivos y uploads
define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 10485760)); // 10MB
define('UPLOAD_ALLOWED_TYPES', env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf'));
define('STORAGE_PUBLIC_PATH', env('STORAGE_PUBLIC_PATH', 'public/storage/uploads'));
define('STORAGE_PRIVATE_PATH', env('STORAGE_PRIVATE_PATH', 'storage/private'));
define('STORAGE_AI_PATH', env('STORAGE_AI_PATH', 'storage/ai_files'));

// Configurar IA
define('OPENROUTER_API_KEY', env('OPENROUTER_API_KEY', ''));
define('OPENROUTER_MODEL', env('OPENROUTER_MODEL', 'tngtech/deepseek-r1t2-chimera:free'));
define('OPENROUTER_BASE_URL', env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'));
define('OPENROUTER_MAX_TOKENS', (int)env('OPENROUTER_MAX_TOKENS', 640));
define('GOOGLE_AI_API_KEYS', env('GOOGLE_AI_API_KEYS', ''));
define('GOOGLE_AI_TEXT_MODEL', env('GOOGLE_AI_TEXT_MODEL', 'gemini-1.5-flash'));
define('GOOGLE_AI_API_BASE_URL', env('GOOGLE_AI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1'));

// Configurar límites de seguridad
define('RATE_LIMIT_LOGIN', (int)env('RATE_LIMIT_LOGIN', 5));
define('RATE_LIMIT_LOGIN_WINDOW', (int)env('RATE_LIMIT_LOGIN_WINDOW', 900));
define('RATE_LIMIT_AI_REQUESTS', (int)env('RATE_LIMIT_AI_REQUESTS', 10));
define('RATE_LIMIT_WINDOW', (int)env('RATE_LIMIT_WINDOW', 3600));

// Configurar sesiones
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));
define('SESSION_NAME', env('SESSION_NAME', 'lucaton_session'));
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));
define('SESSION_SIGNATURE_KEY', env('SESSION_SIGNATURE_KEY', hash('sha256', APP_NAME . '|' . DB_NAME . '|' . APP_ENV)));
define('ROLE_SIGNATURE_KEY', env('ROLE_SIGNATURE_KEY', hash('sha256', SESSION_SIGNATURE_KEY . '|role-signature')));

// Configurar logs
define('LOG_LEVEL', env('LOG_LEVEL', 'info'));
define('LOG_PATH', env('LOG_PATH', 'storage/logs'));

// Función helper para generar URLs de assets
function asset_url($path) {
    $path = ltrim($path, '/');
    
    // Detectar si estamos en el servidor de desarrollo PHP integrado
    $isDevServer = isset($_SERVER['SERVER_SOFTWARE']) && 
                   strpos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false;
    
    if ($isDevServer) {
        // Servidor de desarrollo PHP integrado sirviendo desde public/
        return APP_URL . '/assets/' . $path;
    } else {
        // Servidor web normal (Apache/Nginx)
        return APP_URL . '/public/assets/' . $path;
    }
}

// Función helper para generar URLs públicas
function public_url($path) {
    $path = ltrim($path, '/');
    
    // Detectar si estamos en el servidor de desarrollo
    $isDevServer = isset($_SERVER['SERVER_SOFTWARE']) && 
                   strpos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false;
    
    if ($isDevServer) {
        // Servidor de desarrollo PHP integrado sirviendo desde public/
        return APP_URL . '/' . $path;
    } else {
        // Servidor web normal (Apache/Nginx)
        return APP_URL . '/public/' . $path;
    }
}
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
