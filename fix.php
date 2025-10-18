<?php
/**
 * Legacy User Fix Script
 * This script fixes old users who were created before Nextcloud integration
 * and other recent features were added.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✓ Connected to database\n";
} catch(PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

echo "🔧 Starting Legacy User Fix Process...\n\n";

// 1. Add missing columns to users table
echo "1. Adding missing columns to users table...\n";

$columns_to_add = [
    'nextcloud_username' => 'VARCHAR(255) NULL',
    'nextcloud_password' => 'VARCHAR(255) NULL',
    'workspace_owner_id' => 'INT NULL',
    'role' => 'VARCHAR(50) DEFAULT "owner"'
];

foreach ($columns_to_add as $column => $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
            echo "  ✓ Added column: $column\n";
        } else {
            echo "  - Column already exists: $column\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Error adding column $column: " . $e->getMessage() . "\n";
    }
}

// 2. Add missing columns to assets table
echo "\n2. Adding missing columns to assets table...\n";

$asset_columns_to_add = [
    'share_token' => 'VARCHAR(64) NULL UNIQUE',
    'file_size' => 'INT NULL',
    'mime_type' => 'VARCHAR(100) NULL'
];

foreach ($asset_columns_to_add as $column => $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM assets LIKE '$column'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE assets ADD COLUMN $column $definition");
            echo "  ✓ Added column: $column\n";
        } else {
            echo "  - Column already exists: $column\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Error adding column $column: " . $e->getMessage() . "\n";
    }
}

// 3. Add missing columns to brand_kits table
echo "\n3. Adding missing columns to brand_kits table...\n";

$brand_kit_columns_to_add = [
    'share_token' => 'VARCHAR(64) NULL UNIQUE'
];

foreach ($brand_kit_columns_to_add as $column => $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM brand_kits LIKE '$column'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE brand_kits ADD COLUMN $column $definition");
            echo "  ✓ Added column: $column\n";
        } else {
            echo "  - Column already exists: $column\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Error adding column $column: " . $e->getMessage() . "\n";
    }
}

// 4. Create missing tables
echo "\n4. Creating missing tables...\n";

$tables_to_create = [
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
    "
];

foreach ($tables_to_create as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "  ✓ Created table: $table_name\n";
    } catch (PDOException $e) {
        echo "  ✗ Error creating table $table_name: " . $e->getMessage() . "\n";
    }
}

// 5. Fix legacy users - set up Nextcloud credentials for existing users
echo "\n5. Setting up Nextcloud credentials for legacy users...\n";

try {
    // Get all users without Nextcloud credentials
    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE nextcloud_username IS NULL OR nextcloud_username = ''");
    $stmt->execute();
    $legacy_users = $stmt->fetchAll();
    
    echo "  Found " . count($legacy_users) . " legacy users to fix\n";
    
    foreach ($legacy_users as $user) {
        // Generate Nextcloud credentials for legacy users
        $nextcloud_username = 'stella_' . $user['id'] . '_' . time();
        $nextcloud_password = bin2hex(random_bytes(16));
        
        // Update user with Nextcloud credentials
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET nextcloud_username = ?, 
                nextcloud_password = ?,
                role = COALESCE(role, 'owner')
            WHERE id = ?
        ");
        $update_stmt->execute([$nextcloud_username, $nextcloud_password, $user['id']]);
        
        echo "  ✓ Fixed user: {$user['email']} (ID: {$user['id']})\n";
        echo "    - Nextcloud Username: $nextcloud_username\n";
        echo "    - Nextcloud Password: $nextcloud_password\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Error fixing legacy users: " . $e->getMessage() . "\n";
}

// 6. Generate share tokens for existing assets without them
echo "\n6. Generating share tokens for existing assets...\n";

try {
    $stmt = $pdo->prepare("SELECT id FROM assets WHERE share_token IS NULL OR share_token = ''");
    $stmt->execute();
    $assets_without_tokens = $stmt->fetchAll();
    
    echo "  Found " . count($assets_without_tokens) . " assets without share tokens\n";
    
    foreach ($assets_without_tokens as $asset) {
        $share_token = bin2hex(random_bytes(16));
        $update_stmt = $pdo->prepare("UPDATE assets SET share_token = ? WHERE id = ?");
        $update_stmt->execute([$share_token, $asset['id']]);
        echo "  ✓ Generated token for asset ID: {$asset['id']}\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Error generating share tokens: " . $e->getMessage() . "\n";
}

// 7. Generate share tokens for existing brand kits without them
echo "\n7. Generating share tokens for existing brand kits...\n";

try {
    $stmt = $pdo->prepare("SELECT id FROM brand_kits WHERE share_token IS NULL OR share_token = ''");
    $stmt->execute();
    $kits_without_tokens = $stmt->fetchAll();
    
    echo "  Found " . count($kits_without_tokens) . " brand kits without share tokens\n";
    
    foreach ($kits_without_tokens as $kit) {
        $share_token = bin2hex(random_bytes(16));
        $update_stmt = $pdo->prepare("UPDATE brand_kits SET share_token = ? WHERE id = ?");
        $update_stmt->execute([$share_token, $kit['id']]);
        echo "  ✓ Generated token for brand kit ID: {$kit['id']}\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Error generating share tokens for brand kits: " . $e->getMessage() . "\n";
}

// 8. Add foreign key constraints if they don't exist
echo "\n8. Adding foreign key constraints...\n";

$foreign_keys = [
    "ALTER TABLE users ADD CONSTRAINT fk_users_workspace_owner FOREIGN KEY (workspace_owner_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE assets ADD CONSTRAINT fk_assets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
    "ALTER TABLE assets ADD CONSTRAINT fk_assets_brand_kit FOREIGN KEY (brand_kit_id) REFERENCES brand_kits(id) ON DELETE SET NULL",
    "ALTER TABLE brand_kits ADD CONSTRAINT fk_brand_kits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"
];

foreach ($foreign_keys as $fk_sql) {
    try {
        $pdo->exec($fk_sql);
        echo "  ✓ Added foreign key constraint\n";
    } catch (PDOException $e) {
        // Foreign key might already exist, that's okay
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "  - Foreign key constraint already exists\n";
        } else {
            echo "  ✗ Error adding foreign key: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n🎉 Legacy User Fix Process Complete!\n";
echo "\n📋 Summary:\n";
echo "- Added missing database columns\n";
echo "- Created missing tables\n";
echo "- Set up Nextcloud credentials for legacy users\n";
echo "- Generated share tokens for existing assets and brand kits\n";
echo "- Added foreign key constraints\n";
echo "\n✅ All legacy users should now be able to upload and use all features!\n";
echo "\n⚠️  Note: Legacy users will need to use their new Nextcloud credentials for uploads.\n";
echo "   You may want to notify them or provide a way to reset their credentials.\n";
?>