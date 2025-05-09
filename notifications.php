<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Initialize variables that might be used outside the try block
$error_message = "";
$has_error = false;

// Add CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission.";
        $has_error = true;
    } else {
        $stmt = $conn->prepare("UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}

// Get filter from URL
$valid_filters = ['all', 'appointment', 'system', 'reminder'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $valid_filters) ? $_GET['filter'] : 'all';
$filter_clause = "";
if ($filter !== 'all') {
    $filter_clause = " AND type = ?";
}

// Pagination for notifications
$notification_page = isset($_GET['notification_page']) ? (int)$_GET['notification_page'] : 1;
$limit = 10;
$notification_offset = ($notification_page - 1) * $limit;

try {
    // Fetch notifications
    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM notifications 
        WHERE user_id = ?" . $filter_clause . " 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);

    if ($filter !== 'all') {
        $stmt->bind_param("isii", $user_id, $filter, $limit, $notification_offset);
    } else {
        $stmt->bind_param("iii", $user_id, $limit, $notification_offset);
    }

    $stmt->execute();
    $notifications = $stmt->get_result();

    // Get total rows for pagination
    $total_result = $conn->query("SELECT FOUND_ROWS()");
    $total_rows = $total_result->fetch_row()[0];
    $total_notification_pages = ceil($total_rows / $limit);

    // Get highest notification ID for real-time updates
    $max_id_stmt = $conn->prepare("SELECT MAX(id) FROM notifications WHERE user_id = ?");
    $max_id_stmt->bind_param("i", $user_id);
    $max_id_stmt->execute();
    $max_id_result = $max_id_stmt->get_result();
    $max_notification_id = $max_id_result->fetch_row()[0] ?? 0;
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error fetching data: " . $e->getMessage();
    $_SESSION['error'] = $error_message;
    $has_error = true;
}

// Get unread notification count for the badge
$unread_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_result = $stmt->get_result();
if ($unread_result && $row = $unread_result->fetch_row()) {
    $unread_count = $row[0];
}

// Get counts for each notification type with a single query
$type_counts = ['appointment' => 0, 'system' => 0, 'reminder' => 0];
$count_query = "SELECT type, COUNT(*) as count FROM notifications WHERE user_id = ? GROUP BY type";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    if (isset($type_counts[$row['type']])) {
        $type_counts[$row['type']] = $row['count'];
    }
}
$type_counts['all'] = array_sum($type_counts);
?>
<!DOCTYPE html>
<html>

