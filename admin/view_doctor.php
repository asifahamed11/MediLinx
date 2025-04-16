<?php
session_start();
require_once 'config.php';
$conn = connectDB();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: admin.php?tab=doctors');
    exit;
}

$doctor_id = intval($_GET['id']);
$doctor = [];
$degrees = [];

try {
    // Get doctor info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    
    // Get degrees
    $degree_stmt = $conn->prepare("SELECT * FROM degrees WHERE doctor_id = ?");
    $degree_stmt->bind_param('i', $doctor_id);
    $degree_stmt->execute();
    $degrees = $degree_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching doctor data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
    <style>
        .content {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.profile-img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 3px solid #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.details-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.details-section h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 5px;
}

.degree {
    margin-bottom: 15px;
    padding: 10px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s;
}

.btn:hover {
    background: #2980b9;
}

p {
    margin: 10px 0;
    line-height: 1.6;
}

h2 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
}

    </style>
<head>
    <title>View Doctor</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    
    <div class="content">
        <h2>Doctor Details</h2>
        
        <div class="profile-header">
            <?php if ($doctor['profile_image']): ?>
                <img src="<?= $doctor['profile_image'] ?>" alt="Profile Image" class="profile-img">
            <?php endif; ?>
            <h3><?= htmlspecialchars($doctor['username']) ?></h3>
            <p><?= htmlspecialchars($doctor['specialty']) ?></p>
        </div>
        
        <div class="details-section">
            <h4>Basic Information</h4>
            <p>Email: <?= htmlspecialchars($doctor['email']) ?></p>
            <p>Medical License: <?= htmlspecialchars($doctor['medical_license_number']) ?></p>
            <p>Experience: <?= $doctor['years_of_experience'] ?> years</p>
            <p>Languages: <?= htmlspecialchars($doctor['languages_spoken']) ?></p>
        </div>
        
        <div class="details-section">
            <h4>Education</h4>
            <?php foreach ($degrees as $degree): ?>
                <div class="degree">
                    <strong><?= htmlspecialchars($degree['degree_name']) ?></strong>
                    <p><?= htmlspecialchars($degree['institution']) ?> (<?= $degree['passing_year'] ?>)</p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="details-section">
            <h4>Work Information</h4>
            <p>Address: <?= htmlspecialchars($doctor['work_address']) ?></p>
            <p>Consultation Hours:<br><?= nl2br(htmlspecialchars($doctor['consultation_hours'])) ?></p>
        </div>
        
        <div class="details-section">
            <h4>Biography</h4>
            <p><?= nl2br(htmlspecialchars($doctor['professional_biography'])) ?></p>
        </div>
        
        <a href="edit_doctor.php?id=<?= $doctor_id ?>" class="btn">Edit Doctor</a>
    </div>
</body>
</html>