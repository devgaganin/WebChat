// SPY CHAT - Copyright ©️ github.com/devgagan.in

let currentChatId = null;
let currentOtherUserId = null;
let currentOtherUser = null;
let lastMessageId = 0;
let pollingInterval = null;
let typingTimeout = null;
let autoDeleteCheckInterval = null;
let activeMessageMenu = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeChatListeners();
    initializeModals();
    initializeProfile();
    startAutoDeleteCheck();
    createImageViewer();
    createMessageActionMenu();
    
    // Close menu on outside click
    document.addEventListener('click', function(e) {
        if (activeMessageMenu && !e.target.closest('.message-action-menu') && !e.target.closest('.chat-message')) {
            closeMessageMenu();
        }
    });
    
    // Handle mobile back button
    window.addEventListener('popstate', function() {
        closeChatMobile();
    });
});

// Get CSRF Token
function getCSRFToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) return metaToken.getAttribute('content');
    if (typeof CSRF_TOKEN !== 'undefined') return CSRF_TOKEN;
    const input = document.querySelector('input[name="csrf_token"]');
    if (input) return input.value;
    return '';
}

// Initialize chat item listeners
function initializeChatListeners() {
    const chatItems = document.querySelectorAll('.chat-item');
    chatItems.forEach(item => {
        item.addEventListener('click', function() {
            const chatId = this.dataset.chatId;
            const userId = this.dataset.userId;
            
            const avatarImg = this.querySelector('.chat-avatar img');
            const avatarIcon = this.querySelector('.chat-avatar-icon');
            const avatar = avatarImg ? avatarImg.src : null;
            const hasAvatar = avatarImg !== null;
            
            const username = this.querySelector('.chat-header-info h4').textContent;
            const isOnline = this.querySelector('.online-indicator') !== null;
            
            const userInfo = {
                id: userId,
                username: username,
                display_name: username,
                avatar: avatar,
                has_avatar: hasAvatar,
                is_online: isOnline
            };
            
            openChat(chatId, userId, userInfo);
        });
    });
}

// Open chat - Mobile optimized
function openChat(chatId, otherUserId, userInfo) {
    currentChatId = chatId;
    currentOtherUserId = otherUserId;
    currentOtherUser = userInfo;
    lastMessageId = 0;
    
    // Update active state
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeItem = document.querySelector(`[data-chat-id="${chatId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
    
    // Mobile: Show chat area, hide sidebar
    const chatArea = document.getElementById('chatArea');
    const sidebar = document.getElementById('sidebar');
    
    chatArea.classList.add('active');
    sidebar.classList.add('hidden');
    
    // Add to history for back button
    if (window.history.state !== 'chat') {
        window.history.pushState('chat', '', '');
    }
    
    // Render chat interface
    renderChatInterface(userInfo);
    
    // Load messages
    loadMessages();
    
    // Start polling
    startPolling();
    
    // Scroll to bottom after a brief delay
    setTimeout(scrollToBottom, 300);
}

// Render chat interface (NO TIMER)
function renderChatInterface(user) {
    const chatArea = document.getElementById('chatArea');
    
    const avatarHTML = user.has_avatar && user.avatar ? 
        `<img src="${user.avatar}" alt="Avatar">` :
        `<svg class="chat-avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>`;
    
    chatArea.innerHTML = `
        <div class="chat-header">
            <div class="chat-header-user">
                <button class="btn-icon mobile-back" onclick="closeChatMobile()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div class="chat-avatar ${!user.has_avatar ? 'no-avatar' : ''}">
                    ${avatarHTML}
                    ${user.is_online ? '<span class="online-indicator"></span>' : ''}
                </div>
                <div class="chat-header-info">
                    <h3>${escapeHtml(user.display_name || user.username)}</h3>
                    <span class="user-status ${user.is_online ? 'online' : ''}">${user.is_online ? 'Online' : 'Offline'}</span>
                </div>
            </div>
            <div class="chat-header-actions">
                <button class="btn-icon" onclick="deleteChatConfirm()" title="Delete Chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="loading">Loading messages...</div>
        </div>
        
        <div class="typing-indicator" id="typingIndicator" style="display:none;">
            <span></span><span></span><span></span>
        </div>
        
        <div class="chat-input-container">
            <form id="messageForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="${getCSRFToken()}">
                <input type="hidden" name="chat_id" value="${currentChatId}">
                <input type="hidden" name="receiver_id" value="${currentOtherUserId}">
                <input type="hidden" name="action" value="send_message">
                
                <div id="filePreview" style="display:none;"></div>
                
                <div class="chat-input-wrapper">
                    <button type="button" class="btn-attach" onclick="document.getElementById('fileInput').click()" title="Attach File">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </button>
                    <input type="file" id="fileInput" name="file" style="display:none;" onchange="handleFileSelect(this)">
                    
                    <input type="text" name="message" id="messageInput" placeholder="Type a message..." autocomplete="off" onkeydown="handleTyping(event)">
                    
                    <button type="submit" class="btn-send">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    `;
    
    // Add event listener to message form
    document.getElementById('messageForm').addEventListener('submit', sendMessage);
    
    // Add enter key support
    const messageInput = document.getElementById('messageInput');
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('messageForm').dispatchEvent(new Event('submit'));
        }
    });
}

