<?php
// Debug at the very start
echo "Script started<br>";

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "Session started<br>";
echo "PHP Error Log: " . ini_get('error_log') . "<br>";

// Debug POST data
echo "<pre>POST data: ";
print_r($_POST);
echo "</pre>";

// Debug FILES data
echo "<pre>FILES data: ";
print_r($_FILES);
echo "</pre>";

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "PHPMailer classes imported<br>";

// Include the Composer autoloader
require 'vendor/autoload.php';
echo "Autoloader included<br>";

// Database configuration
$servername = "localhost";
$username_db = "root"; // MySQL username
$password_db = ""; // MySQL password
$dbname = "user_authentication";

echo "Database config set<br>";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connected successfully<br>";

// Get form data
$role = $_POST['role'] ?? '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
$gender = isset($_POST['gender']) ? $_POST['gender'] : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$medical_history = isset($_POST['medical_history']) ? trim($_POST['medical_history']) : '';
$specialty = isset($_POST['specialty']) ? trim($_POST['specialty']) : '';
$degrees_and_certifications = isset($_POST['degrees_and_certifications']) ? trim($_POST['degrees_and_certifications']) : '';
$years_of_experience = isset($_POST['years_of_experience']) ? intval($_POST['years_of_experience']) : 0;
$medical_license_number = isset($_POST['medical_license_number']) ? trim($_POST['medical_license_number']) : '';
$work_address = isset($_POST['work_address']) ? trim($_POST['work_address']) : '';
$available_consultation_hours = isset($_POST['available_consultation_hours']) ? trim($_POST['available_consultation_hours']) : '';
$languages_spoken = isset($_POST['languages_spoken']) ? trim($_POST['languages_spoken']) : '';
$professional_biography = isset($_POST['professional_biography']) ? trim($_POST['professional_biography']) : '';

echo "Form data processed: Role: $role, Username: $username, Email: $email<br>";

// Basic validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    echo "All fields are required.";
    exit;
}

if ($password !== $confirm_password) {
    echo "Passwords do not match.";
    exit;
}

echo "Basic validation passed<br>";

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Email already exists.";
    header("Location: login.php");
    exit;
}
$stmt->close();

echo "Email check passed<br>";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Password hashed<br>";

// Validate image
function validateImage($file) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    return in_array($mime_type, $allowed_mime_types);
}

// Default profile image path
$profile_image_path = 'uploads/default_profile.png';

// Check if the file is uploaded
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $profile_image = $_FILES['profile_image'];

    $target_dir = "uploads/profile_images/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
        echo "Created directory: $target_dir<br>";
    }

    // Extract and validate file extension
    $extension = strtolower(pathinfo($profile_image['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $allowed_extensions)) {
        die("Invalid file type.");
    }

    // Generate unique filename and move the file
    $unique_filename = uniqid() . '_profile.' . $extension;
    $target_file = $target_dir . $unique_filename;

    if (validateImage($profile_image) && move_uploaded_file($profile_image['tmp_name'], $target_file)) {
        $profile_image_path = $target_file;
        echo "Image uploaded to: $profile_image_path<br>";
    } else {
        echo "Invalid file or upload failed.<br>";
        // Continue with default image instead of dying
    }
}

// Generate 6-digit PIN
$pin = rand(100000, 999999);
echo "Generated PIN: $pin<br>";

// Insert user into database with PIN
if ($role === 'patient') {
    echo "Processing patient registration<br>";
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, medical_history, profile_image, email_verification_pin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $address, $medical_history, $profile_image_path, $pin);
    
    // Execute the statement
    if (!$stmt->execute()) {
        echo "Error in patient registration: " . $stmt->error . "<br>";
        exit;
    }
    echo "Patient user inserted successfully<br>";
    
} else if ($role === 'doctor') {
    echo "Processing doctor registration<br>";
    $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, specialty, degrees_and_certifications, years_of_experience, medical_license_number, work_address, available_consultation_hours, languages_spoken, profile_image, professional_biography, email_verification_pin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssisssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $specialty, $degrees_and_certifications, $years_of_experience, $medical_license_number, $work_address, $available_consultation_hours, $languages_spoken, $profile_image_path, $professional_biography, $pin);
    
    if (!$stmt->execute()) {
        echo "Error in doctor registration: " . $stmt->error . "<br>";
        exit;
    }
    echo "Doctor user inserted successfully<br>";
    
    $doctor_id = $conn->insert_id; // Get the ID of the newly inserted doctor
    echo "Doctor ID: $doctor_id<br>";
    
    // Insert degrees
    if (isset($_POST['degree_name']) && !empty($_POST['degree_name'])) {
        echo "Processing degrees<br>";
        $degreeStmt = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['degree_name'] as $index => $degreeName) {
            $institution = $_POST['institution'][$index];
            $year = $_POST['passing_year'][$index];
            $degreeStmt->bind_param("issi", $doctor_id, $degreeName, $institution, $year);
            if (!$degreeStmt->execute()) {
                // Handle degree insertion error
                echo "Error inserting degree: " . $degreeStmt->error . "<br>";
            } else {
                echo "Degree inserted: $degreeName<br>";
            }
        }
        $degreeStmt->close();
    }
} else {
    echo "Invalid role: $role<br>";
    exit;
}

echo "User insertion complete, preparing to send email<br>";

// Continue with email sending...
// Send PIN via email using PHPMailer
$pin_message = "Hello " . htmlspecialchars($username) . ",\n\nThank you for registering with MediLinx. Your verification PIN is: " . $pin . "\n\nPlease enter this PIN in the verification page to verify your email address.\n\nIf you did not sign up for this account, please ignore this email.";
$pin_subject = "MediLinx Email Verification PIN";

$mail = new PHPMailer(true);
try {
    echo "Setting up email<br>";
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // SMTP server
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

    echo "Sending email to $email<br>";
    $mail->send();
    echo "Email sent successfully<br>";
} catch (Exception $e) {
    echo "Registration successful, but failed to send verification PIN: " . $mail->ErrorInfo . "<br>";
}

$stmt->close();
$conn->close();
echo "Database connection closed<br>";

// After successful registration and email sending
echo "Registration complete, redirecting to verify_pin.php<br>";
header("Location: verify_pin.php");
exit;
?>