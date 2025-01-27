<?php
session_start();

// Database configuration
$servername = "localhost";
$username_db = "root"; // Replace with your MySQL username
$password_db = "";     // Replace with your MySQL password
$dbname = "user_authentication";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$email = trim($_POST['email']);
$reset_pin = trim($_POST['reset_pin']);
$new_password = $_POST['new_password'];
$confirm_new_password = $_POST['confirm_new_password'];

// Basic validation
if (empty($email) || empty($reset_pin) || empty($new_password) || empty($confirm_new_password)) {
    echo "All fields are required.";
    exit;
}

if ($new_password !== $confirm_new_password) {
    echo "Passwords do not match.";
    exit;
}

// Retrieve user with matching reset PIN and email
$stmt = $conn->prepare("SELECT pr.user_id, pr.reset_pin_expires_at FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.reset_pin = ? AND u.email = ?");
$stmt->bind_param("ss", $reset_pin, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $pin_expiry);
    $stmt->fetch();
    // Check if PIN has expired
    if (strtotime($pin_expiry) < time()) {
        echo "Reset PIN has expired. Please initiate a new password reset.";
        exit;
    }
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    // Update the user's password
    $stmt->close();
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    if ($stmt->execute()) {
        // Delete the reset PIN
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE reset_pin = ?");
        $stmt->bind_param("s", $reset_pin);
        $stmt->execute();
        echo "Password updated successfully. <a href='login.html'>Login here</a>.";
    } else {
        echo "Error updating password.";
    }
} else {
    echo "Invalid or expired PIN.";
}
$stmt->close();
$conn->close();
?>