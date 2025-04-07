<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)$_POST['appointment_id'];
    
    try {
        $conn->begin_transaction();
        
        // Get appointment details - query depends on role
        if ($role === 'patient') {
            $stmt = $conn->prepare("SELECT a.*, t.doctor_id, t.start_time 
                FROM appointments a
                JOIN time_slots t ON a.slot_id = t.id
                WHERE a.id = ? AND a.patient_id = ?");
            $stmt->bind_param("ii", $appointment_id, $user_id);
        } else if ($role === 'doctor') {
            $stmt = $conn->prepare("SELECT a.*, t.doctor_id, t.start_time, a.patient_id 
                FROM appointments a
                JOIN time_slots t ON a.slot_id = t.id
                WHERE a.id = ? AND t.doctor_id = ?");
            $stmt->bind_param("ii", $appointment_id, $user_id);
        } else {
            throw new Exception("Invalid user role");
        }
        
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception("Invalid appointment");
        }
        
        // Cancel appointment
        $updateAppt = $conn->prepare("UPDATE appointments 
            SET status = 'cancelled' 
            WHERE id = ?");
        $updateAppt->bind_param("i", $appointment_id);
        $updateAppt->execute();
        
        // Free up time slot
        $updateSlot = $conn->prepare("UPDATE time_slots 
            SET status = 'available' 
            WHERE id = ?");
        $updateSlot->bind_param("i", $appointment['slot_id']);
        $updateSlot->execute();
        
        // Create notifications - message depends on who cancelled
        if ($role === 'patient') {
            $message = "Appointment cancelled by patient: " . date('M j, Y', strtotime($appointment['start_time']));
            $doctor_id = $appointment['doctor_id'];
            $patient_id = $user_id;
        } else { // doctor
            $message = "Appointment cancelled by doctor: " . date('M j, Y', strtotime($appointment['start_time']));
            $doctor_id = $user_id;
            $patient_id = $appointment['patient_id'];
        }
        
        $notif_stmt = $conn->prepare("INSERT INTO notifications 
            (user_id, message, type) 
            VALUES (?, ?, 'appointment'), (?, ?, 'appointment')");
        $notif_stmt->bind_param("isis", 
            $patient_id, $message,
            $doctor_id, $message);
        $notif_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Appointment cancelled successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redirect based on role
    if ($role === 'patient') {
        header("Location: appointments.php");
    } else {
        header("Location: appointments.php");
    }
    exit;
}

// If someone just navigates to this page without POST data
if ($role === 'patient') {
    header("Location: appointments.php");
} else {
    header("Location: appointments.php");
}
exit;
?>