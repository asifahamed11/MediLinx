<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Handle filters
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d', strtotime('+7 days'));

// Get all doctors for filter dropdown
$doctors_query = "SELECT id, username, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($doctors_query);
$doctors = [];
while ($doctor = $doctors_result->fetch_assoc()) {
    $doctors[] = $doctor;
}

// Build query for available time slots
$query = "SELECT ts.*, u.username as doctor_name, u.specialty, 
          (SELECT COUNT(*) FROM appointments WHERE slot_id = ts.id) as booked_count
          FROM time_slots ts
          JOIN users u ON ts.doctor_id = u.id
          WHERE ts.status = 'available' 
          AND ts.start_time BETWEEN ? AND ? ";

$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
$types = "ss";

if ($doctor_id) {
    $query .= "AND ts.doctor_id = ? ";
    $params[] = $doctor_id;
    $types .= "i";
}

$query .= "ORDER BY ts.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$slots = [];

while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['start_time']));
    $slots[$date][] = $row;
}

// Get user's existing bookings to prevent double booking
$booked_query = "SELECT slot_id FROM appointments WHERE patient_id = ?";
$booked_stmt = $conn->prepare($booked_query);
$booked_stmt->bind_param("i", $user_id);
$booked_stmt->execute();
$booked_result = $booked_stmt->get_result();
$booked_slots = [];

while ($booked = $booked_result->fetch_assoc()) {
    $booked_slots[] = $booked['slot_id'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MediLinx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #E9C46A;
            --light: #F8F9FA;
            --danger: #E76F51;
            --success: #4CAF50;
            --gray: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--secondary);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--secondary);
            font-size: 2.5rem;
        }

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filters form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group button:hover {
            background-color: #238077;
            transform: translateY(-2px);
        }

        .calendar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .date-group {
            margin-bottom: 1.5rem;
        }

        .date-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .slot-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }

        .slot-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.2rem;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .slot-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .slot-time {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .slot-doctor {
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .slot-specialty {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .slot-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        .book-btn {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .book-btn:hover {
            background-color: #238077;
        }

        .book-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .booked {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .booked .book-btn {
            background-color: var(--danger);
        }

        .no-slots {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
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

        .slot-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--secondary);
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-body textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-height: 100px;
            font-family: inherit;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-footer button {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background-color: #f8f9fa;
            color: var(--gray);
            border: 1px solid #ddd;
        }

        .confirm-btn {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        @media (max-width: 768px) {
            .filters form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            .slot-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1><i class="fas fa-calendar-plus"></i> Book Appointment</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label for="doctor_id">Doctor</label>
                    <select name="doctor_id" id="doctor_id">
                        <option value="">All Doctors</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_id == $doctor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['username']) . ' (' . htmlspecialchars($doctor['specialty']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="form-group">
                    <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="calendar">
            <?php if (empty($slots)): ?>
                <div class="no-slots">
                    <i class="fas fa-calendar-times fa-3x" style="color: var(--gray); margin-bottom: 1rem;"></i>
                    <p>No available appointment slots found for the selected criteria.</p>
                    <p>Try changing your search filters or contact support.</p>
                </div>
            <?php else: ?>
                <?php foreach ($slots as $date => $day_slots): ?>
                    <div class="date-group">
                        <div class="date-header">
                            <?php echo date('l, F j, Y', strtotime($date)); ?>
                        </div>
                        <div class="slot-container">
                            <?php foreach ($day_slots as $slot): ?>
                                <?php
                                $is_booked = in_array($slot['id'], $booked_slots);
                                $is_full = isset($slot['capacity']) && $slot['booked_count'] >= $slot['capacity'];
                                $disabled = $is_booked || $is_full;
                                ?>
                                <div class="slot-card <?php echo $is_booked ? 'booked' : ''; ?>">
                                    <div class="slot-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($slot['start_time'])); ?> -
                                        <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                    </div>
                                    <div class="slot-doctor">
                                        <i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($slot['doctor_name']); ?>
                                    </div>
                                    <div class="slot-specialty">
                                        <i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($slot['specialty']); ?>
                                    </div>
                                    <div class="slot-location">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($slot['location']); ?>
                                    </div>

                                    <?php if ($is_booked): ?>
                                        <button class="book-btn" disabled>
                                            <i class="fas fa-check-circle"></i> Already Booked
                                        </button>
                                    <?php elseif ($is_full): ?>
                                        <button class="book-btn" disabled>
                                            <i class="fas fa-ban"></i> Fully Booked
                                        </button>
                                    <?php else: ?>
                                        <button class="book-btn" onclick="openBookingModal(<?php echo $slot['id']; ?>, '<?php echo htmlspecialchars($slot['doctor_name']); ?>', '<?php echo date('l, F j, Y g:i A', strtotime($slot['start_time'])); ?>')">
                                            <i class="fas fa-calendar-check"></i> Book Appointment
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="slot-modal" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-check"></i> Confirm Appointment</h3>
            </div>
            <div class="modal-body">
                <p id="modalDetails"></p>
                <form id="bookingForm" method="POST" action="book_appointment.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="slot_id" id="slotId">
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="reason">Reason for Visit (Optional)</label>
                        <textarea name="reason" id="reason" placeholder="Please describe your symptoms or reason for this appointment..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeBookingModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="confirm-btn" onclick="submitBooking()">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
            </div>
        </div>
    </div>

    <script>
        function openBookingModal(slotId, doctorName, slotTime) {
            document.getElementById('slotId').value = slotId;
            document.getElementById('modalDetails').innerHTML = `
                <p><strong>Doctor:</strong> Dr. ${doctorName}</p>
                <p><strong>Date & Time:</strong> ${slotTime}</p>
                <p>Are you sure you want to book this appointment?</p>
            `;
            document.getElementById('bookingModal').style.display = 'flex';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }

        function submitBooking() {
            document.getElementById('bookingForm').submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) {
                closeBookingModal();
            }
        }
    </script>
</body>

</html>