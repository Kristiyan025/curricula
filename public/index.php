<?php
/**
 * Curricula - Application Entry Point
 * 
 * Faculty of Mathematics and Informatics, Sofia University
 * Schedule Management System
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require_once BASE_PATH . '/src/Utils/Autoloader.php';

// Load configuration
$config = require BASE_PATH . '/config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Initialize the application
use App\Core\Application;

$app = new Application($config);
$app->run();