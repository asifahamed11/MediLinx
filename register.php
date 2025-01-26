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


// Handle profile image upload
if ($profile_image['error'] === 0) {
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

// Set PIN expiry time (e.g., 1 hour from now)
$pin_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Insert user into database with PIN and expiry
if ($role === 'patient') {
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, medical_history, profile_image, email_verification_pin, email_verification_pin_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $address, $medical_history, $profile_image, $pin, $pin_expiry);
} else if ($role === 'doctor') {
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, specialty, degrees_and_certifications, years_of_experience, medical_license_number, work_address, available_consultation_hours, languages_spoken, profile_image, professional_biography, email_verification_pin, email_verification_pin_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssiissssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $specialty, $degrees_and_certifications, $years_of_experience, $medical_license_number, $work_address, $available_consultation_hours, $languages_spoped, $profile_image, $professional_biography, $pin, $pin_expiry);
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
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'asifahamedstudent@gmail.com';
        $mail->Password   = 'nsxj nitr rumm xrei';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $pin_subject;
        $mail->Body    = $pin_message;

        $mail->send();
        
        // Store user email in session for verification
        $_SESSION['verification_email'] = $email;
        
        echo "Registration successful. A verification PIN has been sent to your email address.";
        // Redirect to pin verification page after 2 seconds
        echo "<script>
            setTimeout(function() {
                window.location.href = 'pin_verification.html';
            }, 2000);
        </script>";
        exit;
        
    } catch (Exception $e) {
        error_log("Registration successful, but failed to send verification PIN: " . $e->getMessage());
        echo "Registration successful, but failed to send verification PIN. Please contact support.";
        exit;
    }
} else {
    // Log the error message
    error_log("Error inserting user into the database: " . $stmt->error);

    // Inform the user without displaying the actual error
    echo "Registration failed. Please try again later.";
}
$stmt->close();
$conn->close();
?>