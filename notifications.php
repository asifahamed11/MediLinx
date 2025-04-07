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
    $stmt = $conn->prepare("UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get notifications with pagination
$stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$notifications = $stmt->get_result();

// Get total rows for pagination
$total_result = $conn->query("SELECT FOUND_ROWS()");
$total_rows = $total_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
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
    .pagination {
        display: flex;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    .pagination a {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        background: #f0f2f5;
        color: #2A9D8F;
        text-decoration: none;
    }
    .pagination a.active {
        background: #2A9D8F;
        color: white;
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
        
        <?php if ($notifications->num_rows === 0): ?>
            <div class="empty-state">
                <p>No notifications found</p>
            </div>
        <?php else: ?>
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
            
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.querySelector('form').addEventListener('submit', (e) => {
        if (!confirm('Mark all notifications as read?')) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>