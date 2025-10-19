<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Suppress errors for cleaner JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/env.php';

try {
    // Get user ID from headers
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    $userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'User authentication required']);
        exit;
    }
    
    $userId = (int)$userId;
    
    // Get database connection
    $config = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get usage statistics
    $usage = [
        'kits' => getKitsCount($pdo, $userId),
        'users' => getUsersCount($pdo, $userId),
        'storage' => getStorageUsage($pdo, $userId)
    ];
    
    echo json_encode($usage);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getKitsCount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM brand_kits WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function getUsersCount($pdo, $userId) {
    try {
        // Get team size for the user's team
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM team_members tm
            JOIN users u ON u.team_id = tm.team_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 1; // At least the owner
    }
}

function getStorageUsage($pdo, $userId) {
    try {
        // Calculate total storage used by user's assets
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(file_size), 0) FROM assets 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $bytes = (int)$stmt->fetchColumn();
        
        // Convert bytes to GB
        return round($bytes / (1024 * 1024 * 1024), 2);
    } catch (Exception $e) {
        return 0;
    }
}
?>
