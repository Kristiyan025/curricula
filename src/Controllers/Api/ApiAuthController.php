<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;

/**
 * API Auth Controller
 * 
 * Handles API authentication - login returns token for subsequent requests.
 */
class ApiAuthController extends BaseController
{
    private AuthService $authService;
    
    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }
    
    /**
     * Login and get token
     */
    public function login(): void
    {
        $data = $this->getJsonBody();
        
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $this->jsonError('Email and password are required', 400);
            return;
        }
        
        $user = $this->authService->login($email, $password);
        
        if (!$user) {
            $this->jsonError('Invalid credentials', 401);
            return;
        }
        
        if (!$user['is_active']) {
            $this->authService->logout();
            $this->jsonError('Account is deactivated', 403);
            return;
        }
        
        // Get roles
        $roles = $this->db->fetchAll(
            "SELECT role FROM user_role WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        
        $this->jsonSuccess([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'roles' => array_column($roles, 'role'),
            ],
            'session_id' => session_id(),
        ], 'Login successful');
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->jsonSuccess([], 'Logout successful');
    }
    
    /**
     * Get current user info
     */
    public function me(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->jsonError('Not authenticated', 401);
            return;
        }
        
        $user = $this->session->getUser();
        
        $this->jsonSuccess([
            'user' => $user
        ]);
    }
}
