-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 08:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abc_security`
--

-- --------------------------------------------------------

--
-- Table structure for table `dgd`
--

CREATE TABLE `dgd` (
  `Report_Number` int(11) NOT NULL,
  `Company_ID` varchar(13) NOT NULL,
  `Establishment` text DEFAULT NULL COMMENT 'AES encrypted',
  `Template_Path` text DEFAULT NULL COMMENT 'AES encrypted file path',
  `Template` varchar(255) DEFAULT NULL COMMENT 'Original filename',
  `Time_of_Report` datetime NOT NULL,
  `AI_Extracted_Text` text DEFAULT NULL COMMENT 'AES encrypted OCR text',
  `iv` varchar(64) NOT NULL COMMENT 'Base64 IV for row encryption',
  `Status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `establishments`
--

CREATE TABLE `establishments` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` varchar(13) DEFAULT NULL COMMENT 'Optional owner/manager user id',
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guards`
--

CREATE TABLE `guards` (
  `Company_ID` varchar(13) NOT NULL,
  `Head_ID` varchar(13) DEFAULT NULL COMMENT 'Supervising head guard user id',
  `Rank` varchar(20) DEFAULT NULL,
  `Last_Name` varchar(255) NOT NULL,
  `First_Name` varchar(255) NOT NULL,
  `Middle_Name` varchar(255) DEFAULT NULL,
  `Post_Assigned` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internal_messages`
--

CREATE TABLE `internal_messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `sender_company_id` varchar(13) NOT NULL,
  `recipient_company_id` varchar(13) NOT NULL,
  `body_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internal_messages`
--

INSERT INTO `internal_messages` (`message_id`, `sender_company_id`, `recipient_company_id`, `body_text`, `is_read`, `created_at`) VALUES
(1, 'grey', 'ABC-2024-0001', 'sadf', 0, '2026-05-20 10:48:29');

-- --------------------------------------------------------

--
-- Table structure for table `memos`
--

CREATE TABLE `memos` (
  `Memo_ID` int(11) NOT NULL,
  `Company_ID` varchar(13) DEFAULT NULL COMMENT 'Sender (admin) user id',
  `Distribution_Protocol` varchar(32) NOT NULL COMMENT 'broadcast|targeted',
  `Category` varchar(64) NOT NULL,
  `Body_Text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memo_recipients`
--

CREATE TABLE `memo_recipients` (
  `Dispatch_ID` int(11) NOT NULL,
  `Memo_ID` int(11) NOT NULL,
  `Company_ID` varchar(13) NOT NULL COMMENT 'Recipient guard user id',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recording`
--

CREATE TABLE `recording` (
  `id` int(10) UNSIGNED NOT NULL,
  `Company_ID` varchar(13) DEFAULT NULL,
  `actor_company_id` varchar(13) DEFAULT NULL,
  `Designation` varchar(64) DEFAULT NULL,
  `Event` varchar(20) NOT NULL COMMENT 'LOGIN|LOGOUT',
  `event_detail` varchar(255) DEFAULT NULL,
  `Time_Of_Event` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recording`
--

INSERT INTO `recording` (`id`, `Company_ID`, `actor_company_id`, `Designation`, `Event`, `event_detail`, `Time_Of_Event`) VALUES
(1, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 09:13:14'),
(2, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 09:13:16'),
(3, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 10:17:02'),
(4, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 12:56:18'),
(5, 'ABC-2024-0001', NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 12:56:54'),
(6, 'ABC-2024-0001', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 12:57:16'),
(7, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 12:57:20'),
(8, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 13:03:23'),
(9, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 13:03:34'),
(10, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 13:09:01'),
(11, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 13:09:27'),
(12, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 13:55:55'),
(13, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 13:56:07'),
(14, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 14:00:57'),
(15, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 14:01:11'),
(16, 'ABC-2024-0001', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 15:19:05'),
(17, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 15:19:38'),
(18, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 15:19:54'),
(19, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 15:20:06'),
(20, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 15:20:20'),
(21, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 15:26:19'),
(22, NULL, 'ABC-2024-0001', 'SUPERADMIN:ABC-2024-0001', 'ACCOUNT_CREATED', 'Role: Head Guard', '2026-05-20 16:27:22'),
(23, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 16:34:58'),
(24, NULL, NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 16:35:10'),
(25, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 16:50:17'),
(26, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 16:54:47'),
(27, 'grey', 'ABC-2024-0001', 'SUPERADMIN:ABC-2024-0001', 'ACCOUNT_CREATED', 'Role: Head Guard', '2026-05-20 17:07:45'),
(28, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 17:07:48'),
(29, 'grey', NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 17:08:02'),
(30, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 17:08:31'),
(31, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 17:37:45'),
(32, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_CREATED', 'Role: Head Guard', '2026-05-20 17:43:56'),
(33, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_DISABLED', NULL, '2026-05-20 17:44:01'),
(34, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_ENABLED', NULL, '2026-05-20 17:44:04'),
(35, 'amor', NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 17:44:37'),
(36, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 17:44:48'),
(37, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_DISABLED', NULL, '2026-05-20 17:44:55'),
(38, 'grey', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 18:04:49'),
(39, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:04:55'),
(40, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:04:57'),
(41, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:28:43'),
(42, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:29:42'),
(43, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:30:05'),
(44, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:07'),
(45, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:30:17'),
(46, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:29'),
(47, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 18:30:34'),
(48, 'grey', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:48'),
(49, 'grey', NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 18:31:04'),
(50, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 18:31:24'),
(51, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:37:35'),
(52, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:40:22'),
(53, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:40:52'),
(54, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:44:41'),
(55, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:44:48'),
(56, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:53:26'),
(57, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 19:09:58'),
(58, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 19:10:03'),
(59, 'grey', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 19:11:49'),
(60, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 19:12:25'),
(61, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 19:14:20'),
(62, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 19:16:47'),
(63, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-21 13:58:05'),
(64, 'grey', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-21 13:58:41'),
(65, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 13:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `migration`, `batch`, `executed_at`) VALUES
(1, '001_create_rbac_tables.sql', 1, '2026-05-20 01:11:20'),
(2, '002_seed_roles_permissions.sql', 1, '2026-05-20 01:11:20'),
(3, 'php/003_migrate_legacy_users.php', 1, '2026-05-20 01:11:20'),
(4, '005_alter_users_hashed_auth.sql', 1, '2026-05-20 01:11:20'),
(5, 'php/005_hash_users_pins.php', 1, '2026-05-20 01:11:20'),
(6, 'php/006_repair_users_password_hashes.php', 1, '2026-05-20 01:11:20'),
(7, 'php/007_numeric_user_roles.php', 1, '2026-05-20 01:11:20'),
(8, 'php/008_consolidate_schema.php', 1, '2026-05-20 01:11:20'),
(9, 'php/009_recording_accountability.php', 2, '2026-05-20 02:28:09'),
(10, 'php/005_alter_users_hashed_auth.php', 3, '2026-05-20 06:08:23'),
(11, 'php/010_internal_messages.php', 3, '2026-05-20 06:08:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `Company_ID` varchar(13) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `role` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=headguard,1=admin,2=superadmin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`Company_ID`, `Email`, `password_hash`, `role_id`, `role`, `is_active`, `failed_login_attempts`, `locked_until`, `last_login_at`, `password_changed_at`, `created_at`, `updated_at`) VALUES
('ABC-2024-0001', 'abc.admin0001@gmail.com', '$2y$10$JPAGPhDLQogMxfQDxxQeoeE2Zv4/IOb1K72rKzdqRbTiU4weISmOS', NULL, 2, 1, 0, NULL, '2026-05-20 16:54:47', '2026-05-20 09:12:51', '2026-05-20 01:12:51', '2026-05-20 08:54:47'),
('ABC-2024-0021', 'abc.guard0021@gmail.com', '$2y$10$sEC7yFme4cLMi//zlSTNpucul1BB9LqxXOojhNl/PUJa.ro6BSPNi', NULL, 0, 1, 0, NULL, NULL, '2026-05-20 09:12:56', '2026-05-20 01:12:56', '2026-05-20 01:12:56'),
('amor', 'christian5787264@gmail.com', '$2y$10$KSOWzWbMYeNhGZDR83.bwetV6vKTRE9cx3hSMSCeoonmLWWqx4c0a', NULL, 0, 0, 0, NULL, '2026-05-20 17:44:37', '2026-05-20 17:44:46', '2026-05-20 09:43:53', '2026-05-20 09:44:55'),
('grey', 'aldrininocencio212527@gmail.com', '$2y$10$DT29ptsKGboY3CT/TGjbmeAbWRM2A7F2RJjT3LfA7ifY/ZMgzNZM.', NULL, 1, 1, 0, NULL, '2026-05-21 13:58:47', '2026-05-20 18:26:02', '2026-05-20 09:07:40', '2026-05-21 05:58:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dgd`
--
ALTER TABLE `dgd`
  ADD PRIMARY KEY (`Report_Number`),
  ADD KEY `idx_dgd_company` (`Company_ID`),
  ADD KEY `idx_dgd_status` (`Status`),
  ADD KEY `idx_dgd_time` (`Time_of_Report`);

--
-- Indexes for table `establishments`
--
ALTER TABLE `establishments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_establishments_company` (`company_id`);

--
-- Indexes for table `guards`
--
ALTER TABLE `guards`
  ADD PRIMARY KEY (`Company_ID`),
  ADD KEY `idx_guards_head` (`Head_ID`);

--
-- Indexes for table `internal_messages`
--
ALTER TABLE `internal_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_internal_messages_recipient` (`recipient_company_id`,`is_read`,`created_at`),
  ADD KEY `idx_internal_messages_pair` (`sender_company_id`,`recipient_company_id`,`created_at`);

--
-- Indexes for table `memos`
--
ALTER TABLE `memos`
  ADD PRIMARY KEY (`Memo_ID`),
  ADD KEY `idx_memos_sender` (`Company_ID`);

--
-- Indexes for table `memo_recipients`
--
ALTER TABLE `memo_recipients`
  ADD PRIMARY KEY (`Dispatch_ID`),
  ADD UNIQUE KEY `uk_memo_recipient` (`Memo_ID`,`Company_ID`),
  ADD KEY `idx_memo_recipients_guard` (`Company_ID`,`is_read`);

--
-- Indexes for table `recording`
--
ALTER TABLE `recording`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recording_company` (`Company_ID`),
  ADD KEY `idx_recording_time` (`Time_Of_Event`),
  ADD KEY `idx_recording_actor` (`actor_company_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_schema_migrations_migration` (`migration`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`Company_ID`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dgd`
--
ALTER TABLE `dgd`
  MODIFY `Report_Number` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `establishments`
--
ALTER TABLE `establishments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_messages`
--
ALTER TABLE `internal_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `memos`
--
ALTER TABLE `memos`
  MODIFY `Memo_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memo_recipients`
--
ALTER TABLE `memo_recipients`
  MODIFY `Dispatch_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recording`
--
ALTER TABLE `recording`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dgd`
--
ALTER TABLE `dgd`
  ADD CONSTRAINT `fk_dgd_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`);

--
-- Constraints for table `establishments`
--
ALTER TABLE `establishments`
  ADD CONSTRAINT `fk_establishments_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL;

--
-- Constraints for table `guards`
--
ALTER TABLE `guards`
  ADD CONSTRAINT `fk_guards_head` FOREIGN KEY (`Head_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_guards_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `internal_messages`
--
ALTER TABLE `internal_messages`
  ADD CONSTRAINT `fk_internal_messages_recipient` FOREIGN KEY (`recipient_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_internal_messages_sender` FOREIGN KEY (`sender_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `memos`
--
ALTER TABLE `memos`
  ADD CONSTRAINT `fk_memos_sender` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL;

--
-- Constraints for table `memo_recipients`
--
ALTER TABLE `memo_recipients`
  ADD CONSTRAINT `fk_memo_recipients_memo` FOREIGN KEY (`Memo_ID`) REFERENCES `memos` (`Memo_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_memo_recipients_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
  ADD CONSTRAINT `fk_recording_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
