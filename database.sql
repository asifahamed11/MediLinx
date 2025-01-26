-- Create the database
CREATE DATABASE user_authentication;

-- Use the database
USE user_authentication;

-- Create the users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('patient', 'doctor') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address VARCHAR(255),
    medical_history TEXT,
    profile_image VARCHAR(255),
    specialty VARCHAR(100),
    degrees_and_certifications TEXT,
    years_of_experience INT,
    medical_license_number VARCHAR(50),
    work_address VARCHAR(255),
    available_consultation_hours TEXT,
    languages_spoken VARCHAR(255),
    professional_biography TEXT,
    email_verified_at DATETIME DEFAULT NULL,
    verification_token VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the password_resets table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reset_token VARCHAR(50) NOT NULL,
    expiry_time DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);