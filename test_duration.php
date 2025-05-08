<?php
require_once 'config.php';

// Connect to the database
$conn = connectDB();

// Test our automatic slot_duration calculation
$start_time = '2023-07-01 09:00:00';
$end_time = '2023-07-01 11:30:00';

$start = new DateTime($start_time);
$end = new DateTime($end_time);

$duration_minutes = round(($end->getTimestamp() - $start->getTimestamp()) / 60);

echo "Start time: $start_time\n";
echo "End time: $end_time\n";
echo "Duration: $duration_minutes minutes\n";

$conn->close();
