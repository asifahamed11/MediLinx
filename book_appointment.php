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

        // Check slot availability and capacity with row locking to prevent race conditions
        $slotStmt = $conn->prepare("
            SELECT ts.*, 
                   (SELECT COUNT(*) FROM appointments WHERE slot_id = ts.id AND status != 'cancelled') as current_bookings 
            FROM time_slots ts
            WHERE ts.id = ? AND ts.status = 'available' 
            FOR UPDATE
        ");
        $slotStmt->bind_param("i", $slot_id);
        $slotStmt->execute();
        $slot = $slotStmt->get_result()->fetch_assoc();

        if (!$slot) {
            $conn->rollback();
            $_SESSION['error'] = "Time slot no longer available";
            header("Location: calendar_booking.php");
            exit;
        }

        // Check if appointment time is in the past
        $current_time = time();
        $appointment_time = strtotime($slot['start_time']);

        if ($appointment_time < $current_time) {
            $conn->rollback();
            $_SESSION['error'] = "Cannot book appointments for past dates and times";
            header("Location: calendar_booking.php");
            exit;
        }

        // Check if slot is at capacity
        if (isset($slot['capacity']) && $slot['current_bookings'] >= $slot['capacity']) {
            $conn->rollback();
            $_SESSION['error'] = "This appointment slot is full";
            header("Location: calendar_booking.php");
            exit;
        }

        // Create appointment
        $apptStmt = $conn->prepare("INSERT INTO appointments 
            (patient_id, slot_id, reason, doctor_id, start_time, end_time, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $apptStmt->bind_param("iisisss", $patient_id, $slot_id, $reason, $slot['doctor_id'], $slot['start_time'], $slot['end_time'], $slot['location']);

        if (!$apptStmt->execute()) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to create appointment: " . $conn->error;
            header("Location: calendar_booking.php");
            exit;
        }

        // Update booked_count in the time slot
        $updateSlotStmt = $conn->prepare("UPDATE time_slots SET booked_count = booked_count + 1 WHERE id = ?");
        $updateSlotStmt->bind_param("i", $slot_id);

        if (!$updateSlotStmt->execute()) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to update slot booking count: " . $conn->error;
            header("Location: calendar_booking.php");
            exit;
        }

        // If the slot is now at capacity, update its status
        if ($slot['current_bookings'] + 1 >= $slot['capacity']) {
            $updateStatusStmt = $conn->prepare("UPDATE time_slots SET status = 'booked' WHERE id = ?");
            $updateStatusStmt->bind_param("i", $slot_id);
            $updateStatusStmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        // Create notification for the doctor
        $doctorId = $slot['doctor_id'];
        $notificationMessage = "New appointment scheduled with patient #$patient_id for " . date('M j, Y g:i A', strtotime($slot['start_time']));
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
        $notifStmt->bind_param("is", $doctorId, $notificationMessage);
        $notifStmt->execute();

        $_SESSION['success'] = "Appointment booked successfully";
        header("Location: appointments.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: calendar_booking.php");
        exit;
    }
}
