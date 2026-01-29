<?php
namespace App\Core;

use App\Utils\Router;
use App\Utils\Session;
use App\Database\Database;
use App\Utils\Logger;

/**
 * Main Application Class
 * 
 * Bootstraps and runs the Curricula application
 */
class Application
{
    private array $config;
    private static ?Application $instance = null;
    private ?Database $db = null;
    private ?Router $router = null;
    private ?Session $session = null;
    private ?Logger $logger = null;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        self::$instance = $this;
        
        // Initialize components
        $this->initializeSession();
        $this->initializeDatabase();
        $this->initializeLogger();
        $this->initializeRouter();
    }
    
    /**
     * Get the application instance (singleton)
     */
    public static function getInstance(): ?Application
    {
        return self::$instance;
    }
    
    /**
     * Get configuration value
     */
    public function getConfig(string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Get database instance
     */
    public function getDb(): Database
    {
        return $this->db;
    }
    
    /**
     * Get session instance
     */
    public function getSession(): Session
    {
        return $this->session;
    }
    
    /**
     * Get logger instance
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    
    /**
     * Get router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Get base path of the application
     */
    public function getBasePath(): string
    {
        return dirname(__DIR__, 2);
    }
    
    /**
     * Initialize session handling
     */
    private function initializeSession(): void
    {
        $this->session = new Session($this->config['session']);
        $this->session->start();
    }
    
    /**
     * Initialize database connection
     */
    private function initializeDatabase(): void
    {
        $this->db = new Database($this->config['database']);
    }
    
    /**
     * Initialize logger
     */
    private function initializeLogger(): void
    {
        $this->logger = new Logger($this->db);
    }
    
    /**
     * Initialize router with routes
     */
    private function initializeRouter(): void
    {
        $this->router = new Router();
        $this->registerRoutes();
    }
    
    /**
     * Register all application routes
     */
    private function registerRoutes(): void
    {
        // Public routes
        $this->router->get('/', 'HomeController@index');
        $this->router->get('/login', 'AuthController@loginForm');
        $this->router->post('/login', 'AuthController@login');
        $this->router->get('/logout', 'AuthController@logout');
        
        // Public schedule viewing routes
        $this->router->get('/schedule/stream', 'HomeController@scheduleByStream');
        $this->router->get('/schedule/group', 'HomeController@scheduleByGroup');
        $this->router->get('/schedule/lecturer', 'HomeController@lecturerSchedule');
        $this->router->get('/schedule/room', 'HomeController@roomSchedule');
        $this->router->get('/schedule/tests', 'HomeController@testSchedule');
        $this->router->get('/schedule/exams', 'HomeController@examSchedule');
        
        // Schedule viewing (all authenticated users)
        $this->router->get('/schedules/weekly', 'ScheduleController@weeklySchedule');
        $this->router->get('/schedules/tests', 'ScheduleController@testSchedule');
        $this->router->get('/schedules/exams', 'ScheduleController@examSchedule');
        
        // API Routes - Authentication
        $this->router->post('/api/login', 'Api\AuthController@login');
        $this->router->post('/api/logout', 'Api\AuthController@logout');
        
        // API Routes - Users (Admin only)
        $this->router->get('/api/users', 'Api\UserController@index');
        $this->router->post('/api/users', 'Api\UserController@store');
        $this->router->get('/api/users/{id}', 'Api\UserController@show');
        $this->router->put('/api/users/{id}', 'Api\UserController@update');
        $this->router->delete('/api/users/{id}', 'Api\UserController@destroy');
        
        // API Routes - Courses
        $this->router->get('/api/courses', 'Api\CourseController@index');
        $this->router->post('/api/courses', 'Api\CourseController@store');
        $this->router->get('/api/courses/{id}', 'Api\CourseController@show');
        $this->router->put('/api/courses/{id}', 'Api\CourseController@update');
        $this->router->delete('/api/courses/{id}', 'Api\CourseController@destroy');
        
        // API Routes - Course Instances
        $this->router->get('/api/course_instances', 'Api\CourseInstanceController@index');
        $this->router->post('/api/course_instances', 'Api\CourseInstanceController@store');
        $this->router->get('/api/course_instances/{id}', 'Api\CourseInstanceController@show');
        $this->router->put('/api/course_instances/{id}', 'Api\CourseInstanceController@update');
        $this->router->delete('/api/course_instances/{id}', 'Api\CourseInstanceController@destroy');
        
        // API Routes - Majors/Streams/Groups
        $this->router->get('/api/majors', 'Api\MajorController@index');
        $this->router->post('/api/majors', 'Api\MajorController@store');
        $this->router->get('/api/majors/{id}', 'Api\MajorController@show');
        $this->router->get('/api/majors/{id}/streams', 'Api\MajorController@streams');
        $this->router->get('/api/streams/{id}/groups', 'Api\StreamController@groups');
        
        // API Routes - Rooms
        $this->router->get('/api/rooms', 'Api\RoomController@index');
        $this->router->post('/api/rooms', 'Api\RoomController@store');
        $this->router->get('/api/rooms/{id}', 'Api\RoomController@show');
        $this->router->put('/api/rooms/{id}', 'Api\RoomController@update');
        $this->router->delete('/api/rooms/{id}', 'Api\RoomController@destroy');
        
        // API Routes - Schedule Viewing
        $this->router->get('/api/schedules/weekly', 'Api\ScheduleController@weekly');
        $this->router->get('/api/schedules/tests', 'Api\ScheduleController@tests');
        $this->router->get('/api/schedules/exams', 'Api\ScheduleController@exams');
        
        // API Routes - Schedule Generation (Admin only)
        $this->router->post('/api/schedules/weekly/generate', 'Api\ScheduleGeneratorController@generateWeekly');
        $this->router->get('/api/schedules/weekly/variants', 'Api\ScheduleGeneratorController@weeklyVariants');
        $this->router->post('/api/schedules/weekly/select', 'Api\ScheduleGeneratorController@selectWeekly');
        
        $this->router->post('/api/schedules/tests/generate', 'Api\ScheduleGeneratorController@generateTests');
        $this->router->get('/api/schedules/tests/variants', 'Api\ScheduleGeneratorController@testVariants');
        $this->router->post('/api/schedules/tests/select', 'Api\ScheduleGeneratorController@selectTests');
        
        $this->router->post('/api/schedules/exams/generate', 'Api\ScheduleGeneratorController@generateExams');
        $this->router->get('/api/schedules/exams/variants', 'Api\ScheduleGeneratorController@examVariants');
        $this->router->post('/api/schedules/exams/select', 'Api\ScheduleGeneratorController@selectExams');
        
        // API Routes - Academic Phase (Admin only)
        $this->router->get('/api/settings/phase', 'Api\SettingsController@getPhase');
        $this->router->post('/api/settings/phase', 'Api\SettingsController@setPhase');
        
        // API Routes - Logs (Admin only)
        $this->router->get('/api/logs', 'Api\LogController@index');
        
        // Admin Panel Routes
        $this->router->get('/admin', 'AdminController@index');
        $this->router->get('/admin/users', 'AdminController@users');
        $this->router->get('/admin/courses', 'AdminController@courses');
        $this->router->get('/admin/courses/create', 'AdminController@courseCreate');
        $this->router->post('/admin/courses/create', 'AdminController@courseStore');
        $this->router->get('/admin/courses/{id}/edit', 'AdminController@courseEdit');
        $this->router->post('/admin/courses/{id}/edit', 'AdminController@courseUpdate');
        $this->router->post('/admin/courses/{id}/delete', 'AdminController@courseDelete');
        $this->router->get('/admin/courses/{id}/instances', 'AdminController@courseInstances');
        $this->router->get('/admin/majors', 'AdminController@majors');
        $this->router->get('/admin/rooms', 'AdminController@rooms');
        $this->router->get('/admin/rooms/create', 'AdminController@roomCreate');
        $this->router->post('/admin/rooms/create', 'AdminController@roomStore');
        $this->router->get('/admin/rooms/{id}/edit', 'AdminController@roomEdit');
        $this->router->post('/admin/rooms/{id}/edit', 'AdminController@roomUpdate');
        $this->router->post('/admin/rooms/{id}/delete', 'AdminController@roomDelete');
        $this->router->get('/admin/schedules', 'AdminController@scheduleGeneration');
        $this->router->get('/admin/schedule', 'AdminController@scheduleGeneration');
        $this->router->post('/admin/schedule/generate/weekly', 'AdminController@generateWeekly');
        $this->router->post('/admin/schedule/generate/tests', 'AdminController@generateTests');
        $this->router->post('/admin/schedule/generate/exams', 'AdminController@generateExams');
        $this->router->get('/admin/schedule/view/{id}', 'AdminController@viewVariant');
        $this->router->post('/admin/schedule/select/{id}', 'AdminController@selectVariant');
        $this->router->post('/admin/schedule/delete/{id}', 'AdminController@deleteVariant');
        $this->router->get('/admin/logs', 'AdminController@logs');
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $uri = $this->getRequestUri();
            $method = $_SERVER['REQUEST_METHOD'];
            
            $this->router->dispatch($method, $uri);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get the request URI
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove trailing slash except for root
        $uri = rtrim($uri, '/') ?: '/';
        
        return $uri;
    }
    
    /**
     * Handle exceptions
     */
    private function handleException(\Exception $e): void
    {
        $this->logger->log('ERROR', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        if ($this->config['app']['debug']) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
            echo 'Възникна грешка. Моля, опитайте отново по-късно.';
        }
    }
}
