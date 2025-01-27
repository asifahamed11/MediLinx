<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'user_authentication');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'asifahamedstudent@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'nsxj nitr rumm xrei'); // Replace with your app password
define('SMTP_PORT', 465);

// Security Configuration
define('PIN_EXPIRY_MINUTES', 15);
define('MAX_PIN_ATTEMPTS', 3);
define('PASSWORD_MIN_LENGTH', 8);

// Error Messages
define('ERROR_DB_CONNECTION', 'Database connection error');
define('ERROR_INVALID_PIN', 'Invalid PIN format');
define('ERROR_PIN_EXPIRED', 'PIN has expired');
define('ERROR_MAX_ATTEMPTS', 'Too many attempts');
?>