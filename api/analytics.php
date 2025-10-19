<?php
/**
 * Analytics Tracking API
 * Lightweight endpoint for tracking events from websites
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-User-ID');

require_once __DIR__ . '/../api/check_pro.php';

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
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Try X-User-ID first (for dashboard stats viewing)
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;

// If no X-User-ID, require API key (for external tracking)
if (!$userId) {
    $apiKey = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(sk_[a-f0-9]+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $apiKey = $matches[1];
        }
    }
    if (!$apiKey && isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    }

    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'API key or authentication required']);
        exit;
    }

    // Validate API key and get user
    try {
        require_once __DIR__ . '/../app/Models/ApiKey.php';
        $apiKeyModel = new ApiKey($pdo);
        $user = $apiKeyModel->validate($apiKey);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid API key']);
            exit;
        }
        
        $userId = $user['id'];
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Authentication error']);
        exit;
    }
} else {
    $userId = (int)$userId;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'track';

try {
    if ($method === 'POST' && $action === 'track') {
        // Track an event
        $data = json_decode(file_get_contents('php://input'), true);
        
        $eventType = $data['event_type'] ?? 'pageview';
        $eventName = $data['event_name'] ?? null;
        $pageUrl = $data['page_url'] ?? null;
        $pageTitle = $data['page_title'] ?? null;
        $referrer = $data['referrer'] ?? null;
        $sessionId = $data['session_id'] ?? null;
        $metadata = isset($data['metadata']) ? json_encode($data['metadata']) : null;
        
        // Get client info
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO analytics_events (
                user_id, event_type, event_name, page_url, page_title,
                referrer, user_agent, ip_address, session_id, metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $eventType, $eventName, $pageUrl, $pageTitle,
            $referrer, $userAgent, $ipAddress, $sessionId, $metadata
        ]);
        
        echo json_encode([
            'success' => true,
            'event_id' => $pdo->lastInsertId()
        ]);
        
    } elseif ($method === 'GET' && $action === 'stats') {
        // 🔒 PRO PLAN REQUIRED - Analytics viewing is a Pro feature
        requireProPlan($pdo, $userId);
        
        // Get analytics stats
        $days = intval($_GET['days'] ?? 7);
        
        // Total events
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM analytics_events
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$userId, $days]);
        $total = $stmt->fetch()['total'];
        
        // Events by type
        $stmt = $pdo->prepare("
            SELECT event_type, COUNT(*) as count
            FROM analytics_events
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY event_type
        ");
        $stmt->execute([$userId, $days]);
        $byType = $stmt->fetchAll();
        
        // Events by day
        $stmt = $pdo->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM analytics_events
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId, $days]);
        $byDay = $stmt->fetchAll();
        
        // Top pages
        $stmt = $pdo->prepare("
            SELECT page_url, page_title, COUNT(*) as count
            FROM analytics_events
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND page_url IS NOT NULL
            GROUP BY page_url, page_title
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$userId, $days]);
        $topPages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_events' => $total,
                'by_type' => $byType,
                'by_day' => $byDay,
                'top_pages' => $topPages,
                'period_days' => $days
            ]
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use: track or stats'
        ]);
    }
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}

