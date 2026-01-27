<?php
namespace App\Controllers;

use App\Core\Application;

/**
 * Base Controller
 */
abstract class BaseController
{
    protected Application $app;
    protected $db;
    protected $session;
    protected $logger;
    
    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->db = $this->app->getDb();
        $this->session = $this->app->getSession();
        $this->logger = $this->app->getLogger();
    }
    
    /**
     * Render a view
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = $this->app->getBasePath() . '/src/Views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $view");
        }
        
        // Extract data to variables
        extract($data);
        
        // Add common variables
        $user = $this->session->getUser();
        $flash = $this->session->getAllFlash();
        
        // Start output buffering
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        
        // Render with layout
        $layoutPath = $this->app->getBasePath() . '/src/Views/layouts/main.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }
    
    /**
     * Render partial view (no layout)
     */
    protected function renderPartial(string $view, array $data = []): void
    {
        $viewPath = $this->app->getBasePath() . '/src/Views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $view");
        }
        
        extract($data);
        require $viewPath;
    }
    
    /**
     * Send JSON response
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send success JSON response
     */
    protected function jsonSuccess(array $data = [], string $message = 'Success', int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
    
    /**
     * Send error JSON response
     */
    protected function jsonError(string $message, int $status = 400, array $errors = []): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Get POST data
     */
    protected function getPost(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get JSON body
     */
    protected function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }
    
    /**
     * Check if user is logged in
     */
    protected function requireAuth(): void
    {
        if (!$this->session->isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonError('Не сте влезли в системата', 401);
            }
            $this->session->setFlash('error', 'Моля, влезте в системата');
            $this->redirect('/login');
        }
    }
    
    /**
     * Check if user has role
     */
    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        
        $user = $this->session->getUser();
        $userRoles = $user['roles'] ?? [];
        
        if (!array_intersect($roles, $userRoles)) {
            if ($this->isApiRequest()) {
                $this->jsonError('Нямате права за тази операция', 403);
            }
            $this->session->setFlash('error', 'Нямате права за тази страница');
            $this->redirect('/');
        }
    }
    
    /**
     * Check if this is an API request
     */
    protected function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->getPost('_csrf') ?? $this->getHeader('X-CSRF-Token');
        return $this->session->validateCsrfToken($token ?? '');
    }
    
    /**
     * Get header value
     */
    protected function getHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }
    
    /**
     * Get current academic year and semester
     */
    protected function getCurrentAcademicPeriod(): array
    {
        $settings = \App\Models\AcademicSettings::getCurrentSettings();
        
        if ($settings) {
            return [
                'year' => $settings->academic_year ?? date('Y'),
                'semester' => $settings->semester ?? 'WINTER'
            ];
        }
        
        // Default: current calendar year and guess semester
        $month = (int) date('n');
        return [
            'year' => (int) date('Y'),
            'semester' => $month >= 9 || $month <= 1 ? 'WINTER' : 'SUMMER'
        ];
    }
    
    /**
     * Log an action with optional user ID
     */
    protected function log(string $action, array $data = [], ?int $userId = null): void
    {
        $this->logger->log($action, $data, $userId);
    }
}
