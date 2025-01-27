<?php
session_start();
require_once 'config.php';

try {
    // Database configuration
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed");
    }

    // Get and validate input
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $reset_pin = trim($_POST['reset_pin']);
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Validate inputs
    if (!$email || !preg_match('/^[0-9]{6}$/', $reset_pin)) {
        throw new Exception("Invalid input format");
    }

    if (strlen($new_password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }

    if ($new_password !== $confirm_new_password) {
        throw new Exception("Passwords do not match");
    }

    // Check reset PIN validity
    $stmt = $conn->prepare("SELECT pr.user_id, pr.created_at 
                           FROM password_resets pr 
                           JOIN users u ON pr.user_id = u.id 
                           WHERE pr.reset_pin = ? AND u.email = ?");
    if (!$stmt) {
        throw new Exception("Database error");
    }

    $stmt->bind_param("ss", $reset_pin, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Check if PIN is expired (15 minutes)
        $pin_time = new DateTime($row['created_at']);
        $current_time = new DateTime();
        $diff = $current_time->diff($pin_time);
        
        if ($diff->i >= 15) {
            throw new Exception("Reset PIN has expired. Please request a new one.");
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Database error");
        }

        $update_stmt->bind_param("si", $hashed_password, $row['user_id']);
        
        if ($update_stmt->execute()) {
            // Delete the used reset PIN
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delete_stmt->bind_param("i", $row['user_id']);
            $delete_stmt->execute();

            $_SESSION['success'] = "Password updated successfully";
            header("Location: login.html");
            exit;
        }
    } else {
        throw new Exception("Invalid reset PIN");
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: reset_password.html");
    exit;
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    if (isset($conn)) $conn->close();
}
?>