<?php
session_start();
require_once 'config.php';

// Database configuration
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "user_authentication";

try {
    // Create connection
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed");
    }

    // Get and validate PIN
    $pin = trim($_POST['pin'] ?? '');
    
    if (!preg_match('/^[0-9]{6}$/', $pin)) {
        throw new Exception("Invalid PIN format");
    }

    // Set timezone
    date_default_timezone_set('Asia/Dhaka');
    $current_time = date("Y-m-d H:i:s");

    // Check PIN validity
    $stmt = $conn->prepare("SELECT id FROM users WHERE email_verification_pin = ? AND email_verified_at IS NULL");
    if (!$stmt) {
        throw new Exception("Database error");
    }

    $stmt->bind_param("s", $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Update verification status
        $update_stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_pin = NULL WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Database error");
        }

        $update_stmt->bind_param("i", $row['id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Email verified successfully!";
            header("Location: login.php");
            exit;
        } else {
            throw new Exception("Error updating verification status");
        }
    } else {
        throw new Exception("Invalid verification PIN");
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: pin_verification.html");
    exit;
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>