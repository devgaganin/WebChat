<?php
require_once 'admin_auth.php';
requireAdminLogin();

$stats = getAdminStatistics();
$recentActivity = getRecentActivity(15);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SPY CHAT</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(0, 217, 255, 0.1); color: var(--primary-color);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                        <small>+<?php echo $stats['new_users_today']; ?> today</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1); color: var(--success-color);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['online_users']); ?></h3>
                        <p>Online Users</p>
                        <small><?php echo round(($stats['online_users'] / max($stats['total_users'], 1)) * 100, 1); ?>% active</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--secondary-color);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_chats']); ?></h3>
                        <p>Total Chats</p>
                        <small>Active conversations</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1); color: var(--warning-color);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_messages']); ?></h3>
                        <p>Total Messages</p>
                        <small>+<?php echo $stats['messages_today']; ?> today</small>
                    </div>
                </div>
            </div>

            <div class="admin-section">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <?php if (empty($recentActivity)): ?>
                        <div class="empty-state">No recent activity</div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php if ($activity['message_type'] === 'text'): ?>
                                        💬
                                    <?php elseif ($activity['message_type'] === 'image'): ?>
                                        🖼️
                                    <?php elseif ($activity['message_type'] === 'video'): ?>
                                        🎥
                                    <?php elseif ($activity['message_type'] === 'audio'): ?>
                                        🎵
                                    <?php else: ?>
                                        📎
                                    <?php endif; ?>
                                </div>
                                <div class="activity-info">
                                    <p><strong>@<?php echo htmlspecialchars($activity['sender_username']); ?></strong> sent a <?php echo $activity['message_type']; ?></p>
                                    <small><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-section">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="quick-actions">
                    <a href="users.php" class="action-card">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>Manage Users</h3>
                        <p>View and manage all users</p>
                    </a>

                    <a href="chats.php" class="action-card">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <h3>View Chats</h3>
                        <p>Monitor conversations</p>
                    </a>

                    <a href="search.php" class="action-card">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3>Search Messages</h3>
                        <p>Search through all messages</p>
                    </a>
                </div>
            </div>
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
    ?>
</body>
</html>
