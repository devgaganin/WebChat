<?php
require_once 'admin_auth.php';
requireAdminLogin();

$query = $_GET['query'] ?? '';
$results = [];

if ($query && strlen($query) >= 2) {
    $results = searchMessages($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Messages - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Search Messages</h1>
                <div class="admin-user">
                    <span>Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>

            <div class="admin-section">
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="text" name="query" placeholder="Search message content..." value="<?php echo htmlspecialchars($query); ?>" autofocus>
                    </form>
                </div>

                <?php if ($query): ?>
                    <?php if (empty($results)): ?>
                        <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 20px; color: var(--text-secondary);">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <p style="color: var(--text-secondary); font-size: 1.1rem;">No messages found for "<?php echo htmlspecialchars($query); ?>"</p>
                        </div>
                    <?php else: ?>
                        <h2>Found <?php echo count($results); ?> message(s) for "<?php echo htmlspecialchars($query); ?>"</h2>
                        <div class="message-preview">
                            <?php foreach ($results as $msg): ?>
                                <div class="message-item">
                                    <div class="message-header">
                                        <div>
                                            <span class="message-sender">@<?php echo htmlspecialchars($msg['sender_username']); ?></span>
                                            <span class="badge badge-warning" style="margin-left: 10px;">Chat ID: <?php echo $msg['chat_id']; ?></span>
                                        </div>
                                        <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                    <div class="message-content">
                                        <?php
                                        // Highlight search query in message
                                        $message = htmlspecialchars($msg['message']);
                                        $highlighted = str_ireplace(
                                            $query, 
                                            '<mark style="background: rgba(0, 217, 255, 0.3); padding: 2px 4px; border-radius: 3px;">' . htmlspecialchars($query) . '</mark>', 
                                            $message
                                        );
                                        echo nl2br($highlighted);
                                        ?>
                                        
                                        <?php if ($msg['self_destruct_timer']): ?>
                                            <span class="badge badge-danger" style="margin-left: 10px;">
                                                🔥 Self-Destruct: <?php echo formatTimer($msg['self_destruct_timer']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <a href="chats.php?chat_id=<?php echo $msg['chat_id']; ?>" class="btn btn-primary btn-sm">View Full Chat</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 20px; color: var(--primary-color);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h2 style="margin-bottom: 10px;">Search All Messages</h2>
                        <p style="color: var(--text-secondary);">Enter keywords to search through all platform messages</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    function formatTimer($seconds) {
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h';
        return floor($seconds / 86400) . 'd';
    }
    ?>
</body>
</html>
