<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'user_authentication');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'asifahamedstudent@gmail.com');
define('SMTP_PASSWORD', 'nsxj nitr rumm xrei');
define('SMTP_PORT', 465);

// Common functions
function connectDB() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "user_authentication"; //  database name

    $conn = mysqli_connect($host, $username, $password, $database);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>