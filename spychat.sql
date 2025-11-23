-- SPY CHAT Database Schema
-- Import this file into your MySQL database


USE u491998838_spychat;

-- Users table
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(100) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_online` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chats table (stores chat sessions between two users)
CREATE TABLE `chats` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user1_id` INT(11) UNSIGNED NOT NULL,
  `user2_id` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chat` (`user1_id`, `user2_id`),
  INDEX `idx_user1` (`user1_id`),
  INDEX `idx_user2` (`user2_id`),
  INDEX `idx_updated` (`updated_at`),
  FOREIGN KEY (`user1_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user2_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE `messages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) UNSIGNED NOT NULL,
  `sender_id` INT(11) UNSIGNED NOT NULL,
  `receiver_id` INT(11) UNSIGNED NOT NULL,
  `message` TEXT,
  `message_type` ENUM('text', 'image', 'video', 'audio', 'file') DEFAULT 'text',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT(11) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `is_delivered` TINYINT(1) DEFAULT 1,
  `self_destruct_timer` INT(11) DEFAULT NULL COMMENT 'Seconds until auto-delete',
  `destruct_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_chat_id` (`chat_id`),
  INDEX `idx_sender` (`sender_id`),
  INDEX `idx_receiver` (`receiver_id`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_destruct` (`destruct_at`),
  FOREIGN KEY (`chat_id`) REFERENCES `chats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File storage table (tracks uploaded files)
CREATE TABLE `file_storage` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT(11) UNSIGNED NOT NULL,
  `file_hash` VARCHAR(64) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT(11) NOT NULL,
  `upload_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expiry_time` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_message` (`message_id`),
  INDEX `idx_expiry` (`expiry_time`),
  FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deleted logs (audit trail)
CREATE TABLE `deleted_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) UNSIGNED DEFAULT NULL,
  `message_id` INT(11) UNSIGNED DEFAULT NULL,
  `deleted_by` INT(11) UNSIGNED NOT NULL,
  `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deletion_type` ENUM('message', 'chat', 'auto_destruct') DEFAULT 'message',
  PRIMARY KEY (`id`),
  INDEX `idx_deleted_by` (`deleted_by`),
  INDEX `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
CREATE TABLE `admins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (username: admin, password: admin123)
INSERT INTO `admins` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Typing status table (for real-time typing indicators)
CREATE TABLE `typing_status` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chat_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `is_typing` TINYINT(1) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_typing` (`chat_id`, `user_id`),
  INDEX `idx_chat` (`chat_id`),
  FOREIGN KEY (`chat_id`) REFERENCES `chats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File access tokens (for secure, expiring file URLs)
CREATE TABLE `file_tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `file_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_expires` (`expires_at`),
  FOREIGN KEY (`file_id`) REFERENCES `file_storage`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
