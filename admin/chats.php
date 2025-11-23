<?php
require_once 'admin_auth.php';
requireAdminLogin();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

$chats = getAllChats($limit, $offset);

$selectedChatId = isset($_GET['chat_id']) ? intval($_GET['chat_id']) : null;
$messages = [];

if ($selectedChatId) {
    $messages = getAdminChatMessages($selectedChatId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chats - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Chat Monitoring</h1>
                <div class="admin-user">
                    <span>Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>

            <div class="admin-section">
                <h2>All Conversations</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Chat ID</th>
                            <th>Between</th>
                            <th>Messages</th>
                            <th>Created</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($chats)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 40px;">No chats found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chats as $chat): ?>
                                <tr>
                                    <td><?php echo $chat['id']; ?></td>
                                    <td>
                                        <strong>@<?php echo htmlspecialchars($chat['user1_username']); ?></strong>
                                        ↔
                                        <strong>@<?php echo htmlspecialchars($chat['user2_username']); ?></strong>
                                    </td>
                                    <td><?php echo $chat['message_count']; ?> messages</td>
                                    <td><?php echo date('M j, Y', strtotime($chat['created_at'])); ?></td>
                                    <td><?php echo timeAgo($chat['updated_at']); ?></td>
                                    <td>
                                        <a href="?chat_id=<?php echo $chat['id']; ?>" class="btn btn-primary btn-sm">View Messages</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $page; ?></span>
                    <?php if (count($chats) === $limit): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next →</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($selectedChatId && !empty($messages)): ?>
                <div class="admin-section">
                    <h2>Messages in Chat #<?php echo $selectedChatId; ?></h2>
                    <div class="message-preview">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <span class="message-sender">@<?php echo htmlspecialchars($msg['sender_username']); ?></span>
                                    <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <div class="message-content">
                                    <?php if ($msg['message_type'] === 'text'): ?>
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?php echo strtoupper($msg['message_type']); ?> FILE</span>
                                        <?php if ($msg['file_name']): ?>
                                            - <?php echo htmlspecialchars($msg['file_name']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($msg['self_destruct_timer']): ?>
                                        <span class="badge badge-danger" style="margin-left: 10px;">
                                            🔥 Self-Destruct: <?php echo formatTimer($msg['self_destruct_timer']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="chats.php" class="btn btn-secondary">← Back to Chats List</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    function timeAgo($timestamp) {
        $time = strtotime($timestamp);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $time);
    }
    
    function formatTimer($seconds) {
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h';
        return floor($seconds / 86400) . 'd';
    }
    ?>
</body>
</html>
