<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\User;
use App\Services\UserService;

/**
 * API Users Controller
 * 
 * RESTful API for user data (admin only for most operations).
 */
class ApiUsersController extends BaseController
{
    private UserService $userService;
    
    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }
    
    /**
     * List users
     * GET /api/users
     */
    public function index(): void
    {
        $this->requireRole('ADMIN');
        
        $role = $this->getQuery('role');
        
        if ($role) {
            $users = $this->userService->getUsersByRole($role);
        } else {
            $users = User::all();
        }
        
        $this->jsonSuccess([
            'users' => array_map(fn($u) => [
                'id' => $u->id,
                'email' => $u->email,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'is_active' => (bool) $u->is_active,
            ], $users)
        ]);
    }
    
    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function show(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $user = User::find($id);
        
        if (!$user) {
            $this->jsonError('User not found', 404);
            return;
        }
        
        $roles = $this->userService->getUserRoles($id);
        
        $this->jsonSuccess([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'is_active' => (bool) $user->is_active,
                'roles' => array_column($roles, 'role'),
            ]
        ]);
    }
    
    /**
     * Create user
     * POST /api/users
     */
    public function store(): void
    {
        $this->requireRole('ADMIN');
        
        $data = $this->getJsonBody();
        
        // Validate required fields
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->jsonError("Field '$field' is required", 400);
                return;
            }
        }
        
        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'roles' => $data['roles'] ?? [],
        ];
        
        $result = $this->userService->createUser($userData);
        
        if ($result['success']) {
            $this->jsonSuccess([
                'user' => [
                    'id' => $result['id'],
                    'email' => $data['email'],
                ]
            ], 'User created');
        } else {
            $this->jsonError($result['message'], 400);
        }
    }
    
    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        
        $user = User::find($id);
        if (!$user) {
            $this->jsonError('User not found', 404);
            return;
        }
        
        $data = $this->getJsonBody();
        
        $updateData = [];
        
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['first_name'])) $updateData['first_name'] = $data['first_name'];
        if (isset($data['last_name'])) $updateData['last_name'] = $data['last_name'];
        if (isset($data['is_active'])) $updateData['is_active'] = (bool) $data['is_active'];
        if (!empty($data['password'])) $updateData['password'] = $data['password'];
        if (isset($data['roles'])) $updateData['roles'] = $data['roles'];
        
        $result = $this->userService->updateUser($id, $updateData);
        
        if ($result['success']) {
            $this->jsonSuccess([], 'User updated');
        } else {
            $this->jsonError($result['message'], 400);
        }
    }
    
    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        
        if ($this->userService->deleteUser($id)) {
            $this->jsonSuccess([], 'User deleted');
        } else {
            $this->jsonError('Failed to delete user', 400);
        }
    }
    
    /**
     * Get lecturers
     * GET /api/users/lecturers
     */
    public function lecturers(): void
    {
        $users = $this->userService->getUsersByRole('LECTURER');
        
        $this->jsonSuccess([
            'lecturers' => array_map(fn($u) => [
                'id' => $u->id,
                'email' => $u->email,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => $u->first_name . ' ' . $u->last_name,
            ], $users)
        ]);
    }
    
    /**
     * Get assistants
     * GET /api/users/assistants
     */
    public function assistants(): void
    {
        $users = $this->userService->getUsersByRole('ASSISTANT');
        
        $this->jsonSuccess([
            'assistants' => array_map(fn($u) => [
                'id' => $u->id,
                'email' => $u->email,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => $u->first_name . ' ' . $u->last_name,
            ], $users)
        ]);
    }
}
