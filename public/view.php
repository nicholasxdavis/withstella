<?php
// Kit share view: /public/view.php?k={kit_share_token}
// Existing asset view: /public/view.php?t={asset_share_token}

$kitToken = $_GET['k'] ?? null;
if ($kitToken) {
    // Render a simple kit page with kit info and asset grid
    $host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
    $db = getenv('DB_DATABASE') ?: 'default';
    $user = getenv('DB_USERNAME') ?: 'mariadb';
    $pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        // Lookup kit by share_token (add column if missing)
        $stmt = $pdo->prepare("SELECT * FROM brand_kits WHERE share_token = ?");
        $stmt->execute([$kitToken]);
        $kit = $stmt->fetch();
        if (!$kit) {
            http_response_code(404);
            echo '<h1>Kit not found</h1>';
            exit;
        }
        $assetsStmt = $pdo->prepare("SELECT * FROM assets WHERE brand_kit_id = ? ORDER BY created_at DESC");
        $assetsStmt->execute([$kit['id']]);
        $assets = $assetsStmt->fetchAll();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($kit['name']); ?> — Brand Kit</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background:#0b0b0b; color:#e6e6e6; margin:0; }
        .container { max-width: 1000px; margin: 0 auto; padding: 24px; }
        .header { display:flex; align-items:center; gap:16px; margin-bottom:16px; }
        .logo { width:64px; height:64px; border-radius:12px; object-fit:cover; background:#111; border:1px solid #222; }
        .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; margin-top:16px; }
        .card { background:#111; border:1px solid #222; border-radius:10px; padding:10px; }
        .thumb { width:100%; height:120px; object-fit:cover; border-radius:8px; background:#0d0d0d; }
        .meta { font-size:12px; color:#aaa; margin-top:6px; display:flex; justify-content:space-between; }
        a.btn { display:inline-block; margin-top:8px; padding:8px 10px; background:#9c7ead; color:#fff; border-radius:6px; text-decoration:none; font-size:12px; }
    </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <?php if (!empty($kit['logo_url'])): ?>
                    <img class="logo" src="<?php echo htmlspecialchars($kit['logo_url']); ?>" alt="Logo" />
                <?php endif; ?>
                <div>
                    <h1 style="margin:0; font-size:24px; color:#fff;"><?php echo htmlspecialchars($kit['name']); ?></h1>
                    <?php if (!empty($kit['description'])): ?>
                        <div style="color:#bdbdbd; font-size:14px; margin-top:4px;"><?php echo nl2br(htmlspecialchars($kit['description'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="grid">
            <?php foreach ($assets as $asset): ?>
                <div class="card">
                    <?php if (!empty($asset['share_token'])): ?>
                        <img class="thumb" src="/public/view.php?t=<?php echo htmlspecialchars($asset['share_token']); ?>" alt="Asset" />
                    <?php endif; ?>
                    <div class="meta">
                        <span><?php echo htmlspecialchars($asset['name']); ?></span>
                        <span><?php echo htmlspecialchars($asset['type']); ?></span>
                    </div>
                    <?php if (!empty($asset['share_token'])): ?>
                        <a class="btn" href="/public/view.php?t=<?php echo htmlspecialchars($asset['share_token']); ?>" target="_blank">Open</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </body>
    </html>
        <?php
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo 'Error loading kit';
        exit;
    }
}
/**
 * Public Asset View Page
 * Allows anyone with the link to view and download an asset
 * No login required
 */

error_reporting(0);
ini_set('display_errors', 0);

// Database connection
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	die('Database connection failed');
}

// Get share token from URL
$token = $_GET['t'] ?? null;

if (!$token) {
	die('Invalid share link');
}

// Look up asset by share token
$stmt = $pdo->prepare("SELECT a.*, u.nextcloud_username, u.nextcloud_password, u.full_name as owner_name 
                       FROM assets a 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.share_token = ?");
$stmt->execute([$token]);
$asset = $stmt->fetch();

if (!$asset) {
	die('Asset not found or link expired');
}

// Get file type info
$isImage = strpos($asset['type'], 'image/') === 0;
$fileSize = round($asset['file_size'] / 1024, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asset['name']); ?> - Shared via Stella</title>
    
    <link rel="icon" href="https://placehold.co/32x32/9c7ead/FFFFFF?text=S" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://assets.vercel.com/raw/upload/v1587415301/fonts/2/Geist.css">
    
    <style>
        :root {
            --primary: #9c7ead;
            --primary-dark: #826a94;
            --primary-light: #b692c7;
            --bg-dark: #000;
            --card-dark: #0a0a0a;
            --surface: #0a0a0a;
            --text-primary: #ededed;
            --text-secondary: #a19e97;
            --border-dark: #232323;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Animated background */
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        
        .bg-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: var(--primary);
            opacity: 0.15;
            border-radius: 50%;
            filter: blur(100px);
            animation: blob 20s infinite;
            z-index: 0;
        }
        
        .blob-1 { top: 10%; left: 10%; }
        .blob-2 { top: 60%; right: 10%; animation-delay: 5s; }
        
        .container {
            max-width: 900px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .glass-card {
            background: var(--surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-dark);
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            padding: 40px 40px 30px;
            border-bottom: 1px solid var(--border-dark);
            background: rgba(156, 126, 173, 0.02);
        }
        
        /* Logo Icon */
        .logo-icon {
            position: relative;
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            filter: drop-shadow(0 0 12px var(--primary));
        }
        
        .logo-icon-ray {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, rgba(156, 126, 173, 0), var(--primary), rgba(156, 126, 173, 0));
            transform-origin: center;
        }
        
        .logo-icon-ray:first-child {
            transform: translate(-50%, -50%) rotate(45deg);
        }
        
        .logo-icon-ray:last-child {
            transform: translate(-50%, -50%) rotate(-45deg);
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .shared-by {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .shared-by .owner-name {
            color: var(--primary);
            font-weight: 600;
        }
        
        .content {
            padding: 40px;
        }
        
        .preview {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 32px;
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-dark);
        }
        
        .preview img {
            max-width: 100%;
            max-height: 600px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .preview i {
            font-size: 80px;
            color: var(--primary);
            opacity: 0.4;
        }
        
        .preview-text {
            margin-top: 20px;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .info-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-dark);
        }
        
        .info-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-label i {
            color: var(--primary);
        }
        
        .info-value {
            color: var(--text-primary);
            font-size: 1.05rem;
            font-weight: 600;
        }
        
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .btn {
            padding: 16px 28px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            font-family: 'Geist', sans-serif;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, var(--primary) 0%, rgba(156, 126, 173, 0) 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
            pointer-events: none;
            opacity: 0;
            z-index: 0;
        }
        
        .btn:hover::before {
            width: 250px;
            height: 250px;
            opacity: 0.3;
        }
        
        .btn > * {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(156, 126, 173, 0.3);
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(156, 126, 173, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border-dark);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }
        
        .footer {
            text-align: center;
            padding: 30px 40px 40px;
            border-top: 1px solid var(--border-dark);
            background: rgba(0, 0, 0, 0.2);
        }
        
        .footer-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .footer a:hover {
            color: var(--primary-light);
        }
        
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--card-dark);
            color: var(--text-primary);
            padding: 16px 24px;
            border-radius: 12px;
            border: 1px solid var(--border-dark);
            display: none;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            z-index: 1000;
        }
        
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 640px) {
            .header, .content, .footer { padding: 24px; }
            .actions { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    
    <div class="container">
        <div class="glass-card">
            <div class="header">
                <div class="logo-icon">
                    <span class="logo-icon-ray"></span>
                    <span class="logo-icon-ray"></span>
                </div>
                <div class="logo-text">Stella</div>
                <p class="shared-by">Shared by <span class="owner-name"><?php echo htmlspecialchars($asset['owner_name']); ?></span></p>
            </div>

            <div class="content">
                <div class="preview">
                    <?php if ($isImage): ?>
                        <img src="/api/image_proxy.php?t=<?php echo $token; ?>&size=600" 
                             alt="<?php echo htmlspecialchars($asset['name']); ?>"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div><i class=\'fas fa-image\'></i><p class=\'preview-text\'>Preview not available for this file type</p></div>'">
                    <?php else: ?>
                        <div>
                            <i class="fas fa-file-alt"></i>
                            <p class="preview-text">Preview not available for this file type</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-file"></i>
                            File Name
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($asset['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-database"></i>
                            File Size
                        </div>
                        <div class="info-value"><?php echo $fileSize; ?> KB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-tag"></i>
                            Type
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($asset['type']); ?></div>
                    </div>
                </div>

                <div class="actions">
                    <a href="/api/image_proxy.php?t=<?php echo $token; ?>&download=true" 
                       class="btn btn-primary">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </a>
                    <button onclick="copyLink()" class="btn btn-secondary">
                        <i class="fas fa-copy"></i>
                        <span>Copy Link</span>
                    </button>
                </div>
            </div>

            <div class="footer">
                <p class="footer-text">
                    Powered by <a href="/">Stella File Management</a>
                </p>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        function copyLink() {
            const url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Link copied to clipboard!');
                }).catch(() => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast('Link copied to clipboard!');
            } catch (err) {
                showToast('Failed to copy');
            }
            document.body.removeChild(textArea);
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>

