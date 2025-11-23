<?php
require_once 'config.php';

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Upload file securely
function uploadFile($file, $messageId, $userId) {
    // Validate file
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedTypes = array_merge(
        ALLOWED_IMAGE_TYPES,
        ALLOWED_VIDEO_TYPES,
        ALLOWED_AUDIO_TYPES,
        ALLOWED_FILE_TYPES
    );
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Determine file type category
    $fileType = 'file';
    if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        $fileType = 'image';
    } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
        $fileType = 'video';
    } elseif (in_array($mimeType, ALLOWED_AUDIO_TYPES)) {
        $fileType = 'audio';
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $hash = hash('sha256', $userId . time() . $file['name']);
    $filename = $hash . '.' . $extension;
    
    // Create user-specific directory
    $userDir = UPLOAD_DIR . '/' . $userId;
    if (!file_exists($userDir)) {
        mkdir($userDir, 0755, true);
    }
    
    $filePath = $userDir . '/' . $filename;
    $relativePath = $userId . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Store file info in database
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO file_storage (message_id, file_hash, file_path, original_name, mime_type, file_size)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $messageId,
        $hash,
        $relativePath,
        sanitizeFilename($file['name']),
        $mimeType,
        $file['size']
    ]);
    
    return [
        'success' => true,
        'file_path' => $relativePath,
        'file_name' => sanitizeFilename($file['name']),
        'file_size' => $file['size'],
        'file_type' => $fileType,
        'mime_type' => $mimeType
    ];
}

// Generate file access token
function generateFileToken($fileId, $userId) {
    $pdo = getDB();
    
    // Check if user has access to this file
    $stmt = $pdo->prepare("
        SELECT fs.id 
        FROM file_storage fs
        INNER JOIN messages m ON m.id = fs.message_id
        WHERE fs.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->execute([$fileId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        return null;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + FILE_TOKEN_LIFETIME);
    
    $stmt = $pdo->prepare("
        INSERT INTO file_tokens (token, file_id, user_id, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$token, $fileId, $userId, $expiresAt]);
    
    return $token;
}

// Validate file token and get file info
function validateFileToken($token) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        SELECT ft.file_id, ft.user_id, fs.file_path, fs.original_name, fs.mime_type
        FROM file_tokens ft
        INNER JOIN file_storage fs ON fs.id = ft.file_id
        WHERE ft.token = ? AND ft.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    
    return $stmt->fetch();
}

// Serve file securely
function serveFile($token) {
    $fileInfo = validateFileToken($token);
    
    if (!$fileInfo) {
        http_response_code(404);
        die('File not found or token expired');
    }
    
    $filePath = UPLOAD_DIR . '/' . $fileInfo['file_path'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Set headers
    header('Content-Type: ' . $fileInfo['mime_type']);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . $fileInfo['original_name'] . '"');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    
    // Serve file
    readfile($filePath);
    exit;
}

// Get file URL with token
function getFileUrl($fileId, $userId) {
    $token = generateFileToken($fileId, $userId);
    if (!$token) {
        return null;
    }
    return SITE_URL . '/serve.php?token=' . $token;
}

// Get file info from message
function getFileInfoFromMessage($messageId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id, file_path, original_name, mime_type, file_size FROM file_storage WHERE message_id = ?");
    $stmt->execute([$messageId]);
    
    return $stmt->fetch();
}

// Delete file
function deleteFile($fileId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT file_path FROM file_storage WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        return false;
    }
    
    $filePath = UPLOAD_DIR . '/' . $file['file_path'];
    
    // Delete physical file
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM file_storage WHERE id = ?");
    $stmt->execute([$fileId]);
    
    // Delete tokens
    $stmt = $pdo->prepare("DELETE FROM file_tokens WHERE file_id = ?");
    $stmt->execute([$fileId]);
    
    return true;
}

// Clean up expired files
function cleanupExpiredFiles() {
    $pdo = getDB();
    
    // Get files from expired messages
    $stmt = $pdo->query("
        SELECT fs.id, fs.file_path 
        FROM file_storage fs
        INNER JOIN messages m ON m.id = fs.message_id
        WHERE m.destruct_at IS NOT NULL AND m.destruct_at <= NOW()
    ");
    
    $files = $stmt->fetchAll();
    
    foreach ($files as $file) {
        deleteFile($file['id']);
    }
    
    return count($files);
}

// Get file size in human readable format
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
