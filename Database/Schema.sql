CREATE DATABASE IF NOT EXISTS `ticketing_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ticketing_db`;

-- 1. Department Table
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. User Table (RBAC: Admin, Agent/IT Support, User)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NULL,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'agent', 'user') DEFAULT 'user',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Problems Category Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Main Report Table
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_no` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `assigned_to` INT NULL,
    `category_id` INT NULL,
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'pending', 'resolved', 'closed') DEFAULT 'open',
    `subject` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` DATETIME NULL,
    CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tickets_agent` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Feedback/Comment Table
CREATE TABLE IF NOT EXISTS `ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_internal_note` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Attachment File Table (Screenshot / Log Ralat)
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attachments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Audit Log Table
CREATE TABLE IF NOT EXISTS `ticket_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_logs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================================
-- Data Permulaan (Seeders)
-- Kata laluan lalai untuk semua akaun demo: admin123
-- Hash dijana menggunakan password_hash('admin123', PASSWORD_BCRYPT)
-- ==========================================================

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Teknologi Maklumat'),
(2, 'Pentadbiran & Kewangan'),
(3, 'Operasi');

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Hardware / Perkakasan', 'Masalah berkaitan PC, laptop, monitor, dan pencetak'),
(2, 'Network & Internet', 'Sambungan LAN, Wi-Fi, VPN, dan capaian intranet'),
(3, 'Software & Aplikasi', 'Pemasangan perisian, ralat sistem, dan lesen'),
(4, 'Akaun & Akses', 'Reset kata laluan, akses e-mel, dan kebenaran fail kongsi');

INSERT INTO `users` (`id`, `department_id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 1, 'System Administrator', 'admin@helpdesk.local', '$2y$10$wN9a3zOqjWkV1qP6y9s7u.vE6dKjKzL7F8sX4x6C0k7Y9G1x8vH6W', 'admin', 'active'),
(2, 1, 'Ahmad Agent IT', 'agent@helpdesk.local', '$2y$10$wN9a3zOqjWkV1qP6y9s7u.vE6dKjKzL7F8sX4x6C0k7Y9G1x8vH6W', 'agent', 'active'),
(3, 2, 'Siti Staff', 'user@helpdesk.local', '$2y$10$wN9a3zOqjWkV1qP6y9s7u.vE6dKjKzL7F8sX4x6C0k7Y9G1x8vH6W', 'user', 'active');