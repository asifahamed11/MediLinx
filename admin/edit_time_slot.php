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

$slot_id = intval($_GET['id']);
$success_message = '';
$error_message = '';

// Get time slot data
$stmt = $conn->prepare("SELECT ts.*, u.username as doctor_name 
                       FROM time_slots ts
                       JOIN users u ON ts.doctor_id = u.id
                       WHERE ts.id = ?");
$stmt->bind_param('i', $slot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=time_slots');
    exit;
}

$time_slot = $result->fetch_assoc();

// Get all doctors
$doctors_query = "SELECT id, username FROM users WHERE role = 'doctor' ORDER BY username";
$doctors_result = mysqli_query($conn, $doctors_query);
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['doctor_id']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            throw new Exception("Doctor, start time and end time are required");
        }

        // Validate start time is before end time
        $start_time = new DateTime($_POST['start_time']);
        $end_time = new DateTime($_POST['end_time']);

        if ($start_time >= $end_time) {
            throw new Exception("End time must be after start time");
        }

        // Start transaction
        $conn->begin_transaction();

        // Update time slot
        $update_query = "UPDATE time_slots SET 
                         doctor_id = ?,
                         start_time = ?,
                         end_time = ?,
                         location = ?,
                         status = ?
                         WHERE id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param(
            'issssi',
            $_POST['doctor_id'],
            $_POST['start_time'],
            $_POST['end_time'],
            $_POST['location'],
            $_POST['status'],
            $slot_id
        );

        $update_stmt->execute();

        // Commit transaction
        $conn->commit();
        $success_message = "Time slot updated successfully!";

        // Refresh time slot data
        $stmt->execute();
        $time_slot = $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Time Slot - Medilinx Admin</title>
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

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
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

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="datetime-local"],
        select,
        textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #7f8c8d;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=time_slots" class="back-link">&larr; Back to Time Slots</a>
        <h1>Edit Time Slot</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="doctor_id">Doctor</label>
                <select id="doctor_id" name="doctor_id" required>
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>" <?php if ($doctor['id'] == $time_slot['doctor_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($doctor['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="datetime-local" id="start_time" name="start_time" required
                    value="<?php echo date('Y-m-d\TH:i', strtotime($time_slot['start_time'])); ?>">
            </div>

            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="datetime-local" id="end_time" name="end_time" required
                    value="<?php echo date('Y-m-d\TH:i', strtotime($time_slot['end_time'])); ?>">
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location"
                    value="<?php echo htmlspecialchars($time_slot['location']); ?>">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="available" <?php if ($time_slot['status'] == 'available') echo 'selected'; ?>>Available</option>
                    <option value="booked" <?php if ($time_slot['status'] == 'booked') echo 'selected'; ?>>Booked</option>
                    <option value="cancelled" <?php if ($time_slot['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="admin.php?tab=time_slots" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>