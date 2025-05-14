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
    header('Location: admin.php?tab=appointments');
    exit;
}

$appointment_id = intval($_GET['id']);
$success_message = '';
$error_message = '';

// Get appointment data
$query = "SELECT a.*, 
                 p.username as patient_name, p.email as patient_email, p.phone as patient_phone,
                 d.username as doctor_name, d.specialty as doctor_specialty,
                 ts.start_time, ts.end_time, ts.location, ts.doctor_id, ts.id as time_slot_id
          FROM appointments a
          JOIN users p ON a.patient_id = p.id
          JOIN time_slots ts ON a.slot_id = ts.id
          JOIN users d ON ts.doctor_id = d.id
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=appointments');
    exit;
}

$appointment = $result->fetch_assoc();

// Get all time slots for the dropdown
$time_slots_query = "SELECT ts.id, ts.start_time, ts.end_time, ts.location, u.username as doctor_name
                     FROM time_slots ts
                     JOIN users u ON ts.doctor_id = u.id
                     WHERE ts.status != 'cancelled'
                     ORDER BY ts.start_time DESC
                     LIMIT 100";
$time_slots_result = mysqli_query($conn, $time_slots_query);
$time_slots = mysqli_fetch_all($time_slots_result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['slot_id']) || empty($_POST['status'])) {
            throw new Exception("Time slot and status are required");
        }

        // Start transaction
        $conn->begin_transaction();

        // Get new time slot data
        $slot_stmt = $conn->prepare("SELECT start_time, end_time, location, doctor_id FROM time_slots WHERE id = ?");
        $slot_stmt->bind_param('i', $_POST['slot_id']);
        $slot_stmt->execute();
        $slot_result = $slot_stmt->get_result();

        if ($slot_result->num_rows === 0) {
            throw new Exception("Selected time slot not found");
        }

        $slot = $slot_result->fetch_assoc();

        // Update appointment
        $update_query = "UPDATE appointments SET 
                         slot_id = ?,
                         reason = ?,
                         status = ?,
                         admin_notes = ?
                         WHERE id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param(
            'isssi',
            $_POST['slot_id'],
            $_POST['reason'],
            $_POST['status'],
            $_POST['admin_notes'],
            $appointment_id
        );

        $update_stmt->execute();

        // Commit transaction
        $conn->commit();
        $success_message = "Appointment updated successfully!";

        // Refresh appointment data
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
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
    <title>Edit Appointment - Medilinx Admin</title>
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

        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
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

        .patient-info {
            margin-bottom: 10px;
        }

        .patient-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .patient-details {
            color: #7f8c8d;
            font-size: 14px;
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
        <a href="admin.php?tab=appointments" class="back-link">&larr; Back to Appointments</a>
        <h1>Edit Appointment</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Patient Information</h2>
            <div class="patient-info">
                <div class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                <div class="patient-details">
                    Email: <?php echo htmlspecialchars($appointment['patient_email']); ?><br>
                    Phone: <?php echo htmlspecialchars($appointment['patient_phone'] ?? 'Not provided'); ?>
                </div>
            </div>
            <a href="view_patient.php?id=<?php echo $appointment['patient_id']; ?>" class="btn btn-primary" style="margin-top: 10px;">View Patient Profile</a>
        </div>

        <form action="" method="post">
            <div class="section">
                <h2>Appointment Details</h2>

                <div class="form-group">
                    <label for="slot_id">Time Slot</label>
                    <select id="slot_id" name="slot_id" required>
                        <option value="">Select Time Slot</option>
                        <?php foreach ($time_slots as $slot): ?>
                            <option value="<?php echo $slot['id']; ?>" <?php if ($slot['id'] == $appointment['time_slot_id']) echo 'selected'; ?>>
                                <?php echo date('M d, Y h:i A', strtotime($slot['start_time'])); ?> -
                                <?php echo date('h:i A', strtotime($slot['end_time'])); ?> |
                                Dr. <?php echo htmlspecialchars($slot['doctor_name']); ?> |
                                <?php echo htmlspecialchars($slot['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Visit</label>
                    <textarea id="reason" name="reason"><?php echo htmlspecialchars($appointment['reason'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="confirmed" <?php if ($appointment['status'] == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="completed" <?php if ($appointment['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if ($appointment['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea id="admin_notes" name="admin_notes"><?php echo htmlspecialchars($appointment['admin_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>