<head>
    <title>My Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            animation: slideIn 0.6s ease-out;
        }

        h2 {
            color: #2A9D8F;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            padding-left: 50px;
        }

        h2 .header-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.8rem;
            color: #2A9D8F;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #E76F51;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        p {
            color: #555;
            margin: 0.8rem 0;
            font-size: 1.1rem;
        }

        .pagination {
            display: flex;
            gap: 0.8rem;
            margin-top: 3rem;
            justify-content: center;
        }

        .pagination a {
            padding: 0.8rem 1.4rem;
            border-radius: 12px;
            background: #fff;
            color: #2A9D8F;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .pagination a:hover {
            transform: translateY(-2px);
            background: #2A9D8F;
            color: white;
            box-shadow: 0 6px 12px rgba(42, 157, 143, 0.3);
        }

        .pagination a.active {
            background: #2A9D8F;
            color: #fff;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            margin-top: 3rem;
            color: #888;
            animation: slideIn 0.6s ease;
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 1.2rem;
            margin: 2rem 0;
            border-radius: 10px;
            border-left: 5px solid #d32f2f;
            animation: slideIn 0.4s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-mark-read {
            padding: 0.8rem 1.6rem;
            border: none;
            background: linear-gradient(135deg, #2A9D8F 0%, #1A6A60 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(42, 157, 143, 0.2);
        }

        .btn-mark-read:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(42, 157, 143, 0.3);
        }

        .btn-mark-read:active {
            transform: scale(0.96);
        }

        .loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .notification-card {
            background: white;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
            transform: translateY(20px);
            position: relative;
        }

        .notification-card.unread {
            background: #f0f7ff;
            border-left: 4px solid #2A9D8F;
        }

        .notification-card.new {
            animation: pulse 2s infinite;
            border-left: 4px solid #E76F51;
            background-color: #fff8f0;
        }

        .notification-message {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            color: #888;
            font-size: 0.9rem;
        }

        .notification-type {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .notification-type.appointment {
            background-color: #e6f7ff;
            color: #0066cc;
        }

        .notification-type.system {
            background-color: #f0f0f0;
            color: #666;
        }

        .notification-type.reminder {
            background-color: #fff8e6;
            color: #cc8800;
        }

        .mark-read-btn {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #2A9D8F;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .mark-read-btn:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            background: #fff;
            color: #555;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-btn.active {
            background: #2A9D8F;
            color: white;
        }

        .filter-btn .count {
            display: inline-block;
            background: rgba(0, 0, 0, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .filter-btn.active .count {
            background: rgba(255, 255, 255, 0.2);
        }

        .notification-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .notification-preferences {
            padding: 0.7rem 1.4rem;
            border: none;
            background: white;
            color: #2A9D8F;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .notification-preferences:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            background: #f9f9f9;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <h2>
            <i class="fas fa-bell header-icon"></i>
            Notifications
            <span class="notification-badge" id="notification-badge"><?= $unread_count ?></span>
        </h2>

        <div class="filter-container">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i>
                All
                <span class="count"><?= $type_counts['all'] ?></span>
            </a>
            <a href="?filter=appointment" class="filter-btn <?= $filter === 'appointment' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                Appointments
                <span class="count"><?= $type_counts['appointment'] ?></span>
            </a>
            <a href="?filter=system" class="filter-btn <?= $filter === 'system' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                System
                <span class="count"><?= $type_counts['system'] ?></span>
            </a>
            <a href="?filter=reminder" class="filter-btn <?= $filter === 'reminder' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                Reminders
                <span class="count"><?= $type_counts['reminder'] ?></span>
            </a>
        </div>

        <div class="notification-actions">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="mark_read" value="1">
                <button type="submit" class="btn-mark-read">
                    <i class="fas fa-check-double"></i>
                    Mark All as Read
                </button>
            </form>

            <a href="notification_preferences.php" class="notification-preferences">
                <i class="fas fa-cog"></i>
                Notification Preferences
            </a>
        </div>

        <div id="notifications-container">
            <?php if ($has_error): ?>
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p>Unable to load notifications. Please try again later.</p>
                </div>
            <?php elseif ($notifications->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash fa-2x"></i>
                    <p>No notifications found</p>
                </div>
            <?php else: ?>
                <?php while ($note = $notifications->fetch_assoc()): ?>
                    <div class="notification-card <?= $note['is_read'] ? '' : 'unread' ?>" data-id="<?= $note['id'] ?>">
                        <div class="notification-message">
                            <?= htmlspecialchars($note['message']) ?>
                        </div>
                        <small class="notification-time">
                            <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                        </small>
                        <span class="notification-type <?= $note['type'] ?>">
                            <?php
                            switch ($note['type']) {
                                case 'appointment':
                                    echo '<i class="fas fa-calendar-alt"></i> ';
                                    break;
                                case 'system':
                                    echo '<i class="fas fa-cog"></i> ';
                                    break;
                                case 'reminder':
                                    echo '<i class="fas fa-clock"></i> ';
                                    break;
                            }
                            echo ucfirst($note['type']);
                            ?>
                        </span>
                        <?php if (!$note['is_read']): ?>
                            <button class="mark-read-btn" data-id="<?= $note['id'] ?>">
                                <i class="fas fa-check"></i> Mark as read
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>

                <?php if ($total_notification_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_notification_pages; $i++): ?>
                            <a href="?filter=<?= htmlspecialchars($filter) ?>&notification_page=<?= $i ?>" <?= $i === $notification_page ? 'class="active"' : '' ?>>
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add staggered animations for cards
        document.querySelectorAll('.notification-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Confirmation for marking all notifications as read
        document.querySelector('.btn-mark-read')?.addEventListener('click', (e) => {
            if (!confirm('Mark all notifications as read?')) {
                e.preventDefault();
            }
        });

        // Variables for real-time notification updates
        let lastNotificationId = <?= $max_notification_id ?? 0 ?>;
        const currentFilter = '<?= htmlspecialchars($filter) ?>';

        // Function to mark individual notification as read
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                const card = document.querySelector(`.notification-card[data-id="${notificationId}"]`);

                // Send AJAX request to mark as read
                const formData = new FormData();
                formData.append('notification_id', notificationId);

                fetch('mark_notification_read.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI
                            card.classList.remove('unread');
                            this.remove();

                            // Update badge count
                            const badge = document.getElementById('notification-badge');
                            badge.textContent = data.unread_count;

                            // Hide badge if no unread notifications
                            if (data.unread_count === 0) {
                                badge.style.display = 'none';
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });

        // Check for new notifications every 15 seconds
        setInterval(() => {
            fetch(`fetch_notifications.php?last_id=${lastNotificationId}&filter=${currentFilter}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        // Update the lastNotificationId
                        lastNotificationId = data.last_id;

                        // Update unread count
                        document.getElementById('notification-badge').textContent = data.unread_count;

                        // Make badge visible again if there are unread notifications
                        if (data.unread_count > 0) {
                            document.getElementById('notification-badge').style.display = '';
                        }

                        // Update notification counters for each filter
                        fetch(`notifications.php?ajax=1`)
                            .then(response => response.json())
                            .then(countData => {
                                if (countData.type_counts) {
                                    // Update all count elements
                                    for (const type in countData.type_counts) {
                                        const countEl = document.querySelector(`.filter-btn[href="?filter=${type}"] .count`);
                                        if (countEl) {
                                            countEl.textContent = countData.type_counts[type];
                                        }
                                    }
                                }
                            });

                        // Add new notifications to the container
                        const container = document.getElementById('notifications-container');

                        // Check if the container shows "No notifications found" and remove it
                        const emptyState = container.querySelector('.empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }

                        // Prepend new notifications that match the current filter
                        data.notifications.forEach(note => {
                            // Check if this notification matches our current filter
                            if (currentFilter === 'all' || note.type === currentFilter) {
                                const card = document.createElement('div');
                                card.className = `notification-card new ${note.is_read ? '' : 'unread'}`;
                                card.dataset.id = note.id;

                                // Create notification type badge
                                let typeIcon = '';
                                switch (note.type) {
                                    case 'appointment':
                                        typeIcon = '<i class="fas fa-calendar-alt"></i>';
                                        break;
                                    case 'system':
                                        typeIcon = '<i class="fas fa-cog"></i>';
                                        break;
                                    case 'reminder':
                                        typeIcon = '<i class="fas fa-clock"></i>';
                                        break;
                                }

                                // Build card content
                                card.innerHTML = `
                                    <div class="notification-message">${note.message}</div>
                                    <small class="notification-time">${note.time_ago}</small>
                                    <span class="notification-type ${note.type}">
                                        ${typeIcon} ${note.type.charAt(0).toUpperCase() + note.type.slice(1)}
                                    </span>
                                    ${note.is_read ? '' : `
                                    <button class="mark-read-btn" data-id="${note.id}">
                                        <i class="fas fa-check"></i> Mark as read
                                    </button>`}
                                `;

                                // Add to container
                                const firstCard = container.querySelector('.notification-card');
                                if (firstCard) {
                                    container.insertBefore(card, firstCard);
                                } else {
                                    const pagination = container.querySelector('.pagination');
                                    if (pagination) {
                                        container.insertBefore(card, pagination);
                                    } else {
                                        container.appendChild(card);
                                    }
                                }

                                // Add event listener to mark-read button
                                if (!note.is_read) {
                                    const btn = card.querySelector('.mark-read-btn');
                                    btn.addEventListener('click', function() {
                                        const formData = new FormData();
                                        formData.append('notification_id', note.id);

                                        fetch('mark_notification_read.php', {
                                                method: 'POST',
                                                body: formData
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    card.classList.remove('unread');
                                                    btn.remove();

                                                    const badge = document.getElementById('notification-badge');
                                                    badge.textContent = data.unread_count;

                                                    if (data.unread_count === 0) {
                                                        badge.style.display = 'none';
                                                    }
                                                }
                                            })
                                            .catch(error => console.error('Error:', error));
                                    });
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error checking for notifications:', error));
        }, 15000);
    </script>
</body>

</html>