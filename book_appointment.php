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
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    try {
        $conn->begin_transaction();

        // Check if the patient has already booked this slot
        $existingBookingStmt = $conn->prepare("
            SELECT id FROM appointments 
            WHERE patient_id = ? AND slot_id = ?
        ");
        $existingBookingStmt->bind_param("ii", $patient_id, $slot_id);
        $existingBookingStmt->execute();
        $existingBookingResult = $existingBookingStmt->get_result();

        if ($existingBookingResult->num_rows > 0) {
            $conn->rollback();
            $_SESSION['error'] = "You have already booked this appointment slot";
            header("Location: calendar_booking.php");
            exit;
        }

        // Check slot availability and capacity
        $slotStmt = $conn->prepare("
            SELECT ts.*, 
                   (SELECT COUNT(*) FROM appointments WHERE slot_id = ts.id) as current_bookings 
            FROM time_slots ts
            WHERE ts.id = ? AND ts.status = 'available' 
            FOR UPDATE
        ");
        $slotStmt->bind_param("i", $slot_id);
        $slotStmt->execute();
        $slot = $slotStmt->get_result()->fetch_assoc();

        if (!$slot) {
            $_SESSION['error'] = "Time slot no longer available";
            header("Location: calendar_booking.php");
            exit;
        }

        // Check if slot is at capacity
        if (isset($slot['capacity']) && $slot['current_bookings'] >= $slot['capacity']) {
            $_SESSION['error'] = "This appointment slot is full";
            header("Location: calendar_booking.php");
            exit;
        }

        // Create appointment
        $apptStmt = $conn->prepare("INSERT INTO appointments 
            (patient_id, slot_id, reason, doctor_id, start_time, end_time, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $apptStmt->bind_param("iisisss", $patient_id, $slot_id, $reason, $slot['doctor_id'], $slot['start_time'], $slot['end_time'], $slot['location']);
        $apptStmt->execute();

        // Update booked_count
        $updateStmt = $conn->prepare("
            UPDATE time_slots 
            SET booked_count = booked_count + 1,
                status = CASE WHEN (booked_count + 1) >= capacity THEN 'booked' ELSE 'available' END
            WHERE id = ?
        ");
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

    // Redirect to appointments page
    header("Location: appointments.php");
    exit;
}
