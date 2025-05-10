-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2025 at 01:47 PM
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
-- Database: `looma`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(11) NOT NULL,
  `achievement_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `points_required` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_rewards`
--

CREATE TABLE `game_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_type` varchar(50) NOT NULL,
  `reward` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_rewards`
--

INSERT INTO `game_rewards` (`id`, `user_id`, `game_type`, `reward`, `created_at`) VALUES
(1, 4, 'memory_match', 17.65, '2025-04-22 07:55:10'),
(2, 4, 'memory_match', 17.65, '2025-04-22 07:56:10'),
(3, 4, 'memory_match', 17.65, '2025-04-22 07:56:43'),
(4, 4, 'memory_match', 25.00, '2025-04-22 14:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `points`
--

CREATE TABLE `points` (
  `point_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `points`
--

INSERT INTO `points` (`point_id`, `user_id`, `points`) VALUES
(1, 4, 16),
(2, 4, 22),
(3, 4, 30),
(21, 4, 5),
(22, 4, 1),
(23, 4, 1),
(24, 4, 1),
(25, 4, 2),
(26, 4, 20),
(27, 4, 20),
(28, 4, 40),
(29, 4, 0),
(30, 4, 40),
(31, 4, 50),
(32, 4, 40),
(33, 4, 5),
(34, 4, 60),
(35, 4, 60),
(36, 4, 60),
(37, 4, 5);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `referrer_id` int(11) DEFAULT NULL,
  `referred_id` int(11) DEFAULT NULL,
  `bonus_paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scramble_rewards`
--

CREATE TABLE `scramble_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spins`
--

