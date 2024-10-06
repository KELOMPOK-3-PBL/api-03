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

-- Dumping data for table testpbl.category: ~2 rows (approximately)
INSERT INTO `category` (`category_id`, `category_name`) VALUES
	(1, 'Biologi'),
	(2, 'Kimia');

-- Dumping data for table testpbl.event: ~2 rows (approximately)
INSERT INTO `event` (`event_id`, `propose_user_id`, `title`, `date_add`, `category_id`, `description`, `poster`, `location`, `place`, `quota`, `date_start`, `date_end`, `updated`, `admin_user_id`, `note`, `status`) VALUES
	(1, 2, 'Ini Judul', NULL, 1, NULL, NULL, 'GKT', NULL, 100, NULL, NULL, NULL, NULL, NULL, 1),
	(2, 2, 'Ini Judul2', NULL, 2, NULL, NULL, 'MST', NULL, 50, NULL, NULL, NULL, NULL, NULL, 2);

-- Dumping data for table testpbl.event_status: ~2 rows (approximately)
INSERT INTO `event_status` (`status_id`, `status_name`) VALUES
	(1, 'Approved'),
	(2, 'Pending');

-- Dumping data for table testpbl.invited: ~0 rows (approximately)

-- Dumping data for table testpbl.user: ~3 rows (approximately)
INSERT INTO `user` (`user_id`, `username`, `email`, `password`, `about`, `role`) VALUES
	(1, 'user1', 'ini@testmail.com', '123', '', 'Member'),
	(2, 'user2', 'ini@testmail.com', '123', '', 'Propose'),
	(3, 'user3', 'ini@testmail.com', '123', '', 'Admin');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
