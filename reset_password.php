<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = "";     // Replace with your MySQL password
$dbname = "user_authentication";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$token = $_POST['token'];
$new_password = $_POST['new_password'];
$confirm_new_password = $_POST['confirm_new_password'];

// Basic validation
if (empty($new_password) || empty($confirm_new_password)) {
    echo "All fields are required.";
    exit;
}

if ($new_password !== $confirm_new_password) {
    echo "Passwords do not match.";
    exit;
}

// Check if token exists and is valid
$stmt = $conn->prepare("SELECT user_id, expiry_time FROM password_resets WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $expiry_time);
    $stmt->fetch();
    if (strtotime($expiry_time) < time()) {
        echo "Reset token has expired.";
        exit;
    }
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    // Update the user's password
    $stmt->close();
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    if ($stmt->execute()) {
        // Delete the reset token
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        echo "Password updated successfully. <a href='login.html'>Login here</a>.";
    } else {
        echo "Error updating password.";
    }
} else {
    echo "Invalid or expired token.";
}
$stmt->close();
$conn->close();
?>