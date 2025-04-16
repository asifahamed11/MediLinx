<?php
session_start();
require_once 'config.php';

// Better content truncation
function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    $truncated = substr($text, 0, strpos($text, ' ', $length));
    return $truncated ? $truncated . '...' : substr($text, 0, $length) . '...';
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = connectDB();
$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get doctor ratings if applicable
$average_rating = 0;
$total_reviews = 0;
if ($user['role'] === 'doctor') {
    $rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE doctor_id = ?");
    $rating_stmt->bind_param("i", $user_id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result();
    $rating_data = $rating_result->fetch_assoc();
    $average_rating = round($rating_data['avg_rating'], 1);
    $total_reviews = $rating_data['total'];
    $rating_stmt->close();
}

// Get user's recent activity
$activity = array();
if ($user['role'] === 'patient') {
    $activity_stmt = $conn->prepare("SELECT a.*, ts.start_time, ts.location, 
                                 u.username as doctor_name 
                                 FROM appointments a
                                 JOIN time_slots ts ON a.slot_id = ts.id
                                 JOIN users u ON ts.doctor_id = u.id
                                 WHERE a.patient_id = ?
                                 ORDER BY ts.start_time DESC LIMIT 5");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();
    $activity = $activity_result->fetch_all(MYSQLI_ASSOC);
    $activity_stmt->close();
} else {
    // Get upcoming appointments for doctors
    $activity_stmt = $conn->prepare("SELECT a.*, ts.start_time, ts.location, 
                                 u.username as patient_name 
                                 FROM appointments a
                                 JOIN time_slots ts ON a.slot_id = ts.id
                                 JOIN users u ON a.patient_id = u.id
                                 WHERE ts.doctor_id = ? AND ts.start_time > NOW()
                                 ORDER BY ts.start_time ASC LIMIT 5");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();
    $activity = $activity_result->fetch_all(MYSQLI_ASSOC);
    $activity_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-light: rgba(42, 157, 143, 0.1);
            --primary-dark: #218377;
            --secondary: #264653;
            --accent: #E76F51;
            --light-bg: #f8f9fa;
            --text: #2d3748;
            --text-light: #718096;
            --success: #38b2ac;
            --warning: #f6ad55;
            --danger: #e53e3e;
            --border-radius: 1rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f8fb 0%, #e9f0f8 100%);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }

        .page-container {
            min-height: 100vh;
            padding: 2rem 1rem;
            position: relative;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: rotate(45deg);
            animation: float 8s ease-in-out infinite;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(15px, 15px) rotate(5deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }

        .profile-image-container {
            position: relative;
            margin: 0 auto;
            width: 180px;
            height: 180px;
            animation: zoomIn 0.5s 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 10;
            position: relative;
        }

        .profile-image:hover {
            transform: scale(1.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .profile-status {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: var(--success);
            border-radius: 50%;
            border: 3px solid white;
            z-index: 20;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 178, 172, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(56, 178, 172, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 178, 172, 0); }
        }

        .profile-name {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            margin: 1.5rem 0 0.5rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.5s 0.5s both;
        }

        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.5s 0.6s both;
        }



        .rating-stars {
            margin: 1rem 0;
            animation: fadeIn 0.5s 0.8s both;
        }

        .star {
            color: #FFD700;
            font-size: 1.2rem;
            margin: 0 0.1rem;
        }

        .profile-actions {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.5s 0.9s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn {
            padding: 0.9rem 1.5rem;
            border-radius: 2rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.3), rgba(255,255,255,0));
            transition: left 0.6s;
        }

        .btn:hover::after {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: white;
            color: var(--secondary);
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            color: var(--primary);
        }

        .profile-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 2rem;
            position: relative;
        }

        .profile-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 280px;
            height: 100%;
            width: 2px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.03), rgba(0,0,0,0.08), rgba(0,0,0,0.03));
        }

        .profile-sidebar {
            animation: fadeInLeft 0.7s 0.3s both;
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }

        .info-card h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card h3 i {
            color: var(--primary);
        }

        .info-item {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: var(--primary-light);
            transform: translateX(5px);
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-label i {
            color: var(--primary);
        }

        .info-value {
            color: var(--text);
            font-size: 0.95rem;
        }

        .degrees-list {
            margin-top: 1rem;
        }

        .degree-item {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .degree-item:hover {
            background: var(--primary-light);
            transform: translateX(5px);
        }

        .degree-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .degree-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .profile-main {
            animation: fadeInRight 0.7s 0.5s both;
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: var(--light-bg);
            padding: 0.5rem;
            border-radius: 1rem;
            position: relative;
            overflow: hidden;
        }

        .tab-indicator {
            position: absolute;
            height: calc(100% - 1rem);
            border-radius: 0.75rem;
            background: var(--primary);
            top: 0.5rem;
            left: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }

        .tab-button {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            z-index: 2;
            font-family: 'Poppins', sans-serif;
        }

        .tab-button.active {
            color: white;
        }

        .tab-content {
            display: none;
            animation: fadeContent 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeContent {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .post-item {
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .post-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .post-header {
            margin-bottom: 1rem;
        }

        .post-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .post-content {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: var(--text);
        }

        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .post-likes {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-likes i {
            color: var(--accent);
        }

        .post-date {
            font-style: italic;
        }

        .activity-list {
            margin-top: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            background: var(--light-bg);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--primary-light);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .appointment-status {
            padding: 0.25rem 0.5rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-confirmed {
            background: rgba(56, 178, 172, 0.1);
            color: var(--success);
        }

        .status-completed {
            background: rgba(56, 178, 172, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }

        .chart-card {
            margin-top: 2rem;
        }

        .chart-container {
            width: 100%;
            height: 240px;
            margin-top: 1rem;
        }

        .loader {
            border: 3px solid rgba(42, 157, 143, 0.1);
            border-radius: 50%;
            border-top: 3px solid var(--primary);
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
                padding: 1.5rem;
            }

            .profile-content::before {
                display: none;
            }

            .profile-header {
                padding: 3rem 1.5rem;
            }

            .profile-name {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 2.5rem 1rem;
            }

            .profile-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .tabs {
                flex-direction: column;
                background: none;
                padding: 0;
            }

            .tab-button {
                margin-bottom: 0.5rem;
                background: var(--light-bg);
            }

            .tab-indicator {
                display: none;
            }

            .tab-button.active {
                background: var(--primary);
            }
        }

        @media (max-width: 576px) {
            .profile-image-container {
                width: 150px;
                height: 150px;
            }

            .profile-name {
                font-size: 1.75rem;
            }

            .profile-role {
                font-size: 1rem;
            }

            .star {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="page-container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image-container">
                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?: 'uploads/default_profile.jpg'); ?>"
                        class="profile-image"
                        alt="Profile Picture"
                        onerror="this.src='uploads/default_profile.png'">
                        
                    <div class="profile-status"></div>
                </div>
                <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="profile-role"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
                
                <?php if($user['role'] === 'doctor'): ?>
                    
                    
                    <?php if($average_rating > 0): ?>
                    <div class="rating-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= floor($average_rating)): ?>
                                <span class="star"><i class="fas fa-star"></i></span>
                            <?php elseif($i - $average_rating < 1 && $i - $average_rating > 0): ?>
                                <span class="star"><i class="fas fa-star-half-alt"></i></span>
                            <?php else: ?>
                                <span class="star"><i class="far fa-star"></i></span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span style="color: white; font-size: 0.9rem; margin-left: 0.5rem;">(<?php echo $total_reviews; ?> reviews)</span>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <?php if($user_id == $_SESSION['user_id']): ?>
                        <a href="edit-profile.php" class="btn btn-primary">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    <?php else: ?>
                        <?php if($user['role'] === 'doctor'): ?>
                            <a href="book-appointment.php?doctor_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-calendar-check"></i> Book Appointment
                            </a>
                        <?php endif; ?>
                        <a href="messages.php?recipient=<?php echo $user['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-envelope"></i> Send Message
                        </a>
                    <?php endif; ?>
                    
                    <?php if($user['role'] === 'doctor'): ?>
                        <a href="posts.php?doctor_id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-file-medical"></i> View All Posts
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-sidebar">
                    <div class="info-card">
                        <h3><i class="fas fa-address-card"></i> Contact Info.</h3>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['address'] ?: 'N/A'); ?></div>
                        </div>
                    </div>

                    <?php if($user['role'] === 'doctor'): ?>
                    <div class="info-card">
                        <h3><i class="fas fa-graduation-cap"></i> Education</h3>
                        <?php
                        $degreeStmt = $conn->prepare("SELECT * FROM degrees WHERE doctor_id = ? ORDER BY passing_year DESC");
                        $degreeStmt->bind_param("i", $user['id']);
                        $degreeStmt->execute();
                        $degrees = $degreeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        if (!empty($degrees)): ?>
                            <div class="degrees-list">
                                <?php foreach ($degrees as $index => $degree): ?>
                                    <div class="degree-item" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
                                        <div class="degree-name"><?= htmlspecialchars($degree['degree_name']) ?></div>
                                        <div class="degree-details">
                                            <span><?= htmlspecialchars($degree['institution']) ?></span>
                                            <span><?= $degree['passing_year'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No degrees added yet</p>
                        <?php endif; ?>
                        <?php $degreeStmt->close(); ?>
                    </div>

                    <div class="info-card">
                        <h3><i class="fas fa-briefcase-medical"></i> Professional Info.</h3>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-stethoscope"></i> Specialty</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['specialty']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-id-card"></i> License Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['medical_license_number']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="profile-main">
    <div class="tabs">
        <div class="tab-indicator"></div>
        <button class="tab-button active" data-tab="personal">
            <i class="fas fa-user"></i> Personal Info
        </button>
        <?php if($user['role'] === 'doctor'): ?>
            <button class="tab-button" data-tab="professional">
                <i class="fas fa-briefcase-medical"></i> Professional Details
            </button>
            <button class="tab-button" data-tab="posts">
                <i class="fas fa-file-medical-alt"></i> Recent Posts
            </button>
        <?php else: ?>
            <button class="tab-button" data-tab="medical">
                <i class="fas fa-heartbeat"></i> Medical History
            </button>
            <button class="tab-button" data-tab="activity">
                <i class="fas fa-calendar-check"></i> Recent Activity
            </button>
        <?php endif; ?>
    </div>

    <div class="tab-content active" id="personal">
        <div class="info-card">
            <h3><i class="fas fa-id-card"></i> Personal Details</h3>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-birthday-cake"></i> Date of Birth</div>
                <div class="info-value"><?php echo htmlspecialchars($user['date_of_birth'] ?: 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-venus-mars"></i> Gender</div>
                <div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: 'Not specified'); ?></div>
            </div>
        </div>
        
        <?php if(!empty($user['languages_spoken'])): ?>
        <div class="info-card">
            <h3><i class="fas fa-language"></i> Languages</h3>
            <div class="info-item">
            <div class="info-value"><?php echo htmlspecialchars($user['languages_spoken'] ?: 'N/A'); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($user['role'] === 'doctor' && !empty($user['professional_biography'])): ?>
        <div class="info-card">
            <h3><i class="fas fa-user-md"></i> Biography</h3>
            <div class="info-value" style="line-height: 1.8; white-space: pre-wrap;">
                <?php echo htmlspecialchars($user['professional_biography']); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if($user['role'] === 'doctor'): ?>
        <div class="tab-content" id="professional">
            <div class="info-card">
                <h3><i class="fas fa-map-marked-alt"></i> Work Location</h3>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-hospital"></i> Work Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['work_address']); ?></div>
                </div>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-clock"></i> Consultation Hours</h3>
                <div class="info-value">
                    <?php 
                    $hours = explode(',', $user['available_consultation_hours']);
                    foreach($hours as $slot) {
                        echo '<div class="info-item">
                                <div class="info-value">
                                    <i class="far fa-clock" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                    '.trim($slot).'
                                </div>
                            </div>';
                    }
                    ?>
                </div>
            </div>
            

        </div>
        
        <div class="tab-content" id="posts">
            <?php
            $conn = connectDB(); // Reconnect to database
            $posts_stmt = $conn->prepare("SELECT p.*, 
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count
                FROM posts p 
                WHERE p.doctor_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT 5");
            $posts_stmt->bind_param("i", $user['id']);
            $posts_stmt->execute();
            $posts_result = $posts_stmt->get_result();
            $doctor_posts = $posts_result->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <?php if (!empty($doctor_posts)): ?>
                <?php foreach ($doctor_posts as $index => $post): ?>
                    <div class="post-item" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
                        <div class="post-header">
                            <h4 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h4>
                        </div>
                        <div class="post-content">
                            <?php echo htmlspecialchars(truncateText($post['content'], 200)); ?>
                        </div>
                        <div class="post-footer">
                            <div class="post-likes">
                                <i class="fas fa-heart"></i>
                                <span><?php echo $post['like_count']; ?> likes</span>
                            </div>
                            <div class="post-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="posts.php?doctor_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-file-medical"></i> View All Posts
                    </a>
                </div>
            <?php else: ?>
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> No Posts Yet</h3>
                    <p style="color: var(--text-light); text-align: center; margin: 2rem 0;">
                        <?php echo htmlspecialchars($user['username']); ?> hasn't published any posts yet.
                    </p>
                </div>
            <?php endif; ?>
            <?php $posts_stmt->close(); ?>
        </div>
    <?php else: ?>
        <div class="tab-content" id="medical">
            <div class="info-card">
                <h3><i class="fas fa-notes-medical"></i> Medical History</h3>
                <div class="info-value" style="white-space: pre-wrap; line-height: 1.8;">
                    <?php echo htmlspecialchars($user['medical_history'] ?: 'No medical history recorded'); ?>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="activity">
            <div class="info-card">
                <h3><i class="fas fa-calendar-check"></i> Recent Appointments</h3>
                
                <?php if (!empty($activity)): ?>
                    <div class="activity-list">
                        <?php foreach ($activity as $index => $item): ?>
                            <div class="activity-item" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
                                <div class="activity-icon">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo ($user['role'] === 'patient') ? 
                                            'Dr. ' . htmlspecialchars($item['doctor_name']) : 
                                            htmlspecialchars($item['patient_name']); ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span>
                                            <i class="far fa-calendar"></i> 
                                            <?php echo date('M j, Y', strtotime($item['start_time'])); ?>
                                        </span>
                                        <span>
                                            <i class="far fa-clock"></i>
                                            <?php echo date('h:i A', strtotime($item['start_time'])); ?>
                                        </span>
                                    </div>
                                    <div class="activity-meta" style="margin-top: 0.5rem;">
                                        <span>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($item['location']); ?>
                                        </span>
                                        <span class="appointment-status status-<?php echo $item['status']; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light); text-align: center; margin: 2rem 0;">
                        No recent appointments found.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script>
        // Tabs functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.dataset.tab;
                const buttonRect = button.getBoundingClientRect();
                const indicator = document.querySelector('.tab-indicator');
                
                // Move tab indicator
                indicator.style.width = `${buttonRect.width}px`;
                indicator.style.left = `${button.offsetLeft}px`;
                
                // Activate correct tab
                document.querySelectorAll('.tab-button, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                button.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Initialize tab indicator position
        window.addEventListener('load', () => {
            const activeTab = document.querySelector('.tab-button.active');
            if (activeTab) {
                const indicator = document.querySelector('.tab-indicator');
                indicator.style.width = `${activeTab.offsetWidth}px`;
                indicator.style.left = `${activeTab.offsetLeft}px`;
            }
            
            // Initialize charts if doctor profile
            <?php if($user['role'] === 'doctor'): ?>
            // Sample data for experience chart
            const ctx = document.getElementById('experienceChart').getContext('2d');
            const experienceChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [
                        'Patient Communication',
                        'Technical Knowledge',
                        'Diagnosis Accuracy',
                        'Treatment Success',
                        'Follow-up Care',
                        'Research Contribution'
                    ],
                    datasets: [{
                        label: 'Professional Skills',
                        data: [90, 85, 95, 88, 92, 80],
                        backgroundColor: 'rgba(42, 157, 143, 0.2)',
                        borderColor: 'rgba(42, 157, 143, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(42, 157, 143, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(42, 157, 143, 1)'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.1
                        }
                    }
                }
            });
            <?php endif; ?>
        });
        
        // Reveal animations for cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.info-card, .degree-item, .post-item, .activity-item').forEach(item => {
            observer.observe(item);
        });
        
        // Add hover animations for profile image
        const profileImage = document.querySelector('.profile-image');
        profileImage.addEventListener('mouseover', () => {
            profileImage.style.transform = 'scale(1.08) rotate(3deg)';
        });
        
        profileImage.addEventListener('mouseout', () => {
            profileImage.style.transform = 'scale(1) rotate(0deg)';
        });
    </script>
</body>
</html>