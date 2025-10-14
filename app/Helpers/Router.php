<?php
/**
 * Router - Sistema de enrutamiento para Lucatón
 * Maneja rutas, parámetros y middleware
 */

class Router {
    private $routes = [];
    private $middlewareStack = [];
    
    public function get($uri, $action) {
        $this->addRoute('GET', $uri, $action);
    }
    
    public function post($uri, $action) {
        $this->addRoute('POST', $uri, $action);
    }
    
    public function put($uri, $action) {
        $this->addRoute('PUT', $uri, $action);
    }
    
    public function delete($uri, $action) {
        $this->addRoute('DELETE', $uri, $action);
    }
    
    public function group($options, $callback) {
        $previousMiddleware = $this->middlewareStack;
        
        if (isset($options['middleware'])) {
            $this->middlewareStack[] = $options['middleware'];
        }
        
        $callback($this);
        
        $this->middlewareStack = $previousMiddleware;
    }
    
    private function addRoute($method, $uri, $action) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $this->middlewareStack
        ];
    }
    
    public function dispatch($method, $uri) {
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
            if ($uri === '') {
                $uri = '/';
            }
        }
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertToRegex($route['uri']);
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extraer parámetros de la URL
                array_shift($matches); // Remover el match completo
                
                // Ejecutar middleware
                foreach ($route['middleware'] as $middleware) {
                    if (!$this->executeMiddleware($middleware)) {
                        return; // Middleware bloqueó la ejecución
                    }
                }
                
                // Ejecutar acción del controlador
                $this->executeAction($route['action'], $matches);
                return;
            }
        }
        
        // Ruta no encontrada
        $this->handle404();
    }
    
    private function convertToRegex($uri) {
        // Convertir {param} a grupos de captura regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }
    
    private function executeMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                return $this->authMiddleware();
            case 'admin':
                return $this->adminMiddleware();
            case 'csrf':
                return $this->csrfMiddleware();
            default:
                return true;
        }
    }
    
    private function authMiddleware() {
        if (!isset($_SESSION['user_id'])) {
            SessionHelper::setFlash('warning', 'Debes iniciar sesión para continuar.');
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? null;
            self::redirect('/login');
        }
        return true;
    }

    private function adminMiddleware() {
        if (!SessionHelper::isAuthenticated()) {
            SessionHelper::setFlash('warning', 'Debes iniciar sesión como administrador para continuar.');
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? null;
            self::redirect('/login');
        }

        if (SessionHelper::userHasRole('admin')) {
            return true;
        }

        http_response_code(403);
        if (file_exists(VIEWS_PATH . '/errors/403.php')) {
            include VIEWS_PATH . '/errors/403.php';
        } else {
            echo '<h1>403 - Acceso denegado</h1>';
        }
        exit;
    }

    private function csrfMiddleware() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[CSRF_TOKEN_NAME] ?? '';
            if (!hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
                http_response_code(403);
                if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                    echo json_encode(['error' => 'Token CSRF inválido']);
                } else {
                    SessionHelper::setFlash('error', 'Tu sesión caducó. Por favor intenta nuevamente.');
                    self::redirect('/');
                }
                exit;
            }
        }
        return true;
    }
    
    private function executeAction($action, $params = []) {
        if (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            
            $controllerClass = $controller;
            $controllerFile = APP_PATH . '/Controllers/' . $controllerClass . '.php';
            
            if (!file_exists($controllerFile)) {
                throw new Exception("Controlador no encontrado: $controllerClass");
            }
            
            require_once $controllerFile;
            
            if (!class_exists($controllerClass)) {
                throw new Exception("Clase de controlador no encontrada: $controllerClass");
            }
            
            $instance = new $controllerClass();
            
            if (!method_exists($instance, $method)) {
                throw new Exception("Método no encontrado: $controllerClass@$method");
            }
            
            // Llamar al método con parámetros
            call_user_func_array([$instance, $method], $params);
        } elseif (is_callable($action)) {
            call_user_func_array($action, $params);
        }
    }
    
    private function handle404() {
        http_response_code(404);
        
        if (file_exists(VIEWS_PATH . '/errors/404.php')) {
            include VIEWS_PATH . '/errors/404.php';
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
    }
    
    /**
     * Generar URL con parámetros
     */
    public static function url($path = '', $params = []) {
        $baseUrl = rtrim(APP_URL, '/');
        $url = $baseUrl . '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Redirigir a una URL
     */
    public static function redirect($path, $status = 302) {
        header('Location: ' . self::url($path), true, $status);
        exit;
    }
}
?>
