<?php
/**
 * Simple health check endpoint
 * Tests basic PHP functionality and database connection
 */

// Prevent any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'status' => 'ok',
    'checks' => []
];

// Check 1: Basic PHP
$result['checks']['php_working'] = true;

// Check 2: Can we require files?
try {
    $dbConfigPath = __DIR__ . '/../config/database.php';
    if (!file_exists($dbConfigPath)) {
        $result['checks']['config_file'] = [
            'status' => 'fail',
            'error' => 'File not found: ' . $dbConfigPath
        ];
    } else {
        $result['checks']['config_file'] = [
            'status' => 'pass',
            'path' => $dbConfigPath
        ];
    }
} catch (Exception $e) {
    $result['checks']['config_file'] = [
        'status' => 'fail',
        'error' => $e->getMessage()
    ];
}

// Check 3: Can we load database config?
try {
    require_once __DIR__ . '/../config/database.php';
    $result['checks']['config_loaded'] = [
        'status' => 'pass',
        'message' => 'Database config loaded'
    ];
    
    // Check if dbConfig exists
    if (isset($dbConfig)) {
        $result['checks']['dbConfig_variable'] = [
            'status' => 'pass',
            'host' => $dbConfig['host'] ?? 'not set',
            'database' => $dbConfig['database'] ?? 'not set'
        ];
    } else {
        $result['checks']['dbConfig_variable'] = [
            'status' => 'fail',
            'error' => '$dbConfig variable not found'
        ];
    }
    
    // Check if getDBConnection function exists
    if (function_exists('getDBConnection')) {
        $result['checks']['getDBConnection_exists'] = [
            'status' => 'pass',
            'message' => 'Function exists'
        ];
        
        // Try to call it
        try {
            $db = getDBConnection();
            if ($db instanceof PDO) {
                $result['checks']['database_connection'] = [
                    'status' => 'pass',
                    'message' => 'Connected successfully'
                ];
                
                // Try a simple query
                try {
                    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC);
                    $result['checks']['database_query'] = [
                        'status' => 'pass',
                        'user_count' => $count['cnt']
                    ];
                } catch (PDOException $e) {
                    $result['checks']['database_query'] = [
                        'status' => 'fail',
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $result['checks']['database_connection'] = [
                    'status' => 'fail',
                    'error' => 'getDBConnection did not return PDO instance'
                ];
            }
        } catch (Exception $e) {
            $result['checks']['database_connection'] = [
                'status' => 'fail',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $result['checks']['getDBConnection_exists'] = [
            'status' => 'fail',
            'error' => 'Function does not exist'
        ];
    }
} catch (Exception $e) {
    $result['checks']['config_loaded'] = [
        'status' => 'fail',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// Check 4: Environment variables
$result['checks']['environment'] = [
    'DB_HOST' => getenv('DB_HOST') ?: 'not set',
    'DB_DATABASE' => getenv('DB_DATABASE') ?: 'not set',
    'DB_USERNAME' => getenv('DB_USERNAME') ? 'set' : 'not set',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ? 'set (hidden)' : 'not set'
];

echo json_encode($result, JSON_PRETTY_PRINT);


