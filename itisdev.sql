-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2025 at 04:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop existing database if necessary
DROP DATABASE IF EXISTS `itisdev`;
CREATE DATABASE `itisdev`;
USE `itisdev`;

-- --------------------------------------------------------
-- Table structure for table `account`
-- --------------------------------------------------------

CREATE TABLE `account` (
  `id` INT(5) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(15) NOT NULL,
  `last_name` VARCHAR(15) NOT NULL,
  `email` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for `account`
INSERT INTO `account` (`first_name`, `last_name`, `email`, `password`) VALUES
('Josheart', 'Legarte', 'admin@gmail.com', '123');

-- --------------------------------------------------------
-- Table structure for table `product`
-- --------------------------------------------------------

CREATE TABLE `product` (
  `id` INT(5) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(20) NOT NULL,
  `category` VARCHAR(15) NOT NULL,
  `sales` INT(11) NOT NULL DEFAULT 0,
  `stocks` INT(11) NOT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL,
  `picture` BLOB NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for `product`
INSERT INTO `product` (`name`, `category`, `sales`, `stocks`, `price`, `picture`) VALUES
('Sample Product', 'Electronics', 10, 100, 99.99, '');

-- --------------------------------------------------------
-- Table structure for table `logs`
-- --------------------------------------------------------

CREATE TABLE `logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `description` VARCHAR(50) NOT NULL,
  `userID` INT(5) NOT NULL,
  `productID` INT(5) DEFAULT NULL,
  `datetime` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`),
  KEY `productID` (`productID`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for `logs`
INSERT INTO `logs` (`description`, `userID`, `productID`, `datetime`) VALUES
('User logged in', 1, NULL, NOW());

COMMIT;
