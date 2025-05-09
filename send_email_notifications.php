<?php
// This script should be run via cron job to send email notifications for unread notifications
// Recommended schedule: Once per hour

require_once 'config.php';
require_once 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use configuration from config.php instead of hardcoded values
$email_config = [
    'host' => SMTP_HOST,
    'username' => SMTP_USERNAME,
    'password' => SMTP_PASSWORD,
    'from_email' => SMTP_USERNAME,
    'from_name' => 'MediLinx Notifications',
    'port' => SMTP_PORT,
    'encryption' => 'ssl' // Will use PHPMailer::ENCRYPTION_SMTPS
];

// Get database connection
$conn = connectDB();

// Set timezone based on config or default to UTC
$timezone = getenv('TIMEZONE') ?: 'UTC';
date_default_timezone_set($timezone);

// Log error/info messages
function log_message($message, $type = 'info')
{
    $log_file = 'email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[$timestamp] [$type] $message\n";
    file_put_contents($log_file, $log_line, FILE_APPEND);
}

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
        $mail->Host       = $email_config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['username'];
        $mail->Password   = $email_config['password'];
        $mail->SMTPSecure = $email_config['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $email_config['port'];

        // Recipients
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
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

        // Use dynamic site URL from config or environment variable if available
        $site_url = getenv('SITE_URL') ?: 'http://yourdomain.com';

        $body .= "
                <p><a href='" . $site_url . "/notifications.php' class='button'>View All Notifications</a></p>
                <p>Thank you for using MediLinx!</p>
            </div>
        </body>
        </html>";

        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));

        $mail->send();
        $emails_sent++;

        // Log success
        log_message("Email sent to " . $user['email'], 'success');
    } catch (Exception $e) {
        // Log error
        log_message("Failed to send email to " . $user['email'] . ": " . $mail->ErrorInfo, 'error');
    }
}

// Log the result
log_message("Sent $emails_sent email notifications");

echo "Completed. Sent $emails_sent email notifications.";
