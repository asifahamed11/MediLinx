<?php
// This script should be run via cron job to send email notifications for unread notifications
// Recommended schedule: Once per hour

require_once 'config.php';
require_once 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get database connection
$conn = connectDB();

// Set timezone
date_default_timezone_set('UTC'); // Adjust to your timezone

// Get users who have enabled email notifications and have unread notifications
$stmt = $conn->prepare("
    SELECT u.id, u.email, u.username, COUNT(n.id) as unread_count
    FROM users u
    JOIN notification_settings ns ON u.id = ns.user_id
    JOIN notifications n ON u.id = n.user_id
    WHERE ns.email_notifications = 1
    AND n.is_read = 0
    GROUP BY u.id
    HAVING unread_count > 0
");

$stmt->execute();
$users = $stmt->get_result();

// Counter for emails sent
$emails_sent = 0;

// Function to get user's notifications
function getUserNotifications($conn, $user_id, $limit = 5)
{
    $stmt = $conn->prepare("
        SELECT * FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Process each user
while ($user = $users->fetch_assoc()) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'notifications@medilinx.com'; // Replace with your email
        $mail->Password   = 'your_password'; // Replace with your password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('notifications@medilinx.com', 'MediLinx Notifications');
        $mail->addAddress($user['email'], $user['username']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'You have ' . $user['unread_count'] . ' unread notifications on MediLinx';

        // Get user's latest notifications
        $notifications = getUserNotifications($conn, $user['id']);

        // Build email body
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h1 { color: #2A9D8F; }
                .notification { padding: 15px; margin-bottom: 15px; background-color: #f9f9f9; border-left: 4px solid #2A9D8F; }
                .time { color: #888; font-size: 12px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #2A9D8F; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Hello " . htmlspecialchars($user['username']) . ",</h1>
                <p>You have " . $user['unread_count'] . " unread notifications on MediLinx. Here are your latest notifications:</p>";

        while ($note = $notifications->fetch_assoc()) {
            $body .= "
                <div class='notification'>
                    <p>" . htmlspecialchars($note['message']) . "</p>
                    <p class='time'>" . date('M j, Y g:i A', strtotime($note['created_at'])) . "</p>
                </div>";
        }

        if ($user['unread_count'] > 5) {
            $body .= "<p>...and " . ($user['unread_count'] - 5) . " more notifications.</p>";
        }

        $body .= "
                <p><a href='http://yourdomain.com/notifications.php' class='button'>View All Notifications</a></p>
                <p>Thank you for using MediLinx!</p>
            </div>
        </body>
        </html>";

        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));

        $mail->send();
        $emails_sent++;

        // Log success
        file_put_contents('email_log.txt', date('Y-m-d H:i:s') . " - Email sent to " . $user['email'] . "\n", FILE_APPEND);
    } catch (Exception $e) {
        // Log error
        file_put_contents('email_log.txt', date('Y-m-d H:i:s') . " - Failed to send email to " . $user['email'] . ": " . $mail->ErrorInfo . "\n", FILE_APPEND);
    }
}

// Log the result
$log_message = date('Y-m-d H:i:s') . " - Sent $emails_sent email notifications\n";
file_put_contents('email_log.txt', $log_message, FILE_APPEND);

echo "Completed. Sent $emails_sent email notifications.";
