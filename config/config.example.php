<?php
/**
 * Example configuration file
 * Copy this file to config.php and update with your settings
 */

return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'curricula',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    
    'app' => [
        'name' => 'Curricula',
        'debug' => true,
        'url' => 'http://localhost/curricula',
        'timezone' => 'Europe/Sofia'
    ],
    
    'session' => [
        'name' => 'curricula_session',
        'lifetime' => 7200, // 2 hours
        'secure' => false,  // Set to true in production with HTTPS
        'httponly' => true
    ],
    
    'scheduling' => [
        'population_size' => 50,
        'generations' => 500,
        'mutation_rate' => 0.1,
        'tournament_size' => 5,
        'max_time' => 1200, // 20 minutes
        'variants_count' => 3
    ],
    
    'academic' => [
        'mandatory_start_hour' => 8,
        'mandatory_end_hour' => 18,
        'elective_end_hour' => 22,
        'slot_duration_minutes' => 120,
        'days' => ['MON', 'TUE', 'WED', 'THU', 'FRI']
    ]
];
