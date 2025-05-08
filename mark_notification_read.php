<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Validate and sanitize input
if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$notification_id = (int)$_POST['notification_id'];

try {
    // First, verify the notification belongs to this user
    $check_stmt = $conn->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $notification_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Notification not found or access denied");
    }

    // Mark notification as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        // Get updated unread count
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $unread_count = $count_result->fetch_row()[0];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $unread_count
        ]);
    } else {
        throw new Exception("Failed to mark notification as read");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
