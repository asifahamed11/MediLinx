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
$success_message = '';
$error_message = '';

// Get all patients
$patients_query = "SELECT id, username FROM users WHERE role = 'patient' ORDER BY username";
$patients_result = mysqli_query($conn, $patients_query);
$patients = mysqli_fetch_all($patients_result, MYSQLI_ASSOC);

// Get all time slots
$time_slots_query = "SELECT ts.id, ts.start_time, ts.end_time, ts.location, u.username as doctor_name 
                     FROM time_slots ts
                     JOIN users u ON ts.doctor_id = u.id
                     WHERE ts.status = 'available'
                     ORDER BY ts.start_time DESC";
$time_slots_result = mysqli_query($conn, $time_slots_query);
$time_slots = mysqli_fetch_all($time_slots_result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['patient_id']) || empty($_POST['slot_id'])) {
            throw new Exception("Patient and time slot are required");
        }

        // Get slot details
        $slot_query = "SELECT doctor_id, start_time, end_time, location FROM time_slots WHERE id = ?";
        $slot_stmt = $conn->prepare($slot_query);
        $slot_stmt->bind_param('i', $_POST['slot_id']);
        $slot_stmt->execute();
        $slot_result = $slot_stmt->get_result();

        if ($slot_result->num_rows === 0) {
            throw new Exception("Selected time slot not found");
        }

        $slot = $slot_result->fetch_assoc();

        // Start transaction
        $conn->begin_transaction();

        // Insert appointment
        $insert_query = "INSERT INTO appointments (patient_id, slot_id, doctor_id, start_time, end_time, location, reason, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";

        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param(
            'iiissss',
            $_POST['patient_id'],
            $_POST['slot_id'],
            $slot['doctor_id'],
            $slot['start_time'],
            $slot['end_time'],
            $slot['location'],
            $_POST['reason']
        );

        $insert_stmt->execute();

        // Update time slot status if needed
        $update_slot = "UPDATE time_slots SET booked_count = booked_count + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_slot);
        $update_stmt->bind_param('i', $_POST['slot_id']);
        $update_stmt->execute();

        // Commit transaction
        $conn->commit();
        $success_message = "Appointment added successfully!";
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment - Medilinx Admin</title>
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
        select,
        textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            height: 100px;
            resize: vertical;
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

        .time-slot-option {
            font-weight: normal;
        }

        .doctor-name {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=appointments" class="back-link">&larr; Back to Appointments</a>
        <h1>Add New Appointment</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="patient_id">Patient</label>
                <select id="patient_id" name="patient_id" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['id']; ?>">
                            <?php echo htmlspecialchars($patient['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="slot_id">Time Slot</label>
                <select id="slot_id" name="slot_id" required>
                    <option value="">Select Time Slot</option>
                    <?php foreach ($time_slots as $slot): ?>
                        <option value="<?php echo $slot['id']; ?>">
                            <span class="doctor-name"><?php echo htmlspecialchars($slot['doctor_name']); ?></span> -
                            <?php echo date('M d, Y H:i', strtotime($slot['start_time'])); ?> to
                            <?php echo date('H:i', strtotime($slot['end_time'])); ?> at
                            <?php echo htmlspecialchars($slot['location']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="reason">Reason for Visit</label>
                <textarea id="reason" name="reason" placeholder="Enter the reason for the appointment"></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Add Appointment</button>
                <a href="admin.php?tab=appointments" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>