<?php
// config.php

// Configure secure session settings before any session operations
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');

    // Set session garbage collection
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);

    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medilinx');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'medilinxx@gmail.com');
define('SMTP_PASSWORD', 'nzxf ckbm eswr oegw');
define('SMTP_PORT', 465);

date_default_timezone_set('UTC');

// Common functions
function connectDB()
{
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "medilinx"; //  database name

    $conn = mysqli_connect($host, $username, $password, $database);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $conn;
}

function redirect($path)
{
    header("Location: $path");
    exit;
}

function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
