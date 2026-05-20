-- =============================================================================
-- ABC Security Agency — consolidated schema (abc_security)
-- Import: phpMyAdmin → Import this file, then run: php database/migrate.php
-- Roles: users.role → 0=headguard, 1=admin, 2=superadmin (no roles table)
-- =============================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `abc_security`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `abc_security`;

-- -----------------------------------------------------------------------------
-- Migration tracking (managed by database/migrate.php)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int unsigned NOT NULL DEFAULT 1,
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_schema_migrations_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Authentication (login accounts)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `Company_ID` varchar(13) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '0=headguard,1=admin,2=superadmin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Company_ID`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Guard roster (HR profile; links to users for field staff)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `guards` (
  `Company_ID` varchar(13) NOT NULL,
  `Head_ID` varchar(13) DEFAULT NULL COMMENT 'Supervising head guard user id',
  `Rank` varchar(20) DEFAULT NULL,
  `Last_Name` varchar(255) NOT NULL,
  `First_Name` varchar(255) NOT NULL,
  `Middle_Name` varchar(255) DEFAULT NULL,
  `Post_Assigned` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Company_ID`),
  KEY `idx_guards_head` (`Head_ID`),
  CONSTRAINT `fk_guards_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_guards_head` FOREIGN KEY (`Head_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Establishments / posts (replaces list_of_establishments)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `establishments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` varchar(13) DEFAULT NULL COMMENT 'Optional owner/manager user id',
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_establishments_company` (`company_id`),
  CONSTRAINT `fk_establishments_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DGD reports (encrypted fields at application layer)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dgd` (
  `Report_Number` int NOT NULL AUTO_INCREMENT,
  `Company_ID` varchar(13) NOT NULL,
  `Establishment` text COMMENT 'AES encrypted',
  `Template_Path` text COMMENT 'AES encrypted file path',
  `Template` varchar(255) DEFAULT NULL COMMENT 'Original filename',
  `Time_of_Report` datetime NOT NULL,
  `AI_Extracted_Text` text COMMENT 'AES encrypted OCR text',
  `iv` varchar(64) NOT NULL COMMENT 'Base64 IV for row encryption',
  `Status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Report_Number`),
  KEY `idx_dgd_company` (`Company_ID`),
  KEY `idx_dgd_status` (`Status`),
  KEY `idx_dgd_time` (`Time_of_Report`),
  CONSTRAINT `fk_dgd_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Internal memos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `memos` (
  `Memo_ID` int NOT NULL AUTO_INCREMENT,
  `Company_ID` varchar(13) DEFAULT NULL COMMENT 'Sender (admin) user id',
  `Distribution_Protocol` varchar(32) NOT NULL COMMENT 'broadcast|targeted',
  `Category` varchar(64) NOT NULL,
  `Body_Text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Memo_ID`),
  KEY `idx_memos_sender` (`Company_ID`),
  CONSTRAINT `fk_memos_sender` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `memo_recipients` (
  `Dispatch_ID` int NOT NULL AUTO_INCREMENT,
  `Memo_ID` int NOT NULL,
  `Company_ID` varchar(13) NOT NULL COMMENT 'Recipient guard user id',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`Dispatch_ID`),
  UNIQUE KEY `uk_memo_recipient` (`Memo_ID`, `Company_ID`),
  KEY `idx_memo_recipients_guard` (`Company_ID`, `is_read`),
  CONSTRAINT `fk_memo_recipients_memo` FOREIGN KEY (`Memo_ID`) REFERENCES `memos` (`Memo_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_memo_recipients_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Audit log (login / logout)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recording` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `Company_ID` varchar(13) DEFAULT NULL,
  `Designation` varchar(20) DEFAULT NULL COMMENT 'HEADGUARD|ADMIN|SUPERADMIN',
  `Event` varchar(20) NOT NULL COMMENT 'LOGIN|LOGOUT',
  `Time_Of_Event` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recording_company` (`Company_ID`),
  KEY `idx_recording_time` (`Time_Of_Event`),
  CONSTRAINT `fk_recording_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Seed data: run after import (do not commit real password hashes to git)
--
--   c:\xampp\php\php.exe database\scripts\create_user.php ABC-2024-0001 123456 1 abc.admin0001@gmail.com
--   c:\xampp\php\php.exe database\scripts\create_user.php ABC-2024-0021 123456 0 abc.guard0021@gmail.com
--
-- Then add guard roster row (after head guard user exists):
--   INSERT INTO guards (Company_ID, Head_ID, Rank, Last_Name, First_Name, Post_Assigned)
--   VALUES ('ABC-2024-0021', 'ABC-2024-0001', 'SO', 'Tamad', 'Juan', 'Post 1');
-- -----------------------------------------------------------------------------

-- Mark migrations as applied (schema already matches; avoids re-creating old roles tables)
INSERT INTO `schema_migrations` (`migration`, `batch`) VALUES
('001_create_rbac_tables.sql', 1),
('002_seed_roles_permissions.sql', 1),
('php/003_migrate_legacy_users.php', 1),
('005_alter_users_hashed_auth.sql', 1),
('php/005_hash_users_pins.php', 1),
('php/006_repair_users_password_hashes.php', 1),
('php/007_numeric_user_roles.php', 1),
('php/008_consolidate_schema.php', 1);
