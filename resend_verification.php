<?php
session_start();
require_once 'config.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

try {
    // Database configuration
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed");
    }

    // Get and validate email
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception("Invalid email format");
    }

    // Check if email exists and is not verified
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND email_verified_at IS NULL");
    if (!$stmt) {
        throw new Exception("Database error");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate new 6-digit PIN
        $pin = sprintf("%06d", mt_rand(100000, 999999));

        // Update PIN in database
        $update_stmt = $conn->prepare("UPDATE users SET email_verification_pin = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Database error");
        }

        $update_stmt->bind_param("si", $pin, $user['id']);
        
        if ($update_stmt->execute()) {
            // Configure PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;

            // Recipients
            $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
            $mail->addAddress($email, $user['username']);

            // Content
            $mail->isHTML(false);
            $mail->Subject = "MediLinx Email Verification PIN";
            $mail->Body = "Hello " . htmlspecialchars($user['username']) . ",\n\n"
                       . "Your new verification PIN is: " . $pin . "\n\n"
                       . "This PIN will expire in 15 minutes.\n\n"
                       . "If you did not request this, please ignore this email.";

            $mail->send();
            
            $_SESSION['success'] = "New verification PIN has been sent to your email";
            header("Location: pin_verification.html");
            exit;
        }
    } else {
        throw new Exception("Email not found or already verified");
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: resend_pin.html");
    exit;
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>