<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, role, username, password, email_verified_at, email_verification_pin, email_verification_pin_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if verification PIN has expired
        if (
            $user['email_verification_pin'] !== null &&
            $user['email_verification_pin_expiry'] !== null &&
            strtotime($user['email_verification_pin_expiry']) < time()
        ) {
            // Generate new PIN if expired
            $pin = sprintf("%06d", mt_rand(0, 999999));
            $pin_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $update_stmt = $conn->prepare("UPDATE users SET email_verification_pin = ?, email_verification_pin_expiry = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $pin, $pin_expiry, $user['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Send new PIN email
            require 'vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = 'ssl';
                $mail->Port = SMTP_PORT;

                $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
                $mail->addAddress($email, $user['username']);
                $mail->Subject = "New MediLinx Email Verification PIN";
                $mail->Body = "Hello " . htmlspecialchars($user['username']) . ",\n\nYour previous verification PIN has expired. Your new verification PIN is: " . $pin . "\n\nThis PIN will expire in 24 hours.\n\nIf you did not request this, please contact support.";
                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send new PIN email: " . $e->getMessage());
            }
        }

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
