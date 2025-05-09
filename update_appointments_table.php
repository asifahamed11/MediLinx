<?php
// This script updates the appointments table structure to include missing columns
require_once 'config.php';

try {
    $conn = connectDB();

    // Start transaction
    $conn->begin_transaction();

    // Check if columns exist before trying to add them
    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'doctor_id'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN doctor_id int(11) DEFAULT NULL AFTER patient_id");
        echo "Added doctor_id column<br>";
    }

    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'start_time'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN start_time datetime DEFAULT NULL AFTER slot_id");
        echo "Added start_time column<br>";
    }

    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'end_time'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN end_time datetime DEFAULT NULL AFTER start_time");
        echo "Added end_time column<br>";
    }

    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'location'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN location varchar(255) DEFAULT NULL AFTER end_time");
        echo "Added location column<br>";
    }

    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'updated_at'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN updated_at timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER created_at");
        echo "Added updated_at column<br>";
    }

    // Check if id column has AUTO_INCREMENT
    $result = $conn->query("SHOW COLUMNS FROM appointments WHERE Field = 'id'");
    $row = $result->fetch_assoc();
    if ($row && strpos($row['Extra'], 'auto_increment') === false) {
        // Need to add AUTO_INCREMENT to the id column
        $conn->query("ALTER TABLE appointments MODIFY id int(11) NOT NULL AUTO_INCREMENT");
        echo "Added AUTO_INCREMENT to id column<br>";
    }

    // Update existing records to populate the doctor_id, start_time, end_time, and location from time_slots
    $conn->query("
        UPDATE appointments a
        JOIN time_slots ts ON a.slot_id = ts.id
        SET 
            a.doctor_id = ts.doctor_id, 
            a.start_time = ts.start_time, 
            a.end_time = ts.end_time, 
            a.location = ts.location
        WHERE a.doctor_id IS NULL
    ");
    echo "Updated existing appointments with data from time_slots<br>";

    // Commit the transaction
    $conn->commit();
    echo "Database updates completed successfully!";
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "Error updating database: " . $e->getMessage();
}
