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
$pin = trim($_POST['pin']);

// Basic validation
if (empty($pin)) {
    echo "PIN is required.";
    exit;
}

// Retrieve user with matching PIN and not yet verified
$stmt = $conn->prepare("SELECT id, username, email_verification_pin_expires_at FROM users WHERE email_verification_pin = ? AND email_verified_at IS NULL");
$stmt->bind_param("s", $pin);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $username, $pin_expiry);
    $stmt->fetch();
    // Check if PIN has expired
    if (strtotime($pin_expiry) < time()) {
        echo "Verification PIN has expired. Please request a new PIN.";
        exit;
    }
    // Update user's email_verified_at to current time
    $stmt->close();
    $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_pin = NULL, email_verification_pin_expires_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Redirect to login page
        header("Location: login.html");
        exit;
    } else {
        echo "Error updating verification status.";
    }
} else {
    echo "Invalid or expired PIN.";
}
$stmt->close();
$conn->close();
?>