-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 10:36 AM
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
(6, 'Quiapo, Manila', 1, '2026-05-22 06:22:45'),
(7, 'Tondo, Manila', 1, '2026-05-22 06:22:45'),
(8, 'Sta. Ana, Manila', 1, '2026-05-22 06:22:45');

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
(10, 8, 9, 1, '2026-05-22 08:10:01');

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
(8, 'amor', 'k/4gfeqbcHkWkARy2OE1Dw==', 'afTg+xvtM3p7E2dKHC+GLXbJx5qDVJJ+I4mMJryPFmr4cOhlhYJfttM6Gzo5VhjF', 'Post incident', '2026-05-21 23:21:24', '/p14iSWwU/OuNFXG9aCTT/KrLNuShEpuz/ptlTwNfXUPJu4SIZbvelF/q1cmiOL4gz50qww5UMuRM4+9kvQbmE7bwR7vy+Wk1ZhBxRaB8mVyaSEreCN5wC6zgxrVqWg2LEX5CBGpMvPeZGzzj5G0dlpzWYtwgaUaYvA1fYAgbNrzCI+wYhlyUXhLRapm1PXhffrRdCXdVt7PSgX0508FOt7Or/NzbFFCs8I5rFNZ/ycErUyOdj7NA7/qNZuwCrnj5DM9RovzApXPPyYnNwr1u8oz6d9YPQR9YYORdJ41BEBbYw1BxvEabaR4MOw/2N3Te1hvRJEkb6pTP8IS3noZsyxiloQ7uJgSUHjxW/Rz2M6F/yYxOARKQN+zE89elQfHPVoP0h+r7ZFBHKQp0JxiwzXSAa4G/iU3z0wv4/QIlBeSxSUA9Q6tLh4EGXGLNPu3virp1UgXimwrLMx/lIWb6HmtbbMoLx+ftmFsjXeoFGHEpRMuSdv7RkFs2JZCLf6oxDPNNU+mX0BNha8Ut7nomMrRjoCenjXLCG/aGSPuzOUBODFCqiFFl05GyWeXha1LWV/s+tDzmeZzdBQx9Wn5W+GUSn1Bqte7qY4hoAJTjZM4KxH5MnPLij8Ajt0JG/e4DV5kKJ9yX+tveZWM9RoIDO4uDzQOEpCeN9pp0SC+SjCXJdzJh0ZTdHLpS2HX6xca0doSGTQfv3HeQwGFLku/wzLFY60SkFFbEKYAgfA/bHQSR0ph9hTyTVDurobTuKkHne319sw+JzLx4Una+kZYI2+377rkSY0Bg7E4XZgUvVxHtkmHYS5lwvz9AGgOMzWvm79ejtjwXCvcQosenyEaiCLBAEvDEfjTV3At/u38j/VbqWErcUZ6RaHmjJc1f6sTTyLxkCQf3JKXgjICbYRfKnjAomxMbqADAB38jlW5rR5abc9ztm0TTwvSTZm9nY0eEwZWacw6d3RU6ruFqqOxn8yjadjmSRe/Wpq7eDdY8pYTocl0vuFyDRvL1JpBFBu0vuJLru6GM3bfEh2yC5v1SDpgMY36ysXfpdHunVbg5fu4zr5AQBVVkkldfEEOyKr3Zl1T6um0VKni0xLFbAWz+qdR2oCALMCjtVcjedY/aEZZ4+6VSl8mQoxaQFkVNVKDyH0RKJ7otC/IrC+u706gPnCNqPmBeGg38WZtAo5wNYoJCdRNbXReRqBRnBYUdFmEaaEu76eDPw6SA/G50hq3FUv5cMDkOfvCo3Ufu0qmGP6A6GhSRgo29mZKNvqIvmgyxwLUerEoWqFVlobCQX1NTf/0+5S/dq0GylatKRyafm08v3/QhNulYqi1W1mJgFkKe4wNUKaJooeqVGIOz/Ysgw0y2W8tVWX8TbmNsRnMRu5NKoTMv4QtJX1YEA34fLiMaaq4NKkD9RVkoTZ+UbPeoaIOH43vpyXKEo5w8emHZ4Tf33ADV2KRG3kv+lMhCGGnIz9/+PJzNb7bHgk32XCvrsB+yTQtxff/FuIbmhyib33cVHft/Tc7h82BBvbvWeSaNqBU5GPf3egNQt8o2QsAhm9fOaYTazllEJyP19PKGW3tQJnyuDQiY3VxtQtlQ6ai29F5oC16R2KDYkzmur6S0DIFmCvpzASUm8oQRvQBpbrQ1S8g9lJeSVK7P9hSz1qj2Qei7X7h06lvdDi/A96f/l2yz6vGtSa92aTZuR95tKuZuK9qSOraBGklSTFTTNceu4U2liTGYi3FIoSKh12yhogh4xIUfFSB1/gEWXLwnow13UAXxgYIpvqyd+8SK0R2B8gLE/Mo0It6OjymmbBpGw==', 'tsgBojOuNDd4K6qJa8kA7g==', 'Closed', '2026-05-21 15:21:24'),
(9, 'amor', 'O8r9mnbLujbFQpzSOsWoWQ==', 'BrjSxqnE6TVNKYPOuM8C/9cnyh86tCEL6TKW5NBh+2Jkm4axz0ing7qWDNwdon5b', 'Daily Attendance Document', '2026-05-22 11:25:14', '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', 'wQoRO4UdkwmdZE9+sOGtLw==', 'Pending', '2026-05-22 03:25:14'),
(10, 'ABC-2024-0001', 'yrdAXB/1v6qyPIAxa3NDrg==', 'axNHRcg0rSp327hAdnaC2VAqbu5WoHuf/utqFYV7aAn00RYiWNPdtaVVTahH4sF6oZchRHFwBO4S6Dgvd9g4hg==', 'Daily Activity', '2026-05-22 15:57:10', 'KhpV4KTiFsz/EJnbHFzhgGvgkcmeyQc5pkyDtmlYlWVJwJuYFkB6t7z+WGCYxNL+fZ9vjnR5uj7zwkcOog6ff+lANtaMzP74ezptx5QtTNo=', 'SlooPADXRPorsU6+aXLhVg==', 'Pending', '2026-05-22 07:57:10'),
(11, 'amor', 'JedPMwVRXHA+veOAxTwTiBo/VeFPMnS+yMqsso39S2M=', 'xBSAZLOWuB+VNcW+ZP9Lunr1QzfgjxcP3DQcKgQ8bVNQL1lw5Yb97+lMs5x/AclLip9mwUDez9QDJPHUA1BUoA==', 'Daily Activity', '2026-05-22 16:10:42', 'heLKmOUN46RSgL+SOvAVdFSlWgfWus/uv2EHSfC3N+Ay7a5IQzVFEzgA9QktW8wM4XeZMSeQfA8IFOx3JZjEfps5liB2IS/RTcU7ubF7AZU=', 'Qeuekw7c+GVhhwB3AyvMPQ==', 'Pending', '2026-05-22 08:10:42'),
(12, 'amor', 'Il1y0stcBNFMO27vJvLLvJT4XKCquET7RTsUsYf0DlI=', '5cQUMMJNcY0QzdJPwQAsxL3JsCvypb35MhlDvMx6LfUgh6KYD3WmGQGfmpVWrWee', 'Incident Report', '2026-05-22 16:21:03', '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', 'FxEEm6PxW7TZlHTKC+8YbQ==', 'Pending', '2026-05-22 08:21:03'),
(13, 'amor', '74AZa9JomJHlhVAP+P8ciPSU+qAZjkrvDQZp944Vxqw=', 'pCkovcAHbg51+BYwMuJ72oeNgXt74q7LAYdHJIWBMCNWjYY39BL70QziR7IS0ZOM', 'Incident Report', '2026-05-22 16:22:17', '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', 'cPo6/LlvAR4UCQ49BwXk1g==', 'Pending', '2026-05-22 08:22:17'),
(14, 'amor', 'bWMR7TeSJAZTuaD3lg+EeSRGBLJCQaWgvprTf56aWmE=', 'nyMmvBwZCakpzRbkD2MrmEB2jB8tpJY0S3Nw4Fb0FUwlNWxiJfTNuTHDc8LXA2Bm', 'Incident Report', '2026-05-22 16:27:02', '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', 'OuDVdE2Pbp6oVTsf/CExrA==', 'Open', '2026-05-22 08:27:02'),
(15, 'amor', 'aGMqnoTKm+Lz//F3RHcJrrDITC6vZ+vD41Et1Le4NJo=', 'gBE6AMzlA7j4CVftlQdXARG9luuDclfI/23XR//TOTBvc6sBOeSYDfkjhia+HB5E', 'Daily Time Record', '2026-05-22 16:29:16', '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', 'OTcZiraGG3/55+YUaZEoag==', 'Open', '2026-05-22 08:29:16'),
(16, 'amor', '1IgIzD+nvgR1K/MZGtKLAERwisIHWsD/yARAnguUmhE=', 'WtbQbiGhI+grfTUN7vX3gmhyhACvZwx3uA/H0ll5nHjuG7Q6pZqF6IRfk1yra8d7', 'Incident Report', '2026-05-22 16:30:17', 'loBiqW25pXQhLjcz3BNKhpFnV+fGL2AT1jk/19KWKeGiE9B7LMvDlMMn8+s5PavHJgXW+Ht86Uio+5HEmIFgytUuLa+AkcHvMX8T4NvB62aN70Qxrc5zQXpn2J8dyL1Vmbntde+RFducuMAxYZq8MSgHx0nJuZjtHeDuM4JWx/j6fSZwX4YKzH20AutipsBZoHOki5O10Z7fxEeixyYbXk5A/AB5RffiMg/4DHprjYBDtnhL3pu6WDasIpssfmhTMTPHtKOA1JpEtt3DSMMzovH9LL4Rksd9tS9qoSQZfxVLKZXk+5iAwMEYL5f5VOkTcVkFS8J1mxCOkqEvgyj4HA1ZRSV/Bo4h1Ntq4bYjOx5enMrGJs7bPhmNfkNtr7Mi5HNOGvXFDLLkROPCMxRAX+Nz3mkIpLO4oihuboB3/CyEUXXq/PckG9scLomuBlOtbSddqzsMAdwA96AToHzMM33j1pIdFFMbIlVtdks0Fikx9+UMajcTNW4BK1onfhaxWw0IhoT/90gIGppXz/0s4kwNBZdxjPGUpGYLzzdnO8rhowTp6XJwjHMhCtthRfjmNYy3WHiCOzn8fQN2KovvAXQtXP5D9eBp3OadJQLT5zgHiZRTK64sPOtms2a2oVSsXrleElCWU6cPhP5tC4ULLr/dPwE+vsvwP+9fLq6qAw3qia1+PEM7dTR/0WnQJyMYlFQOm+yjzL9rFT2XDxkTfXG92EhfOop1AOI3jQ/XYdbPygMFRUFFk8AK+PxUR3u2+dZuMwTnQ7Ri/+hJGbavfknLMv49tfEbfCVg23KpGGRZ3ZmNN0PtsF/61iKNNjEZkC1kMi2k0dv/iFgIPmqrw3tkOjInWMko1hDtzUAa2rmJNa7gLxN/t6IH2wYOBO5kO4dDSCx6Cp0KqjMfKVt62SaeXn/adI4FhpepOaiZHEx2siS43ZNDNwyNpRBwMD7EKIIP8F1cl2WHgEbG5O6NE/vjkoo+2N7193qh7f+u456nQSDO9WEXd+eyKJcVFTMnJfnwFp9tsarePx3U9XL8RzLzHLPgGkLAFZAodY42GVOc2rflLXDYM9MSs8NhcMEIAlXzwXLoTJzrZni0AtOa95a7KDOUiOQjgcMcrTZigN8pOusFiX5Hene19j3PidkLZVJcYyyD+Aex7XUZSE2A4MPY7ndUxHmnfXWWPLax4PX/DctqGdTssDSlg1PoOgquVkQtt4EKLHReHA+Noj3vJFO3X++UMsvKUhGeXUvS6XiDjPCCLW9lpaPhuaFIwWyGmF+BBVnWJZ6050EclxPEHq2JK2pNHmsPBtD7qZNELV29lnqA6Xpw/lm0oMzi0JKvs9zn04ZOhVjchTzi7LYwNUQynxqIkwQH6VAcukUq/QwiczywfZZ6QjcFBal2SOQYtNU6mM7EeK0NzDQ23nhZXuLXwcKdbHePGcbJm5mM0I2CHpSoC/G5vonuEPs/7/sB5cDLuqnMl/NSv+SkH09xA9/C5U+P11Tuxh2C8Do0IW+LFBl/PYpntd+yM8oYeoE4UrH4jtvgIyfHO5yz5lwTu/QKkNV9oAV0w8jBGpN9lEfyZ/gb8CuGP4PGkBqBtDaR8FFrC2NciUslno/YDrB4aQSeq9goaJLx/zNkRDWydZZFK/5s88jeLRzx7zAdC9bIunTl4yFhqtH1xT294DiXKzyz/314O1Qv5MH0RKX58eUZIo5bFO5lBhn1ztX/qHZGslAB791nvWh203NQuu7+UeluYSWb5tcXHJDmfvxM4Sdiwiy3R9v77ACuX4QlYdrzo+4Oex5FvClxSZXErg8Xy0nMlh5yy/SAtba0K52gZNBeMtfGQk4AoGiw+ZATWSQKz+ojWnqHCWWy1qr1fhTNnD5/NJtPGItCODccXIygZkA2O2jRalTTsLz+z6T9b3S4Z5ymTVXPo77wPoCfYI9Q0w6zTV0Q+o/fiZCoPqS0PZDw8FnevB/lpABfTn1nwnQgBCDXED1elHy8uqmdGzqbAsISA6F2qP8W1sCl63/oZZeqLn/inOgtK5cqWHXYe6VuA9t5mkowrKnmyKUaF+ebmko08/Ua0pwWzNDju9cu6OW7DuD/7w/3saFNC+7ebZjRABjQlJzNwpljWY8KrZyJBTX+6d03jlcdbJRYalalkmq7XGuemBb5e4R1P6isE/EpKkcplz8wvBgasreD4RLj058w+sYwug3/GeOjHbooIAcXBD779IPC7+Fi2AXcUVdiWW0L3DOH2uoPdQW+8aWZPNNG1dF8sl/xNX1xs5iOZLslfKYxJOTY0jr133ROvPv+/ckDEY6EkRQPH4tWQHFIyQ39MY6/TX4RAi7K1xmjy27aCTp1Ia45y1vvfhl+tPVPyG6KIH+iAJLVsSvHHLQZHcg3Zb0qSTvKJzHAKmq5QKlak19UtVzK6YiyPKlaPikR+LEfZmgoxIJ6bZyFmxXfjtYMGpeasFBkDODPR6dtyuvfJ0I8OWSeO45Pyw+K8aEAs6kxQx02WiU9/AQW9e6n6X7Wgfn4ZKs1VGw4Jr52WRlRJYdZ+kY96triu8gkRTFy2y3QEitZUPXa5OiIbQIJ2ne/g0ZNSteCDImA5DVGF6SFv4nq7YMXFjuMbXLRdwZyNq2enoiUbIdeK/5Vh/N17fxecnC7AoGvu+Y3klIQtaU=', '6BaYoXME/AjJCy6vfQlU/w==', 'Pending', '2026-05-22 08:30:17');

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

