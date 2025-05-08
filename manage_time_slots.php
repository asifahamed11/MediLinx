<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$doctor_id = $_SESSION['user_id'];
$csrf_token = $_SESSION['csrf_token'];

// Process form submission for creating time slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "CSRF token validation failed";
        header("Location: manage_time_slots.php");
        exit;
    }

    // Get form data
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];

    // Validate the inputs
    if (empty($date) || empty($start_time) || empty($end_time) || empty($location)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: manage_time_slots.php");
        exit;
    }

    // Create datetime strings
    $start_datetime = $date . ' ' . $start_time . ':00';
    $end_datetime = $date . ' ' . $end_time . ':00';

    // Check if start time is before end time
    if (strtotime($start_datetime) >= strtotime($end_datetime)) {
        $_SESSION['error'] = "End time must be after start time";
        header("Location: manage_time_slots.php");
        exit;
    }

    // Check if slots overlap with existing slots
    $check_stmt = $conn->prepare("
        SELECT id FROM time_slots 
        WHERE doctor_id = ? 
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ");
    $check_stmt->bind_param("issssss", $doctor_id, $end_datetime, $start_datetime, $end_datetime, $start_datetime, $start_datetime, $end_datetime);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "The time slot overlaps with an existing slot";
        header("Location: manage_time_slots.php");
        exit;
    }

    // Create time slot
    try {
        $stmt = $conn->prepare("
            INSERT INTO time_slots (doctor_id, start_time, end_time, location, status, capacity) 
            VALUES (?, ?, ?, ?, 'available', ?)
        ");
        $stmt->bind_param("issss", $doctor_id, $start_datetime, $end_datetime, $location, $capacity);
        $stmt->execute();
        $_SESSION['success'] = "Time slot created successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to create time slot: " . $e->getMessage();
    }

    header("Location: manage_time_slots.php");
    exit;
}

// Process form submission for deleting a time slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "CSRF token validation failed";
        header("Location: manage_time_slots.php");
        exit;
    }

    $slot_id = (int)$_POST['slot_id'];

    // Check if slot exists and belongs to the doctor
    $check_stmt = $conn->prepare("SELECT id, status FROM time_slots WHERE id = ? AND doctor_id = ?");
    $check_stmt->bind_param("ii", $slot_id, $doctor_id);
    $check_stmt->execute();
    $slot = $check_stmt->get_result()->fetch_assoc();

    if (!$slot) {
        $_SESSION['error'] = "Time slot not found or you don't have permission to delete it";
        header("Location: manage_time_slots.php");
        exit;
    }

    if ($slot['status'] === 'booked') {
        $_SESSION['error'] = "Cannot delete a booked time slot";
        header("Location: manage_time_slots.php");
        exit;
    }

    // Delete the time slot
    try {
        $stmt = $conn->prepare("DELETE FROM time_slots WHERE id = ? AND doctor_id = ?");
        $stmt->bind_param("ii", $slot_id, $doctor_id);
        $stmt->execute();
        $_SESSION['success'] = "Time slot deleted successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete time slot: " . $e->getMessage();
    }

    header("Location: manage_time_slots.php");
    exit;
}

