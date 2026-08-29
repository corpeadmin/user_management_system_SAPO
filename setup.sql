-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `sample` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sample`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional sample test data
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$wE0v2.o9k3h5yZ5f4QZ5Me8lqU1D.q3o9E8u7y6a5s4d3f2g1h0j'),
(2, 'Jane Doe', 'jane@example.com', '$2y$10$wE0v2.o9k3h5yZ5f4QZ5Me8lqU1D.q3o9E8u7y6a5s4d3f2g1h0j');
