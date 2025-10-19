<?php

// Simple database configuration for plain PHP
// Works with both Laravel and plain PHP contexts

// Use a global constant to store config so it's always available
if (!defined('DB_CONFIG_LOADED')) {
    define('DB_CONFIG_LOADED', true);
    
    $GLOBALS['dbConfig'] = [
        'host' => getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'default',
        'username' => getenv('DB_USERNAME') ?: 'mariadb',
        'password' => getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ];
}

// Set local variable for backward compatibility
$dbConfig = $GLOBALS['dbConfig'];

/**
 * Get a database connection
 * @return PDO Database connection
 * @throws Exception if connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        // Get config from global scope
        $config = $GLOBALS['dbConfig'];
        
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Always return the config array, even on subsequent includes
return $GLOBALS['dbConfig'];
