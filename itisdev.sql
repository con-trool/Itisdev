-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2025 at 09:36 AM
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
-- Database: `itisdev`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id` int(5) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id`, `first_name`, `last_name`, `email`, `password`) VALUES
(1, 'Josheart Adrienne', 'Legarte', 'josheart@gmail.com', '$2y$10$ectNtupgN5Y7JlZQSphdLuH83Uh1iQSPt7QMrkVcywx6HXxrwl6ie'),
(2, 'Con Miko', 'Serrano', 'miko@gmail.com', '$2y$10$Wd/xZB0TjfTCv.qwThMY.O.8GkqDfFAwxM7uRCBWdsVOs6K28MB6G'),
(3, 'Linc', 'Chan', 'linc@gmail.com', '$2y$10$MNdjezW3b6pkWHwaWKM0AeaQ0oVytHSDJKTboBhmtThn6h2w9To6G'),
(6, 'Kr', 'Legarte', 'kr@gmail.com', '$2y$10$Qqn4driTcYIW93LQidlypOgaEHaTTZwogWctci40jIbk87IuWcMna');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `description` varchar(50) NOT NULL,
  `userID` int(5) NOT NULL,
  `productID` int(5) DEFAULT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `description`, `userID`, `productID`, `datetime`) VALUES
(2, 'ewan', 1, NULL, '2025-04-03 17:23:51'),
(3, 'ewan', 1, NULL, '2025-04-04 14:21:58');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `sales` int(11) NOT NULL DEFAULT 0,
  `stocks` int(11) NOT NULL DEFAULT 0,
  `criticalQty` int(5) NOT NULL DEFAULT 5,
  `price` decimal(10,2) NOT NULL,
  `picture` blob NOT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `sales`, `stocks`, `criticalQty`, `price`, `picture`, `status`) VALUES
(11, 'Compact Spiral Fluorescent Lamp', 101, 100, 10, 134.00, 0x75706c6f6164732f313734333833373730395f335332342e77656270, 'active'),
(12, '2 Gang Switch Set - White', 0, 50, 5, 169.00, 0x75706c6f6164732f313734333833373738305f4d443531332e77656270, 'active'),
(13, 'Home Desk Circulator Fan', 0, 10, 2, 5670.00, 0x75706c6f6164732f313734333833383138395f4648463230352e77656270, 'active'),
(14, 'Mini Air Fryer 1.9L', 0, 5, 2, 2340.00, 0x75706c6f6164732f313734333833383235305f464846373031202831292e77656270, 'active'),
(15, 'Smart Wifi Air Purifier', 0, 8, 2, 17000.00, 0x75706c6f6164732f313734333833383239375f465950343031202831292e77656270, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
