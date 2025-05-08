<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "CSRF token validation failed";
    header("Location: manage_time_slots.php");
    exit;
}

// Get form data
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$days = isset($_POST['days']) ? $_POST['days'] : [];
// Removed the slot_duration from POST - will calculate it automatically
$daily_start_time = $_POST['daily_start_time'];
$daily_end_time = $_POST['daily_end_time'];
$location = $_POST['location'];
$doctor_id = $_SESSION['user_id'];
$capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 20;

// Validate inputs
if (
    empty($start_date) || empty($end_date) || empty($days) || empty($daily_start_time) ||
    empty($daily_end_time) || empty($location)
) {
    $_SESSION['error'] = "All fields are required";
    header("Location: manage_time_slots.php");
    exit;
}

if (strtotime($start_date) > strtotime($end_date)) {
    $_SESSION['error'] = "End date must be after start date";
    header("Location: manage_time_slots.php");
    exit;
}

if (strtotime($daily_start_time) >= strtotime($daily_end_time)) {
    $_SESSION['error'] = "Daily end time must be after daily start time";
    header("Location: manage_time_slots.php");
    exit;
}

// Connect to database
$conn = connectDB();
$slots_created = 0;
$errors = 0;

// Convert days array to integers
$days = array_map('intval', $days);

// Loop through each day in the date range
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$end_date_obj->setTime(23, 59, 59); // Include the entire end day

try {
    $conn->begin_transaction();

    while ($current_date <= $end_date_obj) {
        $day_of_week = $current_date->format('w'); // 0 (Sunday) through 6 (Saturday)

        // Check if this day is selected
        if (in_array($day_of_week, $days)) {
            $current_date_str = $current_date->format('Y-m-d');

            // Create a single time slot for this day using the daily start and end times
            $slot_start_time = clone $current_date;
            $slot_start_time->setTime(
                (int)date('H', strtotime($daily_start_time)),
                (int)date('i', strtotime($daily_start_time))
            );

            $slot_end_time = clone $current_date;
            $slot_end_time->setTime(
                (int)date('H', strtotime($daily_end_time)),
                (int)date('i', strtotime($daily_end_time))
            );

            // Calculate slot_duration automatically in minutes
            $duration_minutes = round(($slot_end_time->getTimestamp() - $slot_start_time->getTimestamp()) / 60);

            // Check for overlapping slots
            $check_stmt = $conn->prepare("
                SELECT id FROM time_slots 
                WHERE doctor_id = ? 
                AND (
                    (start_time <= ? AND end_time > ?) OR
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");

            $start_str = $slot_start_time->format('Y-m-d H:i:s');
            $end_str = $slot_end_time->format('Y-m-d H:i:s');

            $check_stmt->bind_param(
                "issssss",
                $doctor_id,
                $end_str,
                $start_str,
                $end_str,
                $start_str,
                $start_str,
                $end_str
            );
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows === 0) {
                // No overlap, create the slot
                $insert_stmt = $conn->prepare("
                    INSERT INTO time_slots (doctor_id, start_time, end_time, location, status, capacity, slot_duration) 
                    VALUES (?, ?, ?, ?, 'available', ?, ?)
                ");
                $insert_stmt->bind_param(
                    "isssii",
                    $doctor_id,
                    $start_str,
                    $end_str,
                    $location,
                    $capacity,
                    $duration_minutes
                );
                $insert_stmt->execute();
                $slots_created++;
            }
        }

        // Move to the next day
        $current_date->modify('+1 day');
    }

    $conn->commit();
    $_SESSION['success'] = "Successfully created {$slots_created} time slots.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error creating time slots: " . $e->getMessage();
}

header("Location: manage_time_slots.php");
exit;
