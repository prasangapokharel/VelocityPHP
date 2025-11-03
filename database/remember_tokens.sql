-- Remember Me Tokens Table (for persistent login)
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `selector` VARCHAR(64) NOT NULL UNIQUE,
  `hashed_validator` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_selector` (`selector`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add last_login to users table
ALTER TABLE `users` ADD COLUMN `last_login` DATETIME NULL AFTER `status`;

