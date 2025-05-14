<?php
session_start();
require_once 'config.php';
$conn = connectDB();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: admin.php?tab=patients');
    exit;
}

$patient_id = intval($_GET['id']);
$patient = [];
$medical_history = [];
$appointments = [];

try {
    // Get patient info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    if (!$patient) {
        header('Location: admin.php?tab=patients');
        exit;
    }

    // Get medical history if exists
    $history_stmt = $conn->prepare("SELECT * FROM medical_history WHERE patient_id = ? ORDER BY date DESC");
    if ($history_stmt) {
        $history_stmt->bind_param('i', $patient_id);
        $history_stmt->execute();
        $medical_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get appointments
    $appointment_stmt = $conn->prepare("
        SELECT a.*, u.username as doctor_name, ts.start_time, ts.end_time 
        FROM appointments a
        JOIN time_slots ts ON a.slot_id = ts.id
        JOIN users u ON ts.doctor_id = u.id
        WHERE a.patient_id = ?
        ORDER BY ts.start_time DESC
    ");
    $appointment_stmt->bind_param('i', $patient_id);
    $appointment_stmt->execute();
    $appointments = $appointment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching patient data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patient - Medilinx Admin</title>
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

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .profile-info {
            flex-grow: 1;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 10px;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            min-width: 150px;
        }

        .info-value {
            flex-grow: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
            margin-right: 10px;
        }

        .btn:hover {
            background: #2980b9;
        }

        .no-data {
            padding: 15px;
            background: #eee;
            border-radius: 4px;
            text-align: center;
            color: #777;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #f39c12;
            color: white;
        }

        .status-confirmed {
            background-color: #2ecc71;
            color: white;
        }

        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .status-completed {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=patients" class="back-link">&larr; Back to Patients</a>
        <h1>Patient Profile</h1>

        <div class="profile-header">
            <?php if (!empty($patient['profile_image'])): ?>
                <img src="../<?= htmlspecialchars($patient['profile_image']) ?>" alt="Profile Image" class="profile-img">
            <?php else: ?>
                <div class="profile-img" style="background-color: #ccc; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 36px; color: #fff;"><?= strtoupper(substr($patient['username'], 0, 1)) ?></span>
                </div>
            <?php endif; ?>

            <div class="profile-info">
                <h2><?= htmlspecialchars($patient['username']) ?></h2>
                <p><?= htmlspecialchars($patient['email']) ?></p>
            </div>
        </div>

        <div class="section">
            <h3>Personal Information</h3>
            <div class="info-row">
                <div class="info-label">Full Name:</div>
                <div class="info-value"><?= htmlspecialchars($patient['full_name'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value"><?= htmlspecialchars($patient['date_of_birth'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value"><?= htmlspecialchars($patient['gender'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone Number:</div>
                <div class="info-value"><?= htmlspecialchars($patient['phone'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Address:</div>
                <div class="info-value"><?= htmlspecialchars($patient['address'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Blood Type:</div>
                <div class="info-value"><?= htmlspecialchars($patient['blood_type'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Emergency Contact:</div>
                <div class="info-value"><?= htmlspecialchars($patient['emergency_contact'] ?? 'Not provided') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Joined:</div>
                <div class="info-value"><?= date('F j, Y', strtotime($patient['created_at'])) ?></div>
            </div>
        </div>

        <div class="section">
            <h3>Medical History</h3>
            <?php if (count($medical_history) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Condition</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medical_history as $history): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($history['date'])) ?></td>
                                <td><?= htmlspecialchars($history['condition_name']) ?></td>
                                <td><?= htmlspecialchars($history['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No medical history found</div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Appointments</h3>
            <?php if (count($appointments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($appointment['start_time'])) ?></td>
                                <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($appointment['reason'] ?? 'Not specified') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                        <?= ucfirst($appointment['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No appointments found</div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="edit_patient.php?id=<?= $patient_id ?>" class="btn">Edit Patient</a>
            <a href="admin.php?tab=patients" class="btn" style="background-color: #7f8c8d;">Back to List</a>
        </div>
    </div>
</body>

</html>