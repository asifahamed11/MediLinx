-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2025 at 06:35 PM
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
-- Database: `user_authentication`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('patient','doctor') NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `medical_license_number` varchar(50) DEFAULT NULL,
  `work_address` varchar(255) DEFAULT NULL,
  `consultation_hours` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_pin` char(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `degrees_and_certifications` varchar(400) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `available_consultation_hours` varchar(500) NOT NULL,
  `languages_spoken` varchar(255) DEFAULT NULL,
  `professional_biography` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `username`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `specialty`, `medical_license_number`, `work_address`, `consultation_hours`, `profile_image`, `email_verified_at`, `email_verification_pin`, `created_at`, `degrees_and_certifications`, `years_of_experience`, `available_consultation_hours`, `languages_spoken`, `professional_biography`) VALUES
(39, 'patient', 'asif93874', 'hadefe9093@intady.com', '$2y$10$vI48zNO52UdonAgtXXWI0umRnVcmRa3B.3A6SCVOcHdnStbZ5bOq.', '1770396221', '2024-10-24', '', '', 'hi', NULL, NULL, NULL, NULL, 'uploads/profile_images/67a9a3f33e431-wallpaper_20250207_121559.jpg', '2025-02-10 06:57:57', NULL, '2025-02-10 06:57:43', NULL, NULL, '', NULL, NULL),
(41, 'doctor', 'asif938741', 'c', '$2y$10$8SNKWseNMYSGndzwyob/VO9ksjJ9R.DMOglNQ.y6xtH0Ytru2DRXG', '1770396222', NULL, '', NULL, NULL, 'car', '0', 'Juhu', NULL, 'uploads/profile_images/67aa34f78b75a_profile.jpg', '2025-02-10 17:19:10', NULL, '2025-02-10 17:18:47', 'asds', 12, 'asd', 'asd', 'asda'),
(42, 'doctor', 'asif', 'givobev205@nike4s.com', '$2y$10$wbTgSKRSqtcg5oolrzJqQ.Gj96toBCBPBaf6jQMbhOv4WrX.z7g6y', '1770396222', NULL, '', NULL, NULL, 'cardiology', '123423', 'popular', NULL, 'uploads/profile_images/67aa379bb6af8_profile.jpg', '2025-02-10 17:30:19', NULL, '2025-02-10 17:30:03', 'HSC', 12, 'all time', 'bangla', 'nothing'),
(43, 'patient', 'asif93874a', 'hineja1542@owlny.com', '$2y$10$b3MEUbO/Ncy2rZgflPcUje4yaEsWwm7QGNbGWubRnlveIF3DeJH56', '1770396221', '2025-02-13', 'Male', '', 'g', NULL, NULL, NULL, NULL, 'uploads/profile_images/67aa45df4e776_profile.jpg', '2025-02-10 18:31:12', NULL, '2025-02-10 18:30:55', NULL, NULL, '', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
