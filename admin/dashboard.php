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

// Function to get counts for dashboard
function getCount($conn, $table, $condition = '')
{
    $query = "SELECT COUNT(*) as count FROM $table";
    if (!empty($condition)) {
        $query .= " WHERE $condition";
    }
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Get dashboard statistics
$total_doctors = getCount($conn, 'users', "role = 'doctor'");
$total_patients = getCount($conn, 'users', "role = 'patient'");
$total_appointments = getCount($conn, 'appointments');
$pending_appointments = getCount($conn, 'appointments', "status = 'pending'");
$total_reviews = getCount($conn, 'reviews');
$total_posts = getCount($conn, 'posts');

// Get recent appointments
$recent_appointments_query = "SELECT a.*, 
                                    p.username as patient_name, 
                                    d.username as doctor_name,
                                    ts.start_time, 
                                    ts.end_time
                              FROM appointments a
                              JOIN users p ON a.patient_id = p.id
                              JOIN time_slots ts ON a.slot_id = ts.id
                              JOIN users d ON ts.doctor_id = d.id
                              ORDER BY ts.start_time DESC
                              LIMIT 5";
$recent_appointments = mysqli_query($conn, $recent_appointments_query);

// Get recent registrations
$recent_users_query = "SELECT id, username, email, role, created_at 
                       FROM users 
                       ORDER BY created_at DESC 
                       LIMIT 5";
$recent_users = mysqli_query($conn, $recent_users_query);

// Get system health status
$system_status = [
    'database' => true,
    'uploads_dir' => is_dir('../uploads') && is_writable('../uploads'),
    'php_version' => version_compare(PHP_VERSION, '7.2.0', '>='),
    'smtp_config' => defined('SMTP_HOST') && !empty(SMTP_HOST)
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Medilinx</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-dark: #0d47a1;
            --primary-light: #d2e3fc;
            --secondary-color: #34a853;
            --danger-color: #ea4335;
            --warning-color: #fbbc05;
            --text-dark: #202124;
            --text-light: #5f6368;
            --sidebar-bg: #202124;
            --sidebar-active: #303134;
            --card-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Arial', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            display: flex;
            min-height: 100vh;
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
            padding: 30px;
            margin-left: 260px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
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
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #d32f2f;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.3);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .card {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid #e0e0e0;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .card.alert::before {
            background-color: var(--danger-color);
        }

        .card.success::before {
            background-color: var(--secondary-color);
        }

        .card.warning::before {
            background-color: var(--warning-color);
        }

        .card h3 {
            color: var(--text-light);
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h3 i {
            color: var(--primary-color);
            font-size: 20px;
        }

        .card.alert h3 i {
            color: var(--danger-color);
        }

        .card.success h3 i {
            color: var(--secondary-color);
        }

        .card.warning h3 i {
            color: var(--warning-color);
        }

        .card p {
            font-size: 32px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card.alert p {
            color: var(--danger-color);
        }

        .card.success p {
            color: var(--secondary-color);
        }

        .section {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 35px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: var(--primary-color);
        }

        .section-body {
            padding: 0;
        }

        .section-body-padded {
            padding: 25px;
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
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 2px 6px rgba(26, 115, 232, 0.3);
        }

        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #2e7d32;
            box-shadow: 0 2px 6px rgba(46, 125, 50, 0.3);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.3);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
        }

        .badge-warning {
            background-color: rgba(251, 188, 5, 0.1);
            color: #f57c00;
        }

        .badge-danger {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--danger-color);
        }

        .badge-info {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
        }

        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow);
            border-color: var(--primary-light);
        }

        .quick-link-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .quick-link-text {
            font-weight: 500;
        }

        .quick-link-text span {
            display: block;
            font-size: 13px;
            color: var(--text-light);
            margin-top: 4px;
        }

        .system-status {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .status-icon {
            font-size: 20px;
        }

        .status-ok {
            color: var(--secondary-color);
        }

        .status-error {
            color: var(--danger-color);
        }

        .status-text {
            font-size: 14px;
        }

        .status-text span {
            display: block;
            font-size: 12px;
            color: var(--text-light);
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
                padding: 20px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
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
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin.php?tab=doctors">
                <i class="fas fa-user-md"></i>
                <span>Doctors</span>
            </a>
            <a href="admin.php?tab=patients">
                <i class="fas fa-users"></i>
                <span>Patients</span>
            </a>
            <a href="admin.php?tab=appointments">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
            <a href="admin.php?tab=time_slots">
                <i class="fas fa-clock"></i>
                <span>Time Slots</span>
            </a>
            <a href="admin.php?tab=posts">
                <i class="fas fa-newspaper"></i>
                <span>Health Posts</span>
            </a>
            <a href="admin.php?tab=reviews">
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
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-primary"><i class="fas fa-cog"></i> Admin Control Panel</a>
                <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card success">
                <h3><i class="fas fa-user-md"></i> Total Doctors</h3>
                <p><?php echo $total_doctors; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-users"></i> Total Patients</h3>
                <p><?php echo $total_patients; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-calendar-check"></i> Total Appointments</h3>
                <p><?php echo $total_appointments; ?></p>
            </div>
            <div class="card alert">
                <h3><i class="fas fa-hourglass-half"></i> Pending Appointments</h3>
                <p><?php echo $pending_appointments; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-star"></i> Reviews</h3>
                <p><?php echo $total_reviews; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-newspaper"></i> Health Posts</h3>
                <p><?php echo $total_posts; ?></p>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-calendar-alt"></i> Recent Appointments</h2>
                <a href="admin.php?tab=appointments" class="btn btn-primary"><i class="fas fa-eye"></i> View All</a>
            </div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = mysqli_fetch_assoc($recent_appointments)): ?>
                            <tr>
                                <td>#<?php echo $appointment['id']; ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($appointment['start_time'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo ($appointment['status'] == 'confirmed') ? 'success' : (($appointment['status'] == 'cancelled') ? 'danger' : 'info');
                                                                ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-user-plus"></i> Recent Registrations</h2>
                <a href="admin.php?tab=patients" class="btn btn-primary"><i class="fas fa-users"></i> View Users</a>
            </div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($recent_users)): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo ($user['role'] == 'doctor') ? 'success' : (($user['role'] == 'admin') ? 'danger' : 'info');
                                                                ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] == 'doctor'): ?>
                                        <a href="view_doctor.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php elseif ($user['role'] == 'patient'): ?>
                                        <a href="view_patient.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-link"></i> Quick Actions</h2>
            </div>
            <div class="section-body section-body-padded">
                <div class="quick-links">
                    <a href="add_doctor.php" class="quick-link">
                        <div class="quick-link-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="quick-link-text">
                            Add Doctor
                            <span>Create a new doctor account</span>
                        </div>
                    </a>
                    <a href="add_patient.php" class="quick-link">
                        <div class="quick-link-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="quick-link-text">
                            Add Patient
                            <span>Register a new patient</span>
                        </div>
                    </a>
                    <a href="add_time_slot.php" class="quick-link">
                        <div class="quick-link-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="quick-link-text">
                            Add Time Slot
                            <span>Create new appointment slots</span>
                        </div>
                    </a>
                    <a href="admin.php?tab=appointments" class="quick-link">
                        <div class="quick-link-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="quick-link-text">
                            Manage Appointments
                            <span>View and edit appointments</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-server"></i> System Status</h2>
            </div>
            <div class="section-body section-body-padded">
                <div class="system-status">
                    <div class="status-item">
                        <div class="status-icon <?php echo $system_status['database'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="status-text">
                            Database
                            <span><?php echo $system_status['database'] ? 'Connected' : 'Connection Issue'; ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?php echo $system_status['uploads_dir'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="status-text">
                            Uploads Directory
                            <span><?php echo $system_status['uploads_dir'] ? 'Writable' : 'Permission Issue'; ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?php echo $system_status['php_version'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="status-text">
                            PHP Version
                            <span><?php echo PHP_VERSION; ?></span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?php echo $system_status['smtp_config'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="status-text">
                            SMTP Configuration
                            <span><?php echo $system_status['smtp_config'] ? 'Configured' : 'Not Configured'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>