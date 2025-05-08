<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if slot_id is provided
if (!isset($_GET['slot_id']) || !is_numeric($_GET['slot_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid slot ID']);
    exit;
}

$slot_id = (int)$_GET['slot_id'];

// Connect to database
$conn = connectDB();

// Check if the user has already booked this slot
$user_booked_check = $conn->prepare("
    SELECT id FROM appointments 
    WHERE patient_id = ? AND slot_id = ?
");
$user_booked_check->bind_param("ii", $_SESSION['user_id'], $slot_id);
$user_booked_check->execute();
$user_has_booked = $user_booked_check->get_result()->num_rows > 0;

if ($user_has_booked) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You have already booked this appointment']);
    exit;
}

// Fetch slot details with doctor information and booking count
$stmt = $conn->prepare("
    SELECT ts.*, 
           u.username as doctor_name,
           IFNULL(ts.capacity, 20) as capacity,
           IFNULL(ts.booked_count, 0) as booked_count,
           (SELECT COUNT(*) FROM appointments a WHERE a.slot_id = ts.id) as current_bookings,
           (SELECT COUNT(*) FROM appointments a WHERE a.slot_id = ts.id AND a.patient_id = ?) as user_has_booked
    FROM time_slots ts
    JOIN users u ON ts.doctor_id = u.id
    WHERE ts.id = ? AND ts.status = 'available'
");

$stmt->bind_param("ii", $_SESSION['user_id'], $slot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Time slot not found or not available']);
    exit;
}

$slot = $result->fetch_assoc();

// Calculate spaces left
$current_bookings = isset($slot['current_bookings']) ? (int)$slot['current_bookings'] : $slot['booked_count'];
$spaces_left = $slot['capacity'] - $current_bookings;

// Format response
$response = [
    'success' => true,
    'slot' => [
        'id' => $slot['id'],
        'doctor_id' => $slot['doctor_id'],
        'doctor_name' => $slot['doctor_name'],
        'start_time' => $slot['start_time'],
        'end_time' => $slot['end_time'],
        'location' => $slot['location'],
        'status' => $slot['status'],
        'capacity' => (int)$slot['capacity'],
        'current_bookings' => $current_bookings,
        'spaces_left' => $spaces_left,
        'user_has_booked' => (bool)$slot['user_has_booked']
    ]
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
