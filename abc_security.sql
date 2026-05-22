-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 05:22 PM
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
(4, NULL, 'Sova Russ', NULL, 'Del Rosario Jr.', 'Sova Russ Del Rosario Jr.', 1, '2026-05-21 07:25:08'),
(9, 'amor', 'christian5787264@gmail.com', NULL, 'amor', 'christian5787264@gmail.com', 1, '2026-05-21 13:18:59');

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
(4, 4, 4, 1, '2026-05-21 07:25:08'),
(9, 2, 9, 1, '2026-05-21 13:18:59');

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

--
-- Dumping data for table `dgd`
--

INSERT INTO `dgd` (`Report_Number`, `Company_ID`, `Establishment`, `Template_Path`, `Template`, `Time_of_Report`, `AI_Extracted_Text`, `iv`, `Status`, `created_at`) VALUES
(1, 'amor', 'p+eFrmkxCPr2Cok+GoSfnA==', 'sVrEsAkg/K69Y2tULP3KoJmBmJOouyWs13CzUiOL2ZiahehkokkOjhrgHdGUlveQ', 'Daily Attendance Document', '2026-05-21 21:27:54', '', 'DqS+K5cuev56MJP+uqkJVw==', 'Pending', '2026-05-21 13:27:54'),
(2, 'amor', 'kl65cDzovxDdy9lgmovxiw==', 'QV81Hy8yPFkPH6y/dlNOyas/c3WO3sJVFqO3g6ZWadqixXFv4bTBoZY3K4tAXlZP', 'Daily Attendance Document', '2026-05-21 21:47:16', '', 'dk2tbrr4IeEwbTwU/kuEsg==', 'Pending', '2026-05-21 13:47:16'),
(3, 'amor', 'Nq5qAhGOonFTbYHvDsxnAw==', 'rfNudGxbNPqMruQpLysPqMaRif9BbbypiF8+liBWWe0Rj/CF8HOff4blqXEDGTMH', 'Daily Attendance Document', '2026-05-21 22:01:31', '', 'IA188c2YiNIF4DFxoGryYA==', 'Pending', '2026-05-21 14:01:31'),
(4, 'amor', '5U4i+6PT2uYHNv/W15PEbg==', '70wUSW69R6mwoepealTiPL8VcqE25MxF5u1sDyJgDkWCyOjriRgr/FomofQxqNYT', 'Daily Attendance Document', '2026-05-21 22:01:42', '', 'F9U0N8ryrsubGFsVmhJiaw==', 'Pending', '2026-05-21 14:01:42'),
(5, 'amor', 'Q+Gr9UE9UnHCLcfOS2edcw==', 'e+myZzVOO84+pNERrw9gXYm2pVKEK/VcyEcaJhkqgKCb4teXu1VFYWlWyukXK1Se', 'Daily Attendance Document', '2026-05-21 22:01:48', '', '1S8ZqPwe095TbeV3jefJ0A==', 'Pending', '2026-05-21 14:01:48'),
(6, 'amor', 'QTlf8Kb1+lwJBeoQ93ZdCA==', 'mrFWnSPbUc9z7Waq0B7Q7LTS5cizdSn/zrpWmdCFB3O03XPByYXJr0kxJTjo7qeK', 'Daily Attendance Document', '2026-05-21 22:01:55', '', 'zVcQWeXoKhr+F9ifW/UEMg==', 'Pending', '2026-05-21 14:01:55'),
(7, 'amor', 'c5vyi3sXL2P5uypcYGjBWQ==', 'DxH95RNB5d0qvteumF53+WUm9TkLOvJeXM9eFQqItfrs4jICDoQ4iKH+7HwZ2Qo4', 'Daily Attendance Document', '2026-05-21 22:14:45', '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', '9LPgf9bvQSkn+31YqlvJjA==', 'Pending', '2026-05-21 14:14:45'),
(8, 'amor', 'k/4gfeqbcHkWkARy2OE1Dw==', 'afTg+xvtM3p7E2dKHC+GLXbJx5qDVJJ+I4mMJryPFmr4cOhlhYJfttM6Gzo5VhjF', 'Post incident', '2026-05-21 23:21:24', '/p14iSWwU/OuNFXG9aCTT/KrLNuShEpuz/ptlTwNfXUPJu4SIZbvelF/q1cmiOL4gz50qww5UMuRM4+9kvQbmE7bwR7vy+Wk1ZhBxRaB8mVyaSEreCN5wC6zgxrVqWg2LEX5CBGpMvPeZGzzj5G0dlpzWYtwgaUaYvA1fYAgbNrzCI+wYhlyUXhLRapm1PXhffrRdCXdVt7PSgX0508FOt7Or/NzbFFCs8I5rFNZ/ycErUyOdj7NA7/qNZuwCrnj5DM9RovzApXPPyYnNwr1u8oz6d9YPQR9YYORdJ41BEBbYw1BxvEabaR4MOw/2N3Te1hvRJEkb6pTP8IS3noZsyxiloQ7uJgSUHjxW/Rz2M6F/yYxOARKQN+zE89elQfHPVoP0h+r7ZFBHKQp0JxiwzXSAa4G/iU3z0wv4/QIlBeSxSUA9Q6tLh4EGXGLNPu3virp1UgXimwrLMx/lIWb6HmtbbMoLx+ftmFsjXeoFGHEpRMuSdv7RkFs2JZCLf6oxDPNNU+mX0BNha8Ut7nomMrRjoCenjXLCG/aGSPuzOUBODFCqiFFl05GyWeXha1LWV/s+tDzmeZzdBQx9Wn5W+GUSn1Bqte7qY4hoAJTjZM4KxH5MnPLij8Ajt0JG/e4DV5kKJ9yX+tveZWM9RoIDO4uDzQOEpCeN9pp0SC+SjCXJdzJh0ZTdHLpS2HX6xca0doSGTQfv3HeQwGFLku/wzLFY60SkFFbEKYAgfA/bHQSR0ph9hTyTVDurobTuKkHne319sw+JzLx4Una+kZYI2+377rkSY0Bg7E4XZgUvVxHtkmHYS5lwvz9AGgOMzWvm79ejtjwXCvcQosenyEaiCLBAEvDEfjTV3At/u38j/VbqWErcUZ6RaHmjJc1f6sTTyLxkCQf3JKXgjICbYRfKnjAomxMbqADAB38jlW5rR5abc9ztm0TTwvSTZm9nY0eEwZWacw6d3RU6ruFqqOxn8yjadjmSRe/Wpq7eDdY8pYTocl0vuFyDRvL1JpBFBu0vuJLru6GM3bfEh2yC5v1SDpgMY36ysXfpdHunVbg5fu4zr5AQBVVkkldfEEOyKr3Zl1T6um0VKni0xLFbAWz+qdR2oCALMCjtVcjedY/aEZZ4+6VSl8mQoxaQFkVNVKDyH0RKJ7otC/IrC+u706gPnCNqPmBeGg38WZtAo5wNYoJCdRNbXReRqBRnBYUdFmEaaEu76eDPw6SA/G50hq3FUv5cMDkOfvCo3Ufu0qmGP6A6GhSRgo29mZKNvqIvmgyxwLUerEoWqFVlobCQX1NTf/0+5S/dq0GylatKRyafm08v3/QhNulYqi1W1mJgFkKe4wNUKaJooeqVGIOz/Ysgw0y2W8tVWX8TbmNsRnMRu5NKoTMv4QtJX1YEA34fLiMaaq4NKkD9RVkoTZ+UbPeoaIOH43vpyXKEo5w8emHZ4Tf33ADV2KRG3kv+lMhCGGnIz9/+PJzNb7bHgk32XCvrsB+yTQtxff/FuIbmhyib33cVHft/Tc7h82BBvbvWeSaNqBU5GPf3egNQt8o2QsAhm9fOaYTazllEJyP19PKGW3tQJnyuDQiY3VxtQtlQ6ai29F5oC16R2KDYkzmur6S0DIFmCvpzASUm8oQRvQBpbrQ1S8g9lJeSVK7P9hSz1qj2Qei7X7h06lvdDi/A96f/l2yz6vGtSa92aTZuR95tKuZuK9qSOraBGklSTFTTNceu4U2liTGYi3FIoSKh12yhogh4xIUfFSB1/gEWXLwnow13UAXxgYIpvqyd+8SK0R2B8gLE/Mo0It6OjymmbBpGw==', 'tsgBojOuNDd4K6qJa8kA7g==', 'Pending', '2026-05-21 15:21:24');

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
-- Table structure for table `guard_dad_submissions`
--

