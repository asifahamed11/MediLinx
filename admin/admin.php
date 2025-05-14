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

// Handle delete operations
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
    header('Location: admin.php?tab=users&deleted=1');
    exit;
}

if (isset($_POST['delete_appointment']) && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);
    mysqli_query($conn, "DELETE FROM appointments WHERE id = $appointment_id");
    header('Location: admin.php?tab=appointments&deleted=1');
    exit;
}

if (isset($_POST['delete_post']) && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    mysqli_query($conn, "DELETE FROM posts WHERE id = $post_id");
    header('Location: admin.php?tab=posts&deleted=1');
    exit;
}

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Function to get counts for dashboard
function getCount($conn, $table)
{
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Function to get users
function getUsers($conn, $role = null)
{
    $query = "SELECT * FROM users";
    if ($role) {
        $query .= " WHERE role = '$role'";
    }
    $query .= " ORDER BY id DESC";
    return mysqli_query($conn, $query);
}

// Function to get appointments
function getAppointments($conn)
{
    $query = "SELECT a.*, u1.username as patient_name, u2.username as doctor_name, t.start_time, t.end_time, t.location 
              FROM appointments a
              JOIN users u1 ON a.patient_id = u1.id
              JOIN users u2 ON (SELECT doctor_id FROM time_slots WHERE id = a.slot_id) = u2.id
              JOIN time_slots t ON a.slot_id = t.id
              ORDER BY a.created_at DESC";
    return mysqli_query($conn, $query);
}

// Function to get posts
function getPosts($conn)
{
    $query = "SELECT p.*, u.username as doctor_name 
              FROM posts p
              JOIN users u ON p.doctor_id = u.id
              ORDER BY p.created_at DESC";
    return mysqli_query($conn, $query);
}

// Function to get reviews
function getReviews($conn)
{
    $query = "SELECT r.*, u1.username as doctor_name, u2.username as patient_name 
              FROM reviews r
              JOIN users u1 ON r.doctor_id = u1.id
              JOIN users u2 ON r.patient_id = u2.id
              ORDER BY r.created_at DESC";
    return mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medilinx Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-dark: #0d47a1;
            --secondary-color: #34a853;
            --danger-color: #ea4335;
            --warning-color: #fbbc05;
            --text-dark: #202124;
            --text-light: #5f6368;
            --sidebar-bg: #202124;
            --sidebar-active: #303134;
            --card-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;

            /* Animation Variables */
            --animation-slow: 0.5s;
            --animation-medium: 0.3s;
            --animation-fast: 0.2s;
        }

        /* Adding keyframes for animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        /* Main animation styles */
        .animate-fade-in {
            animation: fadeIn var(--animation-medium) ease-out;
        }

        .animate-slide-in {
            animation: slideInLeft var(--animation-medium) ease-out;
        }

        .animate-pulse {
            animation: pulse 2s infinite;
        }

        /* Main container animation */
        .main-content {
            animation: fadeIn var(--animation-slow) ease-out;
        }

        /* Staggered card animations */
        .dashboard-cards .card:nth-child(1) {
            animation: fadeIn calc(var(--animation-medium) + 0.1s) ease-out;
        }

        .dashboard-cards .card:nth-child(2) {
            animation: fadeIn calc(var(--animation-medium) + 0.2s) ease-out;
        }

        .dashboard-cards .card:nth-child(3) {
            animation: fadeIn calc(var(--animation-medium) + 0.3s) ease-out;
        }

        .dashboard-cards .card:nth-child(4) {
            animation: fadeIn calc(var(--animation-medium) + 0.4s) ease-out;
        }

        /* Enhanced hover animations */
        .card {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.3s ease,
                background-color 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            background-color: #fafafa;
        }

        /* Table row animations */
        tbody tr {
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        tbody tr:hover {
            transform: translateX(5px);
            background-color: rgba(26, 115, 232, 0.05) !important;
        }

        /* Button animations */
        .btn {
            transition: transform 0.2s ease,
                box-shadow 0.2s ease,
                background-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }

        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }

            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        /* Sidebar animations */
        .sidebar-menu a {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }

        .sidebar-menu a:hover {
            transform: translateX(5px);
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background-color: rgba(255, 255, 255, 0.3);
            transition: width 0.3s ease;
        }

        .sidebar-menu a:hover::before {
            width: 100%;
        }

        /* Alert animations */
        .alert {
            animation: slideInLeft var(--animation-medium) ease-out;
        }

        /* Loading state shimmer effect */
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite linear;
        }

        /* Action button hover effects */
        .btn-view:hover,
        .btn-edit:hover,
        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Status badge animations */
        .status-badge {
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Search field animation */
        .search-bar input {
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            transform: scale(1.01);
        }

        /* Responsive animation adjustments */
        @media (max-width: 768px) {
            .card:hover {
                transform: translateY(-5px) scale(1.01);
            }

            tbody tr:hover {
                transform: translateX(2px);
            }
        }

        /* Disable animations for users who prefer reduced motion */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Arial', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
            color: var(--text-dark);
        }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 5px;
        }

        .sidebar-menu {
            margin-top: 15px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: rgba(255, 255, 255, 0.5);
        }

        .sidebar-menu a.active {
            background-color: var(--sidebar-active);
            border-left-color: var(--primary-color);
            font-weight: 500;
        }

        .main-content {
            flex: 1;
            padding: 20px 30px;
            margin-left: 260px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 25px;
        }

        .header h1 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 24px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #d32f2f;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.3);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid #e0e0e0;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .card h3 {
            color: var(--text-light);
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card h3 i {
            color: var(--primary-color);
        }

        .card p {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .content-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .content-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h2 {
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 600;
        }

        .content-body {
            padding: 0;
            /* No padding for tables */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background-color: rgba(26, 115, 232, 0.03);
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 2px 6px rgba(26, 115, 232, 0.3);
        }

        .btn-edit {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #2e7d32;
            box-shadow: 0 2px 6px rgba(46, 125, 50, 0.3);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background-color: rgba(52, 168, 83, 0.1);
            color: #2e7d32;
            border-left: 4px solid var(--secondary-color);
        }

        .search-bar {
            display: flex;
            margin-bottom: 25px;
            gap: 10px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .search-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .search-btn:hover {
            background-color: var(--primary-dark);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 8px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 14px;
            background-color: white;
            border: 1px solid #e0e0e0;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 4px;
            transition: var(--transition);
        }

        .pagination a:hover,
        .pagination a.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-confirmed {
            background-color: rgba(52, 168, 83, 0.1);
            color: #2e7d32;
        }

        .status-completed {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-dark);
        }

        .status-cancelled {
            background-color: rgba(234, 67, 53, 0.1);
            color: #d32f2f;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }

            .sidebar-header {
                padding: 15px 5px;
            }

            .sidebar-header h2,
            .sidebar-header p,
            .sidebar-menu a span {
                display: none;
            }

            .sidebar-menu a {
                padding: 15px 0;
                justify-content: center;
            }

            .sidebar-menu a i {
                margin-right: 0;
                font-size: 22px;
            }

            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>MediLinx</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin.php?tab=doctors" class="<?php echo $active_tab === 'doctors' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i>
                <span>Doctors</span>
            </a>
            <a href="admin.php?tab=patients" class="<?php echo $active_tab === 'patients' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Patients</span>
            </a>
            <a href="admin.php?tab=appointments" class="<?php echo $active_tab === 'appointments' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
            <a href="admin.php?tab=time_slots" class="<?php echo $active_tab === 'time_slots' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <span>Time Slots</span>
            </a>
            <a href="admin.php?tab=posts" class="<?php echo $active_tab === 'posts' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i>
                <span>Health Posts</span>
            </a>
            <a href="admin.php?tab=reviews" class="<?php echo $active_tab === 'reviews' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
            <a href="admin_logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>
                <?php
                switch ($active_tab) {
                    case 'dashboard':
                        echo '<i class="fas fa-tachometer-alt"></i> Dashboard';
                        break;
                    case 'doctors':
                        echo '<i class="fas fa-user-md"></i> Manage Doctors';
                        break;
                    case 'patients':
                        echo '<i class="fas fa-users"></i> Manage Patients';
                        break;
                    case 'appointments':
                        echo '<i class="fas fa-calendar-check"></i> Manage Appointments';
                        break;
                    case 'time_slots':
                        echo '<i class="fas fa-clock"></i> Manage Time Slots';
                        break;
                    case 'posts':
                        echo '<i class="fas fa-newspaper"></i> Health Posts';
                        break;
                    case 'reviews':
                        echo '<i class="fas fa-star"></i> Reviews';
                        break;
                    default:
                        echo 'Dashboard';
                }
                ?>
            </h1>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-view"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Item deleted successfully!</span>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 'dashboard'): ?>
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Doctors</h3>
                    <p><?php echo getCount($conn, 'users WHERE role = "doctor"'); ?></p>
                </div>
                <div class="card">
                    <h3>Total Patients</h3>
                    <p><?php echo getCount($conn, 'users WHERE role = "patient"'); ?></p>
                </div>
                <div class="card">
                    <h3>Total Appointments</h3>
                    <p><?php echo getCount($conn, 'appointments'); ?></p>
                </div>
                <div class="card">
                    <h3>Reviews</h3>
                    <p><?php echo getCount($conn, 'reviews'); ?></p>
                </div>
            </div>

            <h2>Recent Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $appointments = getAppointments($conn);
                    $count = 0;
                    while ($row = mysqli_fetch_assoc($appointments)) {
                        if ($count >= 5) break; // Show only 5 recent appointments
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                        echo "<td>" . date('M d, Y H:i', strtotime($row['start_time'])) . "</td>";
                        echo "<td>" . ucfirst($row['status']) . "</td>";
                        echo "</tr>";
                        $count++;
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'doctors'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="doctors">
                    <input type="text" name="search" placeholder="Search doctors..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <a href="add_doctor.php" class="btn btn-edit">Add New Doctor</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Specialty</th>
                        <th>License #</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
                    $query = "SELECT * FROM users WHERE role = 'doctor'";

                    if (!empty($search_term)) {
                        $search_term = mysqli_real_escape_string($conn, $search_term);
                        $query .= " AND (username LIKE '%$search_term%' OR email LIKE '%$search_term%' OR specialty LIKE '%$search_term%')";
                    }

                    $doctors = mysqli_query($conn, $query);

                    while ($doctor = mysqli_fetch_assoc($doctors)) {
                        echo "<tr>";
                        echo "<td>" . $doctor['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($doctor['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($doctor['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($doctor['specialty'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($doctor['medical_license_number'] ?? 'N/A') . "</td>";
                        echo "<td>
                                <a href='view_doctor.php?id=" . $doctor['id'] . "' class='btn btn-view'>View</a>
                                <a href='edit_doctor.php?id=" . $doctor['id'] . "' class='btn btn-edit'>Edit</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this doctor?\");'>
                                    <input type='hidden' name='user_id' value='" . $doctor['id'] . "'>
                                    <button type='submit' name='delete_user' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'patients'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="patients">
                    <input type="text" name="search" placeholder="Search patients..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <a href="add_patient.php" class="btn btn-edit">Add New Patient</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
                    $query = "SELECT * FROM users WHERE role = 'patient'";

                    if (!empty($search_term)) {
                        $search_term = mysqli_real_escape_string($conn, $search_term);
                        $query .= " AND (username LIKE '%$search_term%' OR email LIKE '%$search_term%')";
                    }

                    $patients = mysqli_query($conn, $query);

                    while ($patient = mysqli_fetch_assoc($patients)) {
                        echo "<tr>";
                        echo "<td>" . $patient['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($patient['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
                        echo "<td>" . ($patient['date_of_birth'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($patient['gender'] ?? 'N/A') . "</td>";
                        echo "<td>
                                <a href='view_patient.php?id=" . $patient['id'] . "' class='btn btn-view'>View</a>
                                <a href='edit_patient.php?id=" . $patient['id'] . "' class='btn btn-edit'>Edit</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this patient?\");'>
                                    <input type='hidden' name='user_id' value='" . $patient['id'] . "'>
                                    <button type='submit' name='delete_user' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'appointments'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="appointments">
                    <input type="text" name="search" placeholder="Search appointments..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <a href="add_appointment.php" class="btn btn-edit">Add New Appointment</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT a.*, u.username as patient_name, ts.start_time, ts.end_time, ts.location, ts.doctor_id,
                              (SELECT username FROM users WHERE id = ts.doctor_id) as doctor_name
                              FROM appointments a
                              JOIN users u ON a.patient_id = u.id
                              JOIN time_slots ts ON a.slot_id = ts.id";

                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search_term = mysqli_real_escape_string($conn, $_GET['search']);
                        $query .= " WHERE u.username LIKE '%$search_term%' OR 
                                   (SELECT username FROM users WHERE id = ts.doctor_id) LIKE '%$search_term%' OR
                                   a.reason LIKE '%$search_term%'";
                    }

                    $appointments = mysqli_query($conn, $query);

                    while ($appointment = mysqli_fetch_assoc($appointments)) {
                        echo "<tr>";
                        echo "<td>" . $appointment['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['patient_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['doctor_name']) . "</td>";
                        echo "<td>" . date('M d, Y H:i', strtotime($appointment['start_time'])) . "</td>";
                        echo "<td>" . htmlspecialchars($appointment['location']) . "</td>";
                        echo "<td>" . ucfirst($appointment['status']) . "</td>";
                        echo "<td>
                                <a href='view_appointment.php?id=" . $appointment['id'] . "' class='btn btn-view'>View</a>
                                <a href='edit_appointment.php?id=" . $appointment['id'] . "' class='btn btn-edit'>Edit</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this appointment?\");'>
                                    <input type='hidden' name='appointment_id' value='" . $appointment['id'] . "'>
                                    <button type='submit' name='delete_appointment' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'time_slots'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="time_slots">
                    <input type="text" name="search" placeholder="Search time slots..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <a href="add_time_slot.php" class="btn btn-edit">Add New Time Slot</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Doctor</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT ts.*, u.username as doctor_name
                              FROM time_slots ts
                              JOIN users u ON ts.doctor_id = u.id";

                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search_term = mysqli_real_escape_string($conn, $_GET['search']);
                        $query .= " WHERE u.username LIKE '%$search_term%' OR ts.location LIKE '%$search_term%'";
                    }

                    $time_slots = mysqli_query($conn, $query);

                    while ($slot = mysqli_fetch_assoc($time_slots)) {
                        echo "<tr>";
                        echo "<td>" . $slot['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($slot['doctor_name']) . "</td>";
                        echo "<td>" . date('M d, Y H:i', strtotime($slot['start_time'])) . "</td>";
                        echo "<td>" . date('M d, Y H:i', strtotime($slot['end_time'])) . "</td>";
                        echo "<td>" . htmlspecialchars($slot['location']) . "</td>";
                        echo "<td>" . ucfirst($slot['status']) . "</td>";
                        echo "<td>
                                <a href='edit_time_slot.php?id=" . $slot['id'] . "' class='btn btn-edit'>Edit</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this time slot?\");'>
                                    <input type='hidden' name='slot_id' value='" . $slot['id'] . "'>
                                    <button type='submit' name='delete_slot' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'posts'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="posts">
                    <input type="text" name="search" placeholder="Search posts..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <a href="add_post.php" class="btn btn-edit">Add New Post</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Doctor</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, u.username as doctor_name
                              FROM posts p
                              JOIN users u ON p.doctor_id = u.id";

                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search_term = mysqli_real_escape_string($conn, $_GET['search']);
                        $query .= " WHERE p.title LIKE '%$search_term%' OR p.content LIKE '%$search_term%' OR u.username LIKE '%$search_term%'";
                    }

                    $posts = mysqli_query($conn, $query);

                    while ($post = mysqli_fetch_assoc($posts)) {
                        echo "<tr>";
                        echo "<td>" . $post['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($post['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($post['doctor_name']) . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($post['created_at'])) . "</td>";
                        echo "<td>
                                <a href='view_post.php?id=" . $post['id'] . "' class='btn btn-view'>View</a>
                                <a href='edit_post.php?id=" . $post['id'] . "' class='btn btn-edit'>Edit</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this post?\");'>
                                    <input type='hidden' name='post_id' value='" . $post['id'] . "'>
                                    <button type='submit' name='delete_post' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'reviews'): ?>
            <div class="search-bar">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="reviews">
                    <input type="text" name="search" placeholder="Search reviews..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Doctor</th>
                        <th>Patient</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT r.*, 
                              (SELECT username FROM users WHERE id = r.doctor_id) as doctor_name,
                              (SELECT username FROM users WHERE id = r.patient_id) as patient_name
                              FROM reviews r";

                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search_term = mysqli_real_escape_string($conn, $_GET['search']);
                        $query .= " WHERE r.comment LIKE '%$search_term%' OR 
                                   (SELECT username FROM users WHERE id = r.doctor_id) LIKE '%$search_term%' OR
                                   (SELECT username FROM users WHERE id = r.patient_id) LIKE '%$search_term%'";
                    }

                    $reviews = mysqli_query($conn, $query);

                    while ($review = mysqli_fetch_assoc($reviews)) {
                        echo "<tr>";
                        echo "<td>" . $review['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($review['doctor_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($review['patient_name']) . "</td>";
                        echo "<td>" . $review['rating'] . "</td>";
                        echo "<td>" . htmlspecialchars(substr($review['comment'] ?? '', 0, 50)) . (strlen($review['comment'] ?? '') > 50 ? '...' : '') . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($review['created_at'])) . "</td>";
                        echo "<td>
                                <a href='view_review.php?id=" . $review['id'] . "' class='btn btn-view'>View</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this review?\");'>
                                    <input type='hidden' name='review_id' value='" . $review['id'] . "'>
                                    <button type='submit' name='delete_review' class='btn btn-delete'>Delete</button>
                                </form>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == 'settings'): ?>
            <div class="card">
                <h2>System Settings</h2>
                <form action="update_settings.php" method="post">
                    <div style="margin-bottom: 15px;">
                        <label for="site_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Site Name:</label>
                        <input type="text" id="site_name" name="site_name" value="Medilinx" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="admin_email" style="display: block; margin-bottom: 5px; font-weight: bold;">Admin Email:</label>
                        <input type="email" id="admin_email" name="admin_email" value="admin@medilinx.com" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="items_per_page" style="display: block; margin-bottom: 5px; font-weight: bold;">Items Per Page:</label>
                        <select id="items_per_page" name="items_per_page" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="maintenance_mode" style="display: block; margin-bottom: 5px; font-weight: bold;">Maintenance Mode:</label>
                        <select id="maintenance_mode" name="maintenance_mode" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="0" selected>Off</option>
                            <option value="1">On</option>
                        </select>
                    </div>

                    <button type="submit" style="background-color: #2ecc71; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Save Settings</button>
                </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Database Backup</h2>
                <form action="backup_database.php" method="post">
                    <button type="submit" style="background-color: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Create Backup</button>
                </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Change Admin Password</h2>
                <form action="change_admin_password.php" method="post">
                    <div style="margin-bottom: 15px;">
                        <label for="current_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="new_password" style="display: block; margin-bottom: 5px; font-weight: bold;">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="confirm_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <button type="submit" style="background-color: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Change Password</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements when they come into view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            // Observe section elements for animation
            document.querySelectorAll('.section').forEach(section => {
                observer.observe(section);
            });

            // Auto-hide alerts after 5 seconds with animation
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(-10px)';
                    alert.style.transition = 'opacity 1s, transform 1s';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            });

            // Enhanced table row effects
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                // Add slight delay for staggered appearance
                row.style.animation = `fadeIn 0.3s ease-out ${index * 0.05}s both`;

                // Add hover effect
                row.addEventListener('mouseover', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.backgroundColor = 'rgba(26, 115, 232, 0.05)';

                    // Highlight action buttons on row hover
                    const buttons = this.querySelectorAll('.btn');
                    buttons.forEach(btn => {
                        btn.style.transform = 'scale(1.05)';
                    });
                });

                row.addEventListener('mouseout', function() {
                    this.style.transform = '';
                    this.style.backgroundColor = '';

                    // Reset buttons
                    const buttons = this.querySelectorAll('.btn');
                    buttons.forEach(btn => {
                        btn.style.transform = '';
                    });
                });
            });

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;

                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple');
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        document.querySelector(targetId).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>