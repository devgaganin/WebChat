<?php
require_once 'config.php';

// Get or create chat between two users
function getOrCreateChat($user1Id, $user2Id) {
    $pdo = getDB();
    
    // Ensure user1Id is always smaller for consistency
    if ($user1Id > $user2Id) {
        list($user1Id, $user2Id) = [$user2Id, $user1Id];
    }
    
    // Check if chat exists
    $stmt = $pdo->prepare("SELECT id FROM chats WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1Id, $user2Id]);
    $chat = $stmt->fetch();
    
    if ($chat) {
        return $chat['id'];
    }
    
    // Create new chat
    $stmt = $pdo->prepare("INSERT INTO chats (user1_id, user2_id) VALUES (?, ?)");
    $stmt->execute([$user1Id, $user2Id]);
    return $pdo->lastInsertId();
}

// Get all chats for a user
function getUserChats($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS chat_id,
            c.updated_at,
            CASE 
                WHEN c.user1_id = ? THEN c.user2_id 
                ELSE c.user1_id 
            END AS other_user_id,
            u.username,
            u.display_name,
            u.avatar,
            u.is_online,
            u.last_seen,
            (SELECT message FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT message_type FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_type,
            (SELECT created_at FROM messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_time,
            (SELECT COUNT(*) FROM messages WHERE chat_id = c.id AND receiver_id = ? AND is_read = 0) AS unread_count
        FROM chats c
        INNER JOIN users u ON u.id = CASE 
            WHEN c.user1_id = ? THEN c.user2_id 
            ELSE c.user1_id 
        END
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.updated_at DESC
    ");
    
    $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    return $stmt->fetchAll();
}

// Send message
function sendMessage($chatId, $senderId, $receiverId, $message, $messageType = 'text', $filePath = null, $fileName = null, $fileSize = null, $selfDestructTimer = null) {
    $pdo = getDB();
    
    // Calculate destruct time if timer is set
    $destructAt = null;
    if ($selfDestructTimer) {
        $destructAt = date('Y-m-d H:i:s', time() + $selfDestructTimer);
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (chat_id, sender_id, receiver_id, message, message_type, file_path, file_name, file_size, self_destruct_timer, destruct_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $chatId,
        $senderId,
        $receiverId,
        $message,
        $messageType,
        $filePath,
        $fileName,
        $fileSize,
        $selfDestructTimer,
        $destructAt
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // Update chat timestamp
    $stmt = $pdo->prepare("UPDATE chats SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$chatId]);
    
    return $messageId;
}

// Get messages for a chat
function getChatMessages($chatId, $userId, $limit = 50, $offset = 0) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            s.username AS sender_username,
            s.display_name AS sender_display_name,
            s.avatar AS sender_avatar
        FROM messages m
        INNER JOIN users s ON s.id = m.sender_id
        WHERE m.chat_id = ?
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$chatId, $limit, $offset]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE chat_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$chatId, $userId]);
    
    return array_reverse($messages);
}

// Get new messages (for polling)
function getNewMessages($chatId, $lastMessageId, $userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            s.username AS sender_username,
            s.display_name AS sender_display_name,
            s.avatar AS sender_avatar
        FROM messages m
        INNER JOIN users s ON s.id = m.sender_id
        WHERE m.chat_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    
    $stmt->execute([$chatId, $lastMessageId]);
    $messages = $stmt->fetchAll();
    
    // Mark new messages as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE chat_id = ? AND receiver_id = ? AND id > ? AND is_read = 0");
        $stmt->execute([$chatId, $userId, $lastMessageId]);
    }
    
    return $messages;
}

// Delete message (single side or both)
function deleteMessage($messageId, $userId) {
    $pdo = getDB();
    
    // Get message details
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        return false;
    }
    
    // Delete file if exists
    if ($message['file_path']) {
        $filePath = UPLOAD_DIR . '/' . $message['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete file storage record
        $stmt = $pdo->prepare("DELETE FROM file_storage WHERE message_id = ?");
        $stmt->execute([$messageId]);
    }
    
    // Delete message
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    
    // Log deletion
    $stmt = $pdo->prepare("INSERT INTO deleted_logs (message_id, deleted_by, deletion_type) VALUES (?, ?, 'message')");
    $stmt->execute([$messageId, $userId]);
    
    return true;
}

// Delete entire chat (both sides)
function deleteChat($chatId, $userId) {
    $pdo = getDB();
    
    // Get all messages with files
    $stmt = $pdo->prepare("SELECT id, file_path FROM messages WHERE chat_id = ? AND file_path IS NOT NULL");
    $stmt->execute([$chatId]);
    $messages = $stmt->fetchAll();
    
    // Delete all files
    foreach ($messages as $message) {
        $filePath = UPLOAD_DIR . '/' . $message['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Delete file storage records
    $stmt = $pdo->prepare("DELETE FROM file_storage WHERE message_id IN (SELECT id FROM messages WHERE chat_id = ?)");
    $stmt->execute([$chatId]);
    
    // Delete all messages
    $stmt = $pdo->prepare("DELETE FROM messages WHERE chat_id = ?");
    $stmt->execute([$chatId]);
    
    // Delete chat
    $stmt = $pdo->prepare("DELETE FROM chats WHERE id = ?");
    $stmt->execute([$chatId]);
    
    // Log deletion
    $stmt = $pdo->prepare("INSERT INTO deleted_logs (chat_id, deleted_by, deletion_type) VALUES (?, ?, 'chat')");
    $stmt->execute([$chatId, $userId]);
    
    return true;
}

// Auto-delete expired messages (run via CRON)
function cleanupExpiredMessages() {
    $pdo = getDB();
    
    // Get expired messages
    $stmt = $pdo->query("SELECT id, file_path FROM messages WHERE destruct_at IS NOT NULL AND destruct_at <= NOW()");
    $messages = $stmt->fetchAll();
    
    foreach ($messages as $message) {
        // Delete file if exists
        if ($message['file_path']) {
            $filePath = UPLOAD_DIR . '/' . $message['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete message
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message['id']]);
        
        // Log deletion
        $stmt = $pdo->prepare("INSERT INTO deleted_logs (message_id, deleted_by, deletion_type) VALUES (?, 0, 'auto_destruct')");
        $stmt->execute([$message['id']]);
    }
    
    // Clean up file tokens
    $stmt = $pdo->query("DELETE FROM file_tokens WHERE expires_at <= NOW()");
    
    return count($messages);
}

// Set typing status
function setTypingStatus($chatId, $userId, $isTyping) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO typing_status (chat_id, user_id, is_typing)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = NOW()
    ");
    
    $stmt->execute([$chatId, $userId, $isTyping ? 1 : 0, $isTyping ? 1 : 0]);
}

// Get typing status
function getTypingStatus($chatId, $otherUserId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT is_typing 
        FROM typing_status 
        WHERE chat_id = ? AND user_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
    ");
    
    $stmt->execute([$chatId, $otherUserId]);
    $result = $stmt->fetch();
    
    return $result ? (bool)$result['is_typing'] : false;
}

// Get unread message count for user
function getUnreadCount($userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    return $result['count'];
}

// Check if user has access to chat
function userHasAccessToChat($chatId, $userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id FROM chats WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$chatId, $userId, $userId]);
    
    return $stmt->fetch() !== false;
}
