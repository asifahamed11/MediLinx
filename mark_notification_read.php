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
    // Use a transaction to prevent race conditions
    $conn->begin_transaction();

    // Verify and update in a single operation
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 
                                 WHERE id = ? AND user_id = ? AND is_read = 0");
    $update_stmt->bind_param("ii", $notification_id, $user_id);
    $update_stmt->execute();

    // Check if the notification was found and belonged to the user
    if ($update_stmt->affected_rows === 0) {
        // Check if notification exists but is already read
        $check_stmt = $conn->prepare("SELECT id, is_read FROM notifications WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $notification_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Notification not found or access denied");
        } else {
            $row = $result->fetch_assoc();
            if ($row['is_read'] === 1) {
                // Notification already marked as read - not an error
                $already_read = true;
            }
        }
    }

    // Get updated unread count
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $unread_count = $count_result->fetch_row()[0];

    // Commit the transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => isset($already_read) ? 'Notification already marked as read' : 'Notification marked as read',
        'unread_count' => $unread_count
    ]);
} catch (Exception $e) {
    // Rollback the transaction on error
    try {
        $conn->rollback();
    } catch (Exception $rollbackEx) {
        // Transaction may not have been active
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
