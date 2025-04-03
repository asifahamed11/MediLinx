CREATE TABLE `time_slots` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` INT(11) NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `status` ENUM('available', 'booked', 'cancelled') DEFAULT 'available',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `appointments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) NOT NULL,
  `slot_id` INT(11) NOT NULL,
  `reason` TEXT,
  `status` ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`slot_id`) REFERENCES `time_slots`(`id`)
);

CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('appointment', 'system', 'reminder') NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Create degrees table
CREATE TABLE `degrees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` INT(11) NOT NULL,
  `degree_name` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(255) NOT NULL,
  `passing_year` YEAR NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('patient','doctor') NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
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
  `available_consultation_hours` varchar(500) NOT NULL,
  `languages_spoken` varchar(255) DEFAULT NULL,
  `professional_biography` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
