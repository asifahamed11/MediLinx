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

// Get token from URL
$token = $_GET['token'];

// Check if token exists and is valid
$stmt = $conn->prepare("SELECT id, username FROM users WHERE verification_token = ? AND email_verified_at IS NULL");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $username);
    $stmt->fetch();
    // Update the user's email_verified_at to current time
    $stmt->close();
    $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "Your email has been verified successfully. <a href='login.html'>Login here</a>.";
    } else {
        echo "Error updating verification status.";
    }
} else {
    echo "Invalid or expired verification link.";
}
$stmt->close();
$conn->close();
?>