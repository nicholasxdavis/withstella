<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-User-Email, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper function to send JSON response
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Database credentials
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    sendJson(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Get authenticated user info
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
$userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? null;

if (!$userId) {
    sendJson(['success' => false, 'message' => 'Not authenticated'], 401);
}

$userId = (int)$userId;

// Get request data
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Handle different actions
switch ($action) {
    case 'create':
        if ($method === 'POST') {
            handleCreateTicket($pdo, $userId, $userEmail, $data);
        }
        break;
    
    case 'list':
        if ($method === 'GET') {
            handleListTickets($pdo, $userId);
        }
        break;
    
    case 'get':
        if ($method === 'GET') {
            handleGetTicket($pdo, $userId, $_GET['id'] ?? null);
        }
        break;
    
    case 'update':
        if ($method === 'PUT') {
            handleUpdateTicket($pdo, $userId, $data);
        }
        break;
    
    case 'reply':
        if ($method === 'POST') {
            handleReplyTicket($pdo, $userId, $data);
        }
        break;
    
    default:
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Create a new support ticket
 */
function handleCreateTicket($pdo, $userId, $userEmail, $data) {
    try {
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');
        $email = trim($data['email'] ?? $userEmail ?? '');
        $priority = trim($data['priority'] ?? 'medium');
        
        // Validate required fields
        if (empty($subject) || empty($message) || empty($email)) {
            sendJson(['success' => false, 'message' => 'Subject, message, and email are required'], 422);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['success' => false, 'message' => 'Invalid email address'], 422);
        }
        
        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'medium';
        }
        
        // Create ticket
        $stmt = $pdo->prepare("
            INSERT INTO tickets (
                user_id,
                email,
                subject,
                message,
                priority,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, 'open', NOW(), NOW())
        ");
        $stmt->execute([$userId, $email, $subject, $message, $priority]);
        
        $ticketId = $pdo->lastInsertId();
        
        sendJson([
            'success' => true,
            'message' => 'Ticket created successfully',
            'ticket_id' => $ticketId
        ]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * List tickets (admin view or user's own tickets)
 */
function handleListTickets($pdo, $userId) {
    try {
        // Check if user is admin/owner
        $stmt = $pdo->prepare("SELECT role, workspace_owner_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $isAdmin = $user && in_array($user['role'], ['admin', 'owner']);
        
        if ($isAdmin) {
            // Admin can see all tickets
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name, u.email as user_email
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC
            ");
            $stmt->execute();
        } else {
            // Regular users see only their own tickets
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name, u.email as user_email
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$userId]);
        }
        
        $tickets = $stmt->fetchAll();
        
        // Get ticket replies count
        foreach ($tickets as &$ticket) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as reply_count FROM ticket_replies WHERE ticket_id = ?");
            $stmt->execute([$ticket['id']]);
            $result = $stmt->fetch();
            $ticket['reply_count'] = $result['reply_count'];
        }
        
        sendJson(['success' => true, 'tickets' => $tickets]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Get a specific ticket with replies
 */
function handleGetTicket($pdo, $userId, $ticketId) {
    try {
        if (!$ticketId) {
            sendJson(['success' => false, 'message' => 'Ticket ID required'], 400);
        }
        
        // Check if user can access this ticket
        $stmt = $pdo->prepare("SELECT role, workspace_owner_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $isAdmin = $user && in_array($user['role'], ['admin', 'owner']);
        
        if ($isAdmin) {
            // Admin can see any ticket
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name, u.email as user_email
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$ticketId]);
        } else {
            // Regular users can only see their own tickets
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name, u.email as user_email
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.id = ? AND t.user_id = ?
            ");
            $stmt->execute([$ticketId, $userId]);
        }
        
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            sendJson(['success' => false, 'message' => 'Ticket not found'], 404);
        }
        
        // Get ticket replies
        $stmt = $pdo->prepare("
            SELECT tr.*, u.full_name, u.email as user_email
            FROM ticket_replies tr
            LEFT JOIN users u ON tr.user_id = u.id
            WHERE tr.ticket_id = ?
            ORDER BY tr.created_at ASC
        ");
        $stmt->execute([$ticketId]);
        $replies = $stmt->fetchAll();
        
        $ticket['replies'] = $replies;
        
        sendJson(['success' => true, 'ticket' => $ticket]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Update ticket status/priority (admin only)
 */
function handleUpdateTicket($pdo, $userId, $data) {
    try {
        $ticketId = $data['ticket_id'] ?? null;
        $status = $data['status'] ?? null;
        $priority = $data['priority'] ?? null;
        
        if (!$ticketId) {
            sendJson(['success' => false, 'message' => 'Ticket ID required'], 400);
        }
        
        // Check if user is admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !in_array($user['role'], ['admin', 'owner'])) {
            sendJson(['success' => false, 'message' => 'Admin access required'], 403);
        }
        
        // Validate status and priority
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        
        $updates = [];
        $params = [];
        
        if ($status && in_array($status, $validStatuses)) {
            $updates[] = "status = ?";
            $params[] = $status;
        }
        
        if ($priority && in_array($priority, $validPriorities)) {
            $updates[] = "priority = ?";
            $params[] = $priority;
        }
        
        if (empty($updates)) {
            sendJson(['success' => false, 'message' => 'No valid updates provided'], 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $ticketId;
        
        $stmt = $pdo->prepare("UPDATE tickets SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);
        
        sendJson(['success' => true, 'message' => 'Ticket updated successfully']);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Add a reply to a ticket
 */
function handleReplyTicket($pdo, $userId, $data) {
    try {
        $ticketId = $data['ticket_id'] ?? null;
        $message = trim($data['message'] ?? '');
        
        if (!$ticketId || empty($message)) {
            sendJson(['success' => false, 'message' => 'Ticket ID and message are required'], 400);
        }
        
        // Check if user can reply to this ticket
        $stmt = $pdo->prepare("SELECT role, workspace_owner_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $isAdmin = $user && in_array($user['role'], ['admin', 'owner']);
        
        if ($isAdmin) {
            // Admin can reply to any ticket
            $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
        } else {
            // Regular users can only reply to their own tickets
            $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
            $stmt->execute([$ticketId, $userId]);
        }
        
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            sendJson(['success' => false, 'message' => 'Ticket not found or access denied'], 404);
        }
        
        // Add reply
        $stmt = $pdo->prepare("
            INSERT INTO ticket_replies (
                ticket_id,
                user_id,
                message,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$ticketId, $userId, $message]);
        
        // Update ticket's updated_at timestamp
        $stmt = $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$ticketId]);
        
        sendJson(['success' => true, 'message' => 'Reply added successfully']);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

