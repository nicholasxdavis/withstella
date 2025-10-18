<?php

/**
 * Stella Installation Script
 * Run this once to set up your Stella instance
 */

echo "🌟 Stella Installation\n";
echo "====================\n\n";

// Step 1: Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "   ✓ PHP " . PHP_VERSION . " detected\n\n";
} else {
    echo "   ✗ PHP 8.1+ required. You have " . PHP_VERSION . "\n";
    exit(1);
}

// Step 2: Check PDO MySQL extension
echo "2. Checking required extensions...\n";
if (extension_loaded('pdo_mysql')) {
    echo "   ✓ PDO MySQL extension loaded\n\n";
} else {
    echo "   ✗ PDO MySQL extension not found\n";
    echo "   Install it using: apt-get install php-mysql (Ubuntu/Debian)\n";
    exit(1);
}

// Step 3: Test database connection
echo "3. Testing database connection...\n";
$db_config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k',
    'username' => getenv('DB_USERNAME') ?: 'mariadb',
    'password' => getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO',
];

try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Connected to database: {$db_config['database']}\n\n";
} catch (PDOException $e) {
    echo "   ✗ Database connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    echo "   Please verify:\n";
    echo "   - MariaDB is running\n";
    echo "   - Database credentials are correct\n";
    echo "   - Database exists\n";
    exit(1);
}

// Step 4: Run migrations
echo "4. Running database migrations...\n";
require_once 'database/migrate.php';
echo "\n";

// Step 5: Create upload directories
echo "5. Creating upload directories...\n";
$directories = [
    'uploads',
    'uploads/assets',
    'uploads/brand-kits',
    'uploads/logos'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "   ✓ Created: $dir\n";
    } else {
        echo "   ✓ Exists: $dir\n";
    }
}

echo "\n";

// Step 6: Summary
echo "✅ Installation Complete!\n";
echo "=======================\n\n";
echo "Next steps:\n";
echo "1. Visit your domain in a browser\n";
echo "2. Click 'Get Started' to create an account\n";
echo "3. Start managing your brand!\n\n";
echo "Test the API: curl http://localhost/api/test-connection.php\n";
echo "Access homepage: http://localhost/index.html\n";
echo "Access dashboard: http://localhost/dashboard/\n\n";
echo "For Coolify deployment, see SETUP.md\n";