--
-- Dumping data for table `guards`
--

INSERT INTO `guards` (`Company_ID`, `Head_ID`, `Rank`, `Last_Name`, `First_Name`, `Middle_Name`, `Post_Assigned`) VALUES
('ABC-2026-0301', NULL, 'Field', 'Garcia', 'Paulo', 'Emmanuel', NULL),
('ABC-2026-0302', NULL, 'Field', 'Flores', 'Janelle', 'Marie', NULL),
('ABC-2026-0303', NULL, 'Field', 'Navarro', 'Christian', 'Paolo', NULL),
('ABC-2026-0304', NULL, 'Field', 'Torres', 'Samantha', 'Louise', NULL),
('ABC-2026-0305', 'amor', 'Field', 'Bautista', 'Vincent', 'Adrian', NULL),
('ABC-2026-0306', NULL, 'Field', 'Castillo', 'Patricia', 'Anne', NULL),
('ABC-2026-0307', NULL, 'Field', 'Herrera', 'John', 'Carlo', NULL),
('ABC-2026-0308', NULL, 'Field', 'Fernandez', 'Nicole', 'Andrea', NULL),
('ABC-2026-0309', 'amor', 'Field', 'Aquino', 'Rafael', 'Dominic', NULL),
('ABC-2026-0310', NULL, 'Field', 'Salazar', 'Bea', 'Camille', NULL),
('ABC-2026-0311', NULL, 'Field', 'Lim', 'Adrian', 'Miguel', NULL),
('ABC-2026-0312', NULL, 'Field', 'Ramirez', 'Mark', 'Anthony', NULL),
('ABC-2026-0313', NULL, 'Field', 'Gutierrez', 'Mikaela', 'Joy', NULL),
('ABC-2026-0314', NULL, 'Field', 'Diaz', 'Kevin', 'Lawrence', NULL),
('ABC-2026-0315', NULL, 'Field', 'Rivera', 'Alyssa', 'Nicole', NULL),
('ABC-2026-0316', NULL, 'Field', 'Morales', 'Francis', 'Xavier', NULL),
('ABC-2026-0317', NULL, 'Field', 'Santiago', 'Katrina', 'Mae', NULL),
('ABC-2026-0318', NULL, 'Field', 'Cruz', 'Elijah', 'Matthew', NULL),
('ABC-2026-0319', NULL, 'Field', 'Lopez', 'Camille', 'Therese', NULL),
('ABC-2026-0320', NULL, 'Field', 'Romero', 'Nathaniel', 'James', NULL),
('ABC-2026-0321', NULL, 'Field', 'Valdez', 'Bianca', 'Sofia', NULL),
('ABC-2026-0322', NULL, 'Field', 'Perez', 'Angelo', 'Marcus', NULL),
('ABC-2026-0323', NULL, 'Field', 'Velasco', 'Chelsea', 'Anne', NULL),
('ABC-2026-0324', NULL, 'Field', 'Mendoza', 'Carla', 'Denise', NULL),
('ABC-2026-0325', NULL, 'Field', 'Chavez', 'Gabriel', 'Lorenzo', NULL),
('ABC-2026-0326', NULL, 'Field', 'Manalo', 'Danielle', 'Faith', NULL),
('ABC-2026-0327', NULL, 'Field', 'Mercado', 'Joshua', 'Daniel', NULL),
('ABC-2026-0328', NULL, 'Field', 'Evangelista', 'Trisha', 'Mae', NULL),
('ABC-2026-0329', NULL, 'Field', 'Ramos', 'Carl', 'Benedict', NULL),
('ABC-2026-0330', NULL, 'Field', 'Cabrera', 'Princess', 'Mae', NULL),
('ABC-2026-0331', NULL, 'Field', 'Dominguez', 'Ivan', 'Cedrick', NULL),
('ABC-2026-0332', NULL, 'Field', 'Soriano', 'Elaine', 'Patricia', NULL),
('ABC-2026-0333', NULL, 'Field', 'Mendoza', 'Kurt', 'Raphael', NULL),
('ABC-2026-0334', 'amor', 'Field', 'Alonzo', 'Hazel', 'Marie', NULL),
('ABC-2026-0335', NULL, 'Field', 'Pascual', 'Nathan', 'Kyle', NULL);

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
(3, 'DAD-2026-0001', 7, 'amor', 'christian5787264@gmail.com', 'SM Fairview', '2026-05-21', '21 May 2026 — Day shift', NULL, NULL, 'roster_review', 'See uploaded attendance sheet', 'missing', 'pending', 'Daily attendance sheet submitted from the field.', 'DxH95RNB5d0qvteumF53+WUm9TkLOvJeXM9eFQqItfrs4jICDoQ4iKH+7HwZ2Qo4', 'PMOU4lr4UldrtVmyqDiytFA7/QtrNwjBVus4h3bOArgkfoIJXQRq7WUPl0hm4G4CFDIZd5EXHs7cHhYAKwFGkU8QILmDBzSzPV6BjzQM61Xr+5DTdAUCX/BANlyOxqRGvfwU+lY5S06UVDq3xfcnVPH2FfxjCDru/0glYiDNCgA5nwolhBw4Q9l7+LIaR9xK5UYonXH8S86yey+01cCjQQ8aH4+Ug2haV3NO9+IZOaBhXDtYRrGkfUOw3AAF21uO+Qm/Etm8WnShu+BGO+mdNZq+axrq5Dda85dR6WtBvXsHiaREjcJkFkLtd3hC29Y57wKCFbtDqJJSC/FrdbVGeZGKjumNonJGfs3nxd6/IYsm5bT+Y/HXLDNMBJDlPoNo9YvWqwRdKxIrY0jdcWSuf5m2UjyayF7BQ/TtFfUJJuttmMMLo5Wpa7dQLs+4EfDc5wC9xr7AgimtZQikxDZzCdT1UkU9P65VXqPJRkJzvI0+wwXSQuBg0SL1Wd9QhoKsglkJhwzmqJLzesWq9PFEJRj9qkPMHSRPW0olH1LpqryqTQFEInp/7nPDnQgPFple6M8FcVInAen2TelyuVSPzIkP4Md2SO4SZQYfhm+FPxSSgG3teZJ+cRV9zidGVCYsq/vXMi4j9tv1R3SDTErsJGvD1BqEi9P3DruhOhWWrx6FJeZmaRsvailo17Qw8hOfRVZpqA0yAUVt1XeCAy7S4pGM7KW99w900AVMswFQygUTbffZ+B57dk5zgbkShT0KyG040KnGePybiYNjuYOsljvXcH8g/CUG11ZPQsUn3nE6vMetYnA4jGR1bV/QtE4hpx6OiiKXzO0GrDVniutARVgS0bxpoV8oZ9delwnmYc8Vr/KeQZJGbYhHqrNM67JyJBT7aqD/rw0lUxTw9GVOzg7jLgu88Co7jNL/YyQDFJ8i3xoNSnckMJwu66Ox1z/Bil+SR7mzcz1dsYtkYF89QXviVOB7rb5N5FHmTHzNaVIWyotDgaRoL5KSM14VnGhY9lxt3FzIGF0xGimTjRwzHj4alX8yR2DPqnIem3oVTHyNPHA6Zo4pGeJSMPAySIlMkHk2iJmDKv05km7nedn6BxjOsBp5CYK/3DTaRg1WD6ZWdMK0ExDnrpA5Vx7oGylHOgO97n0oE5YzhJ9SnXo/NTnVR4uwe3Ih2HXbm7Fs0j1Frw3ZisgAKZ5ydDypzXtxrkxHejU/mkSZDE1vvNkiUffMCeq9rsl9Dg1DuM4Og5mPXsHgFUWeJPMbLpzTzoM8DCWihUcoKZXdxZUB9lF1WkZMaN0PRV2xP3RiuVg4czUJZHGLmwmxVn47AJspbv8J4CgEdWVdLqC5BsflpNXLszngb9Cpl0VPqLqcT+t89WKdRXPvpu3u/OLeubccTXufL2JO7mVAOgYidAPMFvOWSL/JnYWiXxpYil53H2zluh5bpD7fQMyoEoYC7JpTZRn9pMBOTPW7BYoSaBv/EvlLXKz/dOeJO2YgDrQWLji60WocGx09cam40QKMdGndLiHcO0Zm3W1oq0o0myTZAatFhpaSyNYQghX7ak0f8Pezot5G7cQ207fl0usmdurE3yWop3Gw1qFUJxOwUJ4MJ7+PqhfpKfNQywBrFm3Pb2mTBqeLHmdGiwF4XGbQrcDYD6fkCZLnJly1I/irOx2OKNRMtjpzUlWWYeLSMbt//6wNsvQyXmWANvIy4rYt/vVqjSrZGw8U6By2h3yVjcyMjJzeTU/GghHHOV7lSuHSOwNbbZoXQGldCcTjsFcH1oz3lm0ClmaptQvZnsj8/zUJMFc/9I/0vNhr/Pd8+DOHLcY71W3l2dTRhIQv7Q+azHQHSgD80tWhEqKg+dbbkYtdJ6vePlB9tKV4YUPiRnbgvRpSnK18yjHkCaFdGY3vQjvDjQcT8yStyBSRlhRcMV4oKW+vL+EUWT8mJp3hZjmAjZM0R9cEgCFeaxmsNICRMktrLNtLSv4MzfeW5ITkvnQ8cDNkvZ5yYFRH9j5zz6USCizW8isIk0IyOcSKFmMRwh80mqBxiVuLFvamUCG3LKrSRiii4qzgTwMl3nZvegIgBJVXQYM9j8wI4LGC+9x37wEtOerTZiFyfh9wvHP9TMsGxjnDbFP95X83tXm0I3TmZ+Am2EdxXmNUa6lJgg1HonXmmkrtlsahLmv63+IC7e2RP5rfZmPk1egnZx3GGRASG0h9Nb70Y63PZ2dVGZBw55CBokUPvwlAKLz327w73vqOV4oYSgZKHlUy5dVyYMNs+uTAy3HOK7+fJqeOCsDYbRbSwQwBZxyRgSzE9+I9vuz7M/FvFoKSKK1HQzHFZGaXyI0RUiidt79bWFetpibYn5fiUYKBUOhthhRHkkw/3Rn+JQSpB+kauLMTVjSDMSCYYUwlAO4gf5xsvC3mpuagbL8l00FWG/VVmXb0cia4Pt7Sj03UqHdhSLeNkR3iTXY5S58b1cA3f3Jeg5m2FmeIGahqYO+g/7tcy0WIrzHfBW7JpccFPcPwE+67mpMsTclueQHHHrnLTBvWzVpTDQ1go08l1yxUO5mK4bjDnpT/x5jxEjbHNsKr8kxrhoDjgAMce3eR8ofTVQ4Dh2Qz49UEJo4NqBQH3wPzaNQzFg57j//ZCV1xpwczoLSphJZBxCNsFX1HAVJqv98qZDyMwNwSBYCSRrJkPRTvWx9wT2PvecI+cwUPonG2WqI7beEmey2H9mzPMed+yiBz3R5vgK4Ovg5rks+XUxxzvvDfka+t+YbNsH+LSuem8t+UlSwJHmGYlEURJwhAH++2mSDlOQmiYM8RbiqV01cjG18Bv93bdir0TlGyow==', '9LPgf9bvQSkn+31YqlvJjA==', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', 14.6500000, 121.1200000, 50000.00, '130 SE Dao, Marikina, 1810 Metro Manila, Philippines', '[{\"at\":\"21 May 2026, 22:14\",\"event\":\"Submitted by head guard\",\"note\":\"Sheet: 130 SE Dao, Marikina, 1810 Metro Manila, Philippines \\u00b7 Evidence: 130 SE Dao, Marikina, 1810 Metro Manila, Philippines\"}]', '2026-05-21 22:14:45', '2026-05-21 22:39:03'),
(5, 'DTR-2026-0002', 15, 'amor', 'christian5787264@gmail.com', 'P.M', '2026-05-22', '22 May 2026 — Day shift', NULL, NULL, 'missing_time_out', 'Time-in 0; no time-out (+7 more on sheet)', 'missing', 'ongoing', 'Time-in 0; no time-out (+7 more on sheet)', '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', 'OTcZiraGG3/55+YUaZEoag==', 14.6500000, 121.1200000, 50000.00, '130 Southeast Dao, Marikina, Metro Manila', 14.6500000, 121.1200000, 50000.00, '130 Southeast Dao, Marikina, Metro Manila', 14.6500000, 121.1200000, 50000.00, '130 Southeast Dao, Marikina, Metro Manila', '[{\"at\":\"22 May 2026, 16:29\",\"event\":\"Submitted by head guard\",\"note\":\"Sheet: 130 Southeast Dao, Marikina, Metro Manila \\u00b7 Evidence: 130 Southeast Dao, Marikina, Metro Manila\"}]', '2026-05-22 16:29:16', '2026-05-22 16:29:16');

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
  `status` varchar(32) NOT NULL DEFAULT 'pending',
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

--
-- Dumping data for table `guard_daily_activity_submissions`
--

INSERT INTO `guard_daily_activity_submissions` (`da_id`, `reference_code`, `dgd_report_number`, `head_guard_company_id`, `head_guard_name`, `site_name`, `activity_mode`, `status`, `activity_details_cipher`, `scan_path_cipher`, `ai_extracted_cipher`, `iv`, `submit_latitude`, `submit_longitude`, `submit_accuracy_m`, `location_label`, `history_json`, `submitted_at`, `updated_at`) VALUES
(1, 'GDA-2026-0001', 10, 'ABC-2024-0001', 'ABC-2024-0001', 'Test Post Site', 'normal', 'pending', NULL, 'axNHRcg0rSp327hAdnaC2VAqbu5WoHuf/utqFYV7aAn00RYiWNPdtaVVTahH4sF6oZchRHFwBO4S6Dgvd9g4hg==', 'KhpV4KTiFsz/EJnbHFzhgGvgkcmeyQc5pkyDtmlYlWVJwJuYFkB6t7z+WGCYxNL+fZ9vjnR5uj7zwkcOog6ff+lANtaMzP74ezptx5QtTNo=', 'SlooPADXRPorsU6+aXLhVg==', 14.5547000, 121.0244000, NULL, 'Test', '[{\"at\":\"22 May 2026, 15:57\",\"event\":\"Submitted by head guard\",\"note\":\"Daily activity \\u2014 Normal operation\"}]', '2026-05-22 15:57:10', '2026-05-22 15:57:10'),
(2, 'GDA-2026-0002', 11, 'amor', 'amor', 'Sta. Ana, Manila', 'event', 'reviewed', 'g6FgIosGmiDjZIAWgz+fzQ==', 'xBSAZLOWuB+VNcW+ZP9Lunr1QzfgjxcP3DQcKgQ8bVNQL1lw5Yb97+lMs5x/AclLip9mwUDez9QDJPHUA1BUoA==', 'heLKmOUN46RSgL+SOvAVdFSlWgfWus/uv2EHSfC3N+Ay7a5IQzVFEzgA9QktW8wM4XeZMSeQfA8IFOx3JZjEfps5liB2IS/RTcU7ubF7AZU=', 'Qeuekw7c+GVhhwB3AyvMPQ==', NULL, NULL, NULL, NULL, '[{\"at\":\"22 May 2026, 16:10\",\"event\":\"Submitted by head guard\",\"note\":\"Daily activity \\u2014 With event \\/ activity\"},{\"at\":\"22 May 2026, 16:13\",\"event\":\"Registry: Reviewed\",\"note\":\"Status updated by admin.\",\"actor\":\"grey\"}]', '2026-05-22 16:10:42', '2026-05-22 16:13:03');

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
('ABC-2024-0001', 'on_report', '2026-05-22 07:57:10'),
('amor', 'on_report', '2026-05-22 08:30:17');

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
(1, 'INC-2026-0001', 8, 'amor', 'christian5787264@gmail.com', 'per_post', 'MAY NADAP SA DI MALAMAN NA DAHILAN KOYA NATANGGAL Ako si Badang Ang ulo This document is to be processed digitally. P…', 'Medium', 'SM Fairview', 'accomplished', 'MAY NADAP SA DI MALAMAN NA DAHILAN Ako si Badang', 'MAY NADAP SA DI MALAMAN NA DAHILAN Ako si Badang', 'KOYA NATANGGAL Ang ulo', 'afTg+xvtM3p7E2dKHC+GLXbJx5qDVJJ+I4mMJryPFmr4cOhlhYJfttM6Gzo5VhjF', '/p14iSWwU/OuNFXG9aCTT/KrLNuShEpuz/ptlTwNfXUPJu4SIZbvelF/q1cmiOL4gz50qww5UMuRM4+9kvQbmE7bwR7vy+Wk1ZhBxRaB8mVyaSEreCN5wC6zgxrVqWg2LEX5CBGpMvPeZGzzj5G0dlpzWYtwgaUaYvA1fYAgbNrzCI+wYhlyUXhLRapm1PXhffrRdCXdVt7PSgX0508FOt7Or/NzbFFCs8I5rFNZ/ycErUyOdj7NA7/qNZuwCrnj5DM9RovzApXPPyYnNwr1u8oz6d9YPQR9YYORdJ41BEBbYw1BxvEabaR4MOw/2N3Te1hvRJEkb6pTP8IS3noZsyxiloQ7uJgSUHjxW/Rz2M6F/yYxOARKQN+zE89elQfHPVoP0h+r7ZFBHKQp0JxiwzXSAa4G/iU3z0wv4/QIlBeSxSUA9Q6tLh4EGXGLNPu3virp1UgXimwrLMx/lIWb6HmtbbMoLx+ftmFsjXeoFGHEpRMuSdv7RkFs2JZCLf6oxDPNNU+mX0BNha8Ut7nomMrRjoCenjXLCG/aGSPuzOUBODFCqiFFl05GyWeXha1LWV/s+tDzmeZzdBQx9Wn5W+GUSn1Bqte7qY4hoAJTjZM4KxH5MnPLij8Ajt0JG/e4DV5kKJ9yX+tveZWM9RoIDO4uDzQOEpCeN9pp0SC+SjCXJdzJh0ZTdHLpS2HX6xca0doSGTQfv3HeQwGFLku/wzLFY60SkFFbEKYAgfA/bHQSR0ph9hTyTVDurobTuKkHne319sw+JzLx4Una+kZYI2+377rkSY0Bg7E4XZgUvVxHtkmHYS5lwvz9AGgOMzWvm79ejtjwXCvcQosenyEaiCLBAEvDEfjTV3At/u38j/VbqWErcUZ6RaHmjJc1f6sTTyLxkCQf3JKXgjICbYRfKnjAomxMbqADAB38jlW5rR5abc9ztm0TTwvSTZm9nY0eEwZWacw6d3RU6ruFqqOxn8yjadjmSRe/Wpq7eDdY8pYTocl0vuFyDRvL1JpBFBu0vuJLru6GM3bfEh2yC5v1SDpgMY36ysXfpdHunVbg5fu4zr5AQBVVkkldfEEOyKr3Zl1T6um0VKni0xLFbAWz+qdR2oCALMCjtVcjedY/aEZZ4+6VSl8mQoxaQFkVNVKDyH0RKJ7otC/IrC+u706gPnCNqPmBeGg38WZtAo5wNYoJCdRNbXReRqBRnBYUdFmEaaEu76eDPw6SA/G50hq3FUv5cMDkOfvCo3Ufu0qmGP6A6GhSRgo29mZKNvqIvmgyxwLUerEoWqFVlobCQX1NTf/0+5S/dq0GylatKRyafm08v3/QhNulYqi1W1mJgFkKe4wNUKaJooeqVGIOz/Ysgw0y2W8tVWX8TbmNsRnMRu5NKoTMv4QtJX1YEA34fLiMaaq4NKkD9RVkoTZ+UbPeoaIOH43vpyXKEo5w8emHZ4Tf33ADV2KRG3kv+lMhCGGnIz9/+PJzNb7bHgk32XCvrsB+yTQtxff/FuIbmhyib33cVHft/Tc7h82BBvbvWeSaNqBU5GPf3egNQt8o2QsAhm9fOaYTazllEJyP19PKGW3tQJnyuDQiY3VxtQtlQ6ai29F5oC16R2KDYkzmur6S0DIFmCvpzASUm8oQRvQBpbrQ1S8g9lJeSVK7P9hSz1qj2Qei7X7h06lvdDi/A96f/l2yz6vGtSa92aTZuR95tKuZuK9qSOraBGklSTFTTNceu4U2liTGYi3FIoSKh12yhogh4xIUfFSB1/gEWXLwnow13UAXxgYIpvqyd+8SK0R2B8gLE/Mo0It6OjymmbBpGw==', 'tsgBojOuNDd4K6qJa8kA7g==', NULL, NULL, NULL, NULL, '[{\"at\":\"21 May 2026, 23:21\",\"event\":\"Submitted by head guard\",\"note\":\"Submitted via guard portal\",\"description\":\"MAY NADAP SA DI MALAMAN NA DAHILAN Ako si Badang\",\"incident_description\":\"MAY NADAP SA DI MALAMAN NA DAHILAN Ako si Badang\",\"immediate_action\":\"KOYA NATANGGAL Ang ulo\",\"action_taken\":\"KOYA NATANGGAL Ang ulo\",\"edited_at\":\"22 May 2026, 10:41\",\"edited_by\":\"grey\"},{\"at\":\"22 May 2026, 15:03\",\"event\":\"Report accepted\",\"note\":\"Accepted for operations review \\u2014 case continues.\",\"actor\":\"grey\",\"source\":\"admin\",\"kind\":\"decision\",\"edited_at\":\"22 May 2026, 15:16\",\"edited_by\":\"grey\"},{\"at\":\"22 May 2026, 15:03\",\"event\":\"Registry: Closed\",\"note\":\"\",\"actor\":\"grey\",\"source\":\"admin\",\"kind\":\"status\",\"edited_at\":\"22 May 2026, 15:16\",\"edited_by\":\"grey\"},{\"at\":\"22 May 2026, 15:16\",\"event\":\"Registry: On hold\",\"note\":\"\",\"actor\":\"grey\",\"source\":\"admin\",\"kind\":\"status\",\"edited_at\":\"22 May 2026, 15:16\",\"edited_by\":\"grey\"},{\"at\":\"22 May 2026, 15:16\",\"event\":\"Registry: Closed\",\"note\":\"Registry status updated.\",\"actor\":\"grey\",\"source\":\"admin\",\"kind\":\"status\"}]', '2026-05-21 23:21:24', '2026-05-22 15:16:44'),
(2, 'INC-2026-0002', 14, 'amor', 'christian5787264@gmail.com', 'per_post', 'Policy breach — unauthorized access', 'High', 'Sta. Ana, Manila', 'ongoing', 'Guard under head guard: OF GUARD: · Classified: Policy breach — unauthorized access · On post · High', NULL, NULL, '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', 'OuDVdE2Pbp6oVTsf/CExrA==', NULL, NULL, NULL, NULL, '[{\"at\":\"22 May 2026, 16:27\",\"source\":\"head_guard\",\"kind\":\"field_submission\",\"event\":\"Report filed\",\"description\":\"\",\"immediate_action\":\"\",\"guard_name\":\"OF GUARD:\",\"note\":\"\",\"filed_by\":\"christian5787264@gmail.com\",\"incident_description\":\"\",\"action_taken\":\"\",\"edited_at\":\"22 May 2026, 16:27\",\"edited_by\":\"grey\"},{\"at\":\"22 May 2026, 16:27\",\"source\":\"system\",\"kind\":\"classification\",\"event\":\"Classified\",\"note\":\"Type: Policy breach \\u2014 unauthorized access \\u00b7 On post \\u00b7 Severity High\"},{\"at\":\"22 May 2026, 16:27\",\"source\":\"system\",\"kind\":\"routing\",\"event\":\"Assigned to operations\",\"note\":\"Stage 2 \\u2014 Admin review. Within 1 hour \\u2014 preserve evidence same shift\"}]', '2026-05-22 16:27:02', '2026-05-22 16:27:33'),
(3, 'INC-2026-0003', 16, 'amor', 'christian5787264@gmail.com', 'per_post', 'Policy breach — unauthorized access', 'High', 'Sta. Ana, Manila', 'ongoing', 'Guard under head guard: OF GUARD: · Classified: Policy breach — unauthorized access · On post · High', 'At\nACTION\nthe\na', 'near slipped vistior\na pm 3:15 approximately\nflour wet to due a\nentrance main\nTAKEN\nwith market and cleaned\nimmedataly was area\nwas aid first and sign warning provided\nBY : : CONFIRMATION\nHEAD GUARD', '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', '6BaYoXME/AjJCy6vfQlU/w==', 14.6500000, 121.1200000, 50000.00, '130 Southeast Dao, Marikina, Metro Manila', '[{\"at\":\"22 May 2026, 16:30\",\"source\":\"head_guard\",\"kind\":\"field_submission\",\"event\":\"Report filed\",\"description\":\"At\\nACTION\\nthe\\na\",\"immediate_action\":\"near slipped vistior\\na pm 3:15 approximately\\nflour wet to due a\\nentrance main\\nTAKEN\\nwith market and cleaned\\nimmedataly was area\\nwas aid first and sign warning provided\\nBY : : CONFIRMATION\\nHEAD GUARD\",\"guard_name\":\"OF GUARD:\",\"note\":\"Location: 130 Southeast Dao, Marikina, Metro Manila\",\"filed_by\":\"christian5787264@gmail.com\"},{\"at\":\"22 May 2026, 16:30\",\"source\":\"system\",\"kind\":\"classification\",\"event\":\"Classified\",\"note\":\"Type: Policy breach — unauthorized access · On post · Severity High\"},{\"at\":\"22 May 2026, 16:30\",\"source\":\"system\",\"kind\":\"routing\",\"event\":\"Assigned to operations\",\"note\":\"Stage 2 — Admin review. Within 1 hour — preserve evidence same shift\"}]', '2026-05-22 16:30:18', '2026-05-22 16:30:18');

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
(8, 8, 'amor', 'afTg+xvtM3p7E2dKHC+GLWAx/gvv3KTP9crBZk0MWEOML/1kKcPFnqLOcqOPtqmF', 'lnB31nfA8HXQInUlmklrRTJjbF7+BMxf8Jlf24ls3xjCAPHJQzPwFzWUIES6tKGl0tqeDVBwTZHTp4KoWCVQYQ==', NULL, NULL, '2026-05-21 23:21:24', '2026-05-21 15:21:24'),
(9, 9, 'amor', 'BrjSxqnE6TVNKYPOuM8C/zBhKOwdzTtKABXX5HTHfE0lkcu8sGhqMhd1NsC1q8R7', 'nG+sFK9xpsXhxWBcgOPxnHe57xqVRT8sZqXkiJDoYtywJYdSqZ+V0We9UDOToLvEC3pzqJsVs/KPFX9YvKixS2mrKFcAkVI77zPmW8QqMre5UcAFNobjDfFlLnjKFKC5ToRf+Az5ZCM9kYFWU4lXj6VBjbhxKvlRQ1xgZfsnV1pRKDGdbfN97flts3/draKu', NULL, NULL, '2026-05-22 11:25:14', '2026-05-22 03:25:14'),
(10, 11, 'amor', 'xBSAZLOWuB+VNcW+ZP9Lus8JT4MSiTukj3Pw7GgwIL/3GTw54cqwK0CFlEZekW//', NULL, NULL, NULL, '2026-05-22 16:10:42', '2026-05-22 08:10:42'),
(11, 11, 'amor', 'xBSAZLOWuB+VNcW+ZP9Luv/LbwD+X25rmly5PfLF3T3ukvaxdLhRQQ53h3B3jdDO', NULL, NULL, NULL, '2026-05-22 16:10:42', '2026-05-22 08:10:42'),
(12, 11, 'amor', 'xBSAZLOWuB+VNcW+ZP9LuogYByaj+5MmnfU1a+X8LekdM0KJPn7p67V2/mupg3fk', NULL, NULL, NULL, '2026-05-22 16:10:42', '2026-05-22 08:10:42'),
(13, 11, 'amor', 'xBSAZLOWuB+VNcW+ZP9LuqAYD9vddu6xMy2PEvquViUXrKl7y2pA2lok4lD6CvtR', NULL, NULL, NULL, '2026-05-22 16:10:42', '2026-05-22 08:10:42'),
(14, 11, 'amor', 'xBSAZLOWuB+VNcW+ZP9LumeoMjz1oSCBYEzAwh7nI+06Ynx6Yk75lTKvPNN2pZZ+', NULL, NULL, NULL, '2026-05-22 16:10:42', '2026-05-22 08:10:42'),
(15, 13, 'amor', 'pCkovcAHbg51+BYwMuJ72hfdhEGAWV6hpwqD8vvzhAUJXqL/jzDLqvrstXXngu44', '1Yl1caMbLfq0wLCpPtg2FdAxnENl7dAZDmwkIl9tv24KefteQ86Ml2nM3E6q5BxZxBq+zRmeFqBe1MQ4W5UlBw==', NULL, NULL, '2026-05-22 16:22:17', '2026-05-22 08:22:17'),
(16, 15, 'amor', 'gBE6AMzlA7j4CVftlQdXAckLpPMdCBvVDKfhvfUK4+hT2APy/RZRO7Xp7UBOmqBA', 'BITw8P5yVMY+TNavGP0zfluSh++vM7PLFfehvVj0VCvQ4plCpHT7Acgeuega+pga6pLAzCnNU3wg2Ct8QsZOX3X8OEs78C0j7IXMAQCV8DfxoVWnl/fSq4NDrwPKmgVLIjMkFfI9aOVFsKfRCHFGVg==', NULL, NULL, '2026-05-22 16:29:16', '2026-05-22 08:29:16'),
(17, 16, 'amor', 'WtbQbiGhI+grfTUN7vX3gtP8x0rfm+o4eLmYsxKuLpkwhGv2mdQO595O53YzdYbw', '41Fb6u3YcCKWQ5lNwJrQmnxCd+j5ocPOSLDv1x79E8pLCmvCunVwmDDomay0hHTd', NULL, NULL, '2026-05-22 16:30:17', '2026-05-22 08:30:18');

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
-- Table structure for table `incident_guide_cells`
--

CREATE TABLE `incident_guide_cells` (
  `id` int(10) UNSIGNED NOT NULL,
  `row_id` int(10) UNSIGNED NOT NULL,
  `column_id` int(10) UNSIGNED NOT NULL,
  `cell_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_guide_columns`
--

CREATE TABLE `incident_guide_columns` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `col_order` tinyint(3) UNSIGNED NOT NULL,
  `column_label` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_guide_rows`
--

CREATE TABLE `incident_guide_rows` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `row_order` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_guide_sections`
--

CREATE TABLE `incident_guide_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(64) NOT NULL,
  `section_group` varchar(32) NOT NULL DEFAULT 'operations',
  `title` varchar(255) NOT NULL,
  `intro` text DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_types`
--

CREATE TABLE `incident_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(128) NOT NULL,
  `incident_type` varchar(255) NOT NULL,
  `category` enum('per_post','outside_post') NOT NULL,
  `severity` enum('High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `filing_basis` varchar(255) NOT NULL DEFAULT '',
  `filing_trigger` text NOT NULL,
  `initial_status` varchar(64) NOT NULL DEFAULT 'Ongoing',
  `response_sla` varchar(255) NOT NULL DEFAULT '',
  `responsible` varchar(128) NOT NULL DEFAULT '',
  `system_action` varchar(255) NOT NULL DEFAULT '',
  `remarks` text DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_type_detail_steps`
--

CREATE TABLE `incident_type_detail_steps` (
  `id` int(10) UNSIGNED NOT NULL,
  `incident_type_id` int(10) UNSIGNED NOT NULL,
  `step_order` tinyint(3) UNSIGNED NOT NULL,
  `step_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_type_workflow_steps`
--

CREATE TABLE `incident_type_workflow_steps` (
  `id` int(10) UNSIGNED NOT NULL,
  `incident_type_id` int(10) UNSIGNED NOT NULL,
  `step_order` tinyint(3) UNSIGNED NOT NULL,
  `step_label` varchar(255) NOT NULL
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

--
-- Dumping data for table `memos`
--

INSERT INTO `memos` (`Memo_ID`, `Company_ID`, `Distribution_Protocol`, `Category`, `Body_Text`, `created_at`) VALUES
(1, 'grey', 'broadcast', 'DIRECTIVE', 'asdfasdf', '2026-05-22 05:20:46');

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

--
-- Dumping data for table `memo_recipients`
--

INSERT INTO `memo_recipients` (`Dispatch_ID`, `Memo_ID`, `Company_ID`, `is_read`, `read_at`) VALUES
(1, 1, 'ABC-2024-0021', 0, NULL),
(2, 1, 'AMOR', 0, NULL);

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
  `Event` varchar(64) NOT NULL,
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
(27, NULL, 'ABC-2024-0001', 'SUPERADMIN:ABC-2024-0001', 'ACCOUNT_CREATED', 'Role: Head Guard', '2026-05-20 17:07:45'),
(28, 'ABC-2024-0001', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 17:07:48'),
(29, NULL, NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 17:08:02'),
(30, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 17:08:31'),
(31, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 17:37:45'),
(32, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_CREATED', 'Role: Head Guard', '2026-05-20 17:43:56'),
(33, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_DISABLED', NULL, '2026-05-20 17:44:01'),
(34, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_ENABLED', NULL, '2026-05-20 17:44:04'),
(35, 'amor', NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 17:44:37'),
(36, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 17:44:48'),
(37, 'amor', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_DISABLED', NULL, '2026-05-20 17:44:55'),
(38, NULL, NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 18:04:49'),
(39, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:04:55'),
(40, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:04:57'),
(41, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:28:43'),
(42, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:29:42'),
(43, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:30:05'),
(44, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:07'),
(45, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:30:17'),
(46, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:29'),
(47, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 18:30:34'),
(48, NULL, NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 18:30:48'),
(49, NULL, NULL, 'HEADGUARD', 'LOGIN', NULL, '2026-05-20 18:31:04'),
(50, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-20 18:31:24'),
(51, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:37:35'),
(52, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:40:22'),
(53, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:40:52'),
(54, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 18:44:41'),
(55, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:44:48'),
(56, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 18:53:26'),
(57, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 19:09:58'),
(58, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 19:10:03'),
(59, NULL, NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-20 19:11:49'),
(60, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-20 19:12:25'),
(61, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-20 19:14:20'),
(62, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-20 19:16:47'),
(63, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-21 13:58:05'),
(64, NULL, NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-21 13:58:41'),
(65, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 13:58:47'),
(66, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:36:21'),
(67, NULL, NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:37:38'),
(68, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:46:20'),
(69, NULL, NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:46:27'),
(70, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:46:38'),
(71, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:46:42'),
(72, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:55:19'),
(73, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:55:27'),
(74, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:55:36'),
(75, NULL, NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 14:55:51'),
(76, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 14:56:15'),
(77, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 14:56:26'),
(78, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 14:56:32'),
(79, NULL, NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 15:13:55'),
(80, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 15:14:32'),
(81, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 15:14:38'),
(82, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 15:18:11'),
(83, NULL, NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 15:18:36'),
(84, NULL, NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 15:30:51'),
(85, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 15:31:05'),
(86, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:04:23'),
(87, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 17:04:29'),
(88, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:07:26'),
(89, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 17:07:32'),
(90, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 17:12:06'),
(91, NULL, NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-21 17:12:23'),
(92, NULL, NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-21 18:00:40'),
(93, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:00:54'),
(94, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 18:01:10'),
(95, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:01:14'),
(96, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 18:27:30'),
(97, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 18:27:56'),
(98, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 18:28:19'),
(99, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-21 19:43:38'),
(100, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-21 19:45:53'),
(101, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-21 21:08:22'),
(102, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-21 21:08:38'),
(103, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-22 07:47:53'),
(104, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-22 07:48:52'),
(105, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-22 07:51:16'),
(106, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-22 07:51:32'),
(107, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-22 09:00:24'),
(108, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-22 09:00:34'),
(109, 'amor', NULL, 'GUARD', 'LOGOUT', NULL, '2026-05-22 09:00:49'),
(110, NULL, NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-22 09:00:53'),
(111, NULL, NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-22 09:02:46'),
(112, 'amor', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-22 09:12:03'),
(113, 'grey', 'amor', 'SUPERADMIN:amor', 'ACCOUNT_CREATED', 'Role: Administrator', '2026-05-22 09:12:45'),
(114, 'amor', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-22 09:12:47'),
(115, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-22 09:13:39'),
(116, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-22 09:15:14'),
(117, 'grey', NULL, 'ADMIN', 'LOGIN', NULL, '2026-05-22 09:15:19'),
(118, 'amor', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-22 09:15:40'),
(119, 'amor', NULL, 'SUPERADMIN', 'LOGOUT', NULL, '2026-05-22 09:15:44'),
(120, 'amor', NULL, 'GUARD', 'LOGIN', NULL, '2026-05-22 09:16:06'),
(121, 'grey', NULL, 'ADMIN', 'LOGOUT', NULL, '2026-05-22 09:53:06'),
(122, 'grey', NULL, 'SUPERADMIN', 'LOGIN', NULL, '2026-05-22 09:53:31'),
(123, 'grey', 'grey', 'SUPERADMIN:grey', 'LOGOUT', NULL, '2026-05-22 10:05:17'),
(124, 'grey', 'grey', 'ADMIN:grey', 'LOGIN', NULL, '2026-05-22 10:05:30'),
(125, 'amor', 'amor', 'GUARD:amor', 'LOGOUT', NULL, '2026-05-22 10:10:40'),
(126, 'grey', 'grey', 'SUPERADMIN:grey', 'LOGIN', NULL, '2026-05-22 10:10:58'),
(127, 'adel', 'grey', 'SUPERADMIN:grey', 'ACCOUNT_CREATED', 'Role: Administrator', '2026-05-22 10:26:54'),
(128, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0001; report text or progression saved', '2026-05-22 10:37:53'),
(129, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0001; report text or progression saved', '2026-05-22 10:41:27'),
(130, 'grey', 'grey', 'SUPERADMIN:grey', 'LOGOUT', NULL, '2026-05-22 11:24:01'),
(131, 'amor', 'amor', 'GUARD:amor', 'LOGIN', NULL, '2026-05-22 11:24:11'),
(132, 'grey', 'grey', 'ADMIN:grey', 'LOGOUT', NULL, '2026-05-22 11:24:14'),
(133, 'grey', 'grey', 'ADMIN:grey', 'LOGIN', NULL, '2026-05-22 11:24:23'),
(134, 'amor', 'amor', 'GUARD:amor', 'DAD_SUBMITTED', 'Reference: DAD-2026-0002', '2026-05-22 11:25:14'),
(135, 'grey', 'grey', 'ADMIN:grey', 'MEMO_SENT', 'Broadcast to 2 head guard(s); category: DIRECTIVE', '2026-05-22 13:20:46'),
(136, 'amor', 'amor', 'GUARD:amor', 'LOGOUT', NULL, '2026-05-22 13:58:52'),
(137, 'grey', 'grey', 'ADMIN:grey', 'LOGIN', NULL, '2026-05-22 13:59:06'),
(138, 'grey', 'grey', 'ADMIN:grey', 'LOGOUT', NULL, '2026-05-22 13:59:18'),
(139, 'amor', 'amor', 'GUARD:amor', 'LOGIN', NULL, '2026-05-22 13:59:24'),
(140, 'amor', 'grey', 'ADMIN:grey', 'GUARDS_ASSIGNED', 'Assigned 3 guard(s)', '2026-05-22 14:19:52'),
(141, 'grey', 'grey', 'ADMIN:grey', 'LOGOUT', NULL, '2026-05-22 14:32:36'),
(142, 'grey', 'grey', 'ADMIN:grey', 'LOGIN', NULL, '2026-05-22 14:32:41'),
(143, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0001; report text or progression saved', '2026-05-22 15:03:37'),
(144, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0001; report text or progression saved', '2026-05-22 15:16:35'),
(145, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0001; report text or progression saved', '2026-05-22 15:16:44'),
(146, 'amor', 'grey', 'ADMIN:grey', 'POST_ASSIGNED', 'Assigned to Sta. Ana, Manila', '2026-05-22 16:10:01'),
(147, 'amor', 'amor', 'GUARD:amor', 'INCIDENT_SUBMITTED', 'Reference: INC-2026-0002', '2026-05-22 16:27:02'),
(148, 'amor', 'grey', 'ADMIN:grey', 'INCIDENT_UPDATED', 'Reference: INC-2026-0002; report text or progression saved', '2026-05-22 16:27:33'),
(149, 'amor', 'amor', 'GUARD:amor', 'DTR_SUBMITTED', 'Reference: DTR-2026-0002', '2026-05-22 16:29:16'),
(150, 'amor', 'amor', 'GUARD:amor', 'INCIDENT_SUBMITTED', 'Reference: INC-2026-0003', '2026-05-22 16:30:18');

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
(26, 'php/019_guard_incident_submissions.php', 10, '2026-05-21 15:19:46'),
(27, 'php/020_recording_event_length.php', 11, '2026-05-22 01:57:34'),
(28, '020_incident_guide_reference.sql', 12, '2026-05-22 06:17:42'),
(29, '022_guard_daily_activity_submissions.sql', 12, '2026-05-22 06:17:42'),
(30, '023_seed_field_guard_roster.sql', 12, '2026-05-22 06:17:42'),
(31, '025_callout_posts_manila.sql', 13, '2026-05-22 07:55:58'),
(32, '026_remove_guard_announcement_seed.sql', 13, '2026-05-22 07:55:58'),
(33, '027_guard_daily_activity_status.sql', 14, '2026-05-22 07:58:29');

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
('ABC-2026-0301', 'abc20260301@roster.local', 'Paulo', 'Garcia', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0302', 'abc20260302@roster.local', 'Janelle', 'Flores', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0303', 'abc20260303@roster.local', 'Christian', 'Navarro', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0304', 'abc20260304@roster.local', 'Samantha', 'Torres', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0305', 'abc20260305@roster.local', 'Vincent', 'Bautista', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0306', 'abc20260306@roster.local', 'Patricia', 'Castillo', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0307', 'abc20260307@roster.local', 'John', 'Herrera', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0308', 'abc20260308@roster.local', 'Nicole', 'Fernandez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0309', 'abc20260309@roster.local', 'Rafael', 'Aquino', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0310', 'abc20260310@roster.local', 'Bea', 'Salazar', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0311', 'abc20260311@roster.local', 'Adrian', 'Lim', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0312', 'abc20260312@roster.local', 'Mark', 'Ramirez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0313', 'abc20260313@roster.local', 'Mikaela', 'Gutierrez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0314', 'abc20260314@roster.local', 'Kevin', 'Diaz', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0315', 'abc20260315@roster.local', 'Alyssa', 'Rivera', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0316', 'abc20260316@roster.local', 'Francis', 'Morales', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0317', 'abc20260317@roster.local', 'Katrina', 'Santiago', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0318', 'abc20260318@roster.local', 'Elijah', 'Cruz', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0319', 'abc20260319@roster.local', 'Camille', 'Lopez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0320', 'abc20260320@roster.local', 'Nathaniel', 'Romero', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0321', 'abc20260321@roster.local', 'Bianca', 'Valdez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0322', 'abc20260322@roster.local', 'Angelo', 'Perez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0323', 'abc20260323@roster.local', 'Chelsea', 'Velasco', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0324', 'abc20260324@roster.local', 'Carla', 'Mendoza', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0325', 'abc20260325@roster.local', 'Gabriel', 'Chavez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0326', 'abc20260326@roster.local', 'Danielle', 'Manalo', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0327', 'abc20260327@roster.local', 'Joshua', 'Mercado', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0328', 'abc20260328@roster.local', 'Trisha', 'Evangelista', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0329', 'abc20260329@roster.local', 'Carl', 'Ramos', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0330', 'abc20260330@roster.local', 'Princess', 'Cabrera', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0331', 'abc20260331@roster.local', 'Ivan', 'Dominguez', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0332', 'abc20260332@roster.local', 'Elaine', 'Soriano', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0333', 'abc20260333@roster.local', 'Kurt', 'Mendoza', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0334', 'abc20260334@roster.local', 'Hazel', 'Alonzo', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('ABC-2026-0335', 'abc20260335@roster.local', 'Nathan', 'Pascual', '$2y$10$QGkZW/Q.7x2WNgFpCptYqOUFxsJl9EY0/nBkwdfUnRkaAc0SQCEru', NULL, 1, 0, 0, NULL, NULL, '2026-05-22 14:19:34', '2026-05-22 06:19:34', '2026-05-22 06:19:34'),
('adel', 'macabontoc.adel@gmail.com', 'Adel', 'Macabontoc', '$2y$10$8ly26t6s7lPZHMg6XuVmnuNYROuZEmi3lLElLJxfnUWhmqRcgaGp6', NULL, 1, 1, 0, NULL, NULL, NULL, '2026-05-22 02:26:50', '2026-05-22 02:26:50'),
('amor', 'christian5787264@gmail.com', NULL, NULL, '$2y$10$KSOWzWbMYeNhGZDR83.bwetV6vKTRE9cx3hSMSCeoonmLWWqx4c0a', NULL, 0, 1, 0, NULL, '2026-05-22 13:59:24', '2026-05-20 17:44:46', '2026-05-20 09:43:53', '2026-05-22 05:59:24'),
('grey', 'aldrininocencio212527@gmail.com', 'Aldrin', 'Inocencio', '$2y$10$smWW47z0Wwo9oZ3bPFyGyeiEK.IV6L87Mv7CEjldW2WcF/Zo48NgG', NULL, 1, 1, 0, NULL, '2026-05-22 14:32:41', '2026-05-22 09:14:27', '2026-05-22 01:12:41', '2026-05-22 06:32:41');

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
-- Indexes for table `guard_daily_activity_submissions`
--
ALTER TABLE `guard_daily_activity_submissions`
  ADD PRIMARY KEY (`da_id`),
  ADD UNIQUE KEY `uk_guard_da_reference` (`reference_code`),
  ADD KEY `idx_guard_da_mode` (`activity_mode`),
  ADD KEY `idx_guard_da_head` (`head_guard_company_id`),
  ADD KEY `idx_guard_da_dgd` (`dgd_report_number`),
  ADD KEY `idx_guard_da_status` (`status`);

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
-- Indexes for table `incident_guide_cells`
--
ALTER TABLE `incident_guide_cells`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_guide_cells` (`row_id`,`column_id`),
  ADD KEY `fk_incident_guide_cells_column` (`column_id`);

--
-- Indexes for table `incident_guide_columns`
--
ALTER TABLE `incident_guide_columns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_guide_columns_order` (`section_id`,`col_order`);

--
-- Indexes for table `incident_guide_rows`
--
ALTER TABLE `incident_guide_rows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_guide_rows_order` (`section_id`,`row_order`);

--
-- Indexes for table `incident_guide_sections`
--
ALTER TABLE `incident_guide_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_guide_sections_slug` (`slug`),
  ADD KEY `idx_incident_guide_sections_group` (`section_group`,`sort_order`);

--
-- Indexes for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_types_slug` (`slug`),
  ADD KEY `idx_incident_types_category` (`category`),
  ADD KEY `idx_incident_types_active_sort` (`is_active`,`sort_order`);

--
-- Indexes for table `incident_type_detail_steps`
--
ALTER TABLE `incident_type_detail_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_type_detail_step` (`incident_type_id`,`step_order`);

--
-- Indexes for table `incident_type_workflow_steps`
--
ALTER TABLE `incident_type_workflow_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incident_type_workflow_step` (`incident_type_id`,`step_order`);

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
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `callout_post_assignments`
--
ALTER TABLE `callout_post_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dgd`
--
ALTER TABLE `dgd`
  MODIFY `Report_Number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  MODIFY `dad_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `guard_daily_activity_submissions`
--
ALTER TABLE `guard_daily_activity_submissions`
  MODIFY `da_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `guard_incident_submissions`
--
ALTER TABLE `guard_incident_submissions`
  MODIFY `inc_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `guard_report_evidence`
--
ALTER TABLE `guard_report_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `guard_staff_messages`
--
ALTER TABLE `guard_staff_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_guide_cells`
--
ALTER TABLE `incident_guide_cells`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_guide_columns`
--
ALTER TABLE `incident_guide_columns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_guide_rows`
--
ALTER TABLE `incident_guide_rows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_guide_sections`
--
ALTER TABLE `incident_guide_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_types`
--
ALTER TABLE `incident_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_type_detail_steps`
--
ALTER TABLE `incident_type_detail_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_type_workflow_steps`
--
ALTER TABLE `incident_type_workflow_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_messages`
--
ALTER TABLE `internal_messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `memos`
--
ALTER TABLE `memos`
  MODIFY `Memo_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `memo_recipients`
--
ALTER TABLE `memo_recipients`
  MODIFY `Dispatch_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- Constraints for table `guard_daily_activity_submissions`
--
ALTER TABLE `guard_daily_activity_submissions`
  ADD CONSTRAINT `fk_guard_da_dgd` FOREIGN KEY (`dgd_report_number`) REFERENCES `dgd` (`Report_Number`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_guard_da_head_user` FOREIGN KEY (`head_guard_company_id`) REFERENCES `users` (`Company_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `incident_guide_cells`
--
ALTER TABLE `incident_guide_cells`
  ADD CONSTRAINT `fk_incident_guide_cells_column` FOREIGN KEY (`column_id`) REFERENCES `incident_guide_columns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_incident_guide_cells_row` FOREIGN KEY (`row_id`) REFERENCES `incident_guide_rows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_guide_columns`
--
ALTER TABLE `incident_guide_columns`
  ADD CONSTRAINT `fk_incident_guide_columns_section` FOREIGN KEY (`section_id`) REFERENCES `incident_guide_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_guide_rows`
--
ALTER TABLE `incident_guide_rows`
  ADD CONSTRAINT `fk_incident_guide_rows_section` FOREIGN KEY (`section_id`) REFERENCES `incident_guide_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_type_detail_steps`
--
ALTER TABLE `incident_type_detail_steps`
  ADD CONSTRAINT `fk_incident_type_detail_steps_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_type_workflow_steps`
--
ALTER TABLE `incident_type_workflow_steps`
  ADD CONSTRAINT `fk_incident_type_workflow_steps_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`) ON DELETE CASCADE;

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
