CREATE DATABASE IF NOT EXISTS `testpbl` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `testpbl`;

-- Create user table first
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `about` text,
  `role` enum('Member','Propose','Admin','Superadmin') NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create category table
CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create event_status table
CREATE TABLE IF NOT EXISTS `event_status` (
  `status_id` int NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create event table after user, category, and event_status
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
  `updated` timestamp NULL DEFAULT NULL,
  `admin_user_id` int DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `status` int NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`propose_user_id`),
  KEY `category_id` (`category_id`),
  KEY `event_ibfk_3` (`status`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`propose_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `event_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `event_ibfk_3` FOREIGN KEY (`status`) REFERENCES `event_status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create event_registration table after event and user
CREATE TABLE IF NOT EXISTS `event_registration` (
  `registration_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_present` tinyint(1) DEFAULT '0',
  `registration_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`registration_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `event_registration_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`),
  CONSTRAINT `event_registration_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create invited table after event and user
CREATE TABLE IF NOT EXISTS `invited` (
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  UNIQUE KEY `event_id` (`event_id`,`user_id`),
  KEY `invited_ibfk_1` (`user_id`),
  CONSTRAINT `invited_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `invited_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
