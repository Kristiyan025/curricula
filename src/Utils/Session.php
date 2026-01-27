<?php
namespace App\Utils;

/**
 * Session Management Class
 */
class Session
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Start the session
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['name']);
            session_set_cookie_params([
                'lifetime' => $this->config['lifetime'],
                'path' => $this->config['path'],
                'secure' => $this->config['secure'],
                'httponly' => $this->config['httponly'],
            ]);
            session_start();
        }
    }
    
    /**
     * Set a session value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a session value
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy the session
     */
    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
    
    /**
     * Set flash message (alias for flash)
     */
    public function setFlash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }
    
    /**
     * Set flash message
     */
    public function flash(string $key, string $message): void
    {
        $this->setFlash($key, $message);
    }
    
    /**
     * Get and remove flash message
     */
    public function getFlash(?string $key = null): ?string
    {
        if ($key === null) {
            $flash = $_SESSION['_flash'] ?? null;
            unset($_SESSION['_flash']);
            return is_array($flash) ? json_encode($flash) : $flash;
        }
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }
    
    /**
     * Get all flash messages
     */
    public function getAllFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user roles
     */
    public function getUserRoles(): array
    {
        return $_SESSION['user_roles'] ?? [];
    }
    
    /**
     * Check if current user has role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getUserRoles());
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }
    
    /**
     * Get current logged in user data
     */
    public function getUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'faculty_number' => $_SESSION['user_faculty_number'] ?? null,
            'full_name' => $_SESSION['user_full_name'] ?? null,
            'first_name' => $_SESSION['user_first_name'] ?? '',
            'last_name' => $_SESSION['user_last_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? null,
            'roles' => $_SESSION['user_roles'] ?? []
        ];
    }
    
    /**
     * Set user session data after login
     */
    public function setUser(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_faculty_number'] = $user['faculty_number'] ?? null;
        $_SESSION['user_full_name'] = $user['full_name'] ?? null;
        $_SESSION['user_first_name'] = $user['first_name'] ?? '';
        $_SESSION['user_last_name'] = $user['last_name'] ?? '';
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_roles'] = $user['roles'] ?? [];
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Get CSRF token
     */
    public function getCsrfToken(): string
    {
        return $this->generateCsrfToken();
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(string $token): bool
    {
        $storedToken = $_SESSION['csrf_token'] ?? '';
        return hash_equals($storedToken, $token);
    }
}
