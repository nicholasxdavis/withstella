<?php
/**
 * Update Assets API
 * Updates existing assets with Nextcloud file info
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-ID, X-User-Email');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Load Nextcloud Storage Service and Auth Helper
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
require_once __DIR__ . '/auth_helper.php';
use App\Services\NextcloudStorage;

// Ensure database structure is up to date
define('SUPPRESS_DB_OUTPUT', true);
require_once dirname(__DIR__) . '/database/init.php';

// Database connection
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

$action = $_GET['action'] ?? 'update';

try {
    if ($action === 'check-columns') {
        // Check if columns exist
        $stmt = $pdo->query('DESCRIBE assets');
        $columns = $stmt->fetchAll();
        
        $hasNextcloudFileId = false;
        $hasNextcloudEtag = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'nextcloud_file_id') {
                $hasNextcloudFileId = true;
            }
            if ($column['Field'] === 'nextcloud_etag') {
                $hasNextcloudEtag = true;
            }
        }
        
        sendJson([
            'success' => true,
            'has_nextcloud_file_id' => $hasNextcloudFileId,
            'has_nextcloud_etag' => $hasNextcloudEtag,
            'columns' => array_column($columns, 'Field')
        ]);
        
    } elseif ($action === 'add-columns') {
        // Add missing columns
        $added = [];
        
        try {
            $pdo->exec("ALTER TABLE assets ADD COLUMN nextcloud_file_id VARCHAR(255) NULL");
            $added[] = 'nextcloud_file_id';
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        try {
            $pdo->exec("ALTER TABLE assets ADD COLUMN nextcloud_etag VARCHAR(255) NULL");
            $added[] = 'nextcloud_etag';
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        sendJson([
            'success' => true,
            'message' => 'Columns added successfully',
            'added_columns' => $added
        ]);
        
    } elseif ($action === 'update-assets') {
        // Update existing assets with Nextcloud file info
        $stmt = $pdo->query("SELECT a.*, u.nextcloud_username, u.nextcloud_password 
                             FROM assets a 
                             JOIN users u ON a.user_id = u.id 
                             WHERE a.nextcloud_file_id IS NULL AND a.file_path IS NOT NULL AND a.file_path != ''");
        $assets = $stmt->fetchAll();
        
        $updated = 0;
        $errors = [];
        
        foreach ($assets as $asset) {
            try {
                // Create Nextcloud storage instance
                $storage = new NextcloudStorage($asset['nextcloud_username'], $asset['nextcloud_password']);
                
                // Get file info from Nextcloud
                $fileInfo = $storage->getFileInfo($asset['file_path']);
                
                if ($fileInfo && $fileInfo['file_id']) {
                    // Update the asset with file info
                    $updateStmt = $pdo->prepare("UPDATE assets SET nextcloud_file_id = ?, nextcloud_etag = ? WHERE id = ?");
                    $updateStmt->execute([$fileInfo['file_id'], $fileInfo['etag'], $asset['id']]);
                    $updated++;
                } else {
                    $errors[] = "Could not get file info for asset: " . $asset['name'];
                }
            } catch (Exception $e) {
                $errors[] = "Error processing asset " . $asset['name'] . ": " . $e->getMessage();
            }
        }
        
        sendJson([
            'success' => true,
            'message' => 'Asset update completed',
            'updated_count' => $updated,
            'total_assets' => count($assets),
            'errors' => $errors
        ]);
        
    } else {
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
