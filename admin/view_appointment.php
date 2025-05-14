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
                 ts.start_time, ts.end_time, ts.location
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = isset($_POST['admin_notes']) ? $_POST['admin_notes'] : '';

    try {
        // Update appointment status
        $update_stmt = $conn->prepare("UPDATE appointments SET status = ?, admin_notes = ? WHERE id = ?");
        $update_stmt->bind_param('ssi', $new_status, $notes, $appointment_id);

        if ($update_stmt->execute()) {
            // Send notification to patient
            $patient_id = $appointment['patient_id'];
            $appointment_date = date('M d, Y', strtotime($appointment['start_time']));
            $message = "Your appointment on $appointment_date has been updated to: " . ucfirst($new_status);

            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notify_stmt->bind_param('is', $patient_id, $message);
            $notify_stmt->execute();

            $success_message = "Appointment status updated successfully!";

            // Refresh appointment data
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
        } else {
            $error_message = "Failed to update appointment status.";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Medilinx Admin</title>
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

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-pending {
            background-color: #f39c12;
            color: white;
        }

        .badge-confirmed {
            background-color: #2ecc71;
            color: white;
        }

        .badge-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .badge-completed {
            background-color: #3498db;
            color: white;
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

        .btn-success {
            background-color: #2ecc71;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-secondary {
            background-color: #7f8c8d;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=appointments" class="back-link">&larr; Back to Appointments</a>

        <h1>Appointment Details</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Appointment Information</h3>

            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <?php
                    $status_class = '';
                    switch ($appointment['status']) {
                        case 'pending':
                            $status_class = 'badge-pending';
                            break;
                        case 'confirmed':
                            $status_class = 'badge-confirmed';
                            break;
                        case 'cancelled':
                            $status_class = 'badge-cancelled';
                            break;
                        case 'completed':
                            $status_class = 'badge-completed';
                            break;
                    }
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Date:</div>
                <div class="info-value"><?php echo date('l, F j, Y', strtotime($appointment['start_time'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Time:</div>
                <div class="info-value">
                    <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> -
                    <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['location']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Reason for Visit:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['reason']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Created On:</div>
                <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($appointment['created_at'])); ?></div>
            </div>

            <?php if (!empty($appointment['admin_notes'])): ?>
                <div class="info-row">
                    <div class="info-label">Admin Notes:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($appointment['admin_notes'])); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Patient Information</h3>

            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'Not provided'); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Patient ID:</div>
                <div class="info-value"><?php echo $appointment['patient_id']; ?></div>
            </div>

            <a href="view_patient.php?id=<?php echo $appointment['patient_id']; ?>" class="btn btn-primary">View Patient Profile</a>
        </div>

        <div class="section">
            <h3>Doctor Information</h3>

            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Specialty:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['doctor_specialty'] ?? 'Not specified'); ?></div>
            </div>

            <a href="view_doctor.php?id=<?php echo $appointment['doctor_id']; ?>" class="btn btn-primary">View Doctor Profile</a>
        </div>

        <div class="section">
            <h3>Update Appointment Status</h3>

            <form method="post" action="">
                <div>
                    <label for="status">Appointment Status:</label>
                    <select id="status" name="status">
                        <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div>
                    <label for="admin_notes">Admin Notes (optional):</label>
                    <textarea id="admin_notes" name="admin_notes"><?php echo htmlspecialchars($appointment['admin_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
            </form>
        </div>

        <div class="actions">
            <a href="admin.php?tab=appointments" class="btn btn-secondary">Back to List</a>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</body>

</html>