// Create Message Action Menu
function createMessageActionMenu() {
    const menu = document.createElement('div');
    menu.id = 'messageActionMenu';
    menu.className = 'message-action-menu';
    menu.style.display = 'none';
    menu.innerHTML = `
        <button class="action-menu-item" onclick="editMessage()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            <span>Edit</span>
        </button>
        <button class="action-menu-item delete" onclick="deleteMessage()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            <span>Delete</span>
        </button>
        <button class="action-menu-item" onclick="copyMessage()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            <span>Copy</span>
        </button>
    `;
    document.body.appendChild(menu);
}

// Show Message Actions
function showMessageActions(messageElement, messageId, messageText) {
    const menu = document.getElementById('messageActionMenu');
    if (!menu) return;
    
    // Close previous menu
    closeMessageMenu();
    
    // Store current message data
    menu.dataset.messageId = messageId;
    menu.dataset.messageText = messageText;
    menu.dataset.messageElement = messageElement.dataset.messageId;
    
    // Position menu near message
    const rect = messageElement.getBoundingClientRect();
    const menuHeight = 150; // Approximate menu height
    
    // Check if menu fits below message
    if (rect.bottom + menuHeight < window.innerHeight) {
        menu.style.top = (rect.bottom + 5) + 'px';
    } else {
        menu.style.top = (rect.top - menuHeight - 5) + 'px';
    }
    
    // Position horizontally
    if (messageElement.classList.contains('own')) {
        menu.style.right = '20px';
        menu.style.left = 'auto';
    } else {
        menu.style.left = '20px';
        menu.style.right = 'auto';
    }
    
    menu.style.display = 'block';
    activeMessageMenu = menu;
    
    // Add active class to message
    messageElement.classList.add('message-active');
    
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(10);
    }
}

function closeMessageMenu() {
    const menu = document.getElementById('messageActionMenu');
    if (menu) {
        menu.style.display = 'none';
        activeMessageMenu = null;
    }
    
    // Remove active class from all messages
    document.querySelectorAll('.message-active').forEach(msg => {
        msg.classList.remove('message-active');
    });
}

// Edit Message
function editMessage() {
    const menu = document.getElementById('messageActionMenu');
    if (!menu) return;
    
    const messageId = menu.dataset.messageId;
    const messageText = menu.dataset.messageText;
    
    closeMessageMenu();
    
    // Show edit modal
    const modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Edit Message</h3>
                <button class="btn-close" onclick="this.closest('.modal').remove()">×</button>
            </div>
            <div class="modal-body">
                <textarea id="editMessageText" style="width: 100%; min-height: 100px; padding: 12px; background: var(--background-color); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-primary); font-size: 15px; resize: vertical;">${escapeHtml(messageText)}</textarea>
                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button class="btn btn-primary" style="flex: 1;" onclick="saveEditedMessage('${messageId}')">Save</button>
                    <button class="btn" style="flex: 1; background: var(--surface-elevated); color: var(--text-primary);" onclick="this.closest('.modal').remove()">Cancel</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.getElementById('editMessageText').focus();
}

function saveEditedMessage(messageId) {
    const newText = document.getElementById('editMessageText').value.trim();
    
    if (!newText) {
        showToast('Message cannot be empty', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'edit_message');
    formData.append('csrf_token', getCSRFToken());
    formData.append('message_id', messageId);
    formData.append('message', newText);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Message updated', 'success');
            document.querySelector('.modal').remove();
            
            // Update message in UI
            const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageEl) {
                const textEl = messageEl.querySelector('.chat-message-text');
                if (textEl) {
                    textEl.innerHTML = escapeHtml(newText) + ' <span class="edited-badge">edited</span>';
                }
            }
        } else {
            showToast(data.message || 'Failed to edit message', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Failed to edit message', 'error');
    });
}

