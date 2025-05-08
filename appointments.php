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

$appointments = null;
$total_pages = 1;
$error_message = "";
$has_error = false;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    if ($role === 'doctor') {
        // Doctor sees appointments with patients
        $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS a.*, u.username, t.start_time, t.location 
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON a.patient_id = u.id
            WHERE t.doctor_id = ?
            ORDER BY t.start_time DESC
            LIMIT ? OFFSET ?");
    } else {
        // Patient sees appointments with doctors
        $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS a.*, u.username, t.start_time, t.location 
            FROM appointments a
            JOIN time_slots t ON a.slot_id = t.id
            JOIN users u ON t.doctor_id = u.id
            WHERE a.patient_id = ?
            ORDER BY t.start_time DESC
            LIMIT ? OFFSET ?");
    }

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $success = $stmt->execute();

    if (!$success) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $appointments = $stmt->get_result();

    // Get total rows for pagination
    $total_result = $conn->query("SELECT FOUND_ROWS()");
    if (!$total_result) {
        throw new Exception("Failed to get total rows: " . $conn->error);
    }

    $total_rows = $total_result->fetch_row()[0];
    $total_pages = ceil($total_rows / $limit);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error fetching appointments: " . $e->getMessage();
    $_SESSION['error'] = $error_message;
    $has_error = true;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>My Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-5px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            animation: slideIn 0.6s ease-out;
        }

        h2 {
            color: #2A9D8F;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .appointment-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            margin: 1.5rem 0;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .appointment-card:nth-child(odd) {
            animation-delay: 0.1s;
        }

        .appointment-card:nth-child(even) {
            animation-delay: 0.2s;
        }

        .appointment-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .header h4 {
            color: #264653;
            font-size: 1.4rem;
            margin: 0;
            position: relative;
        }

        .header h4::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 20px;
            height: 3px;
            background: #2A9D8F;
            border-radius: 2px;
        }

        .status-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease;
        }

        .status-badge i {
            font-size: 1.1rem;
        }

        .status-badge.confirmed {
            background-color: #e6f7ff;
            color: #0066cc;
            animation: float 3s ease-in-out infinite;
        }

        .status-badge.completed {
            background-color: #e6fff0;
            color: #00994d;
        }

        .status-badge.cancelled {
            background-color: #ffebeb;
            color: #cc0000;
        }

        p {
            color: #555;
            margin: 0.8rem 0;
            font-size: 1.1rem;
        }

        .pagination {
            display: flex;
            gap: 0.8rem;
            margin-top: 3rem;
            justify-content: center;
        }

        .pagination a {
            padding: 0.8rem 1.4rem;
            border-radius: 12px;
            background: #fff;
            color: #2A9D8F;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .pagination a:hover {
            transform: translateY(-2px);
            background: #2A9D8F;
            color: white;
            box-shadow: 0 6px 12px rgba(42, 157, 143, 0.3);
        }

        .pagination a.active {
            background: #2A9D8F;
            color: #fff;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            margin-top: 3rem;
            color: #888;
            animation: slideIn 0.6s ease;
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 1.2rem;
            margin: 2rem 0;
            border-radius: 10px;
            border-left: 5px solid #d32f2f;
            animation: slideIn 0.4s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-cancel {
            padding: 0.8rem 1.6rem;
            border: none;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff4040 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1.2rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(255, 107, 107, 0.2);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 107, 0.3);
            background: linear-gradient(135deg, #ff4040 0%, #ff1a1a 100%);
        }

        .btn-cancel:active {
            transform: scale(0.96);
        }

        .loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .container h2 {
            position: relative;
            padding-left: 50px;
        }

        .container h2 .header-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.8rem;
            color: #2A9D8F;
        }

        .appointment-card .icon-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0.8rem 0;
        }

        .appointment-card .icon {
            color: #2A9D8F;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2>
            Appointments
        </h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($has_error): ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <p>Unable to load appointments. Please try again later.</p>
            </div>
        <?php elseif ($appointments === null || $appointments->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times fa-2x"></i>
                <p>No appointments found</p>
            </div>
        <?php else: ?>
            <?php while ($apt = $appointments->fetch_assoc()): ?>
                <div class="appointment-card">
                    <div class="header">
                        <h4>
                            <i class="fas fa-user-md"></i>
                            With <?= htmlspecialchars($apt['username']) ?>
                        </h4>
                        <span class="status-badge <?= $apt['status'] ?>">
                            <?php switch ($apt['status']) {
                                case 'confirmed':
                                    echo '<i class="fas fa-check-circle"></i>';
                                    break;
                                case 'completed':
                                    echo '<i class="fas fa-calendar-check"></i>';
                                    break;
                                case 'cancelled':
                                    echo '<i class="fas fa-times-circle"></i>';
                                    break;
                            } ?>
                            <?= ucfirst($apt['status']) ?>
                        </span>
                    </div>
                    <div class="icon-group">
                        <i class="fas fa-clock icon"></i>
                        <span><?= date('M j, Y g:i A', strtotime($apt['start_time'])) ?></span>
                    </div>
                    <div class="icon-group">
                        <i class="fas fa-map-marker-alt icon"></i>
                        <span><?= htmlspecialchars($apt['location']) ?></span>
                    </div>
                    <?php if ($apt['status'] === 'confirmed'): ?>
                        <form method="POST" action="cancel_appointment.php" onsubmit="showSpinner(this)">
                            <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn-cancel">
                                <span class="loading-spinner"></span>
                                <i class="fas fa-times"></i>
                                Cancel Appointment
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showSpinner(form) {
            const btn = form.querySelector('.btn-cancel');
            btn.disabled = true;
            btn.querySelector('.loading-spinner').style.display = 'block';
            btn.style.opacity = '0.8';
        }

        document.querySelectorAll('.appointment-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>

</html>