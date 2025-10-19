<?php
/**
 * Mark User as Pro
 * Simple endpoint to upgrade user to pro plan
 * Used as fallback when returning from Stripe
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database credentials
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    // Get user ID from headers or session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('User authentication required');
    }
    
    // Get database connection
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Update user plan to pro
    $stmt = $pdo->prepare("
        UPDATE users 
        SET plan_type = 'pro',
            subscription_status = 'active',
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$userId]);
    
    // Check if update was successful
    if ($stmt->rowCount() > 0) {
        // Log the upgrade
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO activities (user_id, type, description, created_at)
                VALUES (?, 'upgrade', 'Upgraded to Pro plan', NOW())
            ");
            $logStmt->execute([$userId]);
        } catch (Exception $e) {
            // Ignore activity logging errors
        }
        
        // Update session
        $_SESSION['user_plan'] = 'pro';
        
        echo json_encode([
            'success' => true,
            'message' => 'Plan upgraded to Pro successfully',
            'plan' => 'pro'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Plan already up to date',
            'plan' => 'pro'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

