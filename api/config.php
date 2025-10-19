<?php
/**
 * Config API - Serves frontend configuration
 * Returns environment variables that are safe to expose to frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment configuration
require_once __DIR__ . '/../config/env.php';

// Return safe config values
echo json_encode([
    'success' => true,
    'config' => [
        'stripe' => [
            'publishableKey' => $_ENV['STRIPE_PK'] ?? '',
            'priceId' => $_ENV['STRIPE_PRICE_ID'] ?? '',
            'isProduction' => ($_ENV['STRIPE_PROD'] ?? 'false') === 'true'
        ],
        'app' => [
            'name' => 'Stella',
            'version' => '1.0.0'
        ]
    ]
]);
?>

