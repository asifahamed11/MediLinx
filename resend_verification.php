<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Basic validation
if (empty($email)) {
    echo "Email is required.";
    exit;
}

// Check if email exists and is not verified
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND email_verified_at IS NULL");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $username);
    $stmt->fetch();
    // Generate new PIN
    $pin = rand(100000, 999999);
    $pin_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Update the PIN and expiry in the database
    $stmt->close();
    $stmt = $conn->prepare("UPDATE users SET email_verification_pin = ?, email_verification_pin_expires_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $pin, $pin_expiry, $id);
    if ($pin_expiry > date("Y-m-d H:i:s")) {
        // Send new PIN via email using PHPMailer
        $pin_message = "Hello " . htmlspecialchars($username) . ",\n\nYou have requested a new verification PIN. Your new PIN is: " . $pin . "\n\nPlease enter this PIN in the verification page to verify your email address.\n\nIf you did not request this, please ignore this email.";
        $pin_subject = "MediLinx Email Verification PIN";

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
            $mail->Subject = $pin_subject;
            $mail->Body    = $pin_message;

            $mail->send();
        } catch (Exception $e) {
            echo "Failed to send verification PIN: " . $mail->ErrorInfo;
            exit;
        }
    }
    if ($stmt->execute()) {
        echo "A new verification PIN has been sent to your email address.";
    } else {
        echo "Error updating verification PIN.";
    }
} else {
    echo "Email is already verified or does not exist.";
}
$stmt->close();
$conn->close();
?>