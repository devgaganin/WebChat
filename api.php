<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'chat.php';
require_once 'files.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$currentUserId = getCurrentUserId();

switch ($action) {
    case 'get_messages':
        $chatId = intval($_GET['chat_id'] ?? 0);
        $lastMessageId = intval($_GET['last_message_id'] ?? 0);
        
        if (!$chatId || !userHasAccessToChat($chatId, $currentUserId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid chat']);
            exit;
        }
        
        if ($lastMessageId > 0) {
            $messages = getNewMessages($chatId, $lastMessageId, $currentUserId);
        } else {
            $messages = getChatMessages($chatId, $currentUserId);
        }
        
        // Process messages for frontend
        foreach ($messages as &$message) {
            $message['is_own'] = $message['sender_id'] == $currentUserId;
            $message['time_formatted'] = date('g:i A', strtotime($message['created_at']));
            
            // Generate file URL if message has file
            if ($message['file_path']) {
                $fileInfo = getFileInfoFromMessage($message['id']);
                if ($fileInfo) {
                    $message['file_url'] = getFileUrl($fileInfo['id'], $currentUserId);
                    $message['file_size_formatted'] = formatFileSize($fileInfo['file_size']);
                }
            }
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
        
    case 'send_message':
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        
        $chatId = intval($_POST['chat_id'] ?? 0);
        $receiverId = intval($_POST['receiver_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $selfDestructTimer = intval($_POST['self_destruct_timer'] ?? 0);
        
        if (!$chatId || !userHasAccessToChat($chatId, $currentUserId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid chat']);
            exit;
        }
        
        $messageType = 'text';
        $filePath = null;
        $fileName = null;
        $fileSize = null;
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            // Create temporary message to get ID
            $tempMessageId = sendMessage($chatId, $currentUserId, $receiverId, '', 'text', null, null, null, $selfDestructTimer > 0 ? $selfDestructTimer : null);
            
            $uploadResult = uploadFile($_FILES['file'], $tempMessageId, $currentUserId);
            
            if ($uploadResult['success']) {
                $messageType = $uploadResult['file_type'];
                $filePath = $uploadResult['file_path'];
                $fileName = $uploadResult['file_name'];
                $fileSize = $uploadResult['file_size'];
                
                // Update message with file info
                $pdo = getDB();
                $stmt = $pdo->prepare("
                    UPDATE messages 
                    SET message_type = ?, file_path = ?, file_name = ?, file_size = ?, message = ?
                    WHERE id = ?
                ");
                $stmt->execute([$messageType, $filePath, $fileName, $fileSize, $fileName, $tempMessageId]);
                
                $messageId = $tempMessageId;
            } else {
                // Delete temp message
                deleteMessage($tempMessageId, $currentUserId);
                echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                exit;
            }
        } else {
            if (empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
                exit;
            }
            
            $messageId = sendMessage($chatId, $currentUserId, $receiverId, $message, $messageType, $filePath, $fileName, $fileSize, $selfDestructTimer > 0 ? $selfDestructTimer : null);
        }
        
        if ($messageId) {
            // Get the sent message
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $sentMessage = $stmt->fetch();
            
            $sentMessage['is_own'] = true;
            $sentMessage['time_formatted'] = date('g:i A', strtotime($sentMessage['created_at']));
            
            if ($sentMessage['file_path']) {
                $fileInfo = getFileInfoFromMessage($sentMessage['id']);
                if ($fileInfo) {
                    $sentMessage['file_url'] = getFileUrl($fileInfo['id'], $currentUserId);
                    $sentMessage['file_size_formatted'] = formatFileSize($fileInfo['file_size']);
                }
            }
            
            echo json_encode(['success' => true, 'message' => $sentMessage]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
        break;
        
    case 'typing_status':
        $chatId = intval($_POST['chat_id'] ?? 0);
        $isTyping = $_POST['is_typing'] === 'true';
        
        if (!$chatId || !userHasAccessToChat($chatId, $currentUserId)) {
            echo json_encode(['success' => false]);
            exit;
        }
        
        setTypingStatus($chatId, $currentUserId, $isTyping);
        echo json_encode(['success' => true]);
        break;
        
    case 'check_typing':
        $chatId = intval($_GET['chat_id'] ?? 0);
        $otherUserId = intval($_GET['other_user_id'] ?? 0);
        
        if (!$chatId || !userHasAccessToChat($chatId, $currentUserId)) {
            echo json_encode(['success' => false]);
            exit;
        }
        
        $isTyping = getTypingStatus($chatId, $otherUserId);
        echo json_encode(['success' => true, 'is_typing' => $isTyping]);
        break;
        
    case 'delete_message':
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        
        $messageId = intval($_POST['message_id'] ?? 0);
        
        // Verify user owns this message or is receiver
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$messageId, $currentUserId, $currentUserId]);
        $message = $stmt->fetch();
        
        if (!$message) {
            echo json_encode(['success' => false, 'message' => 'Message not found']);
            exit;
        }
        
        deleteMessage($messageId, $currentUserId);
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_chat':
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        
        $chatId = intval($_POST['chat_id'] ?? 0);
        
        if (!$chatId || !userHasAccessToChat($chatId, $currentUserId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid chat']);
            exit;
        }
        
        deleteChat($chatId, $currentUserId);
        echo json_encode(['success' => true]);
        break;
        
    case 'search_users':
        $query = sanitizeInput($_GET['query'] ?? '');
        
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }
        
        $users = searchUsers($query, $currentUserId);
        
        // Fix avatar handling - don't use tokens for avatars
        foreach ($users as &$user) {
            if (!$user['avatar']) {
                $user['avatar'] = getDefaultAvatar($user['username']);
            } else {
                // Avatar is a direct path, not a token-protected file
                $user['avatar'] = htmlspecialchars($user['avatar']);
            }
        }
        
        echo json_encode(['success' => true, 'users' => $users]);
        break;
        
    case 'start_chat':
        $username = sanitizeInput($_POST['username'] ?? '');
        
        $otherUser = getUserByUsername($username);
        
        if (!$otherUser) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $chatId = getOrCreateChat($currentUserId, $otherUser['id']);
        
        // Fix avatar for response
        if (!$otherUser['avatar']) {
            $otherUser['avatar'] = getDefaultAvatar($otherUser['username']);
        } else {
            $otherUser['avatar'] = htmlspecialchars($otherUser['avatar']);
        }
        
        echo json_encode([
            'success' => true,
            'chat_id' => $chatId,
            'user' => $otherUser
        ]);
        break;
        
    case 'update_profile':
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        
        $displayName = sanitizeInput($_POST['display_name'] ?? '');
        $avatarPath = null;
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Validate image
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image type']);
                exit;
            }
            
            if ($_FILES['avatar']['size'] > MAX_FILE_SIZE) {
                echo json_encode(['success' => false, 'message' => 'Image too large']);
                exit;
            }
            
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $currentUserId . '_' . time() . '.' . $extension;
            $uploadPath = 'uploads/avatars/';
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $fullPath = $uploadPath . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $fullPath)) {
                $avatarPath = $fullPath;
            }
        }
        
        updateUserProfile($currentUserId, $displayName, $avatarPath);
        
        $updatedUser = getCurrentUser();
        if (!$updatedUser['avatar']) {
            $updatedUser['avatar'] = getDefaultAvatar($updatedUser['username']);
        }
        
        echo json_encode(['success' => true, 'user' => $updatedUser]);
        break;
        
    case 'edit_message':
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    $messageId = intval($_POST['message_id'] ?? 0);
    $newMessage = trim($_POST['message'] ?? '');
    
    if (empty($newMessage)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    // Verify user owns this message
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$messageId, $currentUserId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found or unauthorized']);
        exit;
    }
    
    // Only allow editing text messages
    if ($message['message_type'] !== 'text') {
        echo json_encode(['success' => false, 'message' => 'Only text messages can be edited']);
        exit;
    }
    
    // Update message
    $stmt = $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newMessage, $messageId]);
    
    echo json_encode(['success' => true, 'message' => 'Message updated successfully']);
    break;
    
    case 'delete_message':
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    $messageId = intval($_POST['message_id'] ?? 0);
    
    // Verify user owns this message
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$messageId, $currentUserId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found or unauthorized']);
        exit;
    }
    
    deleteMessage($messageId, $currentUserId);
    echo json_encode(['success' => true]);
    break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}