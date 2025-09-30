<?php
/**
 * Lucatón - Punto de entrada principal
 * Plataforma de crowdfunding social con asistencia IA
 */

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constantes de la aplicación
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Cargar configuración
require_once CONFIG_PATH . '/bootstrap.php';

// Inicializar sesión segura
require_once APP_PATH . '/Helpers/SessionHelper.php';
SessionHelper::start();

// Cargar enrutador
require_once APP_PATH . '/Helpers/Router.php';

// Cargar todas las rutas desde el archivo de configuración
$router = require CONFIG_PATH . '/routes.php';

// Procesar la ruta actual
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remover el directorio base dinámico si existe (soporte para subcarpeta)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$guessedBasePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
$configuredBasePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';

// Normalizar valores equivalentes a raíz
$normalizeBasePath = function (string $path): string {
    if ($path === '/' || $path === '\\') {
        return '';
    }
    return $path;
};

$guessedBasePath = $normalizeBasePath($guessedBasePath);
$configuredBasePath = $normalizeBasePath($configuredBasePath);
$basePath = $guessedBasePath ?: $configuredBasePath;

// Si estamos en servidor de desarrollo PHP (puerto 8000), no usar basePath
if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8000') {
    $basePath = '';
}

if ($basePath && strpos($uri, $basePath) === 0) {
    $trimmedUri = substr($uri, strlen($basePath));
    if ($trimmedUri === '' || $trimmedUri[0] === '/') {
        $uri = $trimmedUri;
    }
}

// Normalizar URI vacía (p.ej. /Tesis sin slash final) para despachar correctamente
if ($uri === '' || $uri === false) {
    $uri = '/';
}

// Ejecutar enrutador
try {
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    // Manejo de errores
    http_response_code(500);
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<h1>Error 500</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        include VIEWS_PATH . '/errors/500.php';
    }
}
