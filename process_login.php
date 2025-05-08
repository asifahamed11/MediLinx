<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, role, username, password, email_verified_at, email_verification_pin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Store essential information in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['verification_email'] = $email;

            // Check verification status
            if ($user['email_verification_pin'] !== null) {
                // User needs to verify email
                $_SESSION['needs_verification'] = true;
                header("Location: verify_pin.php");
                exit;
            } else {
                // User is verified, redirect to dashboard
                unset($_SESSION['needs_verification']); // Clear any verification flags
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit;
    }

    $stmt->close();
    $conn->close();
}
