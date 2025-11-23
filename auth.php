<?php
require_once 'config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, display_name, avatar, created_at FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

// Register new user
function registerUser($username, $password) {
    $pdo = getDB();
    
    // Validate username
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'Username must be between 3 and 50 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
    }
    
    // Validate password
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, display_name) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $username]);
        
        return ['success' => true, 'message' => 'Registration successful'];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Login user
function loginUser($username, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Update last seen and online status
    $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW(), is_online = 1 WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    return ['success' => true, 'message' => 'Login successful'];
}

// Logout user
function logoutUser() {
    if (isLoggedIn()) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
    }
    
    session_destroy();
    return true;
}

// Update user online status
function updateOnlineStatus($userId, $isOnline = true) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET is_online = ?, last_seen = NOW() WHERE id = ?");
    $stmt->execute([$isOnline ? 1 : 0, $userId]);
}

// Check if user exists
function userExists($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() !== false;
}

// Get user by username
function getUserByUsername($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, display_name, avatar, is_online, last_seen FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

// Get user by ID
function getUserById($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, display_name, avatar, is_online, last_seen FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Update user profile
function updateUserProfile($userId, $displayName, $avatarPath = null) {
    $pdo = getDB();
    
    if ($avatarPath) {
        $stmt = $pdo->prepare("UPDATE users SET display_name = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$displayName, $avatarPath, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $stmt->execute([$displayName, $userId]);
    }
    
    return true;
}

// Search users
function searchUsers($query, $currentUserId, $limit = 10) {
    $pdo = getDB();
    $searchTerm = "%{$query}%";
    
    $stmt = $pdo->prepare("
        SELECT id, username, display_name, avatar, is_online 
        FROM users 
        WHERE (username LIKE ? OR display_name LIKE ?) AND id != ?
        LIMIT ?
    ");
    $stmt->execute([$searchTerm, $searchTerm, $currentUserId, $limit]);
    return $stmt->fetchAll();
}

// Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Generate default avatar
function getDefaultAvatar($username) {
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    $initial = strtoupper(substr($username, 0, 1));
    $colorIndex = ord($initial) % count($colors);
    $color = $colors[$colorIndex];
    
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='{$color}'/%3E%3Ctext x='50' y='50' font-size='48' fill='white' text-anchor='middle' dominant-baseline='central' font-family='Arial'%3E{$initial}%3C/text%3E%3C/svg%3E";
}
