<?php
// SPY CHAT - Enhanced Cleanup CRON Job with Auto-Delete
// Run this script every 1-2 minutes for real-time auto-delete
// Recommended CRON: */1 * * * * /usr/bin/php /path/to/cron_cleanup.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/files.php';

// Prevent browser access
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die('This script can only be run from command line or with manual_run parameter');
}

echo "[" . date('Y-m-d H:i:s') . "] Starting cleanup process...\n";

try {
    // Clean up expired messages (auto-delete)
    echo "Cleaning expired messages...\n";
    $deletedMessages = cleanupExpiredMessages();
    echo "✓ Deleted {$deletedMessages} expired messages\n";
    
    // Clean up expired files
    echo "Cleaning expired files...\n";
    $deletedFiles = cleanupExpiredFiles();
    echo "✓ Deleted {$deletedFiles} expired files\n";
    
    // Clean up expired file tokens
    echo "Cleaning expired file tokens...\n";
    $pdo = getDB();
    $stmt = $pdo->query("DELETE FROM file_tokens WHERE expires_at <= NOW()");
    $deletedTokens = $stmt->rowCount();
    echo "✓ Deleted {$deletedTokens} expired tokens\n";
    
    // Clean up old typing status (older than 10 seconds)
    echo "Cleaning old typing status...\n";
    $stmt = $pdo->query("DELETE FROM typing_status WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)");
    $deletedTyping = $stmt->rowCount();
    echo "✓ Cleaned {$deletedTyping} typing status entries\n";
    
    // Update offline status for inactive users (not seen in 5 minutes)
    echo "Updating user online status...\n";
    $stmt = $pdo->query("UPDATE users SET is_online = 0 WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_online = 1");
    $updatedUsers = $stmt->rowCount();
    echo "✓ Updated {$updatedUsers} users to offline\n";
    
    // Clean up old deleted logs (keep for 30 days)
    echo "Cleaning old deleted logs...\n";
    $stmt = $pdo->query("DELETE FROM deleted_logs WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $deletedLogs = $stmt->rowCount();
    echo "✓ Deleted {$deletedLogs} old log entries\n";
    
    // Clean up orphaned file storage entries
    echo "Cleaning orphaned files...\n";
    $stmt = $pdo->query("DELETE FROM file_storage WHERE message_id NOT IN (SELECT id FROM messages)");
    $orphanedFiles = $stmt->rowCount();
    echo "✓ Cleaned {$orphanedFiles} orphaned file entries\n";
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Cleanup completed successfully!\n";
    echo "====================================\n";
    echo "Summary:\n";
    echo "- Messages deleted: {$deletedMessages}\n";
    echo "- Files deleted: {$deletedFiles}\n";
    echo "- Tokens deleted: {$deletedTokens}\n";
    echo "- Typing status cleaned: {$deletedTyping}\n";
    echo "- Users updated: {$updatedUsers}\n";
    echo "- Logs deleted: {$deletedLogs}\n";
    echo "- Orphaned files: {$orphanedFiles}\n";
    echo "====================================\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("CRON cleanup error: " . $e->getMessage());
    exit(1);
}

exit(0);
