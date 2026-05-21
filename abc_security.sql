-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 03:02 PM
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
-- Table structure for table `callout_head_guards`
--

CREATE TABLE `callout_head_guards` (
  `head_guard_id` int(10) UNSIGNED NOT NULL,
  `company_id` varchar(13) DEFAULT NULL COMMENT 'users.Company_ID when account exists',
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL COMMENT 'Full name for UI and reports',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `callout_head_guards`
--

INSERT INTO `callout_head_guards` (`head_guard_id`, `company_id`, `first_name`, `middle_name`, `last_name`, `display_name`, `is_active`, `created_at`) VALUES
(1, 'ABC-2024-0021', 'Jose', 'Abad', 'Cruz', 'Jose Abad Cruz', 1, '2026-05-21 07:25:08'),
(2, NULL, 'Lucy', NULL, 'Heartfillia', 'Lucy Heartfillia', 1, '2026-05-21 07:25:08'),
(3, NULL, 'James', NULL, 'Harbor', 'James Harbor', 1, '2026-05-21 07:25:08'),
(4, NULL, 'Sova Russ', NULL, 'Del Rosario Jr.', 'Sova Russ Del Rosario Jr.', 1, '2026-05-21 07:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `callout_posts`
--

CREATE TABLE `callout_posts` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `post_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `callout_posts`
--

INSERT INTO `callout_posts` (`post_id`, `post_name`, `is_active`, `created_at`) VALUES
(1, 'SM Megamall', 1, '2026-05-21 07:25:08'),
(2, 'SM Fairview', 1, '2026-05-21 07:25:08'),
(3, 'SM Marilao', 1, '2026-05-21 07:25:08'),
(4, 'St. Lukes Medical Center', 1, '2026-05-21 07:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `callout_post_assignments`
--

CREATE TABLE `callout_post_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `head_guard_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `callout_post_assignments`
--

INSERT INTO `callout_post_assignments` (`assignment_id`, `post_id`, `head_guard_id`, `is_active`, `assigned_at`) VALUES
(1, 3, 3, 1, '2026-05-21 07:25:08'),
(2, 1, 1, 1, '2026-05-21 07:25:08'),
(3, 2, 2, 1, '2026-05-21 07:25:08'),
(4, 4, 4, 1, '2026-05-21 07:25:08');

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
-- Table structure for table `guard_announcements`
--

CREATE TABLE `guard_announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guard_announcements`
--

INSERT INTO `guard_announcements` (`id`, `title`, `body`, `is_active`, `created_at`) VALUES
(1, 'Shift briefing', 'Review post orders and radio check every hour. Report incidents through the portal immediately.', 1, '2026-05-21 13:01:54'),
(2, 'Uniform inspection', 'Full uniform and ID must be worn during duty hours. Non-compliance will be noted in daily reports.', 1, '2026-05-21 13:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `guard_duty_status`
--

CREATE TABLE `guard_duty_status` (
  `company_id` varchar(13) NOT NULL,
  `duty_status` enum('active','off_duty','on_report') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guard_report_evidence`
--

CREATE TABLE `guard_report_evidence` (
  `id` int(10) UNSIGNED NOT NULL,
  `report_number` int(11) NOT NULL,
  `company_id` varchar(13) NOT NULL,
  `file_name` text NOT NULL,
  `meta_cipher` text DEFAULT NULL,
  `gps_lat` decimal(10,7) DEFAULT NULL,
  `gps_lng` decimal(10,7) DEFAULT NULL,
  `captured_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guard_staff_messages`
--

CREATE TABLE `guard_staff_messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `sender_company_id` varchar(13) NOT NULL,
  `recipient_company_id` varchar(13) NOT NULL,
  `body_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(1, 'grey', 'ABC-2024-0001', 'sadf', 0, '2026-05-20 10:48:29'),
(3, 'grey', 'amor', 'asdf', 0, '2026-05-21 08:36:05'),
(4, 'grey', 'amor', 'aaa', 0, '2026-05-21 08:36:08');

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
-- Table structure for table `message_groups`
--

CREATE TABLE `message_groups` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `group_name` varchar(120) NOT NULL,
  `created_by_company_id` varchar(13) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_groups`
--

INSERT INTO `message_groups` (`group_id`, `group_name`, `created_by_company_id`, `is_active`, `created_at`) VALUES
(1, 'SM Meygamowl', 'grey', 1, '2026-05-21 08:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `message_group_members`
--

CREATE TABLE `message_group_members` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `company_id` varchar(13) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_group_members`
--

INSERT INTO `message_group_members` (`member_id`, `group_id`, `company_id`, `joined_at`) VALUES
(2, 1, 'amor', '2026-05-21 08:43:00'),
(3, 1, 'ABC-2024-0021', '2026-05-21 08:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `message_group_messages`
--

CREATE TABLE `message_group_messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `sender_company_id` varchar(13) NOT NULL,
  `body_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_group_read_state`
--

CREATE TABLE `message_group_read_state` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `company_id` varchar(13) NOT NULL,
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(65, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 13:58:47'),
(66, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:36:21'),
(67, 'grey', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:37:38'),
(68, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:46:20'),
(69, 'grey', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:46:27'),
(70, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:46:38'),
(71, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:46:42'),
(72, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:55:19'),
(73, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:55:27'),
(74, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:55:36'),
(75, 'grey', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:55:51'),
(76, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:56:15'),
(77, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:56:26'),
(78, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:56:32'),
(79, 'grey', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 15:13:55'),
(80, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 15:14:32'),
(81, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 15:14:38'),
(82, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 15:18:11'),
(83, 'grey', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 15:18:36'),
(84, 'grey', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 15:30:51'),
(85, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 15:31:05'),
(86, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:04:23'),
(87, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 17:04:29'),
(88, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:07:26'),
(89, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 17:07:32'),
(90, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:12:06'),
(91, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-21 17:12:23'),
(92, 'grey', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-21 18:00:40'),
(93, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:00:54'),
(94, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 18:01:10'),
(95, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:01:14'),
(96, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 18:27:30'),
(97, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 18:27:56'),
(98, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:28:19'),
(99, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 19:43:38'),
(100, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 19:45:53');

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
(11, 'php/010_internal_messages.php', 3, '2026-05-20 06:08:23'),
(13, '012_callout_posts_head_guards.sql', 4, '2026-05-21 07:37:15'),
(14, 'php/011_retire_headguard_role.php', 4, '2026-05-21 07:37:15'),
(15, 'php/013_message_groups.php', 4, '2026-05-21 07:37:15'),
(16, '014_link_callout_head_guard_accounts.sql', 5, '2026-05-21 08:22:59'),
(17, '015_users_profile_names.sql', 6, '2026-05-21 09:51:00'),
(18, 'php/015_users_profile_names.php', 6, '2026-05-21 09:51:00'),
(19, '013_guard_portal_features.sql', 7, '2026-05-21 13:01:54'),
(20, 'php/016_guard_report_evidence_encrypt.php', 7, '2026-05-21 13:01:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `Company_ID` varchar(13) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `First_Name` varchar(64) DEFAULT NULL,
  `Last_Name` varchar(64) DEFAULT NULL,
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

INSERT INTO `users` (`Company_ID`, `Email`, `First_Name`, `Last_Name`, `password_hash`, `role_id`, `role`, `is_active`, `failed_login_attempts`, `locked_until`, `last_login_at`, `password_changed_at`, `created_at`, `updated_at`) VALUES
('ABC-2024-0001', 'abc.admin0001@gmail.com', NULL, NULL, '$2y$10$JPAGPhDLQogMxfQDxxQeoeE2Zv4/IOb1K72rKzdqRbTiU4weISmOS', NULL, 2, 1, 0, NULL, '2026-05-20 16:54:47', '2026-05-20 09:12:51', '2026-05-20 01:12:51', '2026-05-20 08:54:47'),
('ABC-2024-0021', 'abc.guard0021@gmail.com', NULL, NULL, '$2y$10$sEC7yFme4cLMi//zlSTNpucul1BB9LqxXOojhNl/PUJa.ro6BSPNi', NULL, 0, 1, 0, NULL, NULL, '2026-05-20 09:12:56', '2026-05-20 01:12:56', '2026-05-20 01:12:56'),
('amor', 'christian5787264@gmail.com', NULL, NULL, '$2y$10$KSOWzWbMYeNhGZDR83.bwetV6vKTRE9cx3hSMSCeoonmLWWqx4c0a', NULL, 0, 1, 0, NULL, '2026-05-21 18:27:56', '2026-05-20 17:44:46', '2026-05-20 09:43:53', '2026-05-21 10:27:56'),
('grey', 'aldrininocencio212527@gmail.com', 'Aldrin', 'Inocencio', '$2y$10$DT29ptsKGboY3CT/TGjbmeAbWRM2A7F2RJjT3LfA7ifY/ZMgzNZM.', NULL, 1, 1, 0, NULL, '2026-05-21 19:45:53', '2026-05-20 18:26:02', '2026-05-20 09:07:40', '2026-05-21 11:45:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `callout_head_guards`
--
ALTER TABLE `callout_head_guards`
  ADD PRIMARY KEY (`head_guard_id`),
  ADD UNIQUE KEY `uk_callout_head_guard_display` (`display_name`),
  ADD KEY `idx_callout_head_guard_company` (`company_id`);

--
-- Indexes for table `callout_posts`
--
ALTER TABLE `callout_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `uk_callout_post_name` (`post_name`);

--
-- Indexes for table `callout_post_assignments`
--
ALTER TABLE `callout_post_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uk_callout_post_head` (`post_id`,`head_guard_id`),
  ADD KEY `fk_callout_assign_head_guard` (`head_guard_id`);

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
-- Indexes for table `guard_announcements`
--
ALTER TABLE `guard_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guard_announcements_active` (`is_active`,`created_at`);

--
-- Indexes for table `guard_duty_status`
--
ALTER TABLE `guard_duty_status`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `guard_report_evidence`
--
ALTER TABLE `guard_report_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guard_evidence_report` (`report_number`),
  ADD KEY `idx_guard_evidence_guard` (`company_id`);

--
-- Indexes for table `guard_staff_messages`
--
ALTER TABLE `guard_staff_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_guard_msg_recipient` (`recipient_company_id`,`is_read`,`created_at`),
  ADD KEY `idx_guard_msg_thread` (`sender_company_id`,`recipient_company_id`,`created_at`);

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
-- Indexes for table `message_groups`
--
ALTER TABLE `message_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `idx_message_groups_creator` (`created_by_company_id`,`created_at`);

--
-- Indexes for table `message_group_members`
--
ALTER TABLE `message_group_members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `uk_message_group_member` (`group_id`,`company_id`),
  ADD KEY `idx_message_group_members_user` (`company_id`);

--
-- Indexes for table `message_group_messages`
--
ALTER TABLE `message_group_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_message_group_messages_group` (`group_id`,`created_at`),
  ADD KEY `fk_message_group_messages_sender` (`sender_company_id`);

--
-- Indexes for table `message_group_read_state`
--
ALTER TABLE `message_group_read_state`
  ADD PRIMARY KEY (`group_id`,`company_id`),
  ADD KEY `fk_message_group_read_user` (`company_id`);

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
-- AUTO_INCREMENT for table `callout_head_guards`
--
ALTER TABLE `callout_head_guards`
  MODIFY `head_guard_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `callout_posts`
--
ALTER TABLE `callout_posts`
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `callout_post_assignments`
--
ALTER TABLE `callout_post_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT for table `guard_announcements`
--
ALTER TABLE `guard_announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `guard_report_evidence`
--
ALTER TABLE `guard_report_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guard_staff_messages`
--
ALTER TABLE `guard_staff_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_messages`
--
ALTER TABLE `internal_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `message_groups`
--
ALTER TABLE `message_groups`
  MODIFY `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `message_group_members`
--
ALTER TABLE `message_group_members`
  MODIFY `member_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `message_group_messages`
--
ALTER TABLE `message_group_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recording`
--
ALTER TABLE `recording`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `callout_head_guards`
--
ALTER TABLE `callout_head_guards`
  ADD CONSTRAINT `fk_callout_head_guard_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `callout_post_assignments`
--
ALTER TABLE `callout_post_assignments`
  ADD CONSTRAINT `fk_callout_assign_head_guard` FOREIGN KEY (`head_guard_id`) REFERENCES `callout_head_guards` (`head_guard_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_callout_assign_post` FOREIGN KEY (`post_id`) REFERENCES `callout_posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `guard_duty_status`
--
ALTER TABLE `guard_duty_status`
  ADD CONSTRAINT `fk_guard_duty_status_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `guard_report_evidence`
--
ALTER TABLE `guard_report_evidence`
  ADD CONSTRAINT `fk_guard_evidence_report` FOREIGN KEY (`report_number`) REFERENCES `dgd` (`Report_Number`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guard_evidence_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `guard_staff_messages`
--
ALTER TABLE `guard_staff_messages`
  ADD CONSTRAINT `fk_guard_msg_recipient` FOREIGN KEY (`recipient_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guard_msg_sender` FOREIGN KEY (`sender_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

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
-- Constraints for table `message_groups`
--
ALTER TABLE `message_groups`
  ADD CONSTRAINT `fk_message_groups_creator` FOREIGN KEY (`created_by_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `message_group_members`
--
ALTER TABLE `message_group_members`
  ADD CONSTRAINT `fk_message_group_members_group` FOREIGN KEY (`group_id`) REFERENCES `message_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_group_members_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `message_group_messages`
--
ALTER TABLE `message_group_messages`
  ADD CONSTRAINT `fk_message_group_messages_group` FOREIGN KEY (`group_id`) REFERENCES `message_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_group_messages_sender` FOREIGN KEY (`sender_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `message_group_read_state`
--
ALTER TABLE `message_group_read_state`
  ADD CONSTRAINT `fk_message_group_read_group` FOREIGN KEY (`group_id`) REFERENCES `message_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_group_read_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
  ADD CONSTRAINT `fk_recording_user` FOREIGN KEY (`Company_ID`) REFERENCES `users` (`Company_ID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
