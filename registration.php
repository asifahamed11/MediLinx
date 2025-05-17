<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config.php';  // Include database configuration

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the Composer autoloader
require 'vendor/autoload.php';


$servername = DB_HOST;
$username_db = DB_USER;
$password_db = DB_PASS;
$dbname = DB_NAME;

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Modern sanitization function to replace deprecated FILTER_SANITIZE_STRING
function sanitizeInput($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Get form data with proper sanitization
$role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : '';
$username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
$gender = isset($_POST['gender']) ? sanitizeInput($_POST['gender']) : '';
$address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
$medical_history = isset($_POST['medical_history']) ? sanitizeInput($_POST['medical_history']) : '';
$specialty = isset($_POST['specialty']) ? sanitizeInput($_POST['specialty']) : '';
$degrees_and_certifications = isset($_POST['degrees_and_certifications']) ? sanitizeInput($_POST['degrees_and_certifications']) : '';
$years_of_experience = isset($_POST['years_of_experience']) ? intval($_POST['years_of_experience']) : 0;
$medical_license_number = isset($_POST['medical_license_number']) ? sanitizeInput($_POST['medical_license_number']) : '';
$work_address = isset($_POST['work_address']) ? sanitizeInput($_POST['work_address']) : '';
$available_consultation_hours = isset($_POST['available_consultation_hours']) ? sanitizeInput($_POST['available_consultation_hours']) : '';
$languages_spoken = isset($_POST['languages_spoken']) ? sanitizeInput($_POST['languages_spoken']) : '';
$professional_biography = isset($_POST['professional_biography']) ? sanitizeInput($_POST['professional_biography']) : '';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format.");
}

// Basic validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    die("All required fields must be filled out.");
}

if (strlen($password) < 8) {
    die("Password must be at least 8 characters long.");
}

