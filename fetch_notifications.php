<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Get last notification ID if provided
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Get filter if provided and validate it
$valid_filters = ['all', 'appointment', 'system', 'reminder'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $valid_filters) ? $_GET['filter'] : 'all';
$filter_clause = "";

if ($filter !== 'all') {
    $filter_clause = "AND type = ?";
}

try {
    // Check for new notifications
    $query = "SELECT * FROM notifications 
              WHERE user_id = ? AND id > ? " . $filter_clause . "
              ORDER BY created_at DESC LIMIT 10";

    $stmt = $conn->prepare($query);

    if ($filter !== 'all') {
        $stmt->bind_param("iis", $user_id, $last_id, $filter);
    } else {
        $stmt->bind_param("ii", $user_id, $last_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    $max_id = $last_id;

    while ($row = $result->fetch_assoc()) {
        $max_id = max($max_id, $row['id']);

        $notifications[] = [
            'id' => $row['id'],
            'message' => htmlspecialchars($row['message']),
            'type' => htmlspecialchars($row['type']),
            'is_read' => $row['is_read'],
            'created_at' => date('M j, Y g:i A', strtotime($row['created_at'])),
            'time_ago' => time_elapsed_string($row['created_at'])
        ];
    }

    // Get unread count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unread_result = $stmt->get_result();
    $unread_count = $unread_result->fetch_row()[0];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'last_id' => $max_id
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks from days
    $weeks = floor($diff->d / 7);
    $days_remainder = $diff->d % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => $weeks > 0 ? $weeks : 0,
        'd' => $days_remainder,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    foreach ($string as $k => &$v) {
        if ($k === 'w' || $k === 'd' || $k === 'h' || $k === 'i' || $k === 's') {
            if ($v > 0) {
                $v = $v . ' ' . ($k === 'w' ? 'week' : ($k === 'd' ? 'day' : ($k === 'h' ? 'hour' : ($k === 'i' ? 'minute' : 'second')))) . ($v > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        } else {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
