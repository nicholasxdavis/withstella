<?php
// Health check endpoint for Coolify
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'message' => 'Stella is running'
]);



