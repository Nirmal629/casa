-- ============================================================
-- Casa Club: Updates for Scalability, Direct Joining & Host moderated approvals
-- Run this in phpMyAdmin → casa_test database → SQL tab
-- ============================================================

-- 1. Drop the unique constraint on host_id in ca_clubs to support multi-sport scalability
ALTER TABLE `ca_clubs` DROP INDEX `uq_host_id`;

-- 2. Add join_type field to ca_clubs (A = All, R = Request, H = Home)
ALTER TABLE `ca_clubs` ADD COLUMN `join_type` ENUM('A', 'R', 'H') NOT NULL DEFAULT 'A' AFTER `logo`;

-- 3. Add created_by field to ca_clubs to track host creator ID
ALTER TABLE `ca_clubs` ADD COLUMN `created_by` INT(11) NULL AFTER `status`;

-- 4. Create ca_player_club_status table to track membership states
CREATE TABLE IF NOT EXISTS `ca_player_club_status` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `club_id` INT(11) NOT NULL,
  `host_id` INT(11) NOT NULL,
  `player_id` INT(11) NOT NULL,
  `status` ENUM('pending', 'accepted') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_club_player` (`club_id`, `player_id`),
  FOREIGN KEY (`club_id`) REFERENCES `ca_clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create player_Host_mapping table to track redirection mappings
CREATE TABLE IF NOT EXISTS `player_Host_mapping` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `player_id` INT(11) NOT NULL,
  `host_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_player_host` (`player_id`, `host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
