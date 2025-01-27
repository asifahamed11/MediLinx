<?php
session_start();

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

try {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "user_authentication";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed");
    }

    // Get and validate email
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception("Invalid email format");
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate 6-digit PIN
        $reset_pin = sprintf("%06d", mt_rand(100000, 999999));

        // Store PIN in password_resets table
        $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, reset_pin, created_at) 
                                     VALUES (?, ?, NOW()) 
                                     ON DUPLICATE KEY UPDATE reset_pin = ?, created_at = NOW()");
        if (!$insert_stmt) {
            throw new Exception("Database error");
        }

        $insert_stmt->bind_param("iss", $user['id'], $reset_pin, $reset_pin);
        
        if ($insert_stmt->execute()) {
            // Send PIN via email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'asifahamedstudent@gmail.com'; // SMTP username
            $mail->Password   = 'nsxj nitr rumm xrei'; // SMTP password
            $mail->SMTPSecure = 'ssl'; // or 'tls'
            $mail->Port       = 465; // or 587 for TLS

            // Recipients
            $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
            $mail->addAddress($email, $user['username']);

            // Content
            $mail->isHTML(false);
            $mail->Subject = "MediLinx Password Reset PIN";
            $mail->Body = "Hello " . htmlspecialchars($user['username']) . ",\n\n"
                       . "You have requested to reset your password.\n\n"
                       . "Your password reset PIN is: " . $reset_pin . "\n\n"
                       . "This PIN will expire in 15 minutes.\n\n"
                       . "If you did not request this reset, please ignore this email.";

            $mail->send();
            
            $_SESSION['success'] = "A password reset PIN has been sent to your email";
            // Pass email in URL for the reset form
            header("Location: reset_password.html?email=" . urlencode($email));
            exit;
        }
    } else {
        throw new Exception("Email address not found");
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: forgot_password.html");
    exit;
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($insert_stmt)) $insert_stmt->close();
    if (isset($conn)) $conn->close();
}
?>