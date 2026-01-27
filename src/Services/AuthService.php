<?php
namespace App\Services;

use App\Models\User;
use App\Core\Application;
use App\Utils\Logger;

/**
 * Authentication Service
 */
class AuthService
{
    private $session;
    private $logger;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->session = $app->getSession();
        $this->logger = $app->getLogger();
    }
    
    /**
     * Attempt to log in a user by email or faculty number
     * @return array|null Returns user data array on success, null on failure
     */
    public function login(string $identifier, string $password): ?array
    {
        // Try to find user by email first, then by faculty number
        $user = User::findByEmail($identifier);
        if (!$user) {
            $user = User::findByFacultyNumber($identifier);
        }
        
        if (!$user) {
            return null;
        }
        
        if (!$user->is_active) {
            return null;
        }
        
        if (!$user->verifyPassword($password)) {
            return null;
        }
        
        // Get user roles
        $roles = $user->getRoles();
        
        // Set session data
        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
        $this->session->set('user_roles', $roles);
        $this->session->set('user_name', $user->full_name);
        $this->session->set('faculty_number', $user->faculty_number);
        
        // Store full user data in session for easy access
        $userData = [
            'id' => $user->id,
            'faculty_number' => $user->faculty_number,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'first_name' => $user->first_name ?? '',
            'last_name' => $user->last_name ?? '',
            'is_active' => $user->is_active,
            'roles' => $roles
        ];
        $this->session->setUser($userData);
        
        // Log the event
        $this->logger->log(Logger::USER_LOGIN, [
            'user_id' => $user->id,
            'faculty_number' => $user->faculty_number
        ]);
        
        return $userData;
    }
    
    /**
     * Log out the current user
     */
    public function logout(): void
    {
        $userId = $this->session->getUserId();
        
        if ($userId) {
            $this->logger->log(Logger::USER_LOGOUT, ['user_id' => $userId]);
        }
        
        $this->session->destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->session->isLoggedIn();
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser(): ?User
    {
        $userId = $this->session->getUserId();
        
        if (!$userId) {
            return null;
        }
        
        return User::find($userId);
    }
    
    /**
     * Check if current user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->session->hasRole($role);
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        return $this->session->isAdmin();
    }
    
    /**
     * Require authentication
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            if ($this->isApiRequest()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'message' => 'Необходима е автентикация']);
                exit;
            }
            
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Require admin role
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            if ($this->isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'message' => 'Нямате права за тази операция']);
                exit;
            }
            
            http_response_code(403);
            include BASE_PATH . '/src/Views/errors/403.php';
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole(string $role): void
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            if ($this->isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => true, 'message' => 'Нямате права за тази операция']);
                exit;
            }
            
            http_response_code(403);
            include BASE_PATH . '/src/Views/errors/403.php';
            exit;
        }
    }
    
    /**
     * Check if this is an API request
     */
    private function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
    }
}