// Delete Message
function deleteMessage() {
    const menu = document.getElementById('messageActionMenu');
    if (!menu) return;
    
    const messageId = menu.dataset.messageId;
    
    closeMessageMenu();
    
    if (!confirm('Delete this message? This cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_message');
    formData.append('csrf_token', getCSRFToken());
    formData.append('message_id', messageId);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Message deleted', 'success');
            
            // Remove message from UI with animation
            const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageEl) {
                messageEl.style.opacity = '0';
                messageEl.style.transform = 'scale(0.9)';
                setTimeout(() => messageEl.remove(), 300);
            }
        } else {
            showToast(data.message || 'Failed to delete message', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Failed to delete message', 'error');
    });
}

// Copy Message
function copyMessage() {
    const menu = document.getElementById('messageActionMenu');
    if (!menu) return;
    
    const messageText = menu.dataset.messageText;
    
    closeMessageMenu();
    
    navigator.clipboard.writeText(messageText).then(() => {
        showToast('Message copied', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

// Load messages
function loadMessages() {
    if (!currentChatId) return;
    
    const url = `api.php?action=get_messages&chat_id=${currentChatId}&last_message_id=${lastMessageId}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (lastMessageId === 0) {
                    renderMessages(data.messages, true);
                } else if (data.messages.length > 0) {
                    renderMessages(data.messages, false);
                }
                
                if (data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                }
            }
        })
        .catch(err => console.error('Error loading messages:', err));
}

// Render messages
function renderMessages(messages, replaceAll) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    
    if (replaceAll) {
        if (messages.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No messages yet</p><small>Start the conversation!</small></div>';
            return;
        }
        container.innerHTML = '';
    }
    
    messages.forEach(msg => {
        const messageEl = createMessageElement(msg);
        container.appendChild(messageEl);
    });
    
    scrollToBottom();
}

// Create message element
function createMessageElement(msg) {
    const div = document.createElement('div');
    div.className = `chat-message ${msg.is_own ? 'own' : 'other'}`;
    div.dataset.messageId = msg.id;
    
    // Add click handler for own messages
    if (msg.is_own && msg.message_type === 'text') {
        div.style.cursor = 'pointer';
        div.addEventListener('click', function(e) {
            if (!e.target.closest('.action-menu-item')) {
                showMessageActions(this, msg.id, msg.message);
            }
        });
    }
    
    let content = '';
    
    if (msg.message_type === 'text') {
        const editedBadge = msg.is_edited ? ' <span class="edited-badge">edited</span>' : '';
        content = `<div class="chat-message-text">${escapeHtml(msg.message)}${editedBadge}</div>`;
    } else if (msg.message_type === 'image') {
        content = `<div class="chat-message-media"><img src="${msg.file_url}" alt="Image" onclick="openImageViewer('${msg.file_url}')" onload="scrollToBottom()"></div>`;
    } else if (msg.message_type === 'video') {
        content = `<div class="chat-message-media"><video controls><source src="${msg.file_url}"></video></div>`;
    } else if (msg.message_type === 'audio') {
        content = `<div class="chat-message-media"><audio controls><source src="${msg.file_url}"></audio></div>`;
    } else {
        content = `<div class="chat-message-file">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                <polyline points="13 2 13 9 20 9"></polyline>
            </svg>
            <div>
                <div>${escapeHtml(msg.file_name || 'File')}</div>
                <small>${msg.file_size_formatted || ''}</small>
            </div>
            <a href="${msg.file_url}" download class="btn-download">↓</a>
        </div>`;
    }
    
    div.innerHTML = `
        ${content}
        <div class="chat-message-meta">
            <span class="chat-message-time">${msg.time_formatted}</span>
            ${msg.is_own ? `<span class="chat-message-status">${msg.is_read ? '✓✓' : '✓'}</span>` : ''}
        </div>
    `;
    
    return div;
}

// Create Image Viewer Modal
function createImageViewer() {
    const viewer = document.createElement('div');
    viewer.id = 'imageViewerModal';
    viewer.className = 'image-viewer-modal';
    viewer.innerHTML = `
        <div class="image-viewer-content">
            <img id="imageViewerImg" class="image-viewer-img" src="" alt="Image">
            <button class="image-viewer-close" onclick="closeImageViewer()">×</button>
        </div>
    `;
    document.body.appendChild(viewer);
    
    viewer.addEventListener('click', function(e) {
        if (e.target === viewer || e.target.classList.contains('image-viewer-content')) {
            closeImageViewer();
        }
    });
}

function openImageViewer(imageSrc) {
    const modal = document.getElementById('imageViewerModal');
    const img = document.getElementById('imageViewerImg');
    if (modal && img) {
        img.src = imageSrc;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeImageViewer() {
    const modal = document.getElementById('imageViewerModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Send message (NO TIMER)
function sendMessage(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    const fileInput = document.getElementById('fileInput');
    
    if (!message && !fileInput.files.length) {
        return;
    }
    
    messageInput.disabled = true;
    
    if (navigator.vibrate) {
        navigator.vibrate(10);
    }
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const messageEl = createMessageElement(data.message);
            document.getElementById('chatMessages').appendChild(messageEl);
            messageInput.value = '';
            fileInput.value = '';
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('filePreview').innerHTML = '';
            lastMessageId = data.message.id;
            scrollToBottom();
        } else {
            showToast(data.message || 'Failed to send message', 'error');
        }
    })
    .catch(err => {
        console.error('Error sending message:', err);
        showToast('Failed to send message', 'error');
    })
    .finally(() => {
        messageInput.disabled = false;
        messageInput.focus();
    });
}

// Handle typing
function handleTyping(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        return;
    }
    
    clearTimeout(typingTimeout);
    sendTypingStatus(true);
    
    typingTimeout = setTimeout(() => {
        sendTypingStatus(false);
    }, 3000);
}

function sendTypingStatus(isTyping) {
    if (!currentChatId) return;
    
    const formData = new FormData();
    formData.append('action', 'typing_status');
    formData.append('chat_id', currentChatId);
    formData.append('is_typing', isTyping);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    }).catch(() => {});
}

function checkTyping() {
    if (!currentChatId || !currentOtherUserId) return;
    
    fetch(`api.php?action=check_typing&chat_id=${currentChatId}&other_user_id=${currentOtherUserId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const indicator = document.getElementById('typingIndicator');
                if (indicator) {
                    indicator.style.display = data.is_typing ? 'flex' : 'none';
                    if (data.is_typing) scrollToBottom();
                }
            }
        })
        .catch(() => {});
}

function startPolling() {
    stopPolling();
    pollingInterval = setInterval(() => {
        loadMessages();
        checkTyping();
        updateMessageTimers();
    }, 2000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

function startAutoDeleteCheck() {
    autoDeleteCheckInterval = setInterval(() => {
        updateMessageTimers();
    }, 1000);
}

function updateMessageTimers() {
    const messages = document.querySelectorAll('.chat-message[data-destruct-at]');
    const now = new Date().getTime();
    
    messages.forEach(msg => {
        const destructTime = new Date(msg.dataset.destructAt).getTime();
        const remaining = Math.max(0, Math.floor((destructTime - now) / 1000));
        
        const timerEl = msg.querySelector('.chat-message-timer');
        
        if (remaining <= 0) {
            msg.style.opacity = '0';
            msg.style.transform = 'scale(0.9)';
            msg.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                msg.remove();
                const container = document.getElementById('chatMessages');
                if (container && container.children.length === 0) {
                    container.innerHTML = '<div class="empty-state"><p>No messages yet</p><small>Start the conversation!</small></div>';
                }
            }, 300);
        } else if (timerEl) {
            timerEl.textContent = `🔥 ${formatTimer(remaining)}`;
            timerEl.dataset.remaining = remaining;
            
            if (remaining <= 10) {
                timerEl.style.animation = 'pulse 0.5s infinite';
            }
        }
    });
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5242880) {
        showToast('File size exceeds 5MB limit', 'error');
        input.value = '';
        return;
    }
    
    const preview = document.getElementById('filePreview');
    preview.style.display = 'block';
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface-color); border-radius: 12px; border: 1px solid var(--border-color);">
                    <img src="${e.target.result}" style="max-width:80px; max-height:80px; border-radius:8px; object-fit:cover;">
                    <span style="flex:1; font-size:14px;">${escapeHtml(file.name)}</span>
                    <button type="button" onclick="clearFileSelection()" style="background: var(--error-color); color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size:13px; font-weight:600;">Remove</button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface-color); border-radius: 12px; border: 1px solid var(--border-color);">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:500;">${escapeHtml(file.name)}</div>
                    <small style="color: var(--text-secondary);">${formatFileSize(file.size)}</small>
                </div>
                <button type="button" onclick="clearFileSelection()" style="background: var(--error-color); color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size:13px; font-weight:600;">Remove</button>
            </div>
        `;
    }
}

function clearFileSelection() {
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('filePreview');
    if (fileInput) fileInput.value = '';
    if (preview) {
        preview.style.display = 'none';
        preview.innerHTML = '';
    }
}

function deleteChatConfirm() {
    if (confirm('Delete this entire conversation? This cannot be undone and will delete for BOTH users.')) {
        deleteChat();
    }
}

function deleteChat() {
    const formData = new FormData();
    formData.append('action', 'delete_chat');
    formData.append('csrf_token', getCSRFToken());
    formData.append('chat_id', currentChatId);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Chat deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Failed to delete chat', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Failed to delete chat', 'error');
    });
}

function initializeModals() {
    const newChatBtn = document.getElementById('newChatBtn');
    if (newChatBtn) {
        newChatBtn.addEventListener('click', function() {
            openModal('newChatModal');
        });
    }
    
    const searchNewUser = document.getElementById('searchNewUser');
    if (searchNewUser) {
        let searchTimeout;
        searchNewUser.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`api.php?action=search_users&query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            renderSearchResults(data.users);
                        }
                    })
                    .catch(err => console.error('Search error:', err));
            }, 300);
        });
    }
    
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
}