CREATE TABLE `guard_dad_submissions` (
  `dad_id` int(10) UNSIGNED NOT NULL,
  `reference_code` varchar(32) NOT NULL,
  `dgd_report_number` int(11) DEFAULT NULL,
  `head_guard_company_id` varchar(13) NOT NULL,
  `head_guard_name` varchar(255) DEFAULT NULL,
  `post_name` varchar(255) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_display` varchar(255) DEFAULT NULL,
  `guard_id` varchar(64) DEFAULT NULL,
  `guard_name` varchar(255) DEFAULT NULL,
  `issue` varchar(64) NOT NULL DEFAULT 'roster_review',
  `time_record` text DEFAULT NULL,
  `recorded` varchar(32) NOT NULL DEFAULT 'missing',
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `summary` text DEFAULT NULL,
  `scan_path_cipher` text DEFAULT NULL,
  `ai_extracted_cipher` text DEFAULT NULL,
  `iv` varchar(64) NOT NULL,
  `submit_latitude` decimal(10,7) DEFAULT NULL,
  `submit_longitude` decimal(10,7) DEFAULT NULL,
  `submit_accuracy_m` decimal(8,2) DEFAULT NULL,
  `location_label` varchar(512) DEFAULT NULL,
  `sheet_latitude` decimal(10,7) DEFAULT NULL,
  `sheet_longitude` decimal(10,7) DEFAULT NULL,
  `sheet_accuracy_m` decimal(8,2) DEFAULT NULL,
  `sheet_location_label` varchar(512) DEFAULT NULL,
  `evidence_latitude` decimal(10,7) DEFAULT NULL,
  `evidence_longitude` decimal(10,7) DEFAULT NULL,
  `evidence_accuracy_m` decimal(8,2) DEFAULT NULL,
  `evidence_location_label` varchar(512) DEFAULT NULL,
  `history_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`history_json`)),
  `submitted_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guard_dad_submissions`
