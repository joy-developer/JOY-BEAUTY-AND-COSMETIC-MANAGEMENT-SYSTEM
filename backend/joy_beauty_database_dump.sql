-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.41 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for joy_beauty
DROP DATABASE IF EXISTS `joy_beauty`;
CREATE DATABASE IF NOT EXISTS `joy_beauty` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `joy_beauty`;

-- Dumping structure for table joy_beauty.appointments
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `service_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.appointments: ~0 rows (approximately)
INSERT INTO `appointments` (`appointment_id`, `user_id`, `service_id`, `appointment_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`) VALUES
	(1, 2, 1, '2025-07-15', '10:00:00', '11:00:00', 'confirmed', 'I may be late with about five minutes', '2025-07-17 15:27:15'),
	(2, 3, 1, '2025-07-24', '10:12:00', '11:12:00', 'confirmed', 'Please I need this', '2025-07-18 12:09:29');

-- Dumping structure for table joy_beauty.products
DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` text,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.products: ~12 rows (approximately)
INSERT INTO `products` (`product_id`, `name`, `description`, `category`, `price`, `stock_quantity`, `image_url`, `is_active`, `created_at`) VALUES
	(1, 'Vitamin C Serum', 'Brightening and anti-aging facial serum', 'Skincare', 1800.00, 25, 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1588&q=80', 1, '2025-07-17 15:27:15'),
	(2, 'Hydrating Shampoo', 'Moisturizing shampoo for all hair types', 'Haircare', 720.00, 40, 'https://images.unsplash.com/photo-1625772452859-1c03d5bf1137?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', 1, '2025-07-17 15:27:15'),
	(3, 'Nourishing Conditioner', 'Deep conditioning treatment', 'Haircare', 800.00, 36, 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', 1, '2025-07-17 15:27:15'),
	(4, 'Matte Lipstick', 'Long-lasting matte finish lipstick', 'Makeup', 880.00, 15, 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', 1, '2025-07-17 15:27:15'),
	(5, 'Sunscreen SPF 50', 'Lightweight daily sunscreen', 'Skincare', 1200.00, 5, 'https://images.unsplash.com/photo-1598662972299-5408ddb8a3dc?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(6, 'Hydrating Body Lotion', 'Hydrating body lotion with shea butter', 'Bodycare', 1000.00, 30, 'https://images.unsplash.com/photo-1748390359572-8e7a47bf5cb5?q=80&w=784&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(7, 'Luxury Hair Care Kit', 'Complete kit for luxurious hair treatment, including shampoo, conditioner, and mask.', 'Haircare', 3000.00, 20, 'https://images.unsplash.com/photo-1638609927127-aeb9e74c3cfd?q=80&w=747&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(8, 'Pro Makeup Palette', 'Versatile makeup palette with a range of colors for professional looks.', 'Makeup', 2400.00, 10, 'https://images.unsplash.com/photo-1679141336289-3c935bd791c9?q=80&w=1471&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(9, 'Gentle Facial Cleanser', 'Daily cleanser for all skin types, effectively removes impurities without drying.', 'Skincare', 1120.00, 35, 'https://images.unsplash.com/photo-1748639320154-6ba118bccc74?q=80&w=880&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(10, 'Deep Exfoliating Scrub', 'Invigorating scrub that gently exfoliates dead skin cells for a radiant complexion.', 'Skincare', 1280.00, 25, 'https://plus.unsplash.com/premium_photo-1726768966807-330ecbfa087d?q=80&w=719&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(11, 'Anti-Aging Eye Cream', 'Reduces the appearance of fine lines and wrinkles around the eyes.', 'Skincare', 2200.00, 18, 'https://plus.unsplash.com/premium_photo-1723924840839-822dcc100090?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15'),
	(12, 'Sparkle Lip Gloss Set', 'Set of hydrating lip glosses with a non-sticky, high-shine finish.', 'Makeup', 1520.00, 22, 'https://images.unsplash.com/photo-1590718313039-da29f5ef56d5?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 1, '2025-07-17 15:27:15');

-- Dumping structure for table joy_beauty.sales
DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `sale_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit_card','mobile_money') NOT NULL,
  `payment_status` enum('pending','completed','refunded') DEFAULT 'completed',
  PRIMARY KEY (`sale_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.sales: ~0 rows (approximately)
INSERT INTO `sales` (`sale_id`, `user_id`, `sale_date`, `total_amount`, `payment_method`, `payment_status`) VALUES
	(1, 2, '2025-07-17 15:27:15', 495.00, 'cash', 'completed'),
	(2, 3, '2025-07-18 12:08:44', 350.00, 'cash', 'completed');

-- Dumping structure for table joy_beauty.sale_items
DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE IF NOT EXISTS `sale_items` (
  `sale_item_id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_item_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `sale_items_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.sale_items: ~2 rows (approximately)
INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `product_id`, `service_id`, `quantity`, `unit_price`) VALUES
	(1, 1, 1, 4, 1, 1800.00),
	(2, 1, 2, 1, 1, 2000.00),
	(3, 2, 3, 3, 2, 800.00);

-- Dumping structure for table joy_beauty.services
DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.services: ~10 rows (approximately)
INSERT INTO `services` (`service_id`, `name`, `description`, `price`, `duration_minutes`, `is_active`, `created_at`, `image_url`) VALUES
	(1, 'Basic Facial', 'Deep cleansing facial with exfoliation and mask', 2250.00, 60, 1, '2025-07-17 15:27:15', 'https://plus.unsplash.com/premium_photo-1663011276992-6e52adb20dd3?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OXx8ZmFjaWFsfGVufDB8fDB8fHww'),
	(2, 'Hair Cut', 'Professional haircut with styling', 1575.00, 45, 1, '2025-07-17 15:27:15', 'https://images.unsplash.com/photo-1643837832861-ba85d3b046d9?q=80&w=1454&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
	(3, 'Full Body Massage', '60-minute full body relaxation massage', 3600.00, 60, 1, '2025-07-17 15:27:15', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
	(4, 'Spa Treatments', 'Relaxing massages, body wraps, and detox treatments in our serene spa environment.', 2500.00, 30, 1, '2025-07-17 15:27:15', 'https://images.unsplash.com/photo-1600334129128-685c5582fd35?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80'),
	(5, 'Pedicure', 'Basic pedicure with polish', 1350.00, 45, 1, '2025-07-17 15:27:15', 'https://plus.unsplash.com/premium_photo-1661499249417-c20d6b668469?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
	(6, 'Hair Coloring', 'Full hair coloring service', 3375.00, 90, 1, '2025-07-17 15:27:15', 'https://images.unsplash.com/photo-1554519515-242161756769?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
	(7, 'Skincare Treatments', 'Professional facials, chemical peels, microdermabrasion, and anti-aging treatments tailored to your skin\'s needs.', 3150.00, 90, 1, '2025-07-17 16:35:11', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80'),
	(8, 'Hair Services', 'Cut, color, styling, keratin treatments, and extensions for all hair types with premium products.', 2700.00, 120, 1, '2025-07-17 16:35:11', 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1480&q=80'),
	(9, 'Makeup Artistry', 'Professional makeup for weddings, special occasions, or just because - enhancing your natural features.', 4050.00, 75, 1, '2025-07-17 16:35:11', 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80'),
	(10, 'Manicure', 'Basic manicure with polish', 4500.00, 120, 1, '2025-07-17 16:35:11', 'https://plus.unsplash.com/premium_photo-1661497566854-7a75d3e98996?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');

-- Dumping structure for table joy_beauty.testimonials
DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE IF NOT EXISTS `testimonials` (
  `testimonial_id` int NOT NULL AUTO_INCREMENT,
  `quote` text NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `client_title` varchar(100) DEFAULT NULL,
  `client_image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.testimonials: ~3 rows (approximately)
INSERT INTO `testimonials` (`testimonial_id`, `quote`, `client_name`, `client_title`, `client_image_url`, `created_at`) VALUES
	(1, 'The facial treatment I received was amazing! My skin has never felt so refreshed and glowing. The staff is incredibly professional and knowledgeable.', 'Sarah M.', 'Regular Client', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-17 17:25:43'),
	(2, 'I\'ve been coming to Joy Beauty for my hair for 2 years now and I wouldn\'t go anywhere else. They always know exactly what will suit me best.', 'James K.', 'Hair Client', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80', '2025-07-17 17:25:43'),
	(3, 'The bridal makeup was perfect! I felt beautiful and the makeup lasted all day through tears and dancing. Highly recommend!', 'Emily T.', 'Bride', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1587&q=80', '2025-07-17 17:25:43');

-- Dumping structure for table joy_beauty.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `user_type` enum('admin','client') NOT NULL DEFAULT 'client',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table joy_beauty.users: ~2 rows (approximately)
INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `address`, `user_type`, `created_at`, `updated_at`) VALUES
	(1, 'Admin', 'Joy', 'admin@joybeauty.com', '$2y$10$lFyft0XbPc8V.X2JpDi0suhdrZywmeDJqqWL7PfJIEhGcQASo3M4S', '25494232323', NULL, 'admin', '2025-03-18 01:27:15', '2025-07-18 10:54:08'),
	(2, 'Sarah', 'Muthoni', 'sarah12@gmail.com', '$2y$10$1oJDs5HOBApYO0whRfLG5urJQsRVSC9es4YJUe1pV5s1HzRHXANHW', '254712345678', NULL, 'client', '2025-07-17 15:27:15', '2025-07-17 17:47:07'),
	(3, 'Test', 'Client', 'client@gmail.com', '$2y$10$1oJDs5HOBApYO0whRfLG5urJQsRVSC9es4YJUe1pV5s1HzRHXANHW', '254754601950', NULL, 'client', '2025-07-17 17:46:27', '2025-07-17 18:09:42');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
