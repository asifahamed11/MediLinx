<?php
session_start();

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the Composer autoloader
require 'vendor/autoload.php';

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

// Basic validation
if (empty($email)) {
    echo "Email is required.";
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($user_id, $username);
    $stmt->fetch();
    // Generate new 6-digit reset PIN
    $reset_pin = rand(100000, 999999);

    // Insert or update the reset PIN in the database
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, reset_pin) VALUES (?, ?) ON DUPLICATE KEY UPDATE reset_pin = ?");
    $stmt->bind_param("iss", $user_id, $reset_pin, $reset_pin);
    if ($stmt->execute()) {
        // Send new reset PIN via email using PHPMailer
        $reset_message = "Hello " . htmlspecialchars($username) . ",\n\nYou have requested to reset your password. Your reset PIN is: " . $reset_pin . "\n\nPlease enter this PIN in the reset password page to set a new password.\n\nIf you did not request this, please ignore this email.";
        $reset_subject = "MediLinx Password Reset PIN";

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'asifahamedstudent@gmail.com'; // SMTP username
            $mail->Password   = 'nsxj nitr rumm xrei'; // SMTP password
            $mail->SMTPSecure = 'ssl'; // or 'tls'
            $mail->Port       = 465; // or 587 for TLS

            // Recipients
            $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
            $mail->addAddress($email, $username);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $reset_subject;
            $mail->Body    = $reset_message;

            $mail->send();
        } catch (Exception $e) {
            echo "Failed to send reset PIN: " . $mail->ErrorInfo;
            exit;
        }
        echo "A new password reset PIN has been sent to your email address.";
    } else {
        echo "Error updating reset PIN.";
    }
} else {
    echo "Email does not exist.";
}
$stmt->close();
$conn->close();
?>