<?php

/**
 * Stella Application Configuration
 */

return [
    'name' => 'Stella',
    'version' => '1.0.0',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'debug' => getenv('APP_DEBUG') === 'true' ? true : false,
    
    // Database
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k',
        'username' => getenv('DB_USERNAME') ?: 'mariadb',
        'password' => getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO',
        'charset' => 'utf8mb4',
    ],
    
    // Session
    'session' => [
        'lifetime' => 120, // minutes
        'cookie_name' => 'stella_session',
    ],
    
    // File uploads
    'uploads' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'application/pdf',
            'application/zip',
        ],
        'path' => __DIR__ . '/../uploads/',
    ],
];



