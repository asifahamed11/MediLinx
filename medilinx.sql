CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `slot_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('confirmed','completed','cancelled') DEFAULT 'confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_appointment_patient` (`patient_id`),
  KEY `fk_appointment_doctor` (`doctor_id`),
  KEY `fk_appointment_slot` (`slot_id`),
  CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appointment_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `degrees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `degree_name` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `passing_year` year(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_degree_doctor` (`doctor_id`),
  CONSTRAINT `fk_degree_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','system','reminder') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notification_user` (`user_id`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_posts_doctor` (`doctor_id`),
  CONSTRAINT `fk_posts_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_like` (`post_id`,`user_id`),
  KEY `fk_like_user` (`user_id`),
  CONSTRAINT `fk_like_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_review_doctor` (`doctor_id`),
  KEY `fk_review_patient` (`patient_id`),
  CONSTRAINT `fk_review_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('available','booked','cancelled') DEFAULT 'available',
  `capacity` int(11) DEFAULT 20,
  `booked_count` int(11) DEFAULT 0,
  `slot_duration` int(11) DEFAULT 30 COMMENT 'Duration of each appointment slot in minutes',
  PRIMARY KEY (`id`),
  KEY `fk_slot_doctor` (`doctor_id`),
  CONSTRAINT `fk_slot_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `degrees_and_certifications` varchar(400) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `available_consultation_hours` varchar(500) DEFAULT NULL,
  `languages_spoken` varchar(255) DEFAULT NULL,
  `professional_biography` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;