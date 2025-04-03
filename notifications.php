<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = $user_id");
}

// Get notifications
$notifications = $conn->query("SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <style>
    .notification-card {
        background: white;
        padding: 1.5rem;
        margin: 1rem 0;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .unread {
        background: #f8f9fa;
        border-left: 4px solid var(--primary);
    }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h2>Notifications</h2>
        <form method="POST">
            <button type="submit" class="btn-mark-read">
                Mark All as Read
            </button>
        </form>
        
        <?php while ($note = $notifications->fetch_assoc()): ?>
            <div class="notification-card <?= $note['is_read'] ? '' : 'unread' ?>">
                <div class="notification-message">
                    <?= htmlspecialchars($note['message']) ?>
                </div>
                <small class="notification-time">
                    <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                </small>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>