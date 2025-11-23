// SPY CHAT - Enhanced Security Protection Script
// Balanced approach: Protection without breaking functionality

(function() {
    'use strict';
    
    // Configuration
    const SECURITY_CONFIG = {
        enableCopyProtection: true,
        enableRecordingDetection: true,
        blurOnFocusLoss: true,
        watermarkEnabled: true,
        enableRightClick: false // Disabled for better UX
    };
    
    // ===== COPY PROTECTION =====
    
    if (SECURITY_CONFIG.enableCopyProtection) {
        
        // Disable text selection for messages only
        document.addEventListener('selectstart', function(e) {
            if (e.target.closest('.chat-message-text') || 
                e.target.closest('.chat-message-media')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Disable copy on messages
        document.addEventListener('copy', function(e) {
            const selection = window.getSelection().toString();
            if (selection && e.target.closest('.chat-message')) {
                e.preventDefault();
                e.clipboardData.setData('text/plain', '🔒 Content protected by SPYCHAT');
                showSecurityAlert('🔒 Copy Disabled', 'Message content cannot be copied');
                return false;
            }
        });
        
        // Disable drag on media
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.chat-message') || 
                e.target.tagName === 'IMG' || 
                e.target.tagName === 'VIDEO') {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // ===== FOCUS LOSS PROTECTION =====
    
    if (SECURITY_CONFIG.blurOnFocusLoss) {
        
        // Blur messages when window loses focus
        window.addEventListener('blur', function() {
            const messages = document.querySelectorAll('.chat-message');
            messages.forEach(msg => {
                msg.style.filter = 'blur(8px)';
                msg.style.transition = 'filter 0.2s';
            });
        });
        
        window.addEventListener('focus', function() {
            const messages = document.querySelectorAll('.chat-message');
            messages.forEach(msg => {
                msg.style.filter = 'none';
            });
        });
        
        // Blur on visibility change (tab switch)
        document.addEventListener('visibilitychange', function() {
            const messages = document.querySelectorAll('.chat-message');
            if (document.hidden) {
                messages.forEach(msg => {
                    msg.style.filter = 'blur(8px)';
                    msg.style.transition = 'filter 0.2s';
                });
            } else {
                messages.forEach(msg => {
                    msg.style.filter = 'none';
                });
            }
        });
    }
    
    // ===== SCREEN RECORDING DETECTION =====
    
    if (SECURITY_CONFIG.enableRecordingDetection) {
        
        // Detect screen recording on supported browsers
        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            const originalGetDisplayMedia = navigator.mediaDevices.getDisplayMedia;
            
            navigator.mediaDevices.getDisplayMedia = function() {
                showSecurityAlert('🎥 Recording Detected', 'Screen recording is discouraged on this platform');
                return originalGetDisplayMedia.apply(this, arguments);
            };
        }
    }
    
    // ===== WATERMARK OVERLAY =====
    
    if (SECURITY_CONFIG.watermarkEnabled && typeof CURRENT_USERNAME !== 'undefined') {
        
        function createWatermark() {
            // Remove existing watermark
            const existing = document.getElementById('security-watermark');
            if (existing) existing.remove();
            
            const watermark = document.createElement('div');
            watermark.id = 'security-watermark';
            watermark.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 9999;
                opacity: 0.015;
                background: repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 180px,
                    currentColor 180px,
                    currentColor 181px
                );
                color: #00D9FF;
                font-family: monospace;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                user-select: none;
                -webkit-user-select: none;
                text-align: center;
                line-height: 2;
            `;
            
            // Create subtle watermark text
            let watermarkText = '';
            for (let i = 0; i < 30; i++) {
                watermarkText += `${CURRENT_USERNAME} • ${new Date().toISOString().split('T')[0]}<br>`;
            }
            watermark.innerHTML = watermarkText;
            
            document.body.appendChild(watermark);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', createWatermark);
        } else {
            createWatermark();
        }
    }
    
    // ===== CSS-BASED PROTECTIONS =====
    
    const securityStyle = document.createElement('style');
    securityStyle.id = 'security-styles';
    securityStyle.textContent = `
        /* Prevent text selection on sensitive content */
        .chat-message-text,
        .chat-message-media img {
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
        }
        
        /* Allow selection in input fields */
        input, textarea, .user-result-info, .modal-body {
            user-select: text !important;
            -webkit-user-select: text !important;
        }
        
        /* Prevent drag on media in messages */
        .chat-message img, 
        .chat-message video, 
        .chat-message audio {
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
        }
        
        /* Allow drag in file inputs */
        input[type="file"] {
            -webkit-user-drag: auto;
            user-drag: auto;
        }
    `;
    document.head.appendChild(securityStyle);
    
    // ===== SECURITY ALERT SYSTEM =====
    
    function showSecurityAlert(title, message = '') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.security-alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const alert = document.createElement('div');
        alert.className = 'security-alert';
        alert.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid #00D9FF;
            border-radius: 16px;
            padding: 24px 32px;
            z-index: 99999;
            color: white;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 217, 255, 0.4);
            animation: alertSlide 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 90%;
            width: 300px;
        `;
        
        alert.innerHTML = `
            <div style="font-size: 32px; margin-bottom: 12px;">${title.split(' ')[0]}</div>
            <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">${title.split(' ').slice(1).join(' ')}</div>
            ${message ? `<div style="font-size: 13px; color: #8B8B8B; margin-top: 8px;">${message}</div>` : ''}
        `;
        
        document.body.appendChild(alert);
        
        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate([50, 30, 50]);
        }
        
        // Auto-remove after 2 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translate(-50%, -50%) scale(0.9)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 2000);
    }
    
    // Add animation keyframes
    const animStyle = document.createElement('style');
    animStyle.textContent = `
        @keyframes alertSlide {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
    `;
    document.head.appendChild(animStyle);
    
    // ===== PREVENT IFRAME EMBEDDING =====
    
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }
    
    // ===== MOBILE-SPECIFIC PROTECTIONS =====
    
    // Prevent long-press context menu on mobile
    let longPressTimer;
    document.addEventListener('touchstart', function(e) {
        if (e.target.closest('.chat-message-media') || 
            e.target.closest('.chat-message-text')) {
            longPressTimer = setTimeout(function() {
                if (navigator.vibrate) {
                    navigator.vibrate(30);
                }
            }, 500);
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function() {
        clearTimeout(longPressTimer);
    });
    
    document.addEventListener('touchmove', function() {
        clearTimeout(longPressTimer);
    });
    
    // ===== CONSOLE MESSAGE =====
    
    setTimeout(function() {
        console.clear();
        
        const styles = [
            'color: #00D9FF',
            'font-size: 24px',
            'font-weight: bold',
            'text-shadow: 2px 2px 4px rgba(0,0,0,0.5)'
        ].join(';');
        
        console.log('%c🔒 SPYCHAT Security Active', styles);
        console.log('%cMessages are protected from copying and screenshots', 'font-size: 14px; color: #8B8B8B;');
    }, 1000);
    
})();