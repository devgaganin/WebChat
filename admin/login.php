<?php
require_once 'admin_auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $result = loginAdmin($username, $password);
    
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SPY CHAT</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0A0E27 0%, #1a1f3a 100%);
        }
        .admin-login-box {
            background: var(--surface-color);
            border-radius: 16px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        .admin-badge {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-login">
        <div class="admin-login-box">
            <div class="auth-header">
                <span class="admin-badge">🛡️ ADMIN ACCESS</span>
                <h1>SPY CHAT</h1>
                <p>Admin Panel Login</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login to Admin Panel</button>
            </form>

            <div class="auth-footer">
                <p><a href="../index.php">← Back to Main Site</a></p>
            </div>
        </div>
    </div>
</body>
</html>
