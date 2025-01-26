import os
import json

def create_project_structure():
    """
    Create a PHP project structure similar to the given specification
    """
    # Project root directory
    project_root = 'project'
    os.makedirs(project_root, exist_ok=True)

    # Main PHP files
    main_files = [
        'index.php',
        'patient_register.php',
        'doctor_register.php',
        'register.php',
        'login.html',
        'login.php',
        'dashboard.php',
        'logout.php',
        'verify.php',
        'resend_verification.php',
        'forgot_password.html',
        'forgot_password.php',
        'reset_password.html',
        'reset_password.php'
    ]

    # Create main PHP files with basic content
    for filename in main_files:
        filepath = os.path.join(project_root, filename)
        with open(filepath, 'w') as f:
            if filename.endswith('.php'):
                f.write("<?php\n# TODO: Implement " + filename + " functionality\n?>")
            elif filename.endswith('.html'):
                f.write("<!DOCTYPE html>\n<html>\n<head>\n    <title>" + 
                        filename.replace('.html', '') + "</title>\n" + 
                        '    <link rel="stylesheet" href="styles/styles.css">\n' +
                        "</head>\n<body>\n    <!-- TODO: Add content -->\n</body>\n</html>")

    # Create styles directory and CSS file
    styles_dir = os.path.join(project_root, 'styles')
    os.makedirs(styles_dir, exist_ok=True)
    with open(os.path.join(styles_dir, 'styles.css'), 'w') as f:
        f.write("/* Basic CSS styles */\nbody {\n    font-family: Arial, sans-serif;\n}")

    # Create uploads directories
    uploads_root = os.path.join(project_root, 'uploads')
    os.makedirs(os.path.join(uploads_root, 'profile_images'), exist_ok=True)
    os.makedirs(os.path.join(uploads_root, 'documents'), exist_ok=True)

    # Create vendor directory with placeholder autoload
    vendor_dir = os.path.join(project_root, 'vendor')
    os.makedirs(vendor_dir, exist_ok=True)
    with open(os.path.join(vendor_dir, 'autoload.php'), 'w') as f:
        f.write("<?php\n# Placeholder for PHPMailer autoload\n# You would typically use Composer to manage dependencies\n?>")

    # Create database.sql with basic structure
    with open(os.path.join(project_root, 'database.sql'), 'w') as f:
        f.write("""-- Database Setup Script
CREATE DATABASE IF NOT EXISTS medical_portal;
USE medical_portal;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Additional tables would be added here
""")

    print(f"Project structure created in './{project_root}' directory")

def main():
    create_project_structure()

if __name__ == "__main__":
    main()