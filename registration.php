<?php
session_start();

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the Composer autoloader
require 'vendor/autoload.php';

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
$role = $_POST['role'];
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
$gender = isset($_POST['gender']) ? $_POST['gender'] : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$medical_history = isset($_POST['medical_history']) ? trim($_POST['medical_history']) : '';
$profile_image = $_FILES['profile_image'];
$specialty = isset($_POST['specialty']) ? trim($_POST['specialty']) : '';
$degrees_and_certifications = isset($_POST['degrees_and_certifications']) ? trim($_POST['degrees_and_certifications']) : '';
$years_of_experience = isset($_POST['years_of_experience']) ? intval($_POST['years_of_experience']) : 0;
$medical_license_number = isset($_POST['medical_license_number']) ? trim($_POST['medical_license_number']) : '';
$work_address = isset($_POST['work_address']) ? trim($_POST['work_address']) : '';
$available_consultation_hours = isset($_POST['available_consultation_hours']) ? trim($_POST['available_consultation_hours']) : '';
$languages_spoken = isset($_POST['languages_spoken']) ? trim($_POST['languages_spoken']) : '';
$professional_biography = isset($_POST['professional_biography']) ? trim($_POST['professional_biography']) : '';

// Basic validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    echo "All fields are required.";
    exit;
}

if ($password !== $confirm_password) {
    echo "Passwords do not match.";
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Email already exists.";
    exit;
}
$stmt->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Solution: Add proper file validation
function validateImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed)) {
        return false;
    }
    if ($file['size'] > $max_size) {
        return false;
    }
    return true;
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    if (!validateImage($_FILES['profile_image'])) {
        die('Invalid file type or size');
    }
    $target_dir = "uploads/profile_images/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $target_file = $target_dir . basename($profile_image["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Allow only certain file types
    $allowed_types = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_types)) {
        echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
        exit;
    }

    // Move the uploaded file to the target directory
    if (move_uploaded_file($profile_image["tmp_name"], $target_file)) {
        // Success
    } else {
        echo "Error uploading profile image.";
        exit;
    }
} else {
    // Optional: Handle case where no image is uploaded
    $target_file = '';
}

// Generate 6-digit PIN
$pin = rand(100000, 999999);

// Insert user into database with PIN (no expiry)
if ($role === 'patient') {
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, medical_history, profile_image, email_verification_pin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $address, $medical_history, $profile_image, $pin);
} else if ($role === 'doctor') {
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, specialty, degrees_and_certifications, years_of_experience, medical_license_number, work_address, available_consultation_hours, languages_spoken, profile_image, professional_biography, email_verification_pin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssiisssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $specialty, $degrees_and_certifications, $years_of_experience, $medical_license_number, $work_address, $available_consultation_hours, $languages_spoken, $profile_image, $professional_biography, $pin);
} else {
    echo "Invalid role.";
    exit;
}

if ($stmt->execute()) {
    // Send PIN via email using PHPMailer
    $pin_message = "Hello " . htmlspecialchars($username) . ",\n\nThank you for registering with MediLinx. Your verification PIN is: " . $pin . "\n\nPlease enter this PIN in the verification page to verify your email address.\n\nIf you did not sign up for this account, please ignore this email.";
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
        echo "Registration successful, but failed to send verification PIN: " . $mail->ErrorInfo;
        exit;
    }

    // After successful registration and email sending
    header("Location: verify_pin.php");
    exit;
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>