--

INSERT INTO `guard_dad_submissions` (`dad_id`, `reference_code`, `dgd_report_number`, `head_guard_company_id`, `head_guard_name`, `post_name`, `shift_date`, `shift_display`, `guard_id`, `guard_name`, `issue`, `time_record`, `recorded`, `status`, `summary`, `scan_path_cipher`, `ai_extracted_cipher`, `iv`, `submit_latitude`, `submit_longitude`, `submit_accuracy_m`, `location_label`, `sheet_latitude`, `sheet_longitude`, `sheet_accuracy_m`, `sheet_location_label`, `evidence_latitude`, `evidence_longitude`, `evidence_accuracy_m`, `evidence_location_label`, `history_json`, `submitted_at`, `updated_at`) VALUES
(3, 'DAD-2026-0001', 7, 'amor', 'christian5787264@gmail.com', 'SM Fairview', '2026-05-21', '21 May 2026 — Day shift', NULL, NULL, 'roster_review', 'See uploaded attendance sheet', 'missing', 'pending', 'Daily attendance sheet submitted from the field.', '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', '9LPgf9bvQSkn+31YqlvJjA==', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', '[{\"at\":\"21 May 2026, 22:14\",\"event\":\"Submitted by head guard\",\"note\":\"Sheet: 130 SE Dao, Marikina, 1810 Metro Manila, Philippines \\u00b7 Evidence: 130 SE Dao, Marikina, 1810 Metro Manila, Philippines\"}]', '2026-05-21 22:14:45', '2026-05-21 22:39:03');

-- --------------------------------------------------------

--
-- Table structure for table `guard_duty_status`
--

CREATE TABLE `guard_duty_status` (
  `company_id` varchar(13) NOT NULL,
  `duty_status` enum('active','off_duty','on_report') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guard_duty_status`
--

INSERT INTO `guard_duty_status` (`company_id`, `duty_status`, `updated_at`) VALUES
('amor', 'on_report', '2026-05-21 15:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `guard_incident_submissions`
--

CREATE TABLE `guard_incident_submissions` (
  `inc_id` int(10) UNSIGNED NOT NULL,
  `reference_code` varchar(32) NOT NULL,
  `dgd_report_number` int(11) DEFAULT NULL,
  `head_guard_company_id` varchar(13) NOT NULL,
  `head_guard_name` varchar(255) DEFAULT NULL,
  `category` varchar(32) NOT NULL DEFAULT 'per_post',
  `incident_type` varchar(255) NOT NULL,
  `severity` varchar(16) NOT NULL DEFAULT 'Medium',
  `site_name` varchar(255) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'ongoing',
  `summary` text DEFAULT NULL,
  `incident_description` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `scan_path_cipher` text DEFAULT NULL,
  `ai_extracted_cipher` text DEFAULT NULL,
  `iv` varchar(64) NOT NULL,
  `submit_latitude` decimal(10,7) DEFAULT NULL,
  `submit_longitude` decimal(10,7) DEFAULT NULL,
  `submit_accuracy_m` decimal(8,2) DEFAULT NULL,
  `location_label` varchar(512) DEFAULT NULL,
  `history_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`history_json`)),
  `submitted_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guard_incident_submissions`
--

INSERT INTO `guard_incident_submissions` (`inc_id`, `reference_code`, `dgd_report_number`, `head_guard_company_id`, `head_guard_name`, `category`, `incident_type`, `severity`, `site_name`, `status`, `summary`, `incident_description`, `action_taken`, `scan_path_cipher`, `ai_extracted_cipher`, `iv`, `submit_latitude`, `submit_longitude`, `submit_accuracy_m`, `location_label`, `history_json`, `submitted_at`, `updated_at`) VALUES
(1, 'INC-2026-0001', 8, 'amor', 'christian5787264@gmail.com', 'per_post', 'MAY NADAP SA DI MALAMAN NA DAHILAN KOYA NATANGGAL Ako si Badang Ang ulo This document is to be processed digitally. P…', 'Medium', 'SM Fairview', 'ongoing', 'Incident date (form): This document is to be processed digitally. PLEASE WRITE IN CAPITALIZED/BIG LETTERS.\n\nSubject: INOCENCIO, JOHN ALDRIM R.\n\nMAY NADAP SA DI MALAMAN NA DAHILAN KOYA NATANGGAL Ako si Badang Ang ulo This document is to be processed digitally. PLEASE WRITE IN CAPITALIZED/BIG LETTERS.', 'MAY NADAP SA DI MALAMAN NA DAHILAN KOYA NATANGGAL Ako si Badang Ang ulo This document is to be processed digitally. PLEASE WRITE IN CAPITALIZED/BIG LETTERS.', NULL, 'afTg+xvtM3p7E2dKHC+GLXbJx5qDVJJ+I4mMJryPFmr4cOhlhYJfttM6Gzo5VhjF', '/p14iSWwU/OuNFXG9aCTT/KrLNuShEpuz/ptlTwNfXUPJu4SIZbvelF/q1cmiOL4gz50qww5UMuRM4+9kvQbmE7bwR7vy+Wk1ZhBxRaB8mVyaSEreCN5wC6zgxrVqWg2LEX5CBGpMvPeZGzzj5G0dlpzWYtwgaUaYvA1fYAgbNrzCI+wYhlyUXhLRapm1PXhffrRdCXdVt7PSgX0508FOt7Or/NzbFFCs8I5rFNZ/ycErUyOdj7NA7/qNZuwCrnj5DM9RovzApXPPyYnNwr1u8oz6d9YPQR9YYORdJ41BEBbYw1BxvEabaR4MOw/2N3Te1hvRJEkb6pTP8IS3noZsyxiloQ7uJgSUHjxW/Rz2M6F/yYxOARKQN+zE89elQfHPVoP0h+r7ZFBHKQp0JxiwzXSAa4G/iU3z0wv4/QIlBeSxSUA9Q6tLh4EGXGLNPu3virp1UgXimwrLMx/lIWb6HmtbbMoLx+ftmFsjXeoFGHEpRMuSdv7RkFs2JZCLf6oxDPNNU+mX0BNha8Ut7nomMrRjoCenjXLCG/aGSPuzOUBODFCqiFFl05GyWeXha1LWV/s+tDzmeZzdBQx9Wn5W+GUSn1Bqte7qY4hoAJTjZM4KxH5MnPLij8Ajt0JG/e4DV5kKJ9yX+tveZWM9RoIDO4uDzQOEpCeN9pp0SC+SjCXJdzJh0ZTdHLpS2HX6xca0doSGTQfv3HeQwGFLku/wzLFY60SkFFbEKYAgfA/bHQSR0ph9hTyTVDurobTuKkHne319sw+JzLx4Una+kZYI2+377rkSY0Bg7E4XZgUvVxHtkmHYS5lwvz9AGgOMzWvm79ejtjwXCvcQosenyEaiCLBAEvDEfjTV3At/u38j/VbqWErcUZ6RaHmjJc1f6sTTyLxkCQf3JKXgjICbYRfKnjAomxMbqADAB38jlW5rR5abc9ztm0TTwvSTZm9nY0eEwZWacw6d3RU6ruFqqOxn8yjadjmSRe/Wpq7eDdY8pYTocl0vuFyDRvL1JpBFBu0vuJLru6GM3bfEh2yC5v1SDpgMY36ysXfpdHunVbg5fu4zr5AQBVVkkldfEEOyKr3Zl1T6um0VKni0xLFbAWz+qdR2oCALMCjtVcjedY/aEZZ4+6VSl8mQoxaQFkVNVKDyH0RKJ7otC/IrC+u706gPnCNqPmBeGg38WZtAo5wNYoJCdRNbXReRqBRnBYUdFmEaaEu76eDPw6SA/G50hq3FUv5cMDkOfvCo3Ufu0qmGP6A6GhSRgo29mZKNvqIvmgyxwLUerEoWqFVlobCQX1NTf/0+5S/dq0GylatKRyafm08v3/QhNulYqi1W1mJgFkKe4wNUKaJooeqVGIOz/Ysgw0y2W8tVWX8TbmNsRnMRu5NKoTMv4QtJX1YEA34fLiMaaq4NKkD9RVkoTZ+UbPeoaIOH43vpyXKEo5w8emHZ4Tf33ADV2KRG3kv+lMhCGGnIz9/+PJzNb7bHgk32XCvrsB+yTQtxff/FuIbmhyib33cVHft/Tc7h82BBvbvWeSaNqBU5GPf3egNQt8o2QsAhm9fOaYTazllEJyP19PKGW3tQJnyuDQiY3VxtQtlQ6ai29F5oC16R2KDYkzmur6S0DIFmCvpzASUm8oQRvQBpbrQ1S8g9lJeSVK7P9hSz1qj2Qei7X7h06lvdDi/A96f/l2yz6vGtSa92aTZuR95tKuZuK9qSOraBGklSTFTTNceu4U2liTGYi3FIoSKh12yhogh4xIUfFSB1/gEWXLwnow13UAXxgYIpvqyd+8SK0R2B8gLE/Mo0It6OjymmbBpGw==', 'tsgBojOuNDd4K6qJa8kA7g==', NULL, NULL, NULL, NULL, '[{\"at\":\"21 May 2026, 23:21\",\"event\":\"Submitted by head guard\",\"note\":\"Submitted via guard portal\"}]', '2026-05-21 23:21:24', '2026-05-21 23:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `guard_daily_activity_submissions`
--

CREATE TABLE `guard_daily_activity_submissions` (
  `da_id` int(10) UNSIGNED NOT NULL,
  `reference_code` varchar(32) NOT NULL,
  `dgd_report_number` int(11) DEFAULT NULL,
  `head_guard_company_id` varchar(13) NOT NULL,
  `head_guard_name` varchar(255) DEFAULT NULL,
  `site_name` varchar(255) NOT NULL,
  `activity_mode` varchar(16) NOT NULL,
  `activity_details_cipher` text DEFAULT NULL,
  `scan_path_cipher` text DEFAULT NULL,
  `ai_extracted_cipher` text DEFAULT NULL,
  `iv` varchar(64) NOT NULL,
  `submit_latitude` decimal(10,7) DEFAULT NULL,
  `submit_longitude` decimal(10,7) DEFAULT NULL,
  `submit_accuracy_m` decimal(8,2) DEFAULT NULL,
  `location_label` varchar(512) DEFAULT NULL,
  `history_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`history_json`)),
  `submitted_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Dumping data for table `guard_report_evidence`
--

INSERT INTO `guard_report_evidence` (`id`, `report_number`, `company_id`, `file_name`, `meta_cipher`, `gps_lat`, `gps_lng`, `captured_at`, `created_at`) VALUES
(1, 1, 'amor', 'sVrEsAkg/K69Y2tULP3KoKTsXF12N6uaunfcJbBaC5Htg7NOv+9sD8fOBVWal4Z5', '0BNK21G8k2vyIs+oPhKxntwatLurI3NURHu6F5Ny6biDhi9I+hrEmyfpLh5WGfWM', NULL, NULL, '2026-05-21 21:27:54', '2026-05-21 13:27:54'),
(2, 2, 'amor', 'QV81Hy8yPFkPH6y/dlNOyVtOLq+0iGIo9HjUe6PIV5ewsYnd6b3bH3g6tTfhEOU3', 'HuA5D9NTwr8+r5yn+XwA6HRhW+gMNb9N5u0+a4jsMMSQJ0EXTuTouvf+n7IFWFGp2taGgfOS9Md9Rh+/RjJrU0oVOn4I4B7G4fyqwjqRtcP35HkkgbL0k6mnKjXgBHeV2zcVNT/EImvHSWC8vM/XYqFVCCHfkFQpzoY5oaQJLrUhexwP6+eUDtetc1s1F+NnMG4Meyz9TRqU4p61fLLW9WSxBl9P4MtNcZEJzfkX2nfrT6ca/LA1PPVOw246j/tR641ZexHAdvT9x0xfsvGXTtZv1Xt/cRqshU1UzLADTIg=', NULL, NULL, '2026-05-21 21:47:16', '2026-05-21 13:47:16'),
(3, 3, 'amor', 'rfNudGxbNPqMruQpLysPqGIrRDcrCEnGn5EF1erlLV9cUwlTJxdHqeNAp6M5RseH', 'vgwBwbz6jXSx2BANbuAclKEok4/CUUbtr/nxJ02rlQ1RMJmBFsUw3jduPSoLzWMZZoAB/4DBDh8JaCxfOeuuUoTPj81Dfsb3ZoWPlsP4UGgmtM9DnkHu6Vikia0C2tiXhnijeistzyWl+VR1jpuIfxJe21sY0AOTZ3Aqj9ZyXwx8/CX6T3Zdi/ZP+7Uvi/T8', NULL, NULL, '2026-05-21 22:01:31', '2026-05-21 14:01:31'),
(4, 4, 'amor', '70wUSW69R6mwoepealTiPHOwyaM9v4RWAhonZDArbCwl8YhXrpqNda3M8/2xGzF6', 'psDvXJvw7SHMI5FB59jE6IYIMMAwpZovjAgfu68yRxqUrQiDm9oAYyb8/g+0ppmN41F+gJVlCLgbnaC9k/9I8WEF+tonPbB7jtas5zVhgJNu1XgX66YOP0/kuZ08m57DNWS+4cbx0hk9iSKUTp/b3n9ywly9EIl51mpnDxVdAWa4QyVdC7i2Uj/bNU+C0poY', NULL, NULL, '2026-05-21 22:01:42', '2026-05-21 14:01:42'),
(5, 5, 'amor', 'e+myZzVOO84+pNERrw9gXYB8lFsQkiibJVRYwtFinyHwIbrm/5tB2/F5rGqIPnNX', 'U3JEwep2gFXW0d2F6FwexmuSFU34/aIcI29Vff4+NEx4E93yMrMnEB9QvXncqMmAr5O7ffXzgShUXI6Iy2j5sEgpN1EiSzPRkJG6/dyKqo6AeXBScicpV+uh/bI5iP34sd52f4/ZO3q2d4hC3sdhrLq9KTmyUOiAdmLrUxHLN+n04mIBFmiRRoCts9Ut3041', NULL, NULL, '2026-05-21 22:01:48', '2026-05-21 14:01:48'),
(6, 6, 'amor', 'mrFWnSPbUc9z7Waq0B7Q7EwB5pSLTMhg0FuQ4N1XcJAPe8MCKDQCbSwhl0GV0SEs', 'SxpJ3ZzvkfN2WFdWvUSKG69z7SByGXf10uxBd1omsdxUu3Cm97VYApcCmumkpEUKeTsK1hGSOPN5ysBlRiLRFBognlbLFs17T2B5djvyjKFK3epLRrZ1u1oIBi1XSYvBg5XS6MFyON/9MQ/6Qg1E4ZoR6yCEG95QYqnerNHzNHNRlbrycCPflF5akoatPl8c', NULL, NULL, '2026-05-21 22:01:55', '2026-05-21 14:01:55'),
(7, 7, 'amor', 'DxH95RNB5d0qvteumF53+eLpo88X2cmqHnilB6nEkttCiTkRK4UcJz7j7kNg6cuc', 'vcKArrMKIbu47DTvNCFA8tzv2pdAkkMmxNdyZzjCUeL6EijJ1DBD8jT0g//w33bRkVpX2ycwABWgINQ2tciwKCZ/CgGvmQ3PvXc6GvOolpFYJkqjCKe9kT25S0x/TdacYSgKtewQhMO0rOGZSJTUKhoUamSls5UXeSYN4od8yWRbYA+5j5q1E0rVx+p16rZo', NULL, NULL, '2026-05-21 22:14:45', '2026-05-21 14:14:45'),
(8, 8, 'amor', 'afTg+xvtM3p7E2dKHC+GLWAx/gvv3KTP9crBZk0MWEOML/1kKcPFnqLOcqOPtqmF', 'lnB31nfA8HXQInUlmklrRTJjbF7+BMxf8Jlf24ls3xjCAPHJQzPwFzWUIES6tKGl0tqeDVBwTZHTp4KoWCVQYQ==', NULL, NULL, '2026-05-21 23:21:24', '2026-05-21 15:21:24');

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
(100, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 19:45:53'),
(101, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 21:08:22'),
(102, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 21:08:38');

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
(20, 'php/016_guard_report_evidence_encrypt.php', 7, '2026-05-21 13:01:55'),
(21, '017_guard_dad_submissions.sql', 8, '2026-05-21 13:45:20'),
(22, 'php/017_guard_dad_submissions.php', 8, '2026-05-21 13:45:20'),
(23, '018_guard_dad_dual_location.sql', 9, '2026-05-21 13:58:09'),
(24, 'php/018_guard_dad_dual_location.php', 9, '2026-05-21 13:58:09'),
(25, '019_guard_incident_submissions.sql', 10, '2026-05-21 15:19:46'),
(26, 'php/019_guard_incident_submissions.php', 10, '2026-05-21 15:19:46');

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
('amor', 'christian5787264@gmail.com', NULL, NULL, '$2y$10$KSOWzWbMYeNhGZDR83.bwetV6vKTRE9cx3hSMSCeoonmLWWqx4c0a', NULL, 0, 1, 0, NULL, '2026-05-21 21:08:38', '2026-05-20 17:44:46', '2026-05-20 09:43:53', '2026-05-21 13:08:38'),
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
-- Indexes for table `guard_dad_submissions`
--
ALTER TABLE `guard_dad_submissions`
  ADD PRIMARY KEY (`dad_id`),
  ADD UNIQUE KEY `uk_guard_dad_reference` (`reference_code`),
  ADD KEY `idx_guard_dad_status` (`status`),
  ADD KEY `idx_guard_dad_shift` (`shift_date`),
  ADD KEY `idx_guard_dad_head` (`head_guard_company_id`),
  ADD KEY `idx_guard_dad_dgd` (`dgd_report_number`);

--
-- Indexes for table `guard_duty_status`
--
ALTER TABLE `guard_duty_status`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `guard_incident_submissions`
--
ALTER TABLE `guard_incident_submissions`
  ADD PRIMARY KEY (`inc_id`),
  ADD UNIQUE KEY `uk_guard_inc_reference` (`reference_code`),
  ADD KEY `idx_guard_inc_status` (`status`),
  ADD KEY `idx_guard_inc_category` (`category`),
  ADD KEY `idx_guard_inc_severity` (`severity`),
  ADD KEY `idx_guard_inc_head` (`head_guard_company_id`),
  ADD KEY `idx_guard_inc_dgd` (`dgd_report_number`);

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
  MODIFY `head_guard_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `callout_posts`
--
ALTER TABLE `callout_posts`
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `callout_post_assignments`
--
ALTER TABLE `callout_post_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `dgd`
--
ALTER TABLE `dgd`
  MODIFY `Report_Number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT for table `guard_dad_submissions`
--
ALTER TABLE `guard_dad_submissions`
  MODIFY `dad_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `guard_incident_submissions`
--
ALTER TABLE `guard_incident_submissions`
  MODIFY `inc_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guard_report_evidence`
--
ALTER TABLE `guard_report_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
-- Constraints for table `guard_dad_submissions`
--
ALTER TABLE `guard_dad_submissions`
  ADD CONSTRAINT `fk_guard_dad_dgd` FOREIGN KEY (`dgd_report_number`) REFERENCES `dgd` (`Report_Number`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_guard_dad_head_user` FOREIGN KEY (`head_guard_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guard_duty_status`
--
ALTER TABLE `guard_duty_status`
  ADD CONSTRAINT `fk_guard_duty_status_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE;

--
-- Constraints for table `guard_incident_submissions`
--
ALTER TABLE `guard_incident_submissions`
  ADD CONSTRAINT `fk_guard_inc_dgd` FOREIGN KEY (`dgd_report_number`) REFERENCES `dgd` (`Report_Number`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_guard_inc_head_user` FOREIGN KEY (`head_guard_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
