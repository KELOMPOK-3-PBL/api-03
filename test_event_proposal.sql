-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table testpbl.category
CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.category: ~4 rows (approximately)
INSERT INTO `category` (`category_id`, `category_name`) VALUES
	(1, 'Music'),
	(2, 'Art'),
	(3, 'Technology'),
	(4, 'Sports');

-- Dumping structure for table testpbl.event
CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `propose_user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_add` datetime NOT NULL,
  `category_id` int NOT NULL,
  `description` text NOT NULL,
  `poster` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `place` varchar(255) NOT NULL,
  `quota` int NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  `admin_user_id` int DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `status` int NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `propose_user_id` (`propose_user_id`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`propose_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `event_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `event_ibfk_3` FOREIGN KEY (`status`) REFERENCES `status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.event: ~13 rows (approximately)
INSERT INTO `event` (`event_id`, `propose_user_id`, `title`, `date_add`, `category_id`, `description`, `poster`, `location`, `place`, `quota`, `date_start`, `date_end`, `schedule`, `updated`, `admin_user_id`, `note`, `status`) VALUES
	(1, 4, 'Workshop on Web Development', '2024-10-23 21:18:35', 1, 'A workshop to learn modern web development.', 'poster1.jpg', 'City Hall', 'Main Auditorium', 50, '2024-10-24 21:18:35', '2024-10-30 21:18:35', NULL, NULL, NULL, NULL, 4),
	(2, 5, 'Annual Tech Conference', '2024-10-23 21:18:35', 2, 'Join us for the annual tech conference with industry leaders.', 'poster2.jpg', 'Convention Center', 'Grand Ballroom', 200, '2024-10-25 21:18:35', '2024-11-06 21:18:35', NULL, NULL, NULL, NULL, 4),
	(3, 6, 'Monthly Webinar Series', '2024-10-23 21:18:35', 3, 'A series of webinars on various tech topics.', 'poster3.jpg', 'Online', 'Virtual Room', 100, '2024-10-26 21:18:35', '2024-11-23 21:18:35', NULL, NULL, NULL, NULL, 1),
	(18, 4, ' syk 2024', '2024-11-01 07:09:00', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_070900.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(20, 5, 'test', '2024-11-01 07:24:53', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072453.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(21, 5, 'test', '2024-11-01 07:25:36', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072536.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(22, 4, ' test', '2024-11-01 07:27:33', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072733.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(23, 4, ' test', '2024-11-01 07:28:09', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072809.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(24, 4, ' test2', '2024-11-01 07:33:15', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_073315.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(25, 4, ' test10', '2024-11-01 07:44:20', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_074420.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(26, 4, 'test3', '2024-11-02 02:27:57', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241102_022757.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, NULL, NULL, NULL, 1),
	(27, 5, 'testing sir', '2024-11-02 16:47:17', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241102_164717.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, '2024-11-04 14:21:36', NULL, NULL, 1),
	(28, 5, 'testing sir ke 1000', '2024-11-04 14:22:37', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241104_142237.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10 09:00:00', '2024-12-12 17:00:00', NULL, '2024-11-05 08:08:27', NULL, NULL, 1);

-- Dumping structure for table testpbl.invited
CREATE TABLE IF NOT EXISTS `invited` (
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  UNIQUE KEY `event_id` (`event_id`,`user_id`),
  KEY `invited_ibfk_1` (`user_id`),
  CONSTRAINT `invited_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `invited_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.invited: ~9 rows (approximately)
INSERT INTO `invited` (`event_id`, `user_id`) VALUES
	(1, 4),
	(3, 4),
	(1, 5),
	(2, 5),
	(1, 6),
	(3, 6),
	(2, 7);

-- Dumping structure for table testpbl.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.roles: ~4 rows (approximately)
INSERT INTO `roles` (`role_id`, `role_name`) VALUES
	(1, 'Member'),
	(2, 'Propose'),
	(3, 'Admin'),
	(4, 'Superadmin');

-- Dumping structure for table testpbl.status
CREATE TABLE IF NOT EXISTS `status` (
  `status_id` int NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.status: ~5 rows (approximately)
INSERT INTO `status` (`status_id`, `status_name`) VALUES
	(1, 'Reviewing'),
	(2, 'Pending'),
	(3, 'Rejected'),
	(4, 'Approved'),
	(5, 'Completed');

-- Dumping structure for table testpbl.user
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(50) DEFAULT NULL,
  `about` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.user: ~6 rows (approximately)
INSERT INTO `user` (`user_id`, `username`, `email`, `password`, `avatar`, `about`) VALUES
	(4, 'test1', 'test1@gmail.com', '$2y$10$963z9f2cVKJHQnV7wTBy8O8Au6H9MXmfMe56OOZa4fzT0.1qdMeC2', NULL, 'bio'),
	(5, 'test2', 'test2@gmail.com', '$2y$10$wkOrnTg3HMQSenfmxqTIT.ICuiwJXagzRe0G.ZXeEpraoWosF6ejm', NULL, 'bio'),
	(6, 'test3', 'test3@gmail.com', '$2y$10$iMXjjN1Gx/LX9dISKo4gIun/zMSfW2nKuy4oz7HOrAx667E/Sbs5.', NULL, 'bio'),
	(7, 'test4', 'test4@gmail.com', '$2y$10$Fs.L0rw0ZgpJE9s0Y6zGHu8YDiF.8ftc2086nb2WB6fC3tj/BKRQi', NULL, 'bio'),
	(10, 'test6', 'test6@gmail.com', '$2y$10$fgx.rgMkjihgZd1JsSFTVOPxptu5Esq6hcmeVfKkxl3VMbdif5Bcu', NULL, 'bio'),
	(13, 'coba', 'testdelete@gmail.com', '$2y$10$LR8ovDSrr5g2AnpMq5IgSedFSDLzScjCUENBUvRS6d9K/U42IP1hS', NULL, 'testing');

-- Dumping structure for table testpbl.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.user_roles: ~10 rows (approximately)
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
	(4, 1),
	(4, 2),
	(4, 3),
	(5, 1),
	(5, 2),
	(6, 1),
	(6, 2),
	(7, 1),
	(7, 2),
	(10, 4);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
