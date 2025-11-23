<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'chat.php';

requireLogin();

$currentUser = getCurrentUser();
$chats = getUserChats($currentUser['id']);
$unreadCount = getUnreadCount($currentUser['id']);

// Update online status
updateOnlineStatus($currentUser['id'], true);

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Check if user has avatar or needs profile icon
$hasAvatar = !empty($currentUser['avatar']) && !str_contains($currentUser['avatar'], 'data:image/svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="chat-page">
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-header-row">
                    <div class="user-info">
                        <div class="user-avatar <?php echo !$hasAvatar ? 'no-avatar' : ''; ?>">
                            <?php if ($hasAvatar): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar">
                            <?php else: ?>
                                <svg class="user-avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <h3><?php echo htmlspecialchars($currentUser['display_name'] ?? $currentUser['username']); ?></h3>
                            <span class="user-status online">Online</span>
                        </div>
                    </div>
                    <div class="sidebar-actions">
                        <button class="btn-icon" id="newChatBtn" title="New Chat">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </button>
                        <button class="btn-icon" id="profileBtn" title="Profile">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </button>
                        <a href="logout.php" class="btn-icon btn-logout" title="Logout">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar-search">
                <input type="text" placeholder="Search users..." id="searchUsers">
            </div>

            <div class="chat-list" id="chatList">
                <?php if (empty($chats)): ?>
                    <div class="empty-state">
                        <p>No conversations yet</p>
                        <small>Start a new chat to begin</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($chats as $chat): 
                        $chatHasAvatar = !empty($chat['avatar']) && !str_contains($chat['avatar'], 'data:image/svg');
                    ?>
                        <div class="chat-item" data-chat-id="<?php echo $chat['chat_id']; ?>" data-user-id="<?php echo $chat['other_user_id']; ?>">
                            <div class="chat-avatar <?php echo !$chatHasAvatar ? 'no-avatar' : ''; ?>">
                                <?php if ($chatHasAvatar): ?>
                                    <img src="<?php echo htmlspecialchars($chat['avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <svg class="chat-avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                <?php endif; ?>
                                <?php if ($chat['is_online']): ?>
                                    <span class="online-indicator"></span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-info">
                                <div class="chat-header-info">
                                    <h4><?php echo htmlspecialchars($chat['display_name'] ?? $chat['username']); ?></h4>
                                    <span class="chat-time"><?php echo timeAgo($chat['last_message_time']); ?></span>
                                </div>
                                <div class="chat-preview">
                                    <p><?php 
                                        if ($chat['last_message_type'] === 'text') {
                                            echo htmlspecialchars(substr($chat['last_message'], 0, 50));
                                        } else {
                                            echo '📎 ' . ucfirst($chat['last_message_type'] ?? 'File');
                                        }
                                    ?></p>
                                    <?php if ($chat['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $chat['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="chat-welcome">
                <div class="logo-large">
                    <svg width="80" height="80" viewBox="0 0 40 40" fill="none">
                        <path d="M20 5L35 12.5V27.5L20 35L5 27.5V12.5L20 5Z" stroke="currentColor" stroke-width="2" fill="none"/>
                        <circle cx="20" cy="20" r="5" fill="currentColor"/>
                    </svg>
                </div>
                <h2>SPY CHAT</h2>
                <p>Select a chat to start messaging</p>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div class="modal" id="newChatModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Start New Chat</h3>
                <button class="btn-close" onclick="closeModal('newChatModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" placeholder="Search username..." id="searchNewUser" autocomplete="off">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Your Profile</h3>
                <button class="btn-close" onclick="closeModal('profileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profileForm" enctype="multipart/form-data">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar-large <?php echo !$hasAvatar ? 'no-avatar' : ''; ?>">
                            <?php if ($hasAvatar): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar" id="profileAvatarPreview">
                            <?php else: ?>
                                <svg class="user-avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 50px; height: 50px;">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarInput').click()">Change Avatar</button>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" id="displayName" value="<?php echo htmlspecialchars($currentUser['display_name'] ?? $currentUser['username']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_USER_ID = <?php echo $currentUser['id']; ?>;
        const CURRENT_USERNAME = <?php echo json_encode($currentUser['username']); ?>;
        const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
    </script>
    <script src="assets/js/security.js"></script>
    <script src="assets/js/main.js"></script>
    
    <?php
    // Helper function for time ago
    function timeAgo($timestamp) {
        if (!$timestamp) return '';
        
        $time = strtotime($timestamp);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm';
        if ($diff < 86400) return floor($diff / 3600) . 'h';
        if ($diff < 604800) return floor($diff / 86400) . 'd';
        return date('M j', $time);
    }
    ?>
</body>
</html>
