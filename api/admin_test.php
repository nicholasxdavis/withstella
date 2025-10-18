<?php
/**
 * Admin API Test Endpoint
 * Tests database connection and basic functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => true,
    'tests' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

// Test 1: Check if database config exists
try {
    $dbConfigPath = __DIR__ . '/../config/database.php';
    if (file_exists($dbConfigPath)) {
        $response['tests'][] = [
            'name' => 'Database config file',
            'status' => 'pass',
            'path' => $dbConfigPath
        ];
        
        require_once $dbConfigPath;
        
        // Test 2: Try to get database connection
        try {
            $db = getDBConnection();
            $response['tests'][] = [
                'name' => 'Database connection',
                'status' => 'pass',
                'message' => 'Connected successfully'
            ];
            
            // Test 3: Try to query users table
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                $response['tests'][] = [
                    'name' => 'Query users table',
                    'status' => 'pass',
                    'user_count' => $count['count']
                ];
            } catch (PDOException $e) {
                $response['tests'][] = [
                    'name' => 'Query users table',
                    'status' => 'fail',
                    'error' => $e->getMessage()
                ];
            }
            
            // Test 4: Check all tables
            $tables = ['users', 'assets', 'brand_kits', 'team_members', 'activities', 'governance_rules', 'api_keys', 'analytics_events'];
            $tableStatus = [];
            
            foreach ($tables as $table) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
                    $count = $stmt->fetchColumn();
                    $tableStatus[$table] = [
                        'exists' => true,
                        'rows' => (int)$count
                    ];
                } catch (PDOException $e) {
                    $tableStatus[$table] = [
                        'exists' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $response['tests'][] = [
                'name' => 'All tables check',
                'status' => 'pass',
                'tables' => $tableStatus
            ];
            
        } catch (Exception $e) {
            $response['tests'][] = [
                'name' => 'Database connection',
                'status' => 'fail',
                'error' => $e->getMessage()
            ];
        }
        
    } else {
        $response['tests'][] = [
            'name' => 'Database config file',
            'status' => 'fail',
            'message' => 'File not found',
            'path' => $dbConfigPath
        ];
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Test 5: Check auth helper
try {
    $authHelperPath = __DIR__ . '/../api/auth_helper.php';
    if (file_exists($authHelperPath)) {
        require_once $authHelperPath;
        $response['tests'][] = [
            'name' => 'Auth helper file',
            'status' => 'pass',
            'path' => $authHelperPath
        ];
    } else {
        $response['tests'][] = [
            'name' => 'Auth helper file',
            'status' => 'fail',
            'message' => 'File not found',
            'path' => $authHelperPath
        ];
    }
} catch (Exception $e) {
    $response['tests'][] = [
        'name' => 'Auth helper file',
        'status' => 'fail',
        'error' => $e->getMessage()
    ];
}

// Environment info
$response['environment'] = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'db_host' => getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k',
    'db_database' => getenv('DB_DATABASE') ?: 'default'
];

echo json_encode($response, JSON_PRETTY_PRINT);
