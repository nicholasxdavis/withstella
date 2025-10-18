<?php
/**
 * Database Structure Checker
 * Run this to see what tables and columns exist
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #fff; padding: 20px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        pre { background: #2a2a2a; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #333; }
    </style>
</head>
<body>
    <h1>🔍 Database Structure Check</h1>
    
    <?php
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        echo "<p class='success'>✓ Connected to database</p>";
    } catch(PDOException $e) {
        die("<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>");
    }
    ?>

    <h2>📊 Tables</h2>
    <?php
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error listing tables: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>👥 Users Table Structure</h2>
    <?php
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error describing users table: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>📁 Assets Table Structure</h2>
    <?php
    try {
        $stmt = $pdo->query("DESCRIBE assets");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error describing assets table: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>🎨 Brand Kits Table Structure</h2>
    <?php
    try {
        $stmt = $pdo->query("DESCRIBE brand_kits");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error describing brand_kits table: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>👤 Sample Users</h2>
    <?php
    try {
        $stmt = $pdo->query("SELECT id, email, full_name, role, nextcloud_username, nextcloud_password FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Nextcloud User</th><th>Nextcloud Pass</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['nextcloud_username'] ?: 'NULL') . "</td>";
            echo "<td>" . ($user['nextcloud_password'] ? 'SET' : 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error fetching users: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>📊 Sample Assets</h2>
    <?php
    try {
        $stmt = $pdo->query("SELECT id, user_id, filename, share_token, file_size, mime_type FROM assets LIMIT 5");
        $assets = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Filename</th><th>Share Token</th><th>File Size</th><th>MIME Type</th></tr>";
        foreach ($assets as $asset) {
            echo "<tr>";
            echo "<td>{$asset['id']}</td>";
            echo "<td>{$asset['user_id']}</td>";
            echo "<td>{$asset['filename']}</td>";
            echo "<td>" . ($asset['share_token'] ?: 'NULL') . "</td>";
            echo "<td>" . ($asset['file_size'] ?: 'NULL') . "</td>";
            echo "<td>" . ($asset['mime_type'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error fetching assets: " . $e->getMessage() . "</p>";
    }
    ?>

</body>
</html>

