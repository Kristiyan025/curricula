<?php
namespace App\Utils;

/**
 * Simple Router Implementation
 */
class Router
{
    private array $routes = [];
    private array $params = [];
    
    /**
     * Add a GET route
     */
    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add a POST route
     */
    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add a PUT route
     */
    public function put(string $path, string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete(string $path, string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Add a route to the routing table
     */
    private function addRoute(string $method, string $path, string $handler): void
    {
        // Convert path parameters to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler,
        ];
    }
    
    /**
     * Dispatch the request to the appropriate controller
     */
    public function dispatch(string $method, string $uri): void
    {
        // Handle PUT and DELETE via POST with _method field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $this->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($route['handler']);
                return;
            }
        }
        
        // No route found
        $this->notFound();
    }
    
    /**
     * Call the controller handler
     */
    private function callHandler(string $handler): void
    {
        list($controllerName, $method) = explode('@', $handler);
        
        // Build full controller class name
        $controllerClass = 'App\\Controllers\\' . $controllerName;
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new \Exception("Method {$method} not found in {$controllerClass}");
        }
        
        // Call the controller method with parameters
        call_user_func_array([$controller, $method], $this->params);
    }
    
    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        
        // Check if API request
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => true,
                'message' => 'Ресурсът не е намерен'
            ]);
        } else {
            include BASE_PATH . '/src/Views/errors/404.php';
        }
    }
    
    /**
     * Get route parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
