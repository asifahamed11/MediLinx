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

if ($role === 'doctor') {
    $appointments = $conn->query("
        SELECT a.*, u.username, t.start_time, t.location 
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        JOIN time_slots t ON a.slot_id = t.id
        WHERE t.doctor_id = $user_id
    ");
} else {
    $appointments = $conn->query("
        SELECT a.*, u.username, t.start_time, t.location 
        FROM appointments a
        JOIN users u ON t.doctor_id = u.id
        JOIN time_slots t ON a.slot_id = t.id
        WHERE a.patient_id = $user_id
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <style>
    .appointment-card {
        background: white;
        padding: 1.5rem;
        margin: 1rem 0;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 1rem;
        font-size: 0.9rem;
    }
    .confirmed { background: #d1fae5; color: #065f46; }
    .completed { background: #e0f2fe; color: #075985; }
    .cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h2>My Appointments</h2>
        
        <?php while ($apt = $appointments->fetch_assoc()): ?>
            <div class="appointment-card">
                <div class="header">
                    <h4>With <?= htmlspecialchars($apt['username']) ?></h4>
                    <span class="status-badge <?= $apt['status'] ?>">
                        <?= ucfirst($apt['status']) ?>
                    </span>
                </div>
                <p><?= date('M j, Y g:i A', strtotime($apt['start_time'])) ?></p>
                <p>Location: <?= htmlspecialchars($apt['location']) ?></p>
                <?php if ($apt['status'] === 'confirmed'): ?>
                    <form method="POST" action="cancel_appointment.php">
                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                        <button type="submit" class="btn-cancel">
                            Cancel Appointment
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>