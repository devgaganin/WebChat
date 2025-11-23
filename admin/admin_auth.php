<?php
require_once '../config.php';

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Login admin
function loginAdmin($username, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Set session
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    
    return ['success' => true, 'message' => 'Login successful'];
}

// Logout admin
function logoutAdmin() {
    session_destroy();
    return true;
}

// Require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get all users
function getAllUsers($limit = 50, $offset = 0, $search = '') {
    $pdo = getDB();
    
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, created_at, last_seen, is_online 
            FROM users 
            WHERE username LIKE ? OR display_name LIKE ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $searchTerm = "%{$search}%";
        $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, created_at, last_seen, is_online 
            FROM users 
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
    }
    
    return $stmt->fetchAll();
}

// Get all chats
function getAllChats($limit = 50, $offset = 0) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.created_at,
            c.updated_at,
            u1.username as user1_username,
            u2.username as user2_username,
            (SELECT COUNT(*) FROM messages WHERE chat_id = c.id) as message_count
        FROM chats c
        INNER JOIN users u1 ON u1.id = c.user1_id
        INNER JOIN users u2 ON u2.id = c.user2_id
        ORDER BY c.updated_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    
    return $stmt->fetchAll();
}

// Get chat messages
function getAdminChatMessages($chatId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            u.username as sender_username
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        WHERE m.chat_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$chatId]);
    
    return $stmt->fetchAll();
}

// Search messages
function searchMessages($query, $limit = 100) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            u.username as sender_username,
            c.user1_id,
            c.user2_id
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        INNER JOIN chats c ON c.id = m.chat_id
        WHERE m.message LIKE ?
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $limit]);
    
    return $stmt->fetchAll();
}

// Get statistics
function getAdminStatistics() {
    $pdo = getDB();
    
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Online users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_online = 1");
    $stats['online_users'] = $stmt->fetch()['count'];
    
    // Total chats
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM chats");
    $stats['total_chats'] = $stmt->fetch()['count'];
    
    // Total messages
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
    $stats['total_messages'] = $stmt->fetch()['count'];
    
    // Messages today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages WHERE DATE(created_at) = CURDATE()");
    $stats['messages_today'] = $stmt->fetch()['count'];
    
    // New users today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $stmt->fetch()['count'];
    
    return $stats;
}

// Delete user
function deleteUser($userId) {
    $pdo = getDB();
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get recent activity
function getRecentActivity($limit = 20) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.created_at,
            m.message_type,
            u.username as sender_username,
            c.user1_id,
            c.user2_id
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        INNER JOIN chats c ON c.id = m.chat_id
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}
