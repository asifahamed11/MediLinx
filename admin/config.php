<?php
// config.php
// At the top of config.php
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
    $database = "medilinx"; //  database name updated to match main config

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
