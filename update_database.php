<?php
require_once 'config.php';

$conn = connectDB();

// Check if columns already exist to avoid errors
$result = mysqli_query($conn, 'SHOW COLUMNS FROM time_slots LIKE "capacity"');
if (mysqli_num_rows($result) == 0) {
    $sql = 'ALTER TABLE time_slots ADD COLUMN capacity INT DEFAULT 20, ADD COLUMN booked_count INT DEFAULT 0';
    if (mysqli_query($conn, $sql)) {
        echo 'Database updated successfully';
    } else {
        echo 'Error updating database: ' . mysqli_error($conn);
    }
} else {
    echo 'Database columns already exist';
}

// Update any existing time slots to set initial capacity and booked count
$sql = 'UPDATE time_slots SET capacity = 20, booked_count = 0 WHERE capacity IS NULL';
mysqli_query($conn, $sql);

// Update booked slots to count current bookings
$sql = "UPDATE time_slots ts 
        SET ts.booked_count = (
            SELECT COUNT(*) FROM appointments a WHERE a.slot_id = ts.id
        )";
mysqli_query($conn, $sql);

// Close the connection
mysqli_close($conn);
echo "<br>Database update completed";
