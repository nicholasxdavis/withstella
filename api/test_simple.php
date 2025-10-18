<?php
/**
 * Simple API Test Endpoint
 * Returns basic JSON to test if the API is working
 */

// Disable display_errors to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

// Handle OPTIONS for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Basic test response
    $response = [
        'success' => true,
        'message' => 'API is working correctly',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'headers' => [
            'x_user_id' => $_SERVER['HTTP_X_USER_ID'] ?? 'Not provided',
            'x_user_email' => $_SERVER['HTTP_X_USER_EMAIL'] ?? 'Not provided',
            'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'Provided' : 'Not provided',
            'x_api_key' => isset($_SERVER['HTTP_X_API_KEY']) ? 'Provided' : 'Not provided'
        ]
    ];
    
    // If POST request, include the body
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $response['post_data'] = $input;
        $response['post_json'] = json_decode($input, true);
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>
