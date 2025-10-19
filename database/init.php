<?php
/**
 * Database initialization - creates tables if they don't exist
 * Can be included in other files or run standalone
 */

// Database credentials - Coolify/MariaDB
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

// Check if we're being included in an API file (suppress output)
$is_api_context = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) || 
                  (strpos($_SERVER['PHP_SELF'] ?? '', '/api/') !== false) ||
                  (defined('SUPPRESS_DB_OUTPUT') && SUPPRESS_DB_OUTPUT);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connected_host = $host;
    if (!$is_api_context) {
        echo "✓ Connected to database at: {$host}\n";
    }
} catch(PDOException $e) {
    if ($is_api_context) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    } else {
        die("✗ Database connection failed: " . $e->getMessage() . "\n");
    }
}

// Define tables to create
$tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            workspace_owner_id INT NULL,
            role VARCHAR(50) DEFAULT 'owner',
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nextcloud_username VARCHAR(255) NULL,
            nextcloud_password VARCHAR(255) NULL,
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_remember_token (remember_token),
            INDEX idx_workspace_owner (workspace_owner_id),
            FOREIGN KEY (workspace_owner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'password_reset_tokens' => "
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            email VARCHAR(255) PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'sessions' => "
        CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(255) PRIMARY KEY,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload TEXT NOT NULL,
            last_activity INT NOT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'brand_kits' => "
        CREATE TABLE IF NOT EXISTS brand_kits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            logo_url VARCHAR(255) NULL,
            share_token VARCHAR(64) NULL UNIQUE,
            primary_color VARCHAR(7) NULL,
            secondary_color VARCHAR(7) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'assets' => "
        CREATE TABLE IF NOT EXISTS assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            brand_kit_id INT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            file_url VARCHAR(255) NOT NULL,
            file_path VARCHAR(512) NULL,
            share_token VARCHAR(64) NULL UNIQUE,
            file_size INT NULL,
            nextcloud_file_id VARCHAR(255) NULL,
            nextcloud_etag VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_brand_kit_id (brand_kit_id),
            INDEX idx_type (type),
            INDEX idx_share_token (share_token),
            INDEX idx_nextcloud_file_id (nextcloud_file_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (brand_kit_id) REFERENCES brand_kits(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'team_members' => "
        CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            workspace_owner_id INT NOT NULL,
            member_email VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_owner (workspace_owner_id),
            INDEX idx_email (member_email),
            FOREIGN KEY (workspace_owner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'pending_invites' => "
        CREATE TABLE IF NOT EXISTS pending_invites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inviter_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'viewer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            INDEX idx_inviter (inviter_id),
            INDEX idx_email (email),
            INDEX idx_token (token),
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'activities' => "
        CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'governance_rules' => "
        CREATE TABLE IF NOT EXISTS governance_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            rule_type ENUM('tone', 'forbidden_words', 'required_words', 'max_length', 'min_length', 'regex_pattern') NOT NULL,
            rule_value TEXT NOT NULL,
            severity ENUM('warning', 'error') DEFAULT 'warning',
            enabled BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_user_enabled (user_id, enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'api_keys' => "
        CREATE TABLE IF NOT EXISTS api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            api_key VARCHAR(100) NOT NULL UNIQUE,
            last_used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_api_key (api_key),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'analytics_events' => "
        CREATE TABLE IF NOT EXISTS analytics_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            event_name VARCHAR(255),
            page_url TEXT,
            page_title VARCHAR(500),
            referrer TEXT,
            user_agent TEXT,
            ip_address VARCHAR(45),
            session_id VARCHAR(255),
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_user_event (user_id, event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_session (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'tickets' => "
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'ticket_replies' => "
        CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket_id (ticket_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'download_requests' => "
        CREATE TABLE IF NOT EXISTS download_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            requester_id INT NOT NULL,
            status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT NULL,
            notes TEXT NULL,
            INDEX idx_asset_id (asset_id),
            INDEX idx_requester_id (requester_id),
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at),
            FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
            FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'public_shares' => "
        CREATE TABLE IF NOT EXISTS public_shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            share_token VARCHAR(64) NOT NULL UNIQUE,
            is_active BOOLEAN DEFAULT TRUE,
            expires_at TIMESTAMP NULL,
            download_count INT DEFAULT 0,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_asset_id (asset_id),
            INDEX idx_share_token (share_token),
            INDEX idx_is_active (is_active),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'team_permissions' => "
        CREATE TABLE IF NOT EXISTS team_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            workspace_owner_id INT NOT NULL,
            permission_type ENUM('upload_assets', 'create_kits', 'download_assets', 'manage_shares') NOT NULL,
            allowed_roles JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_workspace_owner (workspace_owner_id),
            INDEX idx_permission_type (permission_type),
            FOREIGN KEY (workspace_owner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'billing_history' => "
        CREATE TABLE IF NOT EXISTS billing_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            stripe_invoice_id VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'usd',
            status VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_stripe_invoice_id (stripe_invoice_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// Create each table
$created = 0;
$errors = 0;

foreach ($tables as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        if (!$is_api_context) {
            echo "✓ Table '{$table_name}' ready\n";
        }
        $created++;
    } catch (PDOException $e) {
        if (!$is_api_context) {
            echo "✗ Error creating table '{$table_name}': {$e->getMessage()}\n";
        }
        $errors++;
    }
}

// Ensure governance_rules.config column exists and allows NULL
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM governance_rules LIKE 'config'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE governance_rules ADD COLUMN config JSON NULL AFTER rule_value");
        if (!$is_api_context) {
            echo "✓ Column 'config' added to 'governance_rules'\n";
        }
    } else {
        // Ensure it allows NULL to avoid insert errors
        $fullColStmt = $pdo->query("SHOW FULL COLUMNS FROM governance_rules LIKE 'config'");
        $fullCol = $fullColStmt ? $fullColStmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($fullCol && strtoupper($fullCol['Null']) === 'NO') {
            $pdo->exec("ALTER TABLE governance_rules MODIFY COLUMN config JSON NULL");
            if (!$is_api_context) {
                echo "✓ Column 'config' modified to allow NULL in 'governance_rules'\n";
            }
        }
    }
} catch (PDOException $e) {
    if (!$is_api_context) {
        echo "✗ Error ensuring governance_rules.config column: {$e->getMessage()}\n";
    }
}

// Ensure assets table has Nextcloud columns
try {
    // Check for nextcloud_file_id column
    $colStmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'nextcloud_file_id'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE assets ADD COLUMN nextcloud_file_id VARCHAR(255) NULL AFTER file_size");
        if (!$is_api_context) {
            echo "✓ Column 'nextcloud_file_id' added to 'assets'\n";
        }
    }
    
    // Check for nextcloud_etag column
    $colStmt = $pdo->query("SHOW COLUMNS FROM assets LIKE 'nextcloud_etag'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE assets ADD COLUMN nextcloud_etag VARCHAR(255) NULL AFTER nextcloud_file_id");
        if (!$is_api_context) {
            echo "✓ Column 'nextcloud_etag' added to 'assets'\n";
        }
    }
    
    // Add index for nextcloud_file_id if it doesn't exist
    $indexStmt = $pdo->query("SHOW INDEX FROM assets WHERE Key_name = 'idx_nextcloud_file_id'");
    $index = $indexStmt ? $indexStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$index) {
        $pdo->exec("ALTER TABLE assets ADD INDEX idx_nextcloud_file_id (nextcloud_file_id)");
        if (!$is_api_context) {
            echo "✓ Index 'idx_nextcloud_file_id' added to 'assets'\n";
        }
    }
} catch (PDOException $e) {
    if (!$is_api_context) {
        echo "✗ Error ensuring assets Nextcloud columns: {$e->getMessage()}\n";
    }
}

// Ensure users table has billing fields
try {
    // Check for plan_type column
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'plan_type'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN plan_type VARCHAR(20) DEFAULT 'free' AFTER password");
        if (!$is_api_context) {
            echo "✓ Column 'plan_type' added to 'users'\n";
        }
    }
    
    // Check for stripe_customer_id column
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'stripe_customer_id'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) NULL AFTER plan_type");
        if (!$is_api_context) {
            echo "✓ Column 'stripe_customer_id' added to 'users'\n";
        }
    }
    
    // Check for stripe_subscription_id column
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'stripe_subscription_id'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(255) NULL AFTER stripe_customer_id");
        if (!$is_api_context) {
            echo "✓ Column 'stripe_subscription_id' added to 'users'\n";
        }
    }
    
    // Check for subscription_status column
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'subscription_status'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN subscription_status VARCHAR(50) DEFAULT 'active' AFTER stripe_subscription_id");
        if (!$is_api_context) {
            echo "✓ Column 'subscription_status' added to 'users'\n";
        }
    }
    
    // Check for trial_ends_at column
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'trial_ends_at'");
    $col = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$col) {
        $pdo->exec("ALTER TABLE users ADD COLUMN trial_ends_at TIMESTAMP NULL AFTER subscription_status");
        if (!$is_api_context) {
            echo "✓ Column 'trial_ends_at' added to 'users'\n";
        }
    }
    
    // Add indexes for billing columns
    $indexStmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_plan_type'");
    $index = $indexStmt ? $indexStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$index) {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_plan_type (plan_type)");
        if (!$is_api_context) {
            echo "✓ Index 'idx_plan_type' added to 'users'\n";
        }
    }
    
    $indexStmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_stripe_customer_id'");
    $index = $indexStmt ? $indexStmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$index) {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_stripe_customer_id (stripe_customer_id)");
        if (!$is_api_context) {
            echo "✓ Index 'idx_stripe_customer_id' added to 'users'\n";
        }
    }
} catch (PDOException $e) {
    if (!$is_api_context) {
        echo "✗ Error ensuring users billing columns: {$e->getMessage()}\n";
    }
}

if (!$is_api_context) {
    echo "\n";
    echo "========================================\n";
    echo "Database Initialization Complete\n";
    echo "========================================\n";
    echo "Connected to: {$connected_host}\n";
    echo "Database: {$dbname}\n";
    echo "Tables processed: " . count($tables) . "\n";
    echo "Success: {$created}\n";
    echo "Errors: {$errors}\n";
    echo "========================================\n";
}

return [
    'success' => $errors === 0,
    'pdo' => $pdo,
    'connected_host' => $connected_host,
    'tables_created' => $created,
    'errors' => $errors
];

