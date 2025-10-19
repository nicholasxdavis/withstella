<?php
/**
 * Environment Variables Loader
 * Loads environment variables from .env file or system environment
 */

function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }
    
    if (!file_exists($path)) {
        // Try to load from system environment variables if .env doesn't exist
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Skip empty lines
        if (empty(trim($line))) {
            continue;
        }
        
        // Check if line contains '='
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Load environment variables
loadEnv();

// Ensure required Stripe environment variables are set
$required_vars = ['STRIPE_PK', 'STRIPE_SK', 'STRIPE_PROD', 'STRIPE_PRICE_ID'];
foreach ($required_vars as $var) {
    if (empty($_ENV[$var])) {
        error_log("Warning: Required environment variable $var is not set");
    }
}
?>
