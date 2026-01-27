<?php
namespace App\Controllers;

use App\Services\AuthService;

/**
 * Auth Controller
 * 
 * Handles authentication (login/logout).
 */
class AuthController extends BaseController
{
    private AuthService $authService;
    
    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }
    
    /**
     * Show login form
     */
    public function loginForm(): void
    {
        // If already logged in, redirect to appropriate dashboard
        if ($this->session->isLoggedIn()) {
            $this->redirectToDashboard();
            return;
        }
        
        $this->render('auth/login', [
            'title' => 'Вход в системата'
        ]);
    }
    
    /**
     * Process login
     */
    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/login');
            return;
        }
        
        $email = trim($this->getPost('email', ''));
        $password = $this->getPost('password', '');
        
        // Validate input
        if (empty($email) || empty($password)) {
            $this->session->setFlash('error', 'Моля, въведете имейл и парола');
            $this->redirect('/login');
            return;
        }
        
        // Attempt login
        $user = $this->authService->login($email, $password);
        
        if (!$user) {
            $this->session->setFlash('error', 'Грешен имейл или парола');
            $this->redirect('/login');
            return;
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            $this->authService->logout();
            $this->session->setFlash('error', 'Акаунтът ви е деактивиран');
            $this->redirect('/login');
            return;
        }
        
        $this->session->setFlash('success', 'Успешен вход');
        $this->redirectToDashboard();
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        $this->authService->logout();
        $this->session->setFlash('success', 'Успешен изход');
        $this->redirect('/');
    }
    
    /**
     * Redirect to appropriate dashboard based on role
     */
    private function redirectToDashboard(): void
    {
        $user = $this->session->getUser();
        $roles = $user['roles'] ?? [];
        
        if (in_array('ADMIN', $roles)) {
            $this->redirect('/admin');
        } elseif (in_array('LECTURER', $roles)) {
            $this->redirect('/lecturer');
        } elseif (in_array('ASSISTANT', $roles)) {
            $this->redirect('/assistant');
        } elseif (in_array('STUDENT', $roles)) {
            $this->redirect('/student');
        } else {
            $this->redirect('/');
        }
    }
    
    /**
     * Show password reset form
     */
    public function forgotPasswordForm(): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Забравена парола'
        ]);
    }
    
    /**
     * Process password reset request
     */
    public function forgotPassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Невалиден CSRF токен');
            $this->redirect('/forgot-password');
            return;
        }
        
        $email = trim($this->getPost('email', ''));
        
        if (empty($email)) {
            $this->session->setFlash('error', 'Моля, въведете имейл');
            $this->redirect('/forgot-password');
            return;
        }
        
        // In a real implementation, this would send a reset email
        // For now, just show a message
        $this->session->setFlash('success', 
            'Ако съществува акаунт с този имейл, ще получите инструкции за възстановяване на паролата');
        $this->redirect('/login');
    }
}
