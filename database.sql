-- database.sql
CREATE DATABASE IF NOT EXISTS user_authentication;
USE user_authentication;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('patient', 'doctor') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    -- Patient specific fields
    address VARCHAR(255),
    medical_history TEXT,
    -- Doctor specific fields
    specialty VARCHAR(100),
    medical_license_number VARCHAR(50),
    work_address VARCHAR(255),
    consultation_hours TEXT,
    -- Common fields
    profile_image VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    verification_pin CHAR(6),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);