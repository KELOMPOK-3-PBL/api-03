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


-- Dumping database structure for testpbl
CREATE DATABASE IF NOT EXISTS `testpbl` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `testpbl`;

-- Dumping structure for table testpbl.category
CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.category: ~4 rows (approximately)
INSERT INTO `category` (`category_id`, `category_name`) VALUES
	(1, 'Seminar'),
	(2, 'Lomba'),
	(3, 'Technology'),
	(4, 'Sports');

-- Dumping structure for table testpbl.event
CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `propose_user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `date_add` date NOT NULL,
  `category_id` int NOT NULL,
  `description` text NOT NULL,
  `poster` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `place` varchar(255) NOT NULL,
  `quota` int NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.event: ~37 rows (approximately)
INSERT INTO `event` (`event_id`, `propose_user_id`, `title`, `date_add`, `category_id`, `description`, `poster`, `location`, `place`, `quota`, `date_start`, `date_end`, `schedule`, `updated`, `admin_user_id`, `note`, `status`) VALUES
	(1, 4, 'Workshop on Web Development', '2024-10-23', 1, 'A workshop to learn modern web development.', 'poster1.jpg', 'City Hall', 'Main Auditorium', 50, '2024-10-24', '2024-10-30', NULL, NULL, 10, 'testing', 6),
	(2, 5, 'Annual Tech Conference', '2024-10-23', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241118_154126.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(3, 6, 'Monthly Webinar Series', '2024-10-23', 3, 'A series of webinars on various tech topics.', 'poster3.jpg', 'Online', 'Virtual Room', 100, '2024-10-26', '2024-11-23', NULL, NULL, NULL, NULL, 6),
	(18, 4, ' syk 2024', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_070900.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(20, 5, 'test', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072453.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(21, 5, 'test', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072536.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(22, 4, ' test', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072733.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(23, 4, ' test', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_072809.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(24, 4, ' test2', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_073315.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(25, 4, ' test10', '2024-11-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241101_074420.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(26, 4, 'test3', '2024-11-02', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241102_022757.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, NULL, NULL, 6),
	(27, 5, 'testing sir', '2024-11-02', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241102_164717.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, '2024-11-04 07:21:36', NULL, NULL, 6),
	(28, 5, ' testing123', '2024-11-04', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241107_032501.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', NULL, '2024-11-06 08:08:04', 7, NULL, 6),
	(29, 5, 'test-fileupload-update', '2024-11-07', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241107_033907.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, 7, 'testing', 6),
	(30, 5, 'update invited_users', '2024-12-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241201_132715.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, 7, NULL, 6),
	(31, 6, 'test-fileupload-update', '2024-12-01', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241201_183525.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, 7, NULL, 6),
	(32, 5, 'test-fileupload-update', '2024-12-04', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/images/poster/20241204_070730.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, 7, NULL, 6),
	(33, 5, 'test-fileupload-update', '2024-12-06', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241206_032046.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 6),
	(35, 5, 'test create untuk delete', '2024-12-13', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_020126.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-12', NULL, NULL, 7, 'testing', 6),
	(36, 5, 'test create untuk delete', '2024-12-13', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_023352.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 5),
	(37, 5, 'test1', '2024-12-13', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_023432.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 5),
	(38, 5, 'test2', '2024-12-13', 1, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_023451.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 5),
	(39, 5, 'test3', '2024-12-13', 1, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_023459.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 5),
	(40, 5, 'test4', '2024-12-13', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241213_023511.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-15', '2024-12-25', NULL, NULL, NULL, NULL, 5),
	(41, 5, 'test-fileupload-update', '2024-12-14', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_161011.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 6),
	(42, 5, 'tesst lagi', '2024-12-14', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_161227.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 6),
	(43, 5, 'tester invited id', '2024-12-14', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_165723.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(44, 5, 'tesst lagi', '2024-12-14', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_165825.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 6),
	(45, 5, 'tester invited id', '2024-12-14', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_170206.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(46, 5, 'tester invited id', '2024-12-14', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_170221.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(47, 5, 'tester invited id', '2024-12-14', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_170242.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(48, 5, 'tester invited id', '2024-12-14', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_170417.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(49, 5, 'tesst lagi', '2024-12-14', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241214_170637.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 6),
	(50, 5, 'tester invited id', '2024-12-15', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241215_154817.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1),
	(51, 5, 'tesst lagi', '2024-12-15', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241215_155149.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 3),
	(52, 5, 'tesst lagi', '2024-12-15', 3, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241215_155826.jpg', ' kandok', ' Teknik Elektro', 100, '2024-12-10', '2024-12-12', '', NULL, NULL, NULL, 3),
	(53, 5, 'tester invited id', '2024-12-18', 2, ' This is a detailed description of the Tech Conference 2024.', '/pbl/api-03/images/poster/20241218_161934.jpg', ' kandok', ' Teknik Elektro', 200, '2024-12-10', '2024-12-25', NULL, NULL, NULL, NULL, 1);

-- Dumping structure for table testpbl.invited
CREATE TABLE IF NOT EXISTS `invited` (
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  UNIQUE KEY `event_id` (`event_id`,`user_id`),
  KEY `invited_ibfk_1` (`user_id`),
  CONSTRAINT `invited_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `invited_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.invited: ~37 rows (approximately)
INSERT INTO `invited` (`event_id`, `user_id`) VALUES
	(35, 4),
	(36, 4),
	(37, 4),
	(38, 4),
	(39, 4),
	(40, 4),
	(35, 5),
	(36, 5),
	(37, 5),
	(38, 5),
	(39, 5),
	(40, 5),
	(35, 6),
	(36, 6),
	(37, 6),
	(38, 6),
	(39, 6),
	(40, 6),
	(29, 13),
	(30, 13),
	(31, 13),
	(32, 13),
	(33, 13),
	(42, 13),
	(44, 13),
	(49, 15),
	(49, 16),
	(29, 17),
	(30, 17),
	(31, 17),
	(32, 17),
	(33, 17),
	(42, 17),
	(44, 17),
	(49, 17),
	(29, 23),
	(32, 23),
	(33, 23),
	(42, 23),
	(44, 23);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.status: ~6 rows (approximately)
INSERT INTO `status` (`status_id`, `status_name`) VALUES
	(1, 'Proposed'),
	(2, 'Review Admin'),
	(3, 'Revision Propose'),
	(4, 'Rejected'),
	(5, 'Approved'),
	(6, 'Completed');

-- Dumping structure for table testpbl.user
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(50) DEFAULT NULL,
  `about` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table testpbl.user: ~12 rows (approximately)
INSERT INTO `user` (`user_id`, `username`, `email`, `password`, `avatar`, `about`) VALUES
	(4, 'test1', 'test1@gmail.com', '$2y$10$963z9f2cVKJHQnV7wTBy8O8Au6H9MXmfMe56OOZa4fzT0.1qdMeC2', NULL, 'bio'),
	(5, 'test2', 'member_propose@gmail.com', '$2y$10$fUPB425uMRfzkvid1E/XL.ep2SNuBFm1buuIgRY0qmnmJprpws81C', '/pbl/api-03/images/avatar/20241206_031749.jpg', 'testing sir'),
	(6, 'test3', 'member_propose2@gmail.com', '$2y$10$iMXjjN1Gx/LX9dISKo4gIun/zMSfW2nKuy4oz7HOrAx667E/Sbs5.', NULL, 'bio'),
	(7, 'test4', 'member_admin@gmail.com', '$2y$10$Fs.L0rw0ZgpJE9s0Y6zGHu8YDiF.8ftc2086nb2WB6fC3tj/BKRQi', NULL, 'bio'),
	(10, 'test6', 'superadmin@gmail.com', '$2y$10$fgx.rgMkjihgZd1JsSFTVOPxptu5Esq6hcmeVfKkxl3VMbdif5Bcu', NULL, 'bio'),
	(13, 'coba', 'testdelete@gmail.com', '$2y$10$LR8ovDSrr5g2AnpMq5IgSedFSDLzScjCUENBUvRS6d9K/U42IP1hS', NULL, 'testing'),
	(15, 'sekedarcoba1', 'testingdelete@gmail.com', '$2y$10$29SsgubdwROojQPW.WB2QOdbRcKSDYygYBSSjIZTugApXmz.19.8W', '/pbl/api-03/images/avatar/20241110_125736.jpg', 'test'),
	(16, 'sekedarcoba2', 'testdelete2@gmail.com', '$2y$10$xpd62Dojzx8yr87S3TlzE.n1ULg2ihZGjZ69o9lU1kkjlNY/2i5aC', '/pbl/api-03/images/avatar/20241110_042435.jpg', 'test'),
	(17, 'sekedartest1', 'testdelete3@gmail.com', '$2y$10$oSp2qOedFwi9i9dboiwgo.qASUpVhZc6vU6/Vubmnfo8Xqw.521z2', '/pbl/api-03/images/avatar/20241110_044119.jpg', 'test'),
	(18, 'sekedartest2', 'testdelete4@gmail.com', '$2y$10$zHhgPknUCBL0TeWdQY7N0.jGLi3F/YcEpzZma/PuUzyYZOOLNZ2bu', '/pbl/api-03/images/avatar/20241110_044255.jpg', 'test'),
	(19, 'sekedartest2', 'xolisek219@jonespal.com', '$2y$10$R2xLi73U1Tr7e2Rx5FRW3.tGyerbtI.rrIKLTT4ZwSnZDmIfXqZnC', '/pbl/api-03/images/avatar/20241110_044536.jpg', 'test'),
	(21, 'test avatar', 'avatar@gmail.com', '$2y$10$bB2wA1Zdpb2Jqe7WoifSZOi5XgGcbnsGgxFvDxZNHp8kOB.XXxg2W', '/pbl/api-03/images/avatar/20241204_051430.jpg', 'avatar test'),
	(22, 'another test avatar', 'avatar2@gmail.com', '$2y$10$nNXywtVSVlsL5TNDTgadTeAl3h2hTcq7GwlBZ9BdWFbMfTIo.Ht7a', '/pbl/api-03/images/avatar/20241204_051916.jpg', 'avatar test'),
	(23, 'test avatar 2', 'avatar3@gmail.com', '$2y$10$f1A2F1GhBIoQRwuPb/PlE.23ZGKjegfLPkkFmTT6uOeA6apU.nCdK', '/pbl/images/avatar/20241204_054350.jpg', 'testing sir'),
	(24, 'agusd test', 'agusdtest@gmail.com', '$2y$10$B3hPAjKg5Ymrn8JZvh4Fzu0zuOQ.ppF4N6/eX/ghQW3f2sZYoBDiO', '/pbl/api-03/images/avatar/20241206_065142.jpg', 'avatar test');

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

-- Dumping data for table testpbl.user_roles: ~19 rows (approximately)
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
	(4, 1),
	(4, 2),
	(4, 3),
	(5, 1),
	(5, 2),
	(6, 1),
	(6, 2),
	(7, 1),
	(7, 3),
	(10, 4),
	(15, 1),
	(15, 3),
	(19, 1),
	(19, 3),
	(21, 1),
	(21, 2),
	(22, 1),
	(22, 2),
	(23, 1),
	(23, 2),
	(24, 1),
	(24, 2);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
