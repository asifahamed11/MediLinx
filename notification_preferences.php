<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if the notification_settings table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'notification_settings'");
if ($check_table->num_rows == 0) {
    try {
        $create_table = "CREATE TABLE notification_settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            email_notifications BOOLEAN DEFAULT 1,
            appointment_notifications BOOLEAN DEFAULT 1,
            system_notifications BOOLEAN DEFAULT 1,
            reminder_notifications BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id)
        )";

        if (!$conn->query($create_table)) {
            throw new Exception("Error creating notification_settings table: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get user notification settings
$stmt = $conn->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// If settings don't exist yet, create them with defaults
if ($result->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO notification_settings 
        (user_id, email_notifications, appointment_notifications, system_notifications, reminder_notifications) 
        VALUES (?, 1, 1, 1, 1)");
    $insert->bind_param("i", $user_id);
    $insert->execute();

    $stmt->execute();
    $result = $stmt->get_result();
}

$settings = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission.";
    } else {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $appointment_notifications = isset($_POST['appointment_notifications']) ? 1 : 0;
        $system_notifications = isset($_POST['system_notifications']) ? 1 : 0;
        $reminder_notifications = isset($_POST['reminder_notifications']) ? 1 : 0;

        $update = $conn->prepare("UPDATE notification_settings 
            SET email_notifications = ?, 
                appointment_notifications = ?, 
                system_notifications = ?, 
                reminder_notifications = ? 
            WHERE user_id = ?");
        $update->bind_param(
            "iiiii",
            $email_notifications,
            $appointment_notifications,
            $system_notifications,
            $reminder_notifications,
            $user_id
        );

        if ($update->execute()) {
            $success_message = "Notification preferences updated successfully!";
            // Update the settings to reflect changes
            $stmt->execute();
            $result = $stmt->get_result();
            $settings = $result->fetch_assoc();
        } else {
            $error_message = "Failed to update preferences. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Notification Preferences</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: #2A9D8F;
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-group:hover {
            background-color: #f0f7ff;
            transform: translateY(-2px);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2A9D8F;
        }

        input:focus+.slider {
            box-shadow: 0 0 1px #2A9D8F;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .option-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .option-text {
            flex-grow: 1;
        }

        .option-text h4 {
            margin: 0 0 8px 0;
            color: #264653;
        }

        .option-text p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .btn-save {
            background: linear-gradient(135deg, #2A9D8F 0%, #1A6A60 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            display: block;
            width: 100%;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(42, 157, 143, 0.2);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(42, 157, 143, 0.3);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            animation: slideIn 0.4s ease;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2><i class="fas fa-bell" style="margin-right: 10px;"></i>Notification Preferences</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="settings-card">
                <div class="form-group">
                    <div class="option-container">
                        <div class="option-text">
                            <h4>Email Notifications</h4>
                            <p>Receive notifications via email for important updates</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_notifications" <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="option-container">
                        <div class="option-text">
                            <h4>Appointment Notifications</h4>
                            <p>Notifications about appointment bookings, cancellations and reminders</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="appointment_notifications" <?= $settings['appointment_notifications'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="option-container">
                        <div class="option-text">
                            <h4>System Notifications</h4>
                            <p>Important system updates and announcements</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="system_notifications" <?= $settings['system_notifications'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="option-container">
                        <div class="option-text">
                            <h4>Reminder Notifications</h4>
                            <p>Receive reminders about upcoming appointments</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="reminder_notifications" <?= $settings['reminder_notifications'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Save Preferences
                </button>
            </div>
        </form>
    </div>

    <script>
        // Add animation to the form groups
        document.querySelectorAll('.form-group').forEach((group, index) => {
            group.style.animationDelay = `${index * 0.1}s`;
            group.style.opacity = '0';
            group.style.animation = 'fadeIn 0.5s ease forwards';
        });
    </script>
</body>

</html>