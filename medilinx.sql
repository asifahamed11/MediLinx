-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 07:05 PM
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
-- Database: `medilinx`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `slot_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('confirmed','completed','cancelled') DEFAULT 'confirmed',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `slot_id`, `start_time`, `end_time`, `location`, `reason`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 1, '2025-05-18 15:30:00', '2025-05-18 21:30:00', 'National Heart Foundation, Dhaka', '', 'confirmed', NULL, '2025-05-14 16:32:35', NULL),
(2, 19, 2, 9, '2025-05-15 13:30:00', '2025-05-15 17:30:00', 'Khulna Medical College Hospital', '', 'cancelled', NULL, '2025-05-14 16:35:11', '2025-05-14 16:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `degrees`
--

CREATE TABLE `degrees` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `degree_name` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `passing_year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `degrees`
--

INSERT INTO `degrees` (`id`, `doctor_id`, `degree_name`, `institution`, `passing_year`) VALUES
(1, 1, 'MBBS', 'Dhaka Medical College', '2003'),
(2, 1, 'FCPS (Cardiology)', 'Bangladesh College of Physicians and Surgeons', '2010'),
(3, 1, 'Fellowship in Interventional Cardiology', 'Royal College of Physicians', '2012'),
(4, 2, 'MBBS', 'Khulna Medical College', '2001'),
(5, 2, 'FCPS (Gynecology)', 'Bangladesh College of Physicians and Surgeons', '2008'),
(6, 2, 'Fellowship in Reproductive Medicine', 'Royal Australian and New Zealand College of Obstetricians and Gynaecologists', '2010'),
(7, 3, 'MBBS', 'Chittagong Medical College', '2005'),
(8, 3, 'DDV', 'Bangabandhu Sheikh Mujib Medical University', '2012'),
(9, 3, 'Fellowship in Cosmetic Dermatology', 'National Skin Centre Singapore', '2014'),
(10, 4, 'MBBS', 'Sylhet MAG Osmani Medical College', '2004'),
(11, 4, 'FCPS (Pediatrics)', 'Bangladesh College of Physicians and Surgeons', '2011'),
(12, 4, 'Fellowship in Pediatric Critical Care', 'Boston Children\'s Hospital', '2013'),
(13, 5, 'MBBS', 'Rajshahi Medical College', '2006'),
(14, 5, 'FCPS (Psychiatry)', 'Bangladesh College of Physicians and Surgeons', '2013'),
(15, 5, 'Fellowship in Child Psychiatry', 'Royal College of Psychiatrists, UK', '2015'),
(16, 6, 'MBBS', 'Rangpur Medical College', '2008'),
(17, 6, 'MD (Endocrinology)', 'Bangabandhu Sheikh Mujib Medical University', '2015'),
(18, 6, 'Fellowship in Diabetes Management', 'All India Institute of Medical Sciences', '2017'),
(19, 7, 'MBBS', 'Sher-e-Bangla Medical College', '2010'),
(20, 7, 'FCPS (Ophthalmology)', 'Bangladesh College of Physicians and Surgeons', '2017'),
(21, 7, 'Fellowship in Retinal Surgery', 'National University of Malaysia', '2019'),
(22, 8, 'MBBS', 'Dhaka Medical College', '1998'),
(23, 8, 'FCPS (Cardiology)', 'Bangladesh College of Physicians and Surgeons', '2005'),
(24, 8, 'Fellowship in Interventional Cardiology', 'Cleveland Clinic, USA', '2007'),
(25, 9, 'MBBS', 'Chittagong Medical College', '2000'),
(26, 9, 'MD (Neurology)', 'Bangabandhu Sheikh Mujib Medical University', '2007'),
(27, 9, 'Fellowship in Stroke Medicine', 'Kings College London, UK', '2009'),
(28, 10, 'MBBS', 'Sir Salimullah Medical College', '2002'),
(29, 10, 'MS (Orthopedics)', 'Bangabandhu Sheikh Mujib Medical University', '2009'),
(30, 10, 'Fellowship in Joint Replacement', 'Charité – Universitätsmedizin Berlin', '2011'),
(31, 11, 'MBBS', 'Mymensingh Medical College', '2001'),
(32, 11, 'MD (Chest Diseases)', 'National Institute of Diseases of Chest & Hospital', '2008'),
(33, 11, 'Fellowship in Respiratory Medicine', 'All India Institute of Medical Sciences', '2010'),
(34, 12, 'MBBS', 'Rangpur Medical College', '2003'),
(35, 12, 'MS (Urology)', 'Bangabandhu Sheikh Mujib Medical University', '2010'),
(36, 12, 'Fellowship in Urological Surgery', 'Chulalongkorn University, Thailand', '2012'),
(37, 13, 'MBBS', 'Sylhet MAG Osmani Medical College', '2007'),
(38, 13, 'MD (Gastroenterology)', 'Bangabandhu Sheikh Mujib Medical University', '2014'),
(39, 13, 'Fellowship in Advanced Endoscopy', 'Osaka University Hospital, Japan', '2016'),
(40, 14, 'MBBS', 'Khulna Medical College', '1999'),
(41, 14, 'MD (Medical Oncology)', 'National Cancer Institute, Bangladesh', '2006'),
(42, 14, 'Fellowship in Clinical Oncology', 'National Cancer Centre Singapore', '2008'),
(43, 15, 'MBBS', 'Shaheed Ziaur Rahman Medical College', '2006'),
(44, 15, 'MS (Otolaryngology)', 'Bangabandhu Sheikh Mujib Medical University', '2013'),
(45, 15, 'Fellowship in Advanced Otology', 'University of Malaya Medical Centre', '2015'),
(46, 16, 'MBBS', 'Comilla Medical College', '2004'),
(47, 16, 'MD (Nephrology)', 'Institute of Post Graduate Medicine & Research', '2011'),
(48, 16, 'Fellowship in Transplant Medicine', 'All India Institute of Medical Sciences', '2013');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `condition_name` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','system','reminder') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'New appointment scheduled with patient #20 for May 18, 2025 3:30 PM', 'appointment', 0, '2025-05-14 16:32:35'),
(2, 2, 'New appointment scheduled with patient #19 for May 15, 2025 1:30 PM', 'appointment', 0, '2025-05-14 16:35:11'),
(3, 19, 'Your appointment on Thursday, May 15, 2025 at 1:30 PM has been cancelled by Dr Nadia Islam.', 'appointment', 0, '2025-05-14 16:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `doctor_id`, `title`, `content`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Primary duties', 'A cardiovascular technician helps doctors diagnose and treat cardiac and peripheral vascular conditions. They use electrocardiograms, Holter monitors, blood pressure tests and stress tests to assess patients\' heart health and identify what is causing their symptoms. Cardiovascular technicians may also use special imaging equipment to monitor patients\' heart rhythm during and after surgical procedures. They document their findings and update patient records to ensure they\'re accurate.', 'uploads/posts/1747241823_1746797275_67ffc3e2e49d0_post.png', '2025-05-14 16:57:03', '2025-05-14 16:57:03');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `doctor_id`, `patient_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 20, 5.0, 'Good', '2025-05-14 16:32:25'),
(2, 2, 19, 4.0, 'Good', '2025-05-14 16:37:38');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('available','booked','cancelled') DEFAULT 'available',
  `capacity` int(11) DEFAULT 20,
  `booked_count` int(11) DEFAULT 0,
  `slot_duration` int(11) DEFAULT 30 COMMENT 'Duration of each appointment slot in minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `doctor_id`, `start_time`, `end_time`, `location`, `status`, `capacity`, `booked_count`, `slot_duration`) VALUES
(1, 1, '2025-05-18 15:30:00', '2025-05-18 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 1, 360),
(2, 1, '2025-05-19 15:30:00', '2025-05-19 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(3, 1, '2025-05-20 15:30:00', '2025-05-20 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(4, 1, '2025-05-21 15:30:00', '2025-05-21 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(5, 1, '2025-05-25 15:30:00', '2025-05-25 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(6, 1, '2025-05-26 15:30:00', '2025-05-26 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(7, 1, '2025-05-27 15:30:00', '2025-05-27 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(8, 1, '2025-05-28 15:30:00', '2025-05-28 21:30:00', 'National Heart Foundation, Dhaka', 'available', 20, 0, 360),
(9, 2, '2025-05-15 13:30:00', '2025-05-15 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(10, 2, '2025-05-17 13:30:00', '2025-05-17 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(11, 2, '2025-05-18 13:30:00', '2025-05-18 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(12, 2, '2025-05-19 13:30:00', '2025-05-19 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(13, 2, '2025-05-20 13:30:00', '2025-05-20 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(14, 2, '2025-05-21 13:30:00', '2025-05-21 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(15, 2, '2025-05-22 13:30:00', '2025-05-22 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(16, 2, '2025-05-24 13:30:00', '2025-05-24 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(17, 2, '2025-05-25 13:30:00', '2025-05-25 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(18, 2, '2025-05-26 13:30:00', '2025-05-26 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(19, 2, '2025-05-27 13:30:00', '2025-05-27 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(20, 2, '2025-05-28 13:30:00', '2025-05-28 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(21, 2, '2025-05-29 13:30:00', '2025-05-29 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240),
(22, 2, '2025-05-31 13:30:00', '2025-05-31 17:30:00', 'Khulna Medical College Hospital', 'available', 20, 0, 240);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `medical_license_number` varchar(100) DEFAULT NULL,
  `work_address` varchar(255) DEFAULT NULL,
  `consultation_hours` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_pin` char(6) DEFAULT NULL,
  `email_verification_pin_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `degrees_and_certifications` varchar(400) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `available_consultation_hours` varchar(500) DEFAULT NULL,
  `languages_spoken` varchar(255) DEFAULT NULL,
  `professional_biography` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `username`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `medical_history`, `specialty`, `medical_license_number`, `work_address`, `consultation_hours`, `profile_image`, `email_verified_at`, `email_verification_pin`, `email_verification_pin_expiry`, `created_at`, `degrees_and_certifications`, `years_of_experience`, `available_consultation_hours`, `languages_spoken`, `professional_biography`) VALUES
(1, 'doctor', 'Dr Sarah Rahman', 'sarah@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345601', '1985-03-15', 'Female', 'House 7, Road 5, Dhanmondi, Dhaka', NULL, 'Cardiology', 'BMDC-C-1201', 'National Heart Foundation, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/(female).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Cardiology), Fellowship (UK)', 12, '9 AM - 12 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Sarah is a renowned cardiologist specializing in interventional cardiology with extensive experience in complex cardiac procedures.'),
(2, 'doctor', 'Dr Nadia Islam', 'nadia@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345602', '1983-06-22', 'Female', 'Khulna Medical College Road, Khulna', NULL, 'Gynecology', 'BMDC-G-1202', 'Khulna Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(female) (2).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Gynecology), Fellowship (Australia)', 15, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Nadia specializes in high-risk pregnancies and reproductive medicine with a focus on infertility treatments.'),
(3, 'doctor', 'Dr Farah Khan', 'farah@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345603', '1987-09-10', 'Female', 'Pahartali, Chittagong', NULL, 'Dermatology', 'BMDC-D-1203', 'Chittagong Medical College Hospital', '9 AM - 4 PM', 'uploads/profile_images/(female) (3).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, DDV, Fellowship (Singapore)', 10, '9 AM - 12 PM, 2 PM - 4 PM', 'Bangla, English', 'Dr. Farah is a skilled dermatologist known for her expertise in cosmetic dermatology and skin disorders.'),
(4, 'doctor', 'Dr Samira Ahmed', 'samira@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345604', '1982-12-05', 'Female', 'Medical College Road, Sylhet', NULL, 'Pediatrics', 'BMDC-P-1204', 'Sylhet MAG Osmani Medical College Hospital', '8 AM - 3 PM', 'uploads/profile_images/(female) (4).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Pediatrics), Fellowship (USA)', 14, '8 AM - 11 AM, 12 PM - 3 PM', 'Bangla, English', 'Dr. Samira has extensive experience in pediatric care and specializes in childhood development disorders.'),
(5, 'doctor', 'Dr Tasnim Haque', 'tasnim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345605', '1984-08-18', 'Female', 'Medical College Road, Rajshahi', NULL, 'Psychiatry', 'BMDC-P-1205', 'Rajshahi Medical College Hospital', '10 AM - 5 PM', 'uploads/profile_images/(female) (5).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Psychiatry), Fellowship (UK)', 13, '10 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Tasnim is dedicated to mental health awareness and treatment, with expertise in mood disorders and anxiety management.'),
(6, 'doctor', 'Dr Rubina Akter', 'rubina@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345606', '1986-04-25', 'Female', 'Band Road, Rangpur', NULL, 'Endocrinology', 'BMDC-E-1206', 'Rangpur Medical College Hospital', '9 AM - 4 PM', 'uploads/profile_images/(female) (6).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Endocrinology), Fellowship (India)', 11, '9 AM - 12 PM, 1 PM - 4 PM', 'Bangla, English, Hindi', 'Dr. Rubina specializes in diabetes management and thyroid disorders with a focus on metabolic diseases.'),
(7, 'doctor', 'Dr Nafisa Hossain', 'nafisa@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345607', '1988-01-30', 'Female', 'Barishal City', NULL, 'Ophthalmology', 'BMDC-O-1207', 'Sher-e-Bangla Medical College Hospital, Barishal', '10 AM - 6 PM', 'uploads/profile_images/(female) (7).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Ophthalmology), Fellowship (Malaysia)', 9, '10 AM - 1 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Nafisa is an expert in retinal surgery and comprehensive eye care with special interest in pediatric ophthalmology.'),
(8, 'doctor', 'Dr Karim Uddin', 'karim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345608', '1975-07-12', 'Male', 'Dhanmondi 27, Dhaka', NULL, 'Cardiology', 'BMDC-C-1208', 'Ibrahim Cardiac Hospital, Dhaka', '9 AM - 5 PM', 'uploads/profile_images/(male) (2).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, FCPS (Cardiology), Fellowship (USA)', 20, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Karim is a pioneer in interventional cardiology with expertise in complex cardiac procedures.'),
(9, 'doctor', 'Dr Rahim Khan', 'rahim@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345609', '1978-03-25', 'Male', 'Agrabad, Chittagong', NULL, 'Neurology', 'BMDC-N-1209', 'Chittagong Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (3).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Neurology), Fellowship (UK)', 18, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Rahim is a leading neurologist specializing in stroke management and neurodegenerative disorders.'),
(10, 'doctor', 'Dr Mahbub Hassan', 'mahbub@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345610', '1980-05-15', 'Male', 'Mirpur-10, Dhaka', NULL, 'Orthopedics', 'BMDC-O-1210', 'National Institute of Traumatology & Orthopedic Rehabilitation', '9 AM - 5 PM', 'uploads/profile_images/(male) (4).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MS (Orthopedics), Fellowship (Germany)', 16, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Mahbub is an experienced orthopedic surgeon specializing in joint replacement surgeries.'),
(11, 'doctor', 'Dr Zahid Hasan', 'zahid@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345611', '1979-08-20', 'Male', 'Medical College Road, Mymensingh', NULL, 'Pulmonology', 'BMDC-P-1211', 'Mymensingh Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (10).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Chest Diseases), Fellowship (India)', 17, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Zahid is a renowned pulmonologist with expertise in respiratory diseases and sleep disorders.'),
(12, 'doctor', 'Dr Masud Rahman', 'masud@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345612', '1982-11-08', 'Male', 'Rangpur City', NULL, 'Urology', 'BMDC-U-1212', 'Rangpur Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (11).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MS (Urology), Fellowship (Thailand)', 14, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Masud specializes in minimally invasive urological surgeries and kidney disorders.'),
(13, 'doctor', 'Dr Faisal Ahmed', 'faisal.ahmed@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345613', '1984-03-20', 'Male', 'Sylhet City', NULL, 'Gastroenterology', 'BMDC-G-1213', 'North East Medical College Hospital', '10 AM - 6 PM', 'uploads/profile_images/(male) (12).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Gastroenterology), Fellowship (Japan)', 12, '10 AM - 2 PM, 3 PM - 6 PM', 'Bangla, English', 'Dr. Faisal is an expert in advanced endoscopy and liver diseases.'),
(14, 'doctor', 'Dr Anwar Hossain', 'anwar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345614', '1977-07-15', 'Male', 'Khulna City', NULL, 'Oncology', 'BMDC-O-1214', 'Khulna Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (13).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Medical Oncology), Fellowship (Singapore)', 19, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Anwar specializes in cancer treatment and chemotherapy protocols.'),
(15, 'doctor', 'Dr Shahriar Islam', 'shahriar@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345615', '1983-09-28', 'Male', 'Central Road, Bogura', NULL, 'ENT', 'BMDC-E-1215', 'Shaheed Ziaur Rahman Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (14).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MS (Otolaryngology), Fellowship (Malaysia)', 15, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Shahriar is an ENT specialist with expertise in microsurgery and cochlear implants.'),
(16, 'doctor', 'Dr Mohsin Ali', 'mohsin@example.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345616', '1981-12-05', 'Male', 'Station Road, Comilla', NULL, 'Nephrology', 'BMDC-N-1216', 'Comilla Medical College Hospital', '9 AM - 5 PM', 'uploads/profile_images/(male) (15).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', 'MBBS, MD (Nephrology), Fellowship (India)', 16, '9 AM - 1 PM, 2 PM - 5 PM', 'Bangla, English', 'Dr. Mohsin is a kidney specialist focusing on dialysis and transplant care.'),
(17, 'patient', 'Asif Ahmed', 'asif@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01770396222', '1990-05-15', 'Male', 'Mohammadpur, Dhaka', 'No major health issues', NULL, NULL, NULL, NULL, 'uploads/profile_images/(male) (16).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', NULL, NULL, NULL, 'Bangla, English', NULL),
(18, 'patient', 'Maliha Rahman', 'maliha@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345631', '1988-08-20', 'Female', 'Uttara, Dhaka', 'Mild asthma', NULL, NULL, NULL, NULL, 'uploads/profile_images/(female) (4).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', NULL, NULL, NULL, 'Bangla, English', NULL),
(19, 'patient', 'Imran Khan', 'imran@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345632', '1992-03-10', 'Male', 'Gulshan, Dhaka', 'Type 1 Diabetes', NULL, NULL, NULL, NULL, 'uploads/profile_images/(male) (17).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', NULL, NULL, NULL, 'Bangla, English', NULL),
(20, 'patient', 'Nabila Haque', 'nabila@gmail.com', '$2y$10$YPkBGw1fH3qzHnagz52pveBYpOV736ek2WFmpXJBaG7QVjays77Hy', '01712345633', '1995-11-25', 'Female', 'Banani, Dhaka', 'No major health issues', NULL, NULL, NULL, NULL, 'uploads/profile_images/(female) (5).jpg', '2024-01-01 06:00:00', NULL, NULL, '2025-05-14 15:53:26', NULL, NULL, NULL, 'Bangla, English', NULL),
(22, 'admin', 'admin', 'admin@medilinx.com', '$2y$10$G5c4/fvpMVXP0nmsekKzYOzlOb1FEmki9tJxb9ZFJS5iSiZVXctau', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-14 16:47:19', NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointment_patient` (`patient_id`),
  ADD KEY `fk_appointment_doctor` (`doctor_id`),
  ADD KEY `fk_appointment_slot` (`slot_id`);

--
-- Indexes for table `degrees`
--
ALTER TABLE `degrees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_degree_doctor` (`doctor_id`);

--
-- Indexes for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_posts_doctor` (`doctor_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_post_like` (`post_id`,`user_id`),
  ADD KEY `fk_like_user` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_doctor` (`doctor_id`),
  ADD KEY `fk_review_patient` (`patient_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_slot_doctor` (`doctor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `degrees`
--
ALTER TABLE `degrees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointment_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointment_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `degrees`
--
ALTER TABLE `degrees`
  ADD CONSTRAINT `fk_degree_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `fk_like_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `fk_slot_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
