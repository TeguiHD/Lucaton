<?php
/**
 * Punto de entrada principal de Lucatón
 * Maneja todas las solicitudes HTTP
 */

// Incluir configuración y bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Inicializar sesión
SessionHelper::start();

try {
    // Cargar rutas
    $router = require_once ROOT_PATH . '/config/routes.php';
    
    // Obtener método HTTP y URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remover el prefijo del proyecto si existe
    $basePath = '/Tesis';
    if (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    
    // Asegurar que la URI comience con /
    if (empty($uri) || $uri === '') {
        $uri = '/';
    }
    
    // Despachar la ruta
    $router->dispatch($method, $uri);
    
} catch (Exception $e) {
    // Log del error
    Logger::error('Error en index.php: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Mostrar página de error
    http_response_code(500);
    
    if (env('APP_DEBUG', false)) {
        echo '<h1>Error del servidor</h1>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Línea:</strong> ' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        if (file_exists(VIEWS_PATH . '/errors/500.php')) {
            include VIEWS_PATH . '/errors/500.php';
        } else {
            echo '<h1>500 - Error interno del servidor</h1>';
            echo '<p>Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.</p>';
        }
    }
}
?>