// Fetch existing time slots
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$slots_stmt = $conn->prepare("
    SELECT id, start_time, end_time, location, status
    FROM time_slots
    WHERE doctor_id = ?
    ORDER BY start_time DESC
    LIMIT ? OFFSET ?
");
$slots_stmt->bind_param("iii", $doctor_id, $limit, $offset);
$slots_stmt->execute();
$slots = $slots_stmt->get_result();

// Get total number of slots for pagination
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM time_slots WHERE doctor_id = ?");
$total_stmt->bind_param("i", $doctor_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_slots = $total_result['total'];
$total_pages = ceil($total_slots / $limit);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Time Slots | MediLinx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #2A9D8F;
            text-align: center;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .forms-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .form-container {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            margin-top: 0;
            color: #264653;
            border-bottom: 2px solid #2A9D8F;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #264653;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #2A9D8F;
            color: white;
        }

        .btn-primary:hover {
            background-color: #264653;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #e63946;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c1121f;
            transform: translateY(-2px);
        }

        .slots-container {
            margin-top: 30px;
        }

        .slot-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .slot-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .slot-details {
            flex: 1;
        }

        .slot-date {
            font-weight: bold;
            color: #264653;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .slot-time {
            color: #2A9D8F;
            margin-bottom: 5px;
        }

        .slot-location {
            color: #666;
        }

        .slot-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-booked {
            background-color: #f8d7da;
            color: #721c24;
        }

        .slot-actions {
            display: flex;
            gap: 10px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            padding: 8px 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            color: #2A9D8F;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .page-link:hover,
        .page-link.active {
            background-color: #2A9D8F;
            color: white;
        }

        .no-slots {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #666;
            font-size: 18px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2A9D8F;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1>Manage Appointment Slots</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="forms-container">
            <div class="form-container">
                <h3 class="form-title">Create New Time Slot</h3>
                <form method="post" action="manage_time_slots.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" required placeholder="Office/Clinic location or Online">
                    </div>

                    <div class="form-group">
                        <label for="capacity">Patient Capacity</label>
                        <input type="number" id="capacity" name="capacity" class="form-control" value="20" min="1" max="100" required>
                        <small class="form-text text-muted">Maximum number of patients who can book this slot (default: 20)</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Time Slot</button>
                </form>
            </div>

            <div class="form-container">
                <h3 class="form-title">Quick Slot Creation</h3>
                <form method="post" action="create_multiple_slots.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Select Days</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="1"> Monday
                            </label>
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="2"> Tuesday
                            </label>
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="3"> Wednesday
                            </label>
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="4"> Thursday
                            </label>
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="5"> Friday
                            </label>
                            <label style="display: inline-flex; align-items: center; margin-right: 10px;">
                                <input type="checkbox" name="days[]" value="6"> Saturday
                            </label>
                            <label style="display: inline-flex; align-items: center;">
                                <input type="checkbox" name="days[]" value="0"> Sunday
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="daily_start_time">Daily Start Time</label>
                        <input type="time" id="daily_start_time" name="daily_start_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="daily_end_time">Daily End Time</label>
                        <input type="time" id="daily_end_time" name="daily_end_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="bulk_location">Location</label>
                        <input type="text" id="bulk_location" name="location" class="form-control" required placeholder="Office/Clinic location or Online">
                    </div>

                    <div class="form-group">
                        <label for="bulk_capacity">Patient Capacity</label>
                        <input type="number" id="bulk_capacity" name="capacity" class="form-control" value="20" min="1" max="100" required>
                        <small class="form-text text-muted">Maximum number of patients who can book each slot (default: 20)</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Multiple Slots</button>
                </form>
            </div>
        </div>

        <div class="slots-container">
            <h2>Your Time Slots</h2>

            <?php if ($slots->num_rows === 0): ?>
                <div class="no-slots">
                    <i class="fas fa-calendar-times" style="font-size: 48px; display: block; margin-bottom: 15px; color: #ccc;"></i>
                    <p>You haven't created any time slots yet.</p>
                </div>
            <?php else: ?>
                <?php while ($slot = $slots->fetch_assoc()): ?>
                    <div class="slot-card">
                        <div class="slot-details">
                            <div class="slot-date"><?php echo date('l, F j, Y', strtotime($slot['start_time'])); ?></div>
                            <div class="slot-time"><?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></div>
                            <div class="slot-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($slot['location']); ?></div>
                        </div>
                        <div class="slot-status status-<?php echo $slot['status']; ?>">
                            <?php echo ucfirst($slot['status']); ?>
                        </div>
                        <div class="slot-actions">
                            <?php if ($slot['status'] === 'available'): ?>
                                <form method="post" action="manage_time_slots.php" onsubmit="return confirm('Are you sure you want to delete this time slot?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>

</html>