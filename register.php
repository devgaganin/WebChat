<?php
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $acceptTerms = isset($_POST['accept_terms']);
        
        if (!$acceptTerms) {
            $error = 'You must accept the Terms of Service to continue';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $result = registerUser($username, $password);
            
            if ($result['success']) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                        <path d="M20 5L35 12.5V27.5L20 35L5 27.5V12.5L20 5Z" stroke="currentColor" stroke-width="2" fill="none"/>
                        <circle cx="20" cy="20" r="5" fill="currentColor"/>
                    </svg>
                </div>
                <h1>SPY CHAT</h1>
                <p>Join the most secure chat platform</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus 
                           pattern="[a-zA-Z0-9_]{3,50}" 
                           title="3-50 characters: letters, numbers, underscore only">
                    <small>3-50 characters: letters, numbers, underscore only</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="accept_terms" required>
                        <span>I accept the <a href="terms.php" target="_blank">Terms of Service</a> and acknowledge this platform is NOT for illegal use</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>

        <div class="auth-info">
            <div class="info-card">
                <h3>🎭 No Personal Data</h3>
                <p>Only username & password required</p>
            </div>
            <div class="info-card">
                <h3>🔥 Self-Destruct Messages</h3>
                <p>Auto-delete messages after set time</p>
            </div>
            <div class="info-card">
                <h3>🛡️ Anti-Screenshot</h3>
                <p>Maximum protection from capture attempts</p>
            </div>
        </div>
    </div>

    <script src="assets/js/security.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
