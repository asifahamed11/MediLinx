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
$email = trim($_POST['email']);
$password = $_POST['password'];

// Basic validation
if (empty($email) || empty($password)) {
    echo "All fields are required.";
    exit;
}

// Retrieve user data
$stmt = $conn->prepare("SELECT id, role, username, password, email_verified_at FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $role, $username, $hashed_password, $email_verified_at);
    $stmt->fetch();
    // Verify password
    if (password_verify($password, $hashed_password)) {
        if ($email_verified_at === NULL) {
            echo "Please verify your email before logging in.";
            exit;
        }
        // Password is correct and email is verified
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Invalid email or password.";
    }
} else {
    echo "Invalid email or password.";
}
$stmt->close();
$conn->close();
?>