function renderSearchResults(users) {
    const container = document.getElementById('searchResults');
    
    if (users.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>No users found</p></div>';
        return;
    }
    
    container.innerHTML = users.map(user => {
        const hasAvatar = user.avatar && !user.avatar.includes('data:image/svg');
        const avatarHTML = hasAvatar ? 
            `<img src="${user.avatar}" alt="Avatar">` :
            `<svg class="chat-avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>`;
        
        return `
            <div class="user-result" onclick="startChat('${escapeHtml(user.username)}')">
                <div class="user-result-avatar ${!hasAvatar ? 'no-avatar' : ''}">
                    ${avatarHTML}
                    ${user.is_online ? '<span class="online-indicator"></span>' : ''}
                </div>
                <div class="user-result-info">
                    <h4>${escapeHtml(user.display_name || user.username)}</h4>
                    <p>@${escapeHtml(user.username)}</p>
                </div>
            </div>
        `;
    }).join('');
}

function startChat(username) {
    const formData = new FormData();
    formData.append('action', 'start_chat');
    formData.append('username', username);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal('newChatModal');
            location.reload();
        } else {
            showToast(data.message || 'Failed to start chat', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showToast('Failed to start chat', 'error');
    });
}

function initializeProfile() {
    const profileBtn = document.getElementById('profileBtn');
    if (profileBtn) {
        profileBtn.addEventListener('click', function() {
            openModal('profileModal');
        });
    }
    
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    showToast('Please select an image file', 'error');
                    e.target.value = '';
                    return;
                }
                
                if (file.size > 5242880) {
                    showToast('Image size exceeds 5MB limit', 'error');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileAvatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('csrf_token', getCSRFToken());
            formData.append('display_name', document.getElementById('displayName').value);
            
            const avatarFile = document.getElementById('avatarInput').files[0];
            if (avatarFile) {
                formData.append('avatar', avatarFile);
            }
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Profile updated successfully!', 'success');
                    closeModal('profileModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Failed to update profile', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to update profile', 'error');
            });
        });
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    if (container) {
        requestAnimationFrame(() => {
            container.scrollTop = container.scrollHeight;
        });
    }
}

function formatTimer(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
    return `${Math.floor(seconds / 86400)}d`;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function closeChatMobile() {
    const chatArea = document.getElementById('chatArea');
    const sidebar = document.getElementById('sidebar');
    
    if (chatArea) chatArea.classList.remove('active');
    if (sidebar) sidebar.classList.remove('hidden');
    
    stopPolling();
    closeMessageMenu();
    
    if (window.history.state === 'chat') {
        window.history.back();
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: ${type === 'error' ? 'var(--error-color)' : type === 'success' ? 'var(--success-color)' : 'var(--surface-color)'};
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
    
    if (navigator.vibrate) {
        navigator.vibrate(type === 'error' ? [50, 50, 50] : 50);
    }
}

window.addEventListener('beforeunload', function() {
    stopPolling();
    if (autoDeleteCheckInterval) {
        clearInterval(autoDeleteCheckInterval);
    }
});

const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateX(-50%) translateY(20px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
`;
document.head.appendChild(style);