if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Validate role
if (!in_array($role, ['patient', 'doctor'])) {
    die("Invalid role selected.");
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        throw new Exception("Email already exists. Please use a different email or log in.");
    }
    $stmt->close();

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        throw new Exception("Username already exists. Please choose a different username.");
    }
    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Improved image validation function
    function validateImage($file)
    {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return false;
        }

        // Check file size (limit to 5MB)
        if ($file['size'] > 5242880) {
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
            if (!mkdir($target_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Validate upload directory is writable
        if (!is_writable($target_dir)) {
            throw new Exception("Upload directory is not writable. Please check permissions.");
        }

        // Extract and validate file extension
        $extension = strtolower(pathinfo($profile_image['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception("Invalid file type. Allowed types: jpg, jpeg, png, gif");
        }

        // Generate unique filename and move the file
        $unique_filename = uniqid() . '_profile.' . $extension;
        $target_file = $target_dir . $unique_filename;

        if (validateImage($profile_image) && move_uploaded_file($profile_image['tmp_name'], $target_file)) {
            $profile_image_path = $target_file;
            // Set proper file permissions after upload
            chmod($target_file, 0644);
        } else {
            throw new Exception("Invalid file or upload failed.");
        }
    }

    // Generate 6-digit PIN (avoiding common patterns)
    do {
        $pin = sprintf("%06d", mt_rand(0, 999999));
    } while (
        // Avoid sequential numbers
        preg_match('/^(\d)\1{5}$/', $pin) || // e.g., 111111
        preg_match('/^(0123|1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321|3210)/', $pin) ||
        $pin === '123123' ||
        $pin === '000000'
    );

    // Set PIN expiration to 24 hours from now
    $pin_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user into database with PIN        
    if ($role === 'patient') {
        $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, address, medical_history, profile_image, email_verification_pin, email_verification_pin_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $role, $username, $email, $hashed_password, $phone, $date_of_birth, $gender, $address, $medical_history, $profile_image_path, $pin, $pin_expiry);

        if (!$stmt->execute()) {
            throw new Exception("Error in patient registration: " . $stmt->error);
        }

        $user_id = $conn->insert_id;
    } else if ($role === 'doctor') {
        $stmt = $conn->prepare("INSERT INTO users (role, username, email, password, phone, date_of_birth, gender, specialty, years_of_experience, medical_license_number, languages_spoken, profile_image, professional_biography, email_verification_pin, email_verification_pin_expiry, degrees_and_certifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "ssssssssississss",
            $role,
            $username,
            $email,
            $hashed_password,
            $phone,
            $date_of_birth,
            $gender,
            $specialty,
            $years_of_experience,
            $medical_license_number,
            $languages_spoken,
            $profile_image_path,
            $professional_biography,
            $pin,
            $pin_expiry,
            $degrees_and_certifications
        );

        if (!$stmt->execute()) {
            throw new Exception("Error in doctor registration: " . $stmt->error);
        }

        $doctor_id = $conn->insert_id;

        // Insert degrees
        if (isset($_POST['degree_name']) && is_array($_POST['degree_name']) && !empty($_POST['degree_name'])) {
            $degreeStmt = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");

            foreach ($_POST['degree_name'] as $index => $degreeName) {
                // Validate array keys exist
                if (!isset($_POST['institution'][$index]) || !isset($_POST['passing_year'][$index])) {
                    continue;
                }

                $institution = sanitizeInput($_POST['institution'][$index]);
                $year = intval($_POST['passing_year'][$index]);

                if (empty($degreeName) || empty($institution) || $year <= 0) {
                    continue; // Skip invalid entries
                }

                $degreeStmt->bind_param("issi", $doctor_id, $degreeName, $institution, $year);
                if (!$degreeStmt->execute()) {
                    throw new Exception("Error inserting degree: " . $degreeStmt->error);
                }
            }
            $degreeStmt->close();
        }

        // Insert time slots if provided
        if (isset($_POST['slot_date']) && is_array($_POST['slot_date']) && !empty($_POST['slot_date'])) {
            $slotStmt = $conn->prepare("INSERT INTO time_slots (doctor_id, start_time, end_time, location) VALUES (?, ?, ?, ?)");

            foreach ($_POST['slot_date'] as $index => $date) {
                // Validate array keys exist
                if (!isset($_POST['start_time'][$index]) || !isset($_POST['end_time'][$index]) || !isset($_POST['location'][$index])) {
                    continue;
                }

                $startTime = sanitizeInput($_POST['start_time'][$index]);
                $endTime = sanitizeInput($_POST['end_time'][$index]);
                $location = sanitizeInput($_POST['location'][$index]);

                if (empty($date) || empty($startTime) || empty($endTime) || empty($location)) {
                    continue; // Skip invalid entries
                }

                // Validate start and end times
                $currentTime = time();
                $slotStartDateTime = strtotime("$date $startTime");
                $slotEndDateTime = strtotime("$date $endTime");

                if ($slotStartDateTime <= $currentTime) {
                    throw new Exception("Invalid time slot: Time must be in the future");
                }

                if ($slotStartDateTime >= $slotEndDateTime) {
                    throw new Exception("Invalid time slot: End time must be after start time");
                }

                $formattedStartTime = date("Y-m-d H:i:s", $slotStartDateTime);
                $formattedEndTime = date("Y-m-d H:i:s", $slotEndDateTime);

                $slotStmt->bind_param(
                    "isss",
                    $doctor_id,
                    $formattedStartTime,
                    $formattedEndTime,
                    $location
                );

                if (!$slotStmt->execute()) {
                    throw new Exception("Error saving time slot: " . $slotStmt->error);
                }
            }
            $slotStmt->close();
        }
    }

    $stmt->close();

    // Send PIN via email using PHPMailer
    $pin_message = "Hello " . htmlspecialchars($username) . ",\n\nThank you for registering with MediLinx. Your verification PIN is: " . $pin . "\n\nPlease enter this PIN in the verification page to verify your email address.\n\nIf you did not sign up for this account, please ignore this email.";
    $pin_subject = "MediLinx Email Verification PIN";

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;

        // Use SMTP credentials from config.php
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;

        $mail->SMTPSecure = 'ssl'; // or 'tls'
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom('no-reply@medilinx.com', 'MediLinx');
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $pin_subject;
        $mail->Body    = $pin_message;

        $mail->send();

        // Commit the transaction only if everything was successful
        $conn->commit();

        // Redirect to verification page
        header("Location: verify_pin.php");
        exit;
    } catch (Exception $e) {
        // Rollback transaction if email sending fails
        $conn->rollback();
        throw new Exception("Registration successful, but failed to send verification PIN: " . $mail->ErrorInfo);
    }
} catch (Exception $e) {
    // Rollback transaction if any error occurred
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

// Close database connection
$conn->close();

// End output buffering
ob_end_flush();
