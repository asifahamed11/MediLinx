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
function getCount($conn, $table) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Function to get users
function getUsers($conn, $role = null) {
    $query = "SELECT * FROM users";
    if ($role) {
        $query .= " WHERE role = '$role'";
    }
    $query .= " ORDER BY id DESC";
    return mysqli_query($conn, $query);
}

// Function to get appointments
function getAppointments($conn) {
    $query = "SELECT a.*, u1.username as patient_name, u2.username as doctor_name, t.start_time, t.end_time, t.location 
              FROM appointments a
              JOIN users u1 ON a.patient_id = u1.id
              JOIN users u2 ON (SELECT doctor_id FROM time_slots WHERE id = a.slot_id) = u2.id
              JOIN time_slots t ON a.slot_id = t.id
              ORDER BY a.created_at DESC";
    return mysqli_query($conn, $query);
}

// Function to get posts
function getPosts($conn) {
    $query = "SELECT p.*, u.username as doctor_name 
              FROM posts p
              JOIN users u ON p.doctor_id = u.id
              ORDER BY p.created_at DESC";
    return mysqli_query($conn, $query);
}

// Function to get reviews
function getReviews($conn) {
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }
        .sidebar-menu {
            margin-top: 20px;
        }
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #34495e;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
        }
        .logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .card h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            margin-right: 5px;
        }
        .btn-view {
            background-color: #3498db;
        }
        .btn-edit {
            background-color: #2ecc71;
        }
        .btn-delete {
            background-color: #e74c3c;
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
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }
        .search-bar button {
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 5px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination a.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Medilinx</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="?tab=dashboard" class="<?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="?tab=doctors" class="<?php echo $active_tab == 'doctors' ? 'active' : ''; ?>">Doctors</a>
            <a href="?tab=patients" class="<?php echo $active_tab == 'patients' ? 'active' : ''; ?>">Patients</a>
            <a href="?tab=appointments" class="<?php echo $active_tab == 'appointments' ? 'active' : ''; ?>">Appointments</a>
            <a href="?tab=time_slots" class="<?php echo $active_tab == 'time_slots' ? 'active' : ''; ?>">Time Slots</a>
            <a href="?tab=posts" class="<?php echo $active_tab == 'posts' ? 'active' : ''; ?>">Posts</a>
            <a href="?tab=reviews" class="<?php echo $active_tab == 'reviews' ? 'active' : ''; ?>">Reviews</a>
            <a href="?tab=settings" class="<?php echo $active_tab == 'settings' ? 'active' : ''; ?>">Settings</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>
                <?php 
                switch($active_tab) {
                    case 'dashboard': echo 'Dashboard'; break;
                    case 'doctors': echo 'Manage Doctors'; break;
                    case 'patients': echo 'Manage Patients'; break;
                    case 'appointments': echo 'Manage Appointments'; break;
                    case 'time_slots': echo 'Manage Time Slots'; break;
                    case 'posts': echo 'Manage Posts'; break;
                    case 'reviews': echo 'Manage Reviews'; break;
                    case 'settings': echo 'System Settings'; break;
                    default: echo 'Dashboard';
                }
                ?>
            </h1>
            <form action="admin_logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="alert alert-success">
                Record deleted successfully!
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
        // Simple JavaScript for search functionality and interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight the current row when hovering
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#f5f5f5';
                });
                
                row.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '';
                });
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 1s';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            });
        });
    </script>
</body>
</html>