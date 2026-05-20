-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2026 at 03:02 AM
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
  `Company_ID` varchar(13) DEFAULT NULL,
  `Establishment` varchar(50) DEFAULT NULL,
  `Template_Path` varchar(255) DEFAULT NULL,
  `Template` blob DEFAULT NULL,
  `Time_of_Report` varchar(255) DEFAULT NULL,
  `iv` varchar(50) NOT NULL,
  `Status` varchar(30) NOT NULL DEFAULT 'Received'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dgd`
--

INSERT INTO `dgd` (`Report_Number`, `Company_ID`, `Establishment`, `Template_Path`, `Template`, `Time_of_Report`, `iv`, `Status`) VALUES
(1, 'ABC-2024-0021', 'T3+l5chy8hOjLafkb+HdLw==', 'DvSeUWuenkdLh7pRTCdrOzpCjbVAu84/9pJfKy5x71mElQHg6qNBq6dKsryDoLKg', 0x696e636964656e745f666f726d2e6a7067, '2026-04-06 01:47:05', 'Uw6ZAtC6lokEFC75CAyJbQ==', 'Approved'),
(2, 'ABC-2024-0021', '0oJ9+JhozEylVVxoIdB3iw==', '6zOPVOpdy32K3MTdQjUlOEByt4QLkdZjTbThLs6YvzHRQo5qA5Uy/DPgSuexQdwO', 0x696e636964656e745f7265702e706e67, '2026-04-06 01:47:18', '9Kg2nQfMcks0B5pRPR2fBg==', 'Pending'),
(3, 'ABC-2024-0021', 'F2PpooX4/j7SV6FKwpUr8g==', '1fVqy2NNCy0LyiCa9pAVdHmBRLwrfXIe6bDRFxGIIeHO2M1PWNH0QXwk9KBkf/wl', 0x696e636964656e745f726570322e706e67, '2026-04-06 01:47:35', 'aeDWMMsgCGR7cXUKPrU+JQ==', 'For Clarification'),
(4, 'ABC-2024-0021', 'TSbSh8rvD3ubWGvfOOKg9Q==', 'pNPamBRVPB8CTIbzbYHEZOr4q531szvO2NytgDV1oPzIk7GLBk8epxB9ap3v+kZW', 0x636f666665652e7068702e6a706567, '2026-04-08 08:43:31', '0yrNdfMKKrWQgaAJDYpzTg==', 'Received');

-- --------------------------------------------------------

--
-- Table structure for table `guards`
--

CREATE TABLE `guards` (
  `Head_ID` varchar(13) DEFAULT NULL,
  `Company_ID` varchar(13) DEFAULT NULL,
  `Rank` varchar(20) DEFAULT NULL,
  `Last_Name` varchar(255) DEFAULT NULL,
  `First_Name` varchar(255) DEFAULT NULL,
  `Middle_Name` varchar(255) DEFAULT NULL,
  `Post_Assigned` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guards`
--

INSERT INTO `guards` (`Head_ID`, `Company_ID`, `Rank`, `Last_Name`, `First_Name`, `Middle_Name`, `Post_Assigned`) VALUES
('ABC-2024-0001', 'ABC-2024-0021', 'SO', 'Tamad', 'Juan', 'Cruz', 'Post 1');

-- --------------------------------------------------------

--
-- Table structure for table `list_of_establishments`
--

