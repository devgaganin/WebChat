<?php
// SPY CHAT Configuration File
// IMPORTANT: Keep this file secure and outside public_html if possible

// Database Configuration
define('DB_HOST', 'localhost'); // Change to your Hostinger DB host
define('DB_NAME', ''); // Your database name
define('DB_USER', ''); // Your database username
define('DB_PASS', ''); // Your database password

// Site Configuration
define('SITE_URL', 'https://spychat.devgagan.in'); // Your site URL (no trailing slash)
define('SITE_NAME', 'SPYCHAT');

// Security Settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_DIR', __DIR__ . '/uploads'); // Files stored outside webroot
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp3']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'application/zip', 'application/x-rar-compressed']);

// File Token Settings
define('FILE_TOKEN_LIFETIME', 3600); // 1 hour for file access tokens

// Self-Destruct Timer Options (in seconds)
define('DESTRUCT_TIMERS', [
    5 => '5 seconds',
    10 => '10 seconds',
    30 => '30 seconds',
    60 => '1 minute',
    300 => '5 minutes',
    3600 => '1 hour',
    86400 => '24 hours'
]);

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database Connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// Security Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return $filename;
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
