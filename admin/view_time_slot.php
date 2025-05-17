<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Connect to database
$conn = connectDB();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: admin.php?tab=time_slots');
    exit;
}

$time_slot_id = intval($_GET['id']);

// Get time slot data
$query = "SELECT ts.*, u.username as doctor_name, u.specialty as doctor_specialty
          FROM time_slots ts
          JOIN users u ON ts.doctor_id = u.id
          WHERE ts.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $time_slot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=time_slots');
    exit;
}

$time_slot = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Slot Details - Medilinx Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h3 {
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .info-row {
            margin-bottom: 15px;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            min-width: 200px;
        }

        .info-value {
            flex-grow: 1;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-booked {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: #3498db;
        }

        .btn-secondary {
            background-color: #7f8c8d;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .delete-form {
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=time_slots" class="back-link">&larr; Back to Time Slots</a>

        <h1>Time Slot Details</h1>

        <div class="section">
            <h3>Time Slot Information</h3>

            <div class="info-row">
                <div class="info-label">Time Slot ID:</div>
                <div class="info-value"><?php echo $time_slot['id']; ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Start Time:</div>
                <div class="info-value"><?php echo date('F j, Y - h:i A', strtotime($time_slot['start_time'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">End Time:</div>
                <div class="info-value"><?php echo date('F j, Y - h:i A', strtotime($time_slot['end_time'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value"><?php echo htmlspecialchars($time_slot['location']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status status-<?php echo $time_slot['status']; ?>">
                        <?php echo ucfirst($time_slot['status']); ?>
                    </span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Capacity:</div>
                <div class="info-value"><?php echo $time_slot['capacity']; ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Booked Count:</div>
                <div class="info-value"><?php echo $time_slot['booked_count']; ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Slot Duration:</div>
                <div class="info-value"><?php echo $time_slot['slot_duration']; ?> minutes</div>
            </div>
        </div>

        <div class="section">
            <h3>Doctor Information</h3>

            <div class="info-row">
                <div class="info-label">Doctor Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($time_slot['doctor_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Specialty:</div>
                <div class="info-value"><?php echo htmlspecialchars($time_slot['doctor_specialty'] ?? 'Not specified'); ?></div>
            </div>

            <a href="view_doctor.php?id=<?php echo $time_slot['doctor_id']; ?>" class="btn btn-primary">View Doctor Profile</a>
        </div>

        <div class="actions">
            <a href="edit_time_slot.php?id=<?php echo $time_slot_id; ?>" class="btn btn-primary">Edit Time Slot</a>
            <a href="admin.php?tab=time_slots" class="btn btn-secondary">Back to Time Slots</a>

            <form method="POST" action="admin.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this time slot?');">
                <input type="hidden" name="time_slot_id" value="<?php echo $time_slot_id; ?>">
                <button type="submit" name="delete_time_slot" class="btn btn-danger">Delete Time Slot</button>
            </form>
        </div>
    </div>
</body>

</html>