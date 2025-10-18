<?php
/**
 * Activities API
 * Handles activity tracking and retrieval
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
try {
    $config = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user ID from headers
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
$userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User authentication required']);
    exit;
}

$userId = (int)$userId;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    if ($method === 'POST' && $action === 'track') {
        // Track a new activity
        $data = json_decode(file_get_contents('php://input'), true);
        
        $type = $data['type'] ?? 'general';
        $description = $data['description'] ?? '';
        $metadata = isset($data['metadata']) ? json_encode($data['metadata']) : null;
        
        if (empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Description is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activities (user_id, type, description, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $type, $description]);
        
        echo json_encode([
            'success' => true,
            'activity_id' => $pdo->lastInsertId()
        ]);
        
    } elseif ($method === 'GET' && $action === 'list') {
        // Get recent activities
        $limit = intval($_GET['limit'] ?? 20);
        $limit = min($limit, 100); // Cap at 100
        
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name, u.email
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ? OR u.workspace_owner_id = ? OR a.user_id IN (
                SELECT u2.id FROM users u2 WHERE u2.workspace_owner_id = ?
            )
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $userId, $userId, $limit]);
        $activities = $stmt->fetchAll();
        
        // Format activities for frontend
        $formattedActivities = array_map(function($activity) {
            return [
                'id' => $activity['id'],
                'type' => $activity['type'],
                'description' => $activity['description'],
                'user_name' => $activity['full_name'] ?? 'Unknown',
                'user_email' => $activity['email'] ?? '',
                'created_at' => $activity['created_at'],
                'time_ago' => timeAgo($activity['created_at'])
            ];
        }, $activities);
        
        echo json_encode([
            'success' => true,
            'activities' => $formattedActivities
        ]);
        
    } elseif ($method === 'GET' && $action === 'stats') {
        // Get activity statistics
        $days = intval($_GET['days'] ?? 30);
        
        // Total activities
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM activities
            WHERE user_id = ? OR user_id IN (
                SELECT u2.id FROM users u2 WHERE u2.workspace_owner_id = ?
            )
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$userId, $userId, $days]);
        $total = $stmt->fetch()['total'];
        
        // Activities by type
        $stmt = $pdo->prepare("
            SELECT type, COUNT(*) as count
            FROM activities
            WHERE user_id = ? OR user_id IN (
                SELECT u2.id FROM users u2 WHERE u2.workspace_owner_id = ?
            )
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY type
            ORDER BY count DESC
        ");
        $stmt->execute([$userId, $userId, $days]);
        $byType = $stmt->fetchAll();
        
        // Activities by day
        $stmt = $pdo->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM activities
            WHERE user_id = ? OR user_id IN (
                SELECT u2.id FROM users u2 WHERE u2.workspace_owner_id = ?
            )
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId, $userId, $days]);
        $byDay = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_activities' => $total,
                'by_type' => $byType,
                'by_day' => $byDay,
                'period_days' => $days
            ]
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use: track, list, or stats'
        ]);
    }
} catch (Exception $e) {
    error_log("Activities API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Calculate time ago string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j', strtotime($datetime));
}
?>
