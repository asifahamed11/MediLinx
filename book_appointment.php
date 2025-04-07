<?php
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed");
}
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

// Add CSRF protection
if (!isset($_SERVER['HTTP_REFERER']) || parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
    die("Invalid request source");
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    $slot_id = (int)$_POST['slot_id'];
    $patient_id = $_SESSION['user_id'];
    
    try {
        $conn->begin_transaction();
        
        // Check slot availability
        $slotStmt = $conn->prepare("SELECT * FROM time_slots 
            WHERE id = ? AND status = 'available' FOR UPDATE");
        $slotStmt->bind_param("i", $slot_id);
        $slotStmt->execute();
        $slot = $slotStmt->get_result()->fetch_assoc();
        
        if (!$slot) {
            $_SESSION['error'] = "Time slot no longer available";
            header("Location: ".$_SERVER['HTTP_REFERER']);
            exit;
        }
        
        // Create appointment
        $apptStmt = $conn->prepare("INSERT INTO appointments 
            (patient_id, slot_id) 
            VALUES (?, ?)");
        $apptStmt->bind_param("ii", $patient_id, $slot_id);
        $apptStmt->execute();
        
        // Update slot status
        $updateStmt = $conn->prepare("UPDATE time_slots 
            SET status = 'booked' 
            WHERE id = ?");
        $updateStmt->bind_param("i", $slot_id);
        $updateStmt->execute();
        
        // Create notification with prepared statement
        $message = "New booking: " . date('M j, Y g:i A', strtotime($slot['start_time']));
        $notif_stmt = $conn->prepare("INSERT INTO notifications 
            (user_id, message, type) 
            VALUES (?, ?, 'appointment')");
        $notif_stmt->bind_param("is", $slot['doctor_id'], $message);
        $notif_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Appointment booked successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}