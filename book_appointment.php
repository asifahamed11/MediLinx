<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
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
            throw new Exception("This time slot is no longer available");
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
        
        // Create notification
        $message = "New booking: " . date('M j, Y g:i A', strtotime($slot['start_time']));
        $conn->query("INSERT INTO notifications 
            (user_id, message, type) 
            VALUES ({$slot['doctor_id']}, '$message', 'appointment')");
        
        $conn->commit();
        $_SESSION['success'] = "Appointment booked successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: doctor-profile.php?id={$slot['doctor_id']}");
    exit;
}