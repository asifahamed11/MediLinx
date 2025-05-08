<?php
// This script should be run via cron job to generate appointment reminders
// Recommended schedule: Once per day

require_once 'config.php';

// Get database connection
$conn = connectDB();

// Set timezone
date_default_timezone_set('UTC'); // Adjust to your timezone

// Current date and time
$now = new DateTime();

// Tomorrow's date
$tomorrow = clone $now;
$tomorrow->modify('+1 day');
$tomorrow_date = $tomorrow->format('Y-m-d');

// Fetch appointments for tomorrow
$stmt = $conn->prepare("
    SELECT a.id, a.patient_id, t.doctor_id, t.start_time, t.location, 
           dp.username as doctor_name, pp.username as patient_name
    FROM appointments a
    JOIN time_slots t ON a.slot_id = t.id
    JOIN users dp ON t.doctor_id = dp.id
    JOIN users pp ON a.patient_id = pp.id
    WHERE DATE(t.start_time) = ?
    AND a.status = 'confirmed'
");

$stmt->bind_param("s", $tomorrow_date);
$stmt->execute();
$result = $stmt->get_result();

// Counter for reminders sent
$reminders_sent = 0;

// Process each appointment
while ($appointment = $result->fetch_assoc()) {
    // Create reminder for patient
    $patient_message = "REMINDER: You have an appointment with Dr. " .
        $appointment['doctor_name'] . " tomorrow at " .
        date('g:i A', strtotime($appointment['start_time'])) .
        " at " . $appointment['location'];

    // Create reminder for doctor
    $doctor_message = "REMINDER: You have an appointment with " .
        $appointment['patient_name'] . " tomorrow at " .
        date('g:i A', strtotime($appointment['start_time'])) .
        " at " . $appointment['location'];

    try {
        // Insert notifications
        $patient_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type)
            VALUES (?, ?, 'reminder')
        ");
        $patient_stmt->bind_param("is", $appointment['patient_id'], $patient_message);
        $patient_stmt->execute();

        $doctor_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type)
            VALUES (?, ?, 'reminder')
        ");
        $doctor_stmt->bind_param("is", $appointment['doctor_id'], $doctor_message);
        $doctor_stmt->execute();

        $reminders_sent += 2;
    } catch (Exception $e) {
        error_log("Error sending reminder for appointment ID " . $appointment['id'] . ": " . $e->getMessage());
    }
}

// Log the result
$log_message = date('Y-m-d H:i:s') . " - Generated $reminders_sent reminder notifications\n";
file_put_contents('reminder_log.txt', $log_message, FILE_APPEND);

echo "Completed. Generated $reminders_sent reminder notifications.";
