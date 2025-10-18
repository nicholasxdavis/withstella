<?php
/**
 * Password Reset Page
 * Allows users to reset their password via email link
 */

error_reporting(0);
ini_set('display_errors', 0);

$token = $_GET['token'] ?? null;
$email = $_GET['email'] ?? null;

if (!$token || !$email) {
    die('Invalid reset link');
}

// Verify token exists and is not expired
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE email = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $stmt->execute([$email, $token]);
    $resetToken = $stmt->fetch();
    
    if (!$resetToken) {
        $tokenExpired = true;
    } else {
        $tokenExpired = false;
    }
} catch(PDOException $e) {
    die('Database connection failed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Stella</title>
    
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
        }
        
        .container {
            max-width: 480px;
            width: 100%;
        }
        
        .glass-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            padding: 40px 40px 30px;
            border-bottom: 1px solid var(--border-dark);
        }
        
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
        
        .logo-icon-ray:first-child { transform: translate(-50%, -50%) rotate(45deg); }
        .logo-icon-ray:last-child { transform: translate(-50%, -50%) rotate(-45deg); }
        
        h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .content {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-dark);
            background: rgba(0, 0, 0, 0.3);
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Geist', sans-serif;
            transition: all 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(156, 126, 173, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Geist', sans-serif;
        }
        
        .btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(156, 126, 173, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .message-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .message-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="header">
                <div class="logo-icon">
                    <span class="logo-icon-ray"></span>
                    <span class="logo-icon-ray"></span>
                </div>
                <h1>Reset Password</h1>
                <p class="subtitle">Enter your new password below</p>
            </div>

            <div class="content">
                <?php if ($tokenExpired): ?>
                    <div class="message message-error">
                        <i class="fas fa-exclamation-circle"></i>
                        This reset link has expired or is invalid. Please request a new one.
                    </div>
                    <div class="back-link">
                        <a href="/"><i class="fas fa-arrow-left"></i> Back to login</a>
                    </div>
                <?php else: ?>
                    <form id="reset-form">
                        <div class="form-group">
                            <label for="new-password">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <input type="password" id="new-password" required 
                                   minlength="8" placeholder="Enter new password (min 8 characters)">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm-password">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" id="confirm-password" required 
                                   minlength="8" placeholder="Confirm new password">
                        </div>
                        
                        <div id="message-container"></div>
                        
                        <button type="submit" class="btn" id="submit-btn">
                            Reset Password
                        </button>
                        
                        <div class="back-link">
                            <a href="/"><i class="fas fa-arrow-left"></i> Back to login</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('reset-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                const messageContainer = document.getElementById('message-container');
                const submitBtn = document.getElementById('submit-btn');
                
                // Clear previous messages
                messageContainer.innerHTML = '';
                
                // Validate passwords match
                if (newPassword !== confirmPassword) {
                    messageContainer.innerHTML = '<div class="message message-error"><i class="fas fa-exclamation-circle"></i> Passwords do not match</div>';
                    return;
                }
                
                // Validate password length
                if (newPassword.length < 8) {
                    messageContainer.innerHTML = '<div class="message message-error"><i class="fas fa-exclamation-circle"></i> Password must be at least 8 characters</div>';
                    return;
                }
                
                // Disable button
                submitBtn.disabled = true;
                submitBtn.textContent = 'Resetting...';
                
                try {
                    const response = await fetch('/api/auth.php?action=reset-password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: '<?php echo htmlspecialchars($email); ?>',
                            token: '<?php echo htmlspecialchars($token); ?>',
                            password: newPassword
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        messageContainer.innerHTML = '<div class="message message-success"><i class="fas fa-check-circle"></i> Password reset successful! Redirecting to login...</div>';
                        setTimeout(() => {
                            window.location.href = '/';
                        }, 2000);
                    } else {
                        messageContainer.innerHTML = `<div class="message message-error"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Reset failed'}</div>`;
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Reset Password';
                    }
                } catch (error) {
                    messageContainer.innerHTML = '<div class="message message-error"><i class="fas fa-exclamation-circle"></i> Connection error. Please try again.</div>';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset Password';
                }
            });
        }
    </script>
</body>
</html>

