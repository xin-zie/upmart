-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 09:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `upmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `bulletin_posts`
--

CREATE TABLE `bulletin_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulletin_posts`
--

INSERT INTO `bulletin_posts` (`id`, `user_id`, `message`, `created_at`) VALUES
(1, 10, 'sigeee na oh', '2026-04-23 15:09:58'),
(2, 10, 'lf feetpixs', '2026-04-23 15:10:30'),
(3, 18, 'lf manila plane ticket', '2026-04-23 15:16:32'),
(4, 21, 'lf kasama sa lake cebu', '2026-04-23 15:19:04'),
(5, 10, 'hanayko', '2026-04-23 15:19:34'),
(6, 21, 'its 3:00 am', '2026-04-24 19:20:01'),
(7, 21, 'juukggk', '2026-04-24 19:25:24'),
(8, 21, 'good evening', '2026-04-28 15:59:25'),
(9, 10, 'good morning', '2026-04-29 00:12:27'),
(10, 10, 'friday na tom', '2026-04-30 06:04:44'),
(11, 15, 'rgrgrgrh', '2026-04-30 06:13:10'),
(12, 15, 'wdwfwf', '2026-05-05 11:43:05'),
(13, 10, 'hi', '2026-05-05 13:55:59'),
(14, 10, 'br', '2026-05-06 04:38:09'),
(15, 10, 'haynakohs', '2026-05-07 02:42:49');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Dorm Essentials'),
(2, 'Arki Mats'),
(3, 'Lab Essentials'),
(4, 'Others'),
(5, 'Books'),
(6, 'Services'),
(7, 'Foods'),
(8, 'School Supplies'),
(9, 'Art Materials'),
(10, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_path` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`image_id`, `product_id`, `image_path`) VALUES
(1, 5, 'uploads/1777461751_Screenshot 2025-11-09 163552.png'),
(3, 7, 'uploads/1777529032_Screenshot 2025-11-13 131756.png'),
(4, 8, 'uploads/1777576996_Screenshot 2025-11-10 145628.png'),
(5, 11, 'uploads/1777646984_Screenshot 2025-11-22 213353.png'),
(6, 12, 'uploads/1777673498_Screenshot 2025-12-05 235708.png'),
(7, 13, 'uploads/1777675022_Screenshot 2025-11-14 220551.png'),
(8, 14, 'uploads/1777738582_f6f797f6-d095-45fa-86f0-a3aaf1953364.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `product_id`, `sender_id`, `receiver_id`, `message_text`, `created_at`) VALUES
(3, 5, 6, 10, 'Hey Natasha! Is this available, i wanted to buy this.', '2026-04-30 20:44:01'),
(4, 5, 6, 10, 'i hope you message me soon.', '2026-04-30 20:54:15'),
(5, 5, 10, 6, 'hello glen, yes it available. What time are you free to pick it up?', '2026-04-30 21:01:49'),
(6, 8, 15, 6, 'MINE', '2026-04-30 21:28:27'),
(7, 7, 10, 15, 'mineee', '2026-04-30 21:46:25'),
(8, 7, 10, 15, '1pc', '2026-04-30 21:46:59'),
(9, 5, 15, 10, 'mine 2pc. kalimudan', '2026-04-30 21:47:37'),
(10, 7, 6, 15, 'i will pay gcash', '2026-04-30 21:57:03'),
(11, 5, 10, 15, 'ok, cash or online payment po?', '2026-04-30 22:16:12'),
(12, 5, 15, 10, 'cash', '2026-04-30 22:17:34'),
(13, 5, 10, 15, 'ok noted! tomorrow 1pm.', '2026-04-30 22:18:13'),
(14, 5, 15, 10, 'okay thanks', '2026-04-30 22:18:42'),
(15, 17, 10, 6, 'mine, gcash po', '2026-05-06 03:33:18'),
(16, 17, 10, 6, 'color pink', '2026-05-06 03:37:23'),
(17, 17, 10, 6, 'hi', '2026-05-06 04:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notif_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notif_id`, `user_id`, `sender_id`, `message`, `notif_type`, `target_id`, `is_read`, `created_at`) VALUES
(95, 15, 10, '<b>Natasha Christine</b> has the item: \'wdwd\'!', NULL, NULL, 0, '2026-05-07 02:50:51'),
(96, 15, 10, '<b>Natasha Christine</b> has the item: \'wdwd\'!', NULL, NULL, 0, '2026-05-07 02:54:17'),
(97, 15, 10, '<b>Natasha Christine</b> has the item: \'wdwd\'!', 'wish_match', 4, 0, '2026-05-07 03:02:01'),
(98, 15, 10, '<b>Natasha Christine</b> has the item: \'wdwd\'!', 'wish_match', 4, 0, '2026-05-07 03:02:25'),
(99, 15, 6, '<b>Glendy</b> has the item: \'wdwd\'!', 'wish_match', 4, 0, '2026-05-07 03:20:08'),
(100, 10, 6, 'Glendy has the \'TIRAMIUSSIEE\' you\'re looking for!', NULL, NULL, 0, '2026-05-07 03:30:56');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `product_id`, `buyer_id`, `seller_id`, `status`, `created_at`) VALUES
(1, 8, 10, 6, 'Pending', '2026-05-01 08:19:54'),
(2, 7, 10, 15, 'Pending', '2026-05-01 09:27:39'),
(3, 13, 10, 6, 'Pending', '2026-05-03 09:50:03'),
(4, 14, 10, 15, 'Pending', '2026-05-03 09:53:48'),
(5, 14, 6, 15, 'Pending', '2026-05-05 11:45:25'),
(6, 13, 6, 6, 'Pending', '2026-05-05 13:05:01'),
(7, 12, 6, 10, 'Completed', '2026-05-05 13:05:16'),
(8, 17, 10, 6, 'Pending', '2026-05-05 13:38:00'),
(9, 0, 15, 0, 'Pending', '2026-05-05 13:42:15'),
(10, 14, 10, 15, 'Pending', '2026-05-05 13:52:14'),
(11, 15, 10, 10, 'Completed', '2026-05-06 12:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `up_email` varchar(100) NOT NULL,
  `status` enum('Pending','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `item_condition` enum('New','Used') NOT NULL,
  `status` enum('Available','Sold','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_status` enum('Pending','Approved','Denied') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `category_id`, `title`, `description`, `price`, `item_condition`, `status`, `created_at`, `approval_status`) VALUES
(5, 10, 4, 'feet pix', 'feet pix ni realize', 5000.00, 'New', 'Available', '2026-05-01 08:19:32', 'Approved'),
(7, 15, 3, 'DIGI PIXS', 'hahahasola', 10000.00, 'New', 'Available', '2026-05-01 08:19:32', 'Approved'),
(8, 6, 2, 'SS', 'ss cheat ticket for lany concert', 100.00, 'New', 'Available', '2026-05-01 08:19:32', 'Approved'),
(10, 10, 1, 'huh', 'ermm', 5666.00, 'New', 'Available', '2026-05-01 14:33:47', 'Approved'),
(11, 6, 1, 'Mirror', 'color red, used, from miniso', 75.00, 'New', 'Available', '2026-05-01 14:50:31', 'Approved'),
(12, 10, 4, 'cup noodles ', 'fresh and authentic from thailand', 95.00, 'New', 'Sold', '2026-05-06 12:23:35', 'Approved'),
(13, 6, 1, 'trial sa post', ' rrwr', 408.00, 'New', 'Available', '2026-05-02 15:26:42', 'Approved'),
(14, 15, 1, 'kapoiooy', 'katugon nko', 500.00, 'New', 'Available', '2026-05-02 16:18:53', 'Approved'),
(15, 10, 1, 'buldaki', 'hbu ayayayayay baho ka og bialt', 140.00, 'New', 'Sold', '2026-05-06 12:24:07', 'Approved'),
(16, 15, 1, 'Tissue', '10 pcs.', 50.00, 'New', 'Available', '2026-05-05 09:58:49', 'Pending'),
(17, 6, 1, 'pillow', 'red, fluffy, etc', 89.00, 'New', 'Available', '2026-05-05 11:51:23', 'Approved'),
(19, 6, 9, 'tumbler', 'nkenenfn', 67.00, 'New', 'Available', '2026-05-06 03:02:39', 'Approved'),
(20, 21, 9, 'efwfwf', 'rydtrr', 85.00, 'New', 'Available', '2026-05-06 02:38:08', 'Pending'),
(21, 21, 9, 'efwfwf', 'rydtrr', 85.00, 'New', 'Available', '2026-05-06 02:42:39', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reason` varchar(50) NOT NULL,
  `details` text NOT NULL,
  `status` enum('Pending','Reviewed','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `user_id`, `reported_user_id`, `reason`, `details`, `status`, `created_at`) VALUES
(1, 13, NULL, 'scam', 'dwqfefef', 'Pending', '2026-04-28 15:43:52');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `inquiry_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `message_link_clicked` tinyint(1) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `up_email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_code` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `is_setup_complete` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `up_email`, `password`, `phone_number`, `social_link`, `bio`, `created_at`, `reset_code`, `reset_expires`, `profile_pic`, `role`, `is_setup_complete`) VALUES
(4, 'Fiona Divine', 'Divine@up.edu.ph', '$2y$10$Y/KfN8/.YxRrDEhVdyvJROjWh2hE2QQ4B2ly0egTf9kCRHq.k79wS', NULL, NULL, NULL, '2026-04-21 16:52:59', NULL, NULL, NULL, 'student', 0),
(6, 'Glendy', 'Glen@up.edu.ph', '$2y$10$Majwwx3EkRW9eMpdzHDASundpEyzPZBBpZAO1IZzlW7f0GiIvsdDq', '09165482753', NULL, 'soanotara', '2026-04-21 17:15:20', NULL, NULL, 'user_6_1777981504.png', 'student', 1),
(10, 'Natasha Christine', 'Arroyo@up.edu.ph', '$2y$10$nxVoe11g8EwYyOhF785pC.FlNHwFK1BxvqZdJIAy9fbTiB0DIWPCC', NULL, NULL, NULL, '2026-04-21 17:27:56', NULL, NULL, NULL, 'student', 1),
(13, 'Princess Diane', 'Mahusay@up.edu.ph', '$2y$10$fg2kpt7VkqSAT5nM9/sG0.kAa0uuclr7teLkDXGr1keiMkmHw4/gq', NULL, NULL, NULL, '2026-04-21 17:37:11', NULL, NULL, NULL, 'student', 0),
(15, 'Rhea Catacutan', 'Rhea@up.edu.ph', '$2y$10$Ws3ivVjtUGINxvzV2ER6zeb6JRIMBkOR64yu/.s0xeIfhfeKjPcKi', '092563847526', NULL, 'biokoto', '2026-04-22 07:58:42', NULL, NULL, 'profile_default.jpg', 'student', 1),
(18, 'Diane', 'pcmahusay@up.edu.ph', '$2y$10$6cv2kg2s0owL/zhDbil0yen4kFDfMsNgZSR8/vft4L/nGdG8yAoF2', NULL, NULL, NULL, '2026-04-22 13:12:15', NULL, NULL, NULL, 'student', 0),
(21, 'Sybil Micarandayo', 'scmicarandayo@up.edu.ph', '$2y$10$Jv4lpm4ZWgqo6sky69tdKOM7OXHrusPfDMLgjMcP4/QLfFRtNr3Qm', NULL, NULL, NULL, '2026-04-23 04:49:46', NULL, NULL, NULL, 'admin', 0);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wish_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `category` enum('Books','Dorm','Food','Electronics','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wish_id`, `user_id`, `item_name`, `category`, `created_at`) VALUES
(1, 21, 'ddwdqf', 'Dorm', '2026-04-28 10:48:13'),
(2, 13, 'buldak', 'Food', '2026-04-28 15:43:24'),
(3, 10, 'bondpaper', 'Other', '2026-04-29 00:13:15'),
(4, 15, 'wdwd', 'Books', '2026-05-05 11:43:13'),
(5, 10, 'TIRAMIUSSIEE', 'Food', '2026-05-05 13:55:47'),
(8, 6, 'Mirror', '', '2026-05-07 03:30:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bulletin_posts`
--
ALTER TABLE `bulletin_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `up_email` (`up_email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wish_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bulletin_posts`
--
ALTER TABLE `bulletin_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wish_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bulletin_posts`
--
ALTER TABLE `bulletin_posts`
  ADD CONSTRAINT `bulletin_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
