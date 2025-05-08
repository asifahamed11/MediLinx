<?php
session_start();
require_once 'config.php';

// Only allow access to administrators or programmatic access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // This is a one-time script that can be run directly
    // We'll allow it to run without authentication for setup purposes
    // But you should delete this file after running it
}

// Connect to the database
$conn = connectDB();

// Add the slot_duration column to the time_slots table if it doesn't exist
try {
    $result = $conn->query("SHOW COLUMNS FROM time_slots LIKE 'slot_duration'");

    if ($result->num_rows === 0) {
        // Column doesn't exist, add it
        $conn->query("ALTER TABLE time_slots ADD COLUMN slot_duration INT DEFAULT 30 COMMENT 'Duration of each appointment slot in minutes'");
        echo "Successfully added slot_duration column to time_slots table.";
    } else {
        echo "slot_duration column already exists in the time_slots table.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();

// Reminder to delete this file
echo "<p>Important: Delete this file after use as it presents a security risk if left on the server.</p>";