CREATE TABLE `list_of_establishments` (
  `Company_ID` varchar(13) DEFAULT NULL,
  `Establishment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_of_establishments`
--

INSERT INTO `list_of_establishments` (`Company_ID`, `Establishment`) VALUES
('ABC-2024-0001', 'Post 1'),
('ABC-2024-0001', 'Post 2'),
('ABC-2024-0001', 'Post 3');

-- --------------------------------------------------------

--
-- Table structure for table `memos`
--

CREATE TABLE `memos` (
  `Memo_ID` int(11) NOT NULL,
  `Company_ID` varchar(13) DEFAULT NULL,
  `Distribution_Protocol` varchar(255) DEFAULT NULL,
  `Category` varchar(255) DEFAULT NULL,
  `Body_Text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memos`
--

INSERT INTO `memos` (`Memo_ID`, `Company_ID`, `Distribution_Protocol`, `Category`, `Body_Text`) VALUES
(27, NULL, 'targeted', 'NOTICE', 'testing');

-- --------------------------------------------------------

--
-- Table structure for table `memo_reception`
--

CREATE TABLE `memo_reception` (
  `Dispatch_ID` int(11) NOT NULL,
  `Memo_ID` int(11) DEFAULT NULL,
  `Company_ID` varchar(13) DEFAULT NULL,
  `Is_Read` tinyint(4) DEFAULT 0,
  `Date_read` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `memo_reception`
--

INSERT INTO `memo_reception` (`Dispatch_ID`, `Memo_ID`, `Company_ID`, `Is_Read`, `Date_read`) VALUES
(26, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recording`
--

CREATE TABLE `recording` (
  `Company_ID` varchar(13) DEFAULT NULL,
  `Designation` varchar(5) DEFAULT NULL,
  `Event` varchar(6) DEFAULT NULL,
  `Time_of_Event` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recording`
--

INSERT INTO `recording` (`Company_ID`, `Designation`, `Event`, `Time_of_Event`) VALUES
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-01 06:10:47'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-01 06:11:20'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-02 02:20:43'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-02 05:33:43'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-03 06:50:01'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-03 07:44:09'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-03 08:02:36'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-03 08:02:50'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 12:36:32'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-04 02:00:09'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 02:06:21'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 02:23:53'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 03:12:55'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-04 04:40:37'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 04:40:47'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-04 05:49:21'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:09:04'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:16:58'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:24:24'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-04 06:30:03'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:30:37'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-04 06:31:06'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 06:31:17'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-04 06:31:29'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:33:30'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-04 06:52:43'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-04 06:52:55'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-04 06:53:23'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-04 06:53:40'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-05 04:58:59'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 10:49:54'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 10:52:31'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 10:52:44'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 10:54:10'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:02:45'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 12:06:56'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:07:14'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 12:45:16'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:45:41'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 12:47:29'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:47:38'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 12:47:55'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:56:06'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 12:57:25'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 12:57:47'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 01:24:56'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 01:25:50'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 01:26:23'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 01:34:06'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 01:34:15'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 01:46:33'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 01:46:42'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 01:47:50'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 01:48:05'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 02:33:12'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 02:33:31'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 02:33:36'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 02:33:46'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 02:45:19'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 02:45:34'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 03:05:11'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 03:05:20'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 03:19:01'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 03:19:13'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 03:36:08'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 03:46:34'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 06:20:05'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 06:55:09'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 07:31:38'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 07:38:08'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 07:38:20'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-06 08:10:20'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 08:10:27'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 08:47:54'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-06 08:49:26'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-06 08:49:44'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-06 08:49:54'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:18:15'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:19:25'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:21:19'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:23:15'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:23:28'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:23:31'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:24:02'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:33:15'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 08:33:28'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 09:23:30'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 10:14:30'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 10:18:00'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 10:20:02'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 10:20:15'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 11:32:39'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 05:15:05'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 07:15:18'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 07:16:22'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 07:16:43'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 07:17:21'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 07:25:35'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 07:26:20'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 07:26:35'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 07:27:54'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 07:28:08'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 08:36:04'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:36:14'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:36:21'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:40:45'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:40:57'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:41:01'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 08:41:10'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:53:59'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:55:31'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-07 08:57:39'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-07 08:57:53'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-07 08:59:34'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-07 08:59:49'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-08 06:39:51'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-08 12:26:45'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-08 12:57:40'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-08 01:37:44'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-08 08:41:38'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-08 08:42:22'),
(NULL, 'GUARD', 'LOGOUT', '2026-04-08 08:45:50'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-08 08:46:08'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-10 09:28:25'),
(NULL, 'ADMIN', 'LOGOUT', '2026-04-10 09:30:36'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-11 01:05:12'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-12 07:36:12'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-04-12 07:37:01'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-04-12 08:21:48'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-14 02:42:40'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-05-14 02:43:38'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-14 05:40:20'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-16 05:57:31'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-16 06:03:53'),
(NULL, 'ADMIN', 'LOGOUT', '2026-05-16 06:05:08'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-16 06:05:33'),
(NULL, 'ADMIN', 'LOGOUT', '2026-05-16 06:05:41'),
('ABC-2024-0021', 'GUARD', 'LOGIN', '2026-05-16 06:06:01'),
(NULL, 'GUARD', 'LOGOUT', '2026-05-16 06:17:46'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-16 18:27:10'),
('ABC-2024-0001', 'ADMIN', 'LOGIN', '2026-05-20 08:57:13'),
('ABC-2024-0001', 'ADMIN', 'LOGOUT', '2026-05-20 08:57:18');

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
(1, '001_create_rbac_tables.sql', 1, '2026-05-20 00:47:29'),
(2, '002_seed_roles_permissions.sql', 1, '2026-05-20 00:47:29'),
(3, 'php/003_migrate_legacy_users.php', 1, '2026-05-20 00:47:29'),
(4, '005_alter_users_hashed_auth.sql', 2, '2026-05-20 00:53:10'),
(5, '006_alter_users_drop_pin.sql', 2, '2026-05-20 00:53:10'),
(6, 'php/005_hash_users_pins.php', 2, '2026-05-20 00:53:10'),
(7, 'php/006_repair_users_password_hashes.php', 3, '2026-05-20 00:54:07'),
(8, 'php/007_numeric_user_roles.php', 4, '2026-05-20 01:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `Company_ID` varchar(13) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=headguard,1=admin,2=superadmin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Designation` varchar(5) DEFAULT NULL,
  `Email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`Company_ID`, `password_hash`, `role`, `is_active`, `failed_login_attempts`, `locked_until`, `last_login_at`, `password_changed_at`, `updated_at`, `Designation`, `Email`) VALUES
('ABC-2024-0001', '$2y$10$ajtl/jKmmXdJeYOqxEfh1eilT6wnoyKtfDoh3mCTAsEtHPaRGxcT6', 1, 1, 0, NULL, '2026-05-20 08:57:13', '2026-05-20 08:54:07', '2026-05-20 01:01:24', 'ADMIN', 'abc.admin0001@gmail.com'),
('ABC-2024-0021', '$2y$10$u/1LEcxz8Hv47yTk1SzP9ukpLs/6f.AUJ9m0rmvPSLftwBul/L3vm', 0, 1, 0, NULL, NULL, '2026-05-20 08:54:07', '2026-05-20 00:54:07', 'GUARD', 'abc.guard0021@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dgd`
--
ALTER TABLE `dgd`
  ADD PRIMARY KEY (`Report_Number`),
  ADD KEY `fk_cid` (`Company_ID`);

--
-- Indexes for table `guards`
--
ALTER TABLE `guards`
  ADD KEY `fk_hid` (`Head_ID`),
  ADD KEY `fk_compid` (`Company_ID`);

--
-- Indexes for table `list_of_establishments`
--
ALTER TABLE `list_of_establishments`
  ADD KEY `fk_companyid` (`Company_ID`);

--
-- Indexes for table `memos`
--
ALTER TABLE `memos`
  ADD PRIMARY KEY (`Memo_ID`),
  ADD KEY `fk_com_ID` (`Company_ID`);

--
-- Indexes for table `memo_reception`
--
ALTER TABLE `memo_reception`
  ADD PRIMARY KEY (`Dispatch_ID`),
  ADD KEY `fk_memID` (`Memo_ID`),
  ADD KEY `fk_comID` (`Company_ID`);

--
-- Indexes for table `recording`
--
ALTER TABLE `recording`
  ADD KEY `fk_company_id` (`Company_ID`);

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
  ADD PRIMARY KEY (`Company_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dgd`
--
ALTER TABLE `dgd`
  MODIFY `Report_Number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `memos`
--
ALTER TABLE `memos`
  MODIFY `Memo_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `memo_reception`
--
ALTER TABLE `memo_reception`
  MODIFY `Dispatch_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dgd`
--
ALTER TABLE `dgd`
  ADD CONSTRAINT `fk_cid` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`);

--
-- Constraints for table `guards`
--
ALTER TABLE `guards`
  ADD CONSTRAINT `fk_compid` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`),
  ADD CONSTRAINT `fk_hid` FOREIGN KEY (`Head_ID`) REFERENCES `users` (`Company_ID`);

--
-- Constraints for table `list_of_establishments`
--
ALTER TABLE `list_of_establishments`
  ADD CONSTRAINT `fk_companyid` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`);

--
-- Constraints for table `memos`
--
ALTER TABLE `memos`
  ADD CONSTRAINT `fk_com_ID` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`);

--
-- Constraints for table `memo_reception`
--
ALTER TABLE `memo_reception`
  ADD CONSTRAINT `fk_comID` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`),
  ADD CONSTRAINT `fk_memID` FOREIGN KEY (`Memo_ID`) REFERENCES `memos` (`Memo_ID`);

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
  ADD CONSTRAINT `fk_company_id` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