CREATE TABLE `spins` (
  `spin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `spin_type` enum('registration','weekly','bet') NOT NULL,
  `stake` decimal(10,2) DEFAULT 0.00,
  `win_amount` decimal(10,2) DEFAULT 0.00,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `outcome_label` varchar(100) DEFAULT NULL,
  `spin_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `wheel_config_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  `reward_source` enum('normal','referral','promo','loyalty') DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spins`
--

INSERT INTO `spins` (`spin_id`, `user_id`, `spin_type`, `stake`, `win_amount`, `played_at`, `outcome_label`, `spin_status`, `wheel_config_id`, `ip_address`, `device_info`, `reward_source`) VALUES
(1, 4, 'registration', 0.00, 221.00, '2025-04-19 18:55:35', NULL, 'confirmed', NULL, '::1', NULL, ''),
(2, 4, 'weekly', 0.00, 300.00, '2025-04-19 19:07:17', NULL, 'confirmed', NULL, '::1', NULL, 'loyalty'),
(3, 4, 'weekly', 100.00, 200.00, '2025-04-30 08:57:45', NULL, 'confirmed', NULL, '::1', NULL, 'loyalty'),
(4, 4, 'weekly', 100.00, 200.00, '2025-04-30 08:58:06', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(5, 4, 'bet', 100.00, 400.00, '2025-04-30 12:48:10', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(6, 4, 'bet', 100.00, 0.00, '2025-04-30 12:48:27', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(7, 4, 'bet', 100.00, 400.00, '2025-04-30 12:50:22', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(8, 4, 'bet', 1000.00, 500.00, '2025-04-30 12:54:36', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(9, 4, 'bet', 100.00, 400.00, '2025-04-30 12:55:38', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(10, 4, 'bet', 100.00, 50.00, '2025-05-01 16:53:57', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(11, 4, 'bet', 100.00, 600.00, '2025-05-01 17:14:22', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(12, 4, 'bet', 100.00, 600.00, '2025-05-01 17:19:01', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(13, 4, 'bet', 100.00, 600.00, '2025-05-01 17:24:03', NULL, 'confirmed', NULL, '::1', NULL, 'normal'),
(14, 4, 'bet', 102.00, 51.00, '2025-05-01 17:24:27', NULL, 'confirmed', NULL, '::1', NULL, 'normal');

-- --------------------------------------------------------

--
-- Stand-in structure for view `spin_history`
-- (See below for the actual view)
--
CREATE TABLE `spin_history` (
`spin_id` int(11)
,`username` varchar(50)
,`spin_type` enum('registration','weekly','bet')
,`stake` decimal(10,2)
,`win_amount` decimal(10,2)
,`outcome_label` varchar(100)
,`reward_source` enum('normal','referral','promo','loyalty')
,`spin_status` enum('pending','confirmed','cancelled')
,`played_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `spin_rewards`
--

CREATE TABLE `spin_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spin_rewards`
--

INSERT INTO `spin_rewards` (`id`, `user_id`, `reward`, `created_at`) VALUES
(1, 4, 5, '2025-04-19 18:21:27'),
(2, 4, 100, '2025-04-19 18:23:29'),
(3, 4, 100, '2025-04-19 18:25:45'),
(4, 4, 50, '2025-04-19 18:25:56'),
(5, 4, 20, '2025-04-19 18:26:48'),
(6, 4, 221, '2025-04-19 18:55:35'),
(7, 4, 300, '2025-04-19 19:07:17'),
(8, 4, 200, '2025-04-30 08:57:45'),
(9, 4, 200, '2025-04-30 08:58:06'),
(10, 4, 400, '2025-04-30 12:48:10'),
(11, 4, 0, '2025-04-30 12:48:27'),
(12, 4, 400, '2025-04-30 12:50:22'),
(13, 4, 500, '2025-04-30 12:54:37'),
(14, 4, 400, '2025-04-30 12:55:38'),
(15, 4, 50, '2025-05-01 16:53:58'),
(16, 4, 600, '2025-05-01 17:14:22'),
(17, 4, 600, '2025-05-01 17:19:01'),
(18, 4, 600, '2025-05-01 17:24:03'),
(19, 4, 51, '2025-05-01 17:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `phone_number`, `transaction_id`, `status`, `created_at`) VALUES
(1, 4, 'withdrawal', 200.00, '+25476858125', '6815dbba2ef9d', 'pending', '2025-05-03 09:02:54'),
(2, 4, 'withdrawal', 200.00, '+25476858125', '6815e77034baa', 'pending', '2025-05-03 09:52:50'),
(3, 4, 'withdrawal', 1000.00, '+25476858125', '6815e7c42e54c', 'pending', '2025-05-03 09:54:13'),
(4, 4, 'withdrawal', 400.00, '+25476858125', '6815e9e22ea6b', 'pending', '2025-05-03 10:03:14'),
(5, 4, 'withdrawal', 350.00, '+25476858125', '6815f0995a936', 'pending', '2025-05-03 10:31:54'),
(6, 4, 'withdrawal', 250.00, '+25476858125', '6815fff2657ed', 'pending', '2025-05-03 11:37:24'),
(7, 4, 'withdrawal', 200.00, '+25476858125', '6816066dcf3fb', 'pending', '2025-05-03 12:05:06'),
(8, 4, 'withdrawal', 200.00, '+25476858125', '681879316bb7c', 'pending', '2025-05-05 08:39:18');

-- --------------------------------------------------------

--
-- Table structure for table `trivia_questions`
--

CREATE TABLE `trivia_questions` (
  `question_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `correct_answer` varchar(255) NOT NULL,
  `wrong_answers` text NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_activated` tinyint(1) DEFAULT 0,
  `referral_code` varchar(10) DEFAULT NULL,
  `referred_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `phone`, `email`, `password`, `is_verified`, `is_activated`, `referral_code`, `referred_by`, `created_at`) VALUES
(1, 'Evans Osumba', 'evans', '+254707868194', 'osumbaevans21@gmail.com', '$2y$10$db4jspVinA.ZgV1zuruAQejMYn9hcZZJ7El.ye1/YP0PAAqJTShRu', 1, 0, 'RZ7FDWOQ', NULL, '2025-04-17 00:01:59'),
(4, 'Collins Odhiambo Otieno', 'collins', '+254768581254', 'otienocollins0549@gmail.com', '$2y$10$7/GNd7L8xBOa2Z5G/kmR6OpFnNIfw0JCB.VqHmiCm5/dKzxDAfcvi', 1, 0, 'L1MOX7IP', NULL, '2025-04-15 08:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `user_achievements`
--

CREATE TABLE `user_achievements` (
  `user_achievement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `achieved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_game_history`
--

CREATE TABLE `user_game_history` (
  `game_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `game_type` enum('trivia','survey','spin') NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_game_history`
--

INSERT INTO `user_game_history` (`game_id`, `user_id`, `game_type`, `points_earned`, `played_at`) VALUES
(1, 4, 'spin', 16, '2025-04-18 19:51:50'),
(2, 4, 'spin', 22, '2025-04-19 18:55:35'),
(3, 4, 'spin', 30, '2025-04-19 19:07:17'),
(4, 4, '', 5, '2025-04-22 07:43:12'),
(5, 4, '', 2, '2025-04-22 07:55:10'),
(6, 4, '', 2, '2025-04-22 07:56:10'),
(7, 4, '', 2, '2025-04-22 07:56:43'),
(8, 4, '', 2, '2025-04-22 14:02:03'),
(9, 4, 'spin', 20, '2025-04-30 08:57:44'),
(10, 4, 'spin', 20, '2025-04-30 08:58:06'),
(11, 4, 'spin', 40, '2025-04-30 12:48:09'),
(12, 4, 'spin', 0, '2025-04-30 12:48:27'),
(13, 4, 'spin', 40, '2025-04-30 12:50:22'),
(14, 4, 'spin', 50, '2025-04-30 12:54:36'),
(15, 4, 'spin', 40, '2025-04-30 12:55:38'),
(16, 4, 'spin', 5, '2025-05-01 16:53:56'),
(17, 4, 'spin', 60, '2025-05-01 17:14:21'),
(18, 4, 'spin', 60, '2025-05-01 17:19:01'),
(19, 4, 'spin', 60, '2025-05-01 17:24:03'),
(20, 4, 'spin', 5, '2025-05-01 17:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `wallet`
--

CREATE TABLE `wallet` (
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `last_interact` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet`
--

INSERT INTO `wallet` (`wallet_id`, `user_id`, `balance`, `last_interact`) VALUES
(1, 1, 7660.00, '2025-04-20 10:26:31'),
(4, 4, 2908.95, '2025-05-01 17:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `wheel_configs`
--

CREATE TABLE `wheel_configs` (
  `config_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `word_scramble_anagrams`
--

CREATE TABLE `word_scramble_anagrams` (
  `id` int(11) NOT NULL,
  `letters` varchar(50) NOT NULL,
  `possible_words` text NOT NULL,
  `clue` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `word_scramble_anagrams`
--

INSERT INTO `word_scramble_anagrams` (`id`, `letters`, `possible_words`, `clue`) VALUES
(1, 'STOP', 'POST,SPOT,TOPS', 'Words related to stopping'),
(2, 'TEAM', 'MEAT,MATE,TAME', 'Words related to groups or food'),
(3, 'RATS', 'STAR,TARS,ARTS', 'Words related to the sky or creativity');

-- --------------------------------------------------------

--
-- Table structure for table `word_scramble_multiple`
--

CREATE TABLE `word_scramble_multiple` (
  `id` int(11) NOT NULL,
  `scrambled_letters` varchar(50) NOT NULL,
  `correct_words` text NOT NULL,
  `clue` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `word_scramble_multiple`
--

INSERT INTO `word_scramble_multiple` (`id`, `scrambled_letters`, `correct_words`, `clue`) VALUES
(1, 'KBOO', 'BOOK', 'Something you read'),
(2, 'TSEA', 'EAST, TEA', 'A direction and a drink'),
(3, 'OLKO', 'LOOK', 'To see something');

-- --------------------------------------------------------

--
-- Table structure for table `word_scramble_single`
--

CREATE TABLE `word_scramble_single` (
  `id` int(11) NOT NULL,
  `scrambled_word` varchar(50) NOT NULL,
  `correct_word` varchar(50) NOT NULL,
  `clue` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `word_scramble_single`
--

INSERT INTO `word_scramble_single` (`id`, `scrambled_word`, `correct_word`, `clue`) VALUES
(1, 'OLHE', 'HELLO', 'A greeting'),
(2, 'ELPPA', 'APPLE', 'A type of fruit'),
(3, 'TCAE', 'CAT', 'A common pet');

-- --------------------------------------------------------

--
-- Structure for view `spin_history`
--
DROP TABLE IF EXISTS `spin_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `spin_history`  AS SELECT `s`.`spin_id` AS `spin_id`, `u`.`username` AS `username`, `s`.`spin_type` AS `spin_type`, `s`.`stake` AS `stake`, `s`.`win_amount` AS `win_amount`, `s`.`outcome_label` AS `outcome_label`, `s`.`reward_source` AS `reward_source`, `s`.`spin_status` AS `spin_status`, `s`.`played_at` AS `played_at` FROM (`spins` `s` join `users` `u` on(`s`.`user_id` = `u`.`user_id`)) ORDER BY `s`.`played_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`);

--
-- Indexes for table `game_rewards`
--
ALTER TABLE `game_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`point_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Indexes for table `scramble_rewards`
--
ALTER TABLE `scramble_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `spins`
--
ALTER TABLE `spins`
  ADD PRIMARY KEY (`spin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `spin_rewards`
--
ALTER TABLE `spin_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trivia_questions`
--
ALTER TABLE `trivia_questions`
  ADD PRIMARY KEY (`question_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `referral_code` (`referral_code`);

--
-- Indexes for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`user_achievement_id`),
  ADD UNIQUE KEY `user_achievement_unique` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`);

--
-- Indexes for table `user_game_history`
--
ALTER TABLE `user_game_history`
  ADD PRIMARY KEY (`game_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet`
--
ALTER TABLE `wallet`
  ADD PRIMARY KEY (`wallet_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wheel_configs`
--
ALTER TABLE `wheel_configs`
  ADD PRIMARY KEY (`config_id`);

--
-- Indexes for table `word_scramble_anagrams`
--
ALTER TABLE `word_scramble_anagrams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `word_scramble_multiple`
--
ALTER TABLE `word_scramble_multiple`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `word_scramble_single`
--
ALTER TABLE `word_scramble_single`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_rewards`
--
ALTER TABLE `game_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `points`
--
ALTER TABLE `points`
  MODIFY `point_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scramble_rewards`
--
ALTER TABLE `scramble_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spins`
--
ALTER TABLE `spins`
  MODIFY `spin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `spin_rewards`
--
ALTER TABLE `spin_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `trivia_questions`
--
ALTER TABLE `trivia_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_game_history`
--
ALTER TABLE `user_game_history`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `wallet`
--
ALTER TABLE `wallet`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wheel_configs`
--
ALTER TABLE `wheel_configs`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `game_rewards`
--
ALTER TABLE `game_rewards`
  ADD CONSTRAINT `game_rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `points`
--
ALTER TABLE `points`
  ADD CONSTRAINT `points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `spins`
--
ALTER TABLE `spins`
  ADD CONSTRAINT `spins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `spin_rewards`
--
ALTER TABLE `spin_rewards`
  ADD CONSTRAINT `spin_rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_game_history`
--
ALTER TABLE `user_game_history`
  ADD CONSTRAINT `user_game_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `wallet`
--
ALTER TABLE `wallet`
  ADD CONSTRAINT `wallet_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
