<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_message = "";
$error_message = "";

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Process cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission.";
    } else {
        $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
        $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';

        if ($appointment_id <= 0) {
            $error_message = "Invalid appointment ID.";
        } else {
            try {
                // Start transaction
                $conn->begin_transaction();

                // First, get appointment details to verify ownership and create notifications
                $stmt = $conn->prepare("
                    SELECT a.*, t.start_time, t.doctor_id, 
                           d.username as doctor_name, p.username as patient_name
                    FROM appointments a
                    JOIN time_slots t ON a.slot_id = t.id
                    JOIN users d ON t.doctor_id = d.id
                    JOIN users p ON a.patient_id = p.id
                    WHERE a.id = ? 
                    AND (a.patient_id = ? OR t.doctor_id = ?)
                    AND a.status = 'confirmed'
                ");
                $stmt->bind_param("iii", $appointment_id, $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    throw new Exception("Appointment not found or already cancelled.");
                }

                $appointment = $result->fetch_assoc();
                $canceller_type = ($appointment['patient_id'] == $user_id) ? 'patient' : 'doctor';
                $canceller_name = ($canceller_type == 'patient') ? $appointment['patient_name'] : $appointment['doctor_name'];

                // Update appointment status
                $update = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $update->bind_param("i", $appointment_id);

                if (!$update->execute()) {
                    throw new Exception("Failed to cancel appointment. Please try again.");
                }

                // Create notification for the other party
                $notification_recipient_id = ($canceller_type == 'patient') ? $appointment['doctor_id'] : $appointment['patient_id'];
                $appointment_date = date('l, F j, Y', strtotime($appointment['start_time']));
                $appointment_time = date('g:i A', strtotime($appointment['start_time']));

                $reason_text = !empty($cancellation_reason) ? " Reason: " . $cancellation_reason : "";
                $notification_message = "Your appointment on {$appointment_date} at {$appointment_time} has been cancelled by {$canceller_name}.{$reason_text}";

                $notify = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
                $notify->bind_param("is", $notification_recipient_id, $notification_message);

                if (!$notify->execute()) {
                    throw new Exception("Failed to send notification about cancellation.");
                }

                // Free the slot
                $free_slot = $conn->prepare("UPDATE time_slots SET status = 'available', booked_count = GREATEST(booked_count - 1, 0) WHERE id = ?");
                $free_slot->bind_param("i", $appointment['slot_id']);

                if (!$free_slot->execute()) {
                    throw new Exception("Failed to free the time slot.");
                }

                // Commit transaction
                $conn->commit();
                $success_message = "Appointment cancelled successfully. A notification has been sent.";
            } catch (Exception $e) {
                // Rollback on error
                try {
                    $conn->rollback();
                } catch (Exception $rollbackEx) {
                    // Transaction may not have been active
                }
                $error_message = $e->getMessage();
            }
        }
    }
}

// Fetch user's appointments that can be cancelled (upcoming confirmed appointments)
$upcoming_appointments = [];

try {
    if ($role === 'patient') {
        $stmt = $conn->prepare("
            SELECT a.id, a.status, t.start_time, t.location, u.username as doctor_name
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON t.doctor_id = u.id
            WHERE a.patient_id = ?
            AND a.status = 'confirmed'
            AND t.start_time > NOW()
            ORDER BY t.start_time ASC
        ");
        $stmt->bind_param("i", $user_id);
    } else if ($role === 'doctor') {
        $stmt = $conn->prepare("
            SELECT a.id, a.status, t.start_time, t.location, u.username as patient_name
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON a.patient_id = u.id
            WHERE t.doctor_id = ?
            AND a.status = 'confirmed'
            AND t.start_time > NOW()
            ORDER BY t.start_time ASC
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $error_message = "Invalid user role.";
    }

    if (isset($stmt)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $upcoming_appointments[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching appointments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cancel Appointment</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        h2 {
            color: #2A9D8F;
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
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

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .appointment-list {
            margin-top: 2rem;
        }

        .appointment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            padding: 1.2rem;
            margin-bottom: 1rem;
            border-left: 4px solid #2A9D8F;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .appointment-info {
            flex-grow: 1;
        }

        .appointment-info h3 {
            margin: 0 0 0.5rem 0;
            color: #264653;
        }

        .appointment-info p {
            margin: 0.3rem 0;
            color: #666;
        }

        .cancel-form {
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #444;
        }

        select,
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #e74c3c;
            color: white;
        }

        .btn-cancel:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #777;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #999;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2><i class="fas fa-calendar-times" style="margin-right: 10px;"></i>Cancel Appointment</h2>

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

        <div class="card">
            <h3>Cancel an Upcoming Appointment</h3>
            <p>Please select the appointment you wish to cancel. Note that cancelling an appointment will notify the other party.</p>

            <?php if (empty($upcoming_appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p>You don't have any upcoming appointments that can be cancelled.</p>
                </div>
            <?php else: ?>
                <form method="POST" class="cancel-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="cancel_appointment" value="1">

                    <div class="form-group">
                        <label for="appointment_id">Select Appointment:</label>
                        <select name="appointment_id" id="appointment_id" required>
                            <option value="">-- Select an appointment --</option>
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <?php
                                $appointment_date = date('l, F j, Y', strtotime($appointment['start_time']));
                                $appointment_time = date('g:i A', strtotime($appointment['start_time']));
                                $with_person = isset($appointment['doctor_name']) ?
                                    'Dr. ' . htmlspecialchars($appointment['doctor_name']) :
                                    htmlspecialchars($appointment['patient_name']);
                                ?>
                                <option value="<?= $appointment['id'] ?>">
                                    <?= $appointment_date ?> at <?= $appointment_time ?> with <?= $with_person ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cancellation_reason">Reason for Cancellation (Optional):</label>
                        <textarea name="cancellation_reason" id="cancellation_reason" placeholder="Please provide a reason for cancelling this appointment"></textarea>
                    </div>

                    <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                        <i class="fas fa-calendar-times"></i> Cancel Appointment
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($upcoming_appointments)): ?>
            <div class="appointment-list">
                <h3>Your Upcoming Appointments</h3>

                <?php foreach ($upcoming_appointments as $appointment): ?>
                    <div class="appointment-card">
                        <div class="appointment-info">
                            <h3>
                                <?= date('l, F j, Y', strtotime($appointment['start_time'])) ?>
                            </h3>
                            <p>
                                <i class="far fa-clock"></i>
                                <?= date('g:i A', strtotime($appointment['start_time'])) ?>
                            </p>
                            <p>
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($appointment['location']) ?>
                            </p>
                            <p>
                                <i class="fas fa-user-md"></i>
                                <?php if (isset($appointment['doctor_name'])): ?>
                                    With Dr. <?= htmlspecialchars($appointment['doctor_name']) ?>
                                <?php else: ?>
                                    With <?= htmlspecialchars($appointment['patient_name']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Highlight the selected appointment in the list
        document.getElementById('appointment_id')?.addEventListener('change', function() {
            const selectedId = this.value;
            document.querySelectorAll('.appointment-card').forEach(card => {
                card.style.borderLeftColor = '#2A9D8F';
            });

            if (selectedId) {
                // Find the appointment in the list and highlight it
                const appointmentCards = document.querySelectorAll('.appointment-card');
                const index = Array.from(this.options).findIndex(option => option.value === selectedId) - 1;

                if (index >= 0 && index < appointmentCards.length) {
                    appointmentCards[index].style.borderLeftColor = '#e74c3c';
                    appointmentCards[index].scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    </script>
</body>

</html>