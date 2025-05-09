<?php
session_start();
require_once 'config.php';

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$appointments = null;
$total_pages = 1;
$error_message = "";
$has_error = false;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get current month and year from query params or use current date
$view_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$view_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Calculate month boundaries with padding for calendar view
$prev_month = $view_month == 1 ? 12 : $view_month - 1;
$prev_year = $view_month == 1 ? $view_year - 1 : $view_year;
$next_month = $view_month == 12 ? 1 : $view_month + 1;
$next_year = $view_month == 12 ? $view_year + 1 : $view_year;

// Start from the last week of the previous month
$prev_month_start = date('Y-m-d', strtotime($prev_year . '-' . $prev_month . '-15'));
$query_start = date('Y-m-d 00:00:00', strtotime('last Monday of ' . $prev_month_start));

// End at the second week of the next month
$next_month_start = date('Y-m-d', strtotime($next_year . '-' . $next_month . '-01'));
$query_end = date('Y-m-d 23:59:59', strtotime('second Sunday of ' . $next_month_start));

error_log("Query date range: $query_start to $query_end");

// Handle AJAX cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_cancel'])) {
    header('Content-Type: application/json');

    // Debug - log all POST data
    error_log("Cancel appointment request received: " . json_encode($_POST));

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Parse and validate appointment ID
    if (!isset($_POST['appointment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
        exit;
    }

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);

    // Add detailed debugging info for appointment ID validation
    if ($appointment_id === false || $appointment_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid appointment ID format',
            'debug' => [
                'raw_id' => $_POST['appointment_id'],
                'filtered_id' => $appointment_id,
                'is_numeric' => is_numeric($_POST['appointment_id']),
                'type' => gettype($_POST['appointment_id'])
            ]
        ]);
        exit;
    }

    $cancellation_reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    try {
        $conn = connectDB();
        $user_id = $_SESSION['user_id'];

        error_log("Processing cancellation for appointment ID: $appointment_id by user ID: $user_id");

        // Start transaction
        $conn->begin_transaction();

        // Get appointment details
        $stmt = $conn->prepare("
            SELECT a.*, t.start_time, t.doctor_id, 
                   d.username as doctor_name, p.username as patient_name
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users d ON t.doctor_id = d.id
            JOIN users p ON a.patient_id = p.id
            WHERE a.id = ? 
            AND (a.patient_id = ? OR t.doctor_id = ?)
            AND a.status = 'confirmed'
        ");

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("iii", $appointment_id, $user_id, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Debug query to see what appointments are available for this user
            $debug_stmt = $conn->prepare("
                SELECT a.id, a.status, a.patient_id, t.doctor_id
                FROM appointments a
                JOIN time_slots t ON a.slot_id = t.id
                WHERE a.id = ?
            ");
            $debug_stmt->bind_param("i", $appointment_id);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            $debug_data = $debug_result->fetch_assoc();

            // Get user info for better error diagnosis
            $user_debug = "User #$user_id role: $role";

            // Log the issue for server-side debugging
            error_log("Appointment cancellation failed - ID $appointment_id not found or not accessible by $user_debug");
            error_log("Debug appointment data: " . json_encode($debug_data));

            throw new Exception("Appointment not found or already cancelled. Please refresh the page and try again.");
        }

        $appointment = $result->fetch_assoc();
        $canceller_type = ($appointment['patient_id'] == $user_id) ? 'patient' : 'doctor';
        $canceller_name = ($canceller_type == 'patient') ? $appointment['patient_name'] : $appointment['doctor_name'];

        // Update appointment status
        $update = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $update->bind_param("i", $appointment_id);

        if (!$update->execute()) {
            throw new Exception("Failed to cancel appointment");
        }

        // Create notification for the other party
        $notification_recipient_id = ($canceller_type == 'patient') ? $appointment['doctor_id'] : $appointment['patient_id'];
        $appointment_date = date('l, F j, Y', strtotime($appointment['start_time']));
        $appointment_time = date('g:i A', strtotime($appointment['start_time']));

        $reason_text = !empty($cancellation_reason) ? " Reason: " . $cancellation_reason : "";
        $notification_message = "Your appointment on {$appointment_date} at {$appointment_time} has been cancelled by {$canceller_name}.{$reason_text}";

        $notify = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
        $notify->bind_param("is", $notification_recipient_id, $notification_message);

        if (!$notify->execute()) {
            throw new Exception("Failed to send notification");
        }

        // Free the slot
        $free_slot = $conn->prepare("UPDATE time_slots SET status = 'available', booked_count = GREATEST(booked_count - 1, 0) WHERE id = ?");
        $free_slot->bind_param("i", $appointment['slot_id']);

        if (!$free_slot->execute()) {
            throw new Exception("Failed to free the time slot");
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Appointment cancelled successfully',
            'appointment_id' => $appointment_id
        ]);
        exit;
    } catch (Exception $e) {
        // Rollback on error
        try {
            $conn->rollback();
        } catch (Exception $rollbackEx) {
            // Transaction may not have been active
        }

        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

try {
    if ($role === 'doctor') {
        // Doctor sees appointments with patients - include extra patient info
        $query = "SELECT 
                a.id, a.status, a.patient_id, a.reason, a.slot_id,
                u.username, u.profile_image, 
                t.start_time, t.end_time, t.location, t.doctor_id
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON a.patient_id = u.id
            WHERE t.doctor_id = ?
            AND t.start_time >= ? AND t.start_time <= ?
            ORDER BY t.start_time ASC";

        error_log("Doctor query: " . str_replace(['?', "\n"], ["'$user_id', '$query_start', '$query_end'", " "], $query));
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $query_start, $query_end);
    } else {
        // Patient sees appointments with doctors
        $query = "SELECT 
                a.id, a.status, a.patient_id, a.reason, a.slot_id,
                u.username, u.profile_image, 
                t.start_time, t.end_time, t.location, t.doctor_id
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON t.doctor_id = u.id
            WHERE a.patient_id = ?
            AND t.start_time >= ? AND t.start_time <= ?
            ORDER BY t.start_time ASC";

        error_log("Patient query: " . str_replace(['?', "\n"], ["'$user_id', '$query_start', '$query_end'", " "], $query));
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $query_start, $query_end);
    }

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $success = $stmt->execute();

    error_log("Executing query for month view: $query_start to $query_end");

    if (!$success) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $appointments = $stmt->get_result();

    // Debug the appointments result
    error_log("Appointments query executed. Number of results: " . $appointments->num_rows);

    // Get sample of first few appointments
    if ($appointments->num_rows > 0) {
        $appointments->data_seek(0);
        $sample = $appointments->fetch_assoc();
        error_log("Sample appointment data: " . json_encode($sample));
        $appointments->data_seek(0); // Reset pointer back to beginning
    }

    // Get total rows for pagination
    $total_rows = $appointments->num_rows;
    $total_pages = ceil($total_rows / $limit);

    error_log("Total appointments found: $total_rows across $total_pages pages");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error fetching appointments: " . $e->getMessage();
    $_SESSION['error'] = $error_message;
    $has_error = true;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>My Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-5px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            animation: slideIn 0.6s ease-out;
        }

        h2 {
            color: #2A9D8F;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .appointment-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            margin: 1.5rem 0;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .appointment-card:nth-child(odd) {
            animation-delay: 0.1s;
        }

        .appointment-card:nth-child(even) {
            animation-delay: 0.2s;
        }

        .appointment-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .header h4 {
            color: #264653;
            font-size: 1.4rem;
            margin: 0;
            position: relative;
        }

        .header h4::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 20px;
            height: 3px;
            background: #2A9D8F;
            border-radius: 2px;
        }

        .status-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease;
        }

        .status-badge i {
            font-size: 1.1rem;
        }

        .status-badge.confirmed {
            background-color: #e6f7ff;
            color: #0066cc;
            animation: float 3s ease-in-out infinite;
        }

        .status-badge.completed {
            background-color: #e6fff0;
            color: #00994d;
        }

        .status-badge.cancelled {
            background-color: #ffebeb;
            color: #cc0000;
        }

        p {
            color: #555;
            margin: 0.8rem 0;
            font-size: 1.1rem;
        }

        .pagination {
            display: flex;
            gap: 0.8rem;
            margin-top: 3rem;
            justify-content: center;
        }

        .pagination a {
            padding: 0.8rem 1.4rem;
            border-radius: 12px;
            background: #fff;
            color: #2A9D8F;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .pagination a:hover {
            transform: translateY(-2px);
            background: #2A9D8F;
            color: white;
            box-shadow: 0 6px 12px rgba(42, 157, 143, 0.3);
        }

        .pagination a.active {
            background: #2A9D8F;
            color: #fff;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            margin-top: 3rem;
            color: #888;
            animation: slideIn 0.6s ease;
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 1.2rem;
            margin: 2rem 0;
            border-radius: 10px;
            border-left: 5px solid #d32f2f;
            animation: slideIn 0.4s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-cancel {
            padding: 0.8rem 1.6rem;
            border: none;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff4040 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1.2rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(255, 107, 107, 0.2);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 107, 0.3);
            background: linear-gradient(135deg, #ff4040 0%, #ff1a1a 100%);
        }

        .btn-cancel:active {
            transform: scale(0.96);
        }

        .loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .container h2 {
            position: relative;
            padding-left: 50px;
        }

        .container h2 .header-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.8rem;
            color: #2A9D8F;
        }

        .appointment-card .icon-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0.8rem 0;
        }

        .appointment-card .icon {
            color: #2A9D8F;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #e74c3c;
            margin: 0;
        }

        .close-modal {
            font-size: 1.5rem;
            color: #777;
            cursor: pointer;
            background: none;
            border: none;
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: #e74c3c;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        /* Patient info in modal */
        .patient-info {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 1.5rem;
        }

        #patientImageContainer {
            flex-shrink: 0;
        }

        #patientImageContainer .patient-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .patient-image-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #aaa;
        }

        .patient-details {
            flex: 1;
        }

        .patient-details h3 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #333;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
        }

        .appointment-reason {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-outline {
            padding: 0.7rem 1.5rem;
            border: 1px solid #ccc;
            background: transparent;
            color: #666;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            background: #f8f8f8;
        }

        .modal-spinner {
            display: none;
            margin-right: 8px;
        }

        .alert-float {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            animation: slideInRight 0.3s ease, fadeOut 0.5s ease 3.5s forwards;
            max-width: 350px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        .alert-success-float {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger-float {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Calendar view styles */
        .calendar-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 2rem;
        }

        .calendar-header {
            grid-column: 1 / span 7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .month-year {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2A9D8F;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar-nav button {
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .calendar-nav button:hover {
            background: #f0f7ff;
            transform: translateY(-2px);
        }

        .day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .calendar-day {
            background: white;
            min-height: 120px;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .calendar-day:hover {
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.2);
            transform: translateY(-2px);
        }

        .calendar-day.has-events {
            box-shadow: 0 4px 8px rgba(42, 157, 143, 0.2);
        }

        .calendar-day.today {
            border: 2px solid #2A9D8F;
        }

        .day-number {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .today .day-number {
            background: #2A9D8F;
            color: white;
            border-radius: 50%;
        }

        .day-events {
            margin-top: 25px;
        }

        .calendar-event {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            word-break: break-word;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-event.confirmed {
            background-color: rgba(230, 247, 255, 0.7);
            border-left: 3px solid #0066cc;
        }

        .calendar-event.completed {
            background-color: rgba(230, 255, 240, 0.7);
            border-left: 3px solid #00994d;
        }

        .calendar-event.cancelled {
            background-color: rgba(255, 235, 235, 0.7);
            border-left: 3px solid #cc0000;
        }

        .calendar-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .event-time {
            font-weight: bold;
            font-size: 0.75rem;
            margin-bottom: 3px;
        }

        .event-name {
            font-weight: bold;
        }

        .other-month {
            opacity: 0.5;
        }

        /* Appointment stats badges */
        .appointment-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            position: absolute;
            top: 5px;
            right: 5px;
        }

        .appointment-stat {
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .stat-confirmed {
            background-color: rgba(230, 247, 255, 0.9);
            color: #0066cc;
        }

        .stat-cancelled {
            background-color: rgba(255, 235, 235, 0.9);
            color: #cc0000;
        }

        .stat-completed {
            background-color: rgba(230, 255, 240, 0.9);
            color: #00994d;
        }

        /* Date appointments modal */
        .date-appointments-modal {
            display: none;
            max-width: 800px;
            width: 90%;
        }

        .date-appointments-list {
            max-height: 60vh;
            overflow-y: auto;
            padding: 10px 0;
        }

        .appointment-item {
            display: flex;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f9f9f9;
            transition: all 0.2s;
            align-items: center;
            gap: 15px;
        }

        .appointment-item:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .appointment-item .patient-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .appointment-item .patient-image-placeholder {
            width: 50px;
            height: 50px;
        }

        .appointment-item-details {
            flex: 1;
        }

        .appointment-item-time {
            font-weight: bold;
            color: #2A9D8F;
        }

        .appointment-item-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
        }

        .appointment-actions {
            display: flex;
            gap: 5px;
        }

        .appointment-actions button {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view-details {
            background: #f0f0f0;
            color: #333;
        }

        .btn-view-details:hover {
            background: #e0e0e0;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2>
            <i class="fas fa-calendar-alt header-icon"></i>
            Appointments Calendar
        </h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($has_error): ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <p>Unable to load appointments. Please try again later.</p>
            </div>
        <?php elseif ($appointments === null || $appointments->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times fa-2x"></i>
                <p>No appointments found</p>
            </div>
        <?php else: ?>
            <?php
            // Get current month and year from query params or use current date
            $current_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $current_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

            error_log("Displaying calendar for month: $current_month, year: $current_year");

            // Create calendar data structure
            $first_day_of_month = mktime(0, 0, 0, $current_month, 1, $current_year);
            $days_in_month = date('t', $first_day_of_month);
            $first_day_of_week = date('N', $first_day_of_month); // 1 (Monday) to 7 (Sunday)

            // Calculate previous and next month
            $prev_month = $current_month - 1;
            $prev_year = $current_year;
            if ($prev_month < 1) {
                $prev_month = 12;
                $prev_year--;
            }

            $next_month = $current_month + 1;
            $next_year = $current_year;
            if ($next_month > 12) {
                $next_month = 1;
                $next_year++;
            }

            // Organize appointments by date for calendar view
            $appointments_by_date = [];

            if ($appointments && $appointments->num_rows > 0) {
                $appointments->data_seek(0); // Reset result pointer

                // Debug output
                error_log("Organizing appointments for calendar view. Total appointments: " . $appointments->num_rows);

                while ($apt = $appointments->fetch_assoc()) {
                    // Make sure start_time is present
                    if (!isset($apt['start_time'])) {
                        error_log("WARNING: Appointment missing start_time: " . json_encode($apt));
                        continue;
                    }

                    // Extract just the date portion
                    $date_key = date('Y-m-d', strtotime($apt['start_time']));

                    if (!isset($appointments_by_date[$date_key])) {
                        $appointments_by_date[$date_key] = [];
                    }

                    // Ensure ID is present
                    if (!isset($apt['id'])) {
                        error_log("WARNING: Appointment missing ID: " . json_encode($apt));
                        continue;
                    }

                    $appointments_by_date[$date_key][] = $apt;

                    // Debug log
                    error_log("Added appointment ID: " . $apt['id'] . " to date: " . $date_key . " with status: " . $apt['status']);
                }
            }

            // Debug - log all dates with appointments
            if (count($appointments_by_date) > 0) {
                error_log("Dates with appointments: " . implode(", ", array_keys($appointments_by_date)));
            } else {
                error_log("No dates have appointments in the calendar view.");
            }

            // Get today's date
            $today = date('Y-m-d');
            ?>

            <div class="calendar-header">
                <h3 class="month-year"><?= date('F Y', $first_day_of_month) ?></h3>
                <div class="calendar-nav">
                    <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn-outline">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                    <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="btn-outline">Today</a>
                    <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn-outline">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="calendar-container">
                <!-- Day headers -->
                <div class="day-header">Monday</div>
                <div class="day-header">Tuesday</div>
                <div class="day-header">Wednesday</div>
                <div class="day-header">Thursday</div>
                <div class="day-header">Friday</div>
                <div class="day-header">Saturday</div>
                <div class="day-header">Sunday</div>

                <?php
                // Previous month days
                $days_from_prev_month = $first_day_of_week - 1;
                $prev_month_days = date('t', mktime(0, 0, 0, $prev_month, 1, $prev_year));

                for ($i = $days_from_prev_month - 1; $i >= 0; $i--) {
                    $day = $prev_month_days - $i;
                    $date = sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $day);
                    $has_events = isset($appointments_by_date[$date]) && !empty($appointments_by_date[$date]);
                    error_log("Previous month day $day ($date): has_events=" . ($has_events ? 'true' : 'false'));
                ?>
                    <div class="calendar-day other-month <?= $has_events ? 'has-events' : '' ?>">
                        <div class="day-number"><?= $day ?></div>
                        <div class="day-events">
                            <?php if ($has_events): ?>
                                <?php
                                error_log("Prev month day $day ($date) has " . count($appointments_by_date[$date]) . " appointments");
                                foreach ($appointments_by_date[$date] as $apt):
                                ?>
                                    <div class="calendar-event <?= $apt['status'] ?>"
                                        data-appointment-id="<?= $apt['id'] ?>"
                                        data-patient-name="<?= htmlspecialchars($apt['username']) ?>"
                                        data-appointment-time="<?= date('g:i A', strtotime($apt['start_time'])) ?>"
                                        data-appointment-date="<?= date('l, F j, Y', strtotime($apt['start_time'])) ?>"
                                        data-appointment-status="<?= $apt['status'] ?>"
                                        data-appointment-location="<?= htmlspecialchars($apt['location']) ?>"
                                        <?php if (isset($apt['reason'])): ?>
                                        data-appointment-reason="<?= htmlspecialchars($apt['reason']) ?>"
                                        <?php endif; ?>
                                        <?php if (isset($apt['profile_image'])): ?>
                                        data-patient-image="<?= htmlspecialchars($apt['profile_image']) ?>"
                                        <?php endif; ?>
                                        onclick="showAppointmentDetails(this)">
                                        <div class="event-time"><?= date('g:i A', strtotime($apt['start_time'])) ?></div>
                                        <div class="event-name"><?= htmlspecialchars($apt['username']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php error_log("Day $day ($date) has no appointments"); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }

                // Current month days
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                    $is_today = ($date === $today);
                    $has_events = isset($appointments_by_date[$date]) && !empty($appointments_by_date[$date]);
                    error_log("Current month day $day ($date): has_events=" . ($has_events ? 'true' : 'false'));

                    // Count appointments by status
                    $stats = ['confirmed' => 0, 'cancelled' => 0, 'completed' => 0];
                    if ($has_events) {
                        foreach ($appointments_by_date[$date] as $apt) {
                            if (isset($apt['status']) && isset($stats[$apt['status']])) {
                                $stats[$apt['status']]++;
                            }
                        }
                    }
                ?>
                    <div class="calendar-day <?= $is_today ? 'today' : '' ?> <?= $has_events ? 'has-events' : '' ?>"
                        <?php if ($role === 'doctor' && $has_events): ?>
                        onclick="showDateAppointments('<?= $date ?>')"
                        <?php endif; ?>>
                        <div class="day-number"><?= $day ?></div>

                        <?php if ($role === 'doctor' && $has_events): ?>
                            <div class="appointment-stats">
                                <?php if ($stats['confirmed'] > 0): ?>
                                    <div class="appointment-stat stat-confirmed">
                                        <i class="fas fa-calendar-check"></i> <?= $stats['confirmed'] ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($stats['cancelled'] > 0): ?>
                                    <div class="appointment-stat stat-cancelled">
                                        <i class="fas fa-calendar-times"></i> <?= $stats['cancelled'] ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($stats['completed'] > 0): ?>
                                    <div class="appointment-stat stat-completed">
                                        <i class="fas fa-check-circle"></i> <?= $stats['completed'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="day-events">
                            <?php if ($has_events && $role !== 'doctor'): ?>
                                <?php
                                // Debug this date's appointments
                                error_log("Day $day has " . count($appointments_by_date[$date]) . " appointments");

                                foreach ($appointments_by_date[$date] as $apt):
                                    // Debug this specific appointment
                                    error_log("Day $day - Appointment: " . json_encode($apt));
                                ?>
                                    <div class="calendar-event <?= $apt['status'] ?>"
                                        data-appointment-id="<?= $apt['id'] ?>"
                                        data-patient-name="<?= htmlspecialchars($apt['username']) ?>"
                                        data-appointment-time="<?= date('g:i A', strtotime($apt['start_time'])) ?>"
                                        data-appointment-date="<?= date('l, F j, Y', strtotime($apt['start_time'])) ?>"
                                        data-appointment-status="<?= $apt['status'] ?>"
                                        data-appointment-location="<?= htmlspecialchars($apt['location']) ?>"
                                        <?php if (isset($apt['reason'])): ?>
                                        data-appointment-reason="<?= htmlspecialchars($apt['reason']) ?>"
                                        <?php endif; ?>
                                        <?php if (isset($apt['profile_image'])): ?>
                                        data-patient-image="<?= htmlspecialchars($apt['profile_image']) ?>"
                                        <?php endif; ?>
                                        onclick="showAppointmentDetails(this)">
                                        <div class="event-time"><?= date('g:i A', strtotime($apt['start_time'])) ?></div>
                                        <div class="event-name"><?= htmlspecialchars($apt['username']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php error_log("Day $day has no appointments or is doctor view"); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }

                // Next month days
                $days_used = $days_from_prev_month + $days_in_month;
                $days_remaining = 42 - $days_used; // 42 = 6 rows of 7 days

                for ($day = 1; $day <= $days_remaining; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $next_year, $next_month, $day);
                    $has_events = isset($appointments_by_date[$date]) && !empty($appointments_by_date[$date]);
                    error_log("Next month day $day ($date): has_events=" . ($has_events ? 'true' : 'false'));
                ?>
                    <div class="calendar-day other-month <?= $has_events ? 'has-events' : '' ?>">
                        <div class="day-number"><?= $day ?></div>
                        <div class="day-events">
                            <?php if ($has_events): ?>
                                <?php
                                // Debug this date's appointments
                                error_log("Next month day $day ($date) has " . count($appointments_by_date[$date]) . " appointments");

                                foreach ($appointments_by_date[$date] as $apt):
                                ?>
                                    <div class="calendar-event <?= $apt['status'] ?>"
                                        data-appointment-id="<?= $apt['id'] ?>"
                                        data-patient-name="<?= htmlspecialchars($apt['username']) ?>"
                                        data-appointment-time="<?= date('g:i A', strtotime($apt['start_time'])) ?>"
                                        data-appointment-date="<?= date('l, F j, Y', strtotime($apt['start_time'])) ?>"
                                        data-appointment-status="<?= $apt['status'] ?>"
                                        data-appointment-location="<?= htmlspecialchars($apt['location']) ?>"
                                        <?php if (isset($apt['reason'])): ?>
                                        data-appointment-reason="<?= htmlspecialchars($apt['reason']) ?>"
                                        <?php endif; ?>
                                        <?php if (isset($apt['profile_image'])): ?>
                                        data-patient-image="<?= htmlspecialchars($apt['profile_image']) ?>"
                                        <?php endif; ?>
                                        onclick="showAppointmentDetails(this)">
                                        <div class="event-time"><?= date('g:i A', strtotime($apt['start_time'])) ?></div>
                                        <div class="event-name"><?= htmlspecialchars($apt['username']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php error_log("Next month day $day has no appointments"); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add appointment details modal -->
    <div id="appointmentDetailModal" class="modal appointment-detail-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-calendar-check"></i> <span id="appointmentTitle">Appointment Details</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="patient-info">
                    <div id="patientImageContainer">
                        <!-- Will be filled by JavaScript -->
                    </div>
                    <div class="patient-details">
                        <h3 id="patientName"></h3>
                        <div class="patient-age" id="patientAgeContainer" style="display:none;">
                            <span class="detail-label">Age:</span> <span id="patientAge"></span> years
                        </div>
                        <div id="appointmentDatetime"></div>
                        <div id="appointmentLocation"></div>
                    </div>
                </div>

                <div class="appointment-reason" id="appointmentReasonContainer" style="display:none;">
                    <span class="detail-label">Reason for visit:</span>
                    <p id="appointmentReason"></p>
                </div>

                <div id="appointmentActions">
                    <!-- Cancel button will be added here by JavaScript if needed -->
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel appointment modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-calendar-times"></i> Cancel Appointment</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment? A notification will be sent to the patient.</p>
                <div class="form-group">
                    <label for="cancelReason">Reason for Cancellation (Optional):</label>
                    <textarea id="cancelReason" class="form-control" rows="3" placeholder="Please provide a reason for cancelling this appointment"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" id="cancelModalBtn">No, Keep It</button>
                <button type="button" class="btn-cancel" id="confirmCancelBtn">
                    <span class="loading-spinner modal-spinner"></span>
                    Yes, Cancel Appointment
                </button>
            </div>
        </div>
    </div>

    <!-- Add date appointments modal -->
    <div id="dateAppointmentsModal" class="modal date-appointments-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-calendar-day"></i> <span id="selectedDateTitle">Appointments</span></h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="dateAppointmentsList" class="date-appointments-list">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSpinner(form) {
            const btn = form.querySelector('.btn-cancel');
            btn.disabled = true;
            btn.querySelector('.loading-spinner').style.display = 'block';
            btn.style.opacity = '0.8';
        }

        document.querySelectorAll('.appointment-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Cancel appointment modal functionality
        const modal = document.getElementById('cancelModal');
        const closeBtn = modal.querySelector('.close-modal');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        const cancelBtns = document.querySelectorAll('.cancel-appointment-btn');
        let currentAppointmentId = null;

        // Open modal when cancel button is clicked
        cancelBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                currentAppointmentId = this.getAttribute('data-id');
                modal.style.display = 'flex';
                document.getElementById('cancelReason').value = '';
            });
        });

        // Close modal when X or "No" is clicked
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        cancelModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Handle appointment cancellation with improved debugging
        confirmCancelBtn.addEventListener('click', function() {
            console.log("Confirm cancel button clicked. Current appointment ID:", currentAppointmentId);

            if (!currentAppointmentId) {
                console.error("No appointment ID found!");
                showAlert('Error: No appointment ID found', 'danger');
                return;
            }

            console.log("Attempting to cancel appointment ID:", currentAppointmentId);

            const reason = document.getElementById('cancelReason').value;
            const spinner = this.querySelector('.modal-spinner');

            // Show loading spinner
            spinner.style.display = 'inline-block';
            this.disabled = true;

            // Create form data and log its contents
            const formData = new FormData();
            formData.append('ajax_cancel', '1');
            formData.append('appointment_id', parseInt(currentAppointmentId, 10)); // Ensure integer conversion
            formData.append('reason', reason);
            formData.append('csrf_token', '<?= $csrf_token ?>');

            // Log form data
            console.log("FormData entries:");
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Send AJAX request
            fetch('appointments.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log("Raw response:", response);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Server response:", data);
                    // Hide loading spinner
                    spinner.style.display = 'none';
                    this.disabled = false;

                    // Close modal
                    modal.style.display = 'none';

                    if (data.success) {
                        // Find all instances of this appointment in the calendar
                        const appointmentEvents = document.querySelectorAll(`.calendar-event[data-appointment-id="${currentAppointmentId}"]`);

                        appointmentEvents.forEach(event => {
                            // Update status class
                            event.classList.remove('confirmed');
                            event.classList.add('cancelled');

                            // Update status in the data attribute
                            event.dataset.appointmentStatus = 'cancelled';
                        });

                        // Show success message
                        showAlert('Appointment cancelled successfully!', 'success');
                    } else {
                        // Show error message with detailed info if available
                        let errorMsg = data.message || 'Failed to cancel appointment';
                        if (data.debug) {
                            console.error("Detailed error info:", data.debug);
                        }
                        showAlert(errorMsg, 'danger');
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    spinner.style.display = 'none';
                    this.disabled = false;

                    // Close modal
                    modal.style.display = 'none';

                    // Show error message
                    console.error('Error:', error);
                    showAlert('An error occurred: ' + error.message, 'danger');
                });
        });

        // Function to show alert message
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert-float alert-${type}-float`;
            alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(alert);

            // Remove alert after 4 seconds
            setTimeout(() => {
                alert.remove();
            }, 4000);
        }

        // Function to show appointment details modal
        function showAppointmentDetails(element) {
            const modal = document.getElementById('appointmentDetailModal');
            const patientName = document.getElementById('patientName');
            const patientAgeContainer = document.getElementById('patientAgeContainer');
            const appointmentDatetime = document.getElementById('appointmentDatetime');
            const appointmentLocation = document.getElementById('appointmentLocation');
            const appointmentReason = document.getElementById('appointmentReason');
            const appointmentReasonContainer = document.getElementById('appointmentReasonContainer');
            const patientImageContainer = document.getElementById('patientImageContainer');
            const appointmentActions = document.getElementById('appointmentActions');
            const appointmentTitle = document.getElementById('appointmentTitle');

            // Get data from the element
            const appointmentId = element.dataset.appointmentId;

            // Debug logging
            console.log("Element clicked:", element);
            console.log("All data attributes:", element.dataset);
            console.log("Raw appointment ID from dataset:", appointmentId);

            const name = element.dataset.patientName;
            const time = element.dataset.appointmentTime;
            const date = element.dataset.appointmentDate;
            const status = element.dataset.appointmentStatus;
            const location = element.dataset.appointmentLocation;
            const reason = element.dataset.appointmentReason;
            const patientImage = element.dataset.patientImage;

            // Set modal content
            patientName.textContent = name;
            appointmentDatetime.innerHTML = `<i class="fas fa-clock icon"></i> ${date} at ${time}`;
            appointmentLocation.innerHTML = `<i class="fas fa-map-marker-alt icon"></i> ${location}`;
            appointmentTitle.textContent = `Appointment Details (${status.charAt(0).toUpperCase() + status.slice(1)})`;

            // Always hide the age container since we don't have DOB
            patientAgeContainer.style.display = 'none';

            // Set appointment reason if available
            if (reason) {
                appointmentReason.textContent = reason;
                appointmentReasonContainer.style.display = 'block';
            } else {
                appointmentReasonContainer.style.display = 'none';
            }

            // Set patient image
            if (patientImage) {
                patientImageContainer.innerHTML = `<img src="${patientImage}" alt="${name}" class="patient-image">`;
            } else {
                patientImageContainer.innerHTML = `<div class="patient-image-placeholder"><i class="fas fa-user"></i></div>`;
            }

            // Add cancel button for confirmed appointments if doctor role
            appointmentActions.innerHTML = '';
            if (status === 'confirmed' && '<?= $role ?>' === 'doctor') {
                appointmentActions.innerHTML = `
                    <button type="button" class="btn-cancel cancel-appointment-btn" data-id="${appointmentId}">
                        <i class="fas fa-times"></i> Cancel Appointment
                    </button>
                `;

                // Add cancel button event listener
                const cancelBtn = appointmentActions.querySelector('.cancel-appointment-btn');
                cancelBtn.addEventListener('click', function() {
                    // Debug logging
                    console.log("Cancel button clicked for appointment ID:", appointmentId);

                    // Close details modal
                    modal.style.display = 'none';

                    // Get and show the cancel modal instead
                    currentAppointmentId = appointmentId; // Use the variable from closure instead of attribute

                    // Debug logging
                    console.log("Setting currentAppointmentId to:", currentAppointmentId);

                    document.getElementById('cancelModal').style.display = 'flex';
                    document.getElementById('cancelReason').value = '';
                });
            }

            // Show modal
            modal.style.display = 'flex';

            // Close modal when X is clicked
            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Fix any calendar grid layout issues after page load
        window.addEventListener('load', function() {
            const calendar = document.querySelector('.calendar-container');
            if (calendar) {
                const days = calendar.querySelectorAll('.calendar-day');
                if (days.length < 42) {
                    // Add placeholder days if needed
                    const missingDays = 42 - days.length;
                    for (let i = 0; i < missingDays; i++) {
                        const placeholderDay = document.createElement('div');
                        placeholderDay.className = 'calendar-day other-month';
                        placeholderDay.innerHTML = '<div class="day-number">-</div>';
                        calendar.appendChild(placeholderDay);
                    }
                }
            }
        });

        // Store all appointments data for date-based filtering
        const allAppointmentsByDate = {};

        <?php if ($role === 'doctor' && !empty($appointments_by_date)): ?>
            <?php foreach ($appointments_by_date as $date => $apts): ?>
                allAppointmentsByDate['<?= $date ?>'] = <?= json_encode($apts) ?>;
            <?php endforeach; ?>
        <?php endif; ?>

        // Show all appointments for a specific date
        function showDateAppointments(date) {
            // Stop event propagation to prevent conflicts with other click handlers
            event.stopPropagation();

            const modal = document.getElementById('dateAppointmentsModal');
            const dateTitle = document.getElementById('selectedDateTitle');
            const appointmentsList = document.getElementById('dateAppointmentsList');

            // Format the date for display
            const displayDate = new Date(date);
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            dateTitle.textContent = displayDate.toLocaleDateString('en-US', options) + ' Appointments';

            // Clear previous appointments
            appointmentsList.innerHTML = '';

            if (!allAppointmentsByDate[date] || allAppointmentsByDate[date].length === 0) {
                appointmentsList.innerHTML = '<p class="text-center">No appointments found for this date.</p>';
            } else {
                // Sort appointments by time
                const appointments = allAppointmentsByDate[date].sort((a, b) => {
                    return new Date(a.start_time) - new Date(b.start_time);
                });

                // Create appointment items
                appointments.forEach(apt => {
                    const appointmentItem = document.createElement('div');
                    appointmentItem.className = `appointment-item ${apt.status}`;

                    // Patient image
                    const imageHtml = apt.profile_image ?
                        `<img src="${apt.profile_image}" alt="${apt.username}" class="patient-image">` :
                        `<div class="patient-image-placeholder"><i class="fas fa-user"></i></div>`;

                    // Format time
                    const time = new Date(apt.start_time).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });

                    // Status badge
                    const statusText = apt.status.charAt(0).toUpperCase() + apt.status.slice(1);
                    const statusClass = `appointment-item-status ${apt.status}`;

                    appointmentItem.innerHTML = `
                        ${imageHtml}
                        <div class="appointment-item-details">
                            <div>
                                <span class="appointment-item-time">${time}</span>
                                <span class="${statusClass}">${statusText}</span>
                            </div>
                            <h4>${apt.username}</h4>
                            ${apt.reason ? `<p><small>${apt.reason}</small></p>` : ''}
                        </div>
                        <div class="appointment-actions">
                            ${apt.status === 'confirmed' ? `
                            <button type="button" class="btn-cancel" 
                                data-id="${apt.id}">
                                <i class="fas fa-times"></i> Cancel
                            </button>` : ''}
                        </div>
                    `;

                    appointmentsList.appendChild(appointmentItem);
                });

                // Add event listeners to cancel buttons
                appointmentsList.querySelectorAll('.btn-cancel').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        currentAppointmentId = this.getAttribute('data-id');
                        modal.style.display = 'none';
                        document.getElementById('cancelModal').style.display = 'flex';
                        document.getElementById('cancelReason').value = '';
                    });
                });
            }

            // Show modal
            modal.style.display = 'flex';

            // Close modal when X is clicked
            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>