<?php
session_start();
require_once 'config.php';

// Create CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get doctor information
$doctor = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'doctor'");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: 404.php");
    exit;
}
$doctor = $result->fetch_assoc();

// Get doctor's degrees
$degrees = [];
$degree_stmt = $conn->prepare("SELECT * FROM degrees WHERE doctor_id = ? ORDER BY passing_year DESC");
$degree_stmt->bind_param("i", $doctor_id);
$degree_stmt->execute();
$degrees = $degree_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get doctor's posts with like counts
$posts = [];
$stmt = $conn->prepare("SELECT posts.*, 
    (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) as like_count,
    EXISTS(SELECT 1 FROM post_likes WHERE post_id = posts.id AND user_id = ?) as user_liked 
    FROM posts 
    WHERE posts.doctor_id = ? 
    ORDER BY posts.created_at DESC");
$stmt->bind_param("ii", $_SESSION['user_id'], $doctor_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get reviews and average rating
$reviews = [];
$avg_rating = 0;
$stmt = $conn->prepare("SELECT r.*, u.username, u.profile_image 
                       FROM reviews r
                       JOIN users u ON r.patient_id = u.id
                       WHERE r.doctor_id = ?
                       ORDER BY r.created_at DESC");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE doctor_id = ?");
$rating_stmt->bind_param("i", $doctor_id);
$rating_stmt->execute();
$avg_rating = $rating_stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0;

// Check if current user has already reviewed
$has_reviewed = false;
if ($_SESSION['role'] === 'patient') {
    $review_check = $conn->prepare("SELECT id FROM reviews WHERE doctor_id = ? AND patient_id = ?");
    $review_check->bind_param("ii", $doctor_id, $_SESSION['user_id']);
    $review_check->execute();
    $has_reviewed = $review_check->get_result()->num_rows > 0;
}

// Handle review submission
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && !$has_reviewed) {
    $rating = (float)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $review_error = 'Please select a valid rating between 1 and 5';
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (doctor_id, patient_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $doctor_id, $_SESSION['user_id'], $rating, $comment);
        if ($stmt->execute()) {
            header("Location: doctor-profile.php?id=$doctor_id");
            exit;
        } else {
            $review_error = 'Error submitting review. Please try again.';
        }
    }
}

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    $check_stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $post_id, $user_id);
        $delete_stmt->execute();
    } else {
        $like_stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $like_stmt->bind_param("ii", $post_id, $user_id);
        $like_stmt->execute();
    }

    header("Location: doctor-profile.php?id=$doctor_id");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?= htmlspecialchars($doctor['username']) ?> - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #E76F51;
            --light-bg: #f8f9fa;
            --text: #2d3748;
            --gradient: linear-gradient(135deg, var(--primary) 0%, #2AC8B8 100%);
        }

        body {
            background: var(--light-bg);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            background: white;
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(42, 157, 143, 0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .doctor-profile {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 3rem;
            align-items: start;
        }

        .profile-sidebar {
            top: 5rem;
            text-align: center;
            align-self: flex-start;
            height: fit-content;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid var(--primary);
            box-shadow: 0 8px 24px rgba(42, 157, 143, 0.2);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .rating-badge {
            background: var(--gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .doctor-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .detail-item {
            background: rgba(42, 157, 143, 0.05);
            padding: 1.5rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .detail-item:hover {
            transform: translateY(-3px);
        }

        .detail-item i {
            color: var(--primary);
            font-size: 1.2rem;
            width: 30px;
        }

        .section-card {
            background: white;
            padding: 2.5rem;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            animation: cardEnter 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post-card {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 1rem;
            margin: 1.5rem 0;
            animation: imageLoad 0.6s ease-out;
        }

        @keyframes imageLoad {
            from {
                opacity: 0;
                transform: scale(0.98);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .review-card {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 1.5rem;
            padding: 2rem;
            margin: 1.5rem 0;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-3px);
        }

        .review-user {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .star-rating {
            color: #ffc107;
            display: flex;
            gap: 0.3rem;
            margin: 0.5rem 0;
        }

        .review-form {
            background: rgba(42, 157, 143, 0.03);
            padding: 2.5rem;
            border-radius: 2rem;
            margin: 2rem 0;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }

        .rating-star {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: all 0.2s ease;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        textarea {
            width: 100%;
            padding: 1.5rem;
            border: 2px solid rgba(42, 157, 143, 0.1);
            border-radius: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.1);
            outline: none;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .doctor-profile {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                position: relative;
                top: 0;
            }

            .container {
                padding: 1rem;
            }

            .section-card {
                padding: 1.5rem;
            }
        }

        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .like-button {
            background: rgba(42, 157, 143, 0.08);
            border: none;
            color: var(--primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            padding: 8px 16px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .like-button:hover {
            background: rgba(42, 157, 143, 0.15);
            transform: scale(1.05);
        }

        .like-button.liked {
            color: var(--accent);
            background: rgba(231, 111, 81, 0.1);
        }

        .like-button.liked svg {
            animation: heartBounce 0.6s ease;
        }

        @keyframes heartBounce {
            0% {
                transform: scale(1);
            }

            30% {
                transform: scale(1.3);
            }

            50% {
                transform: scale(0.9);
            }

            70% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        .education-section {
            margin-top: 2.5rem;
        }

        .education-section h3 {
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .degrees-list {
            display: grid;
            gap: 1.5rem;
        }

        .degree-item {
            background: rgba(42, 157, 143, 0.05);
            padding: 1.5rem;
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }

        .degree-item:hover {
            transform: translateY(-3px);
            background: rgba(42, 157, 143, 0.08);
        }

        .degree-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .degree-header h4 {
            color: var(--primary);
            font-size: 1.1rem;
            margin: 0;
        }

        .degree-header .year {
            color: #666;
            font-size: 0.9rem;
        }

        .institution,
        .specialization {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            margin-top: 0.5rem;
        }

        .institution i,
        .specialization i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .no-degrees {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 1rem;
        }

        .time-slots {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .time-slot-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .book-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .book-btn:hover {
            background: var(--primary-dark);
        }

        /* Calendar Styles */
        .calendar-container {
            margin-top: 20px;
            animation: fadeIn 0.8s ease-out;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(42, 157, 143, 0.2);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.3);
        }

        .current-month {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            color: var(--secondary);
            padding: 10px;
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: 10px;
            padding: 5px;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        .calendar-day:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .day-number {
            text-align: center;
            font-weight: 500;
            padding: 5px;
            color: var(--text);
        }

        .today .day-number {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .empty-day {
            background: #f5f5f5;
            cursor: default;
        }

        .empty-day:hover {
            transform: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .has-events {
            position: relative;
        }

        .has-events::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }

        .event-indicator {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            margin: 0 auto;
        }

        .day-events {
            flex: 1;
            overflow-y: auto;
            font-size: 0.7rem;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        .day-events::-webkit-scrollbar {
            width: 4px;
        }

        .day-events::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 4px;
        }

        .event {
            background: rgba(42, 157, 143, 0.1);
            border-left: 3px solid var(--primary);
            padding: 3px 5px;
            margin: 3px 0;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
            transition: all 0.2s ease;
        }

        .event:hover {
            background: rgba(42, 157, 143, 0.2);
        }

        /* Modal Styles */
        .day-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            animation: modalEnter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            background: var(--gradient);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .modal-slot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s ease;
        }

        .modal-slot:hover {
            background: rgba(42, 157, 143, 0.05);
        }

        .modal-slot-time {
            font-weight: 500;
        }

        .modal-slot-location {
            color: #666;
            font-size: 0.9rem;
        }

        .modal-book-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modal-book-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .full-slot {
            background: #e0e0e0;
            color: #666;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .view-only {
            background: #f0f0f0;
            color: #666;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="doctor-profile">
                <div class="profile-sidebar">
                    <img src="<?= htmlspecialchars($doctor['profile_image'] ?: 'uploads/default_profile.png') ?>"
                        class="profile-image"
                        alt="<?= htmlspecialchars($doctor['username']) ?>"
                        onerror="this.src='uploads/default_profile.png'">
                    <div class="rating-badge">
                        <i class="fas fa-star"></i>
                        <?= number_format($avg_rating, 1) ?>
                    </div>
                </div>

                <div class="profile-info">
                    <h1><?= htmlspecialchars($doctor['username']) ?></h1>
                    <p class="text-primary"><?= htmlspecialchars($doctor['specialty']) ?></p>

                    <div class="doctor-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($doctor['work_address']) ?>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-briefcase"></i>
                            <?= $doctor['years_of_experience'] ?> Years Experience
                        </div>
                        <?php if (!empty($doctor['languages_spoken'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-language"></i>
                                <?= htmlspecialchars(str_replace(',', ', ', $doctor['languages_spoken'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bio-section">
                        <h3>Professional Biography</h3>
                        <p><?= nl2br(htmlspecialchars($doctor['professional_biography'])) ?></p>
                    </div>
                    <div class="education-section">
                        <h3>Education & Qualifications</h3>
                        <?php if (!empty($degrees)): ?>
                            <div class="degrees-list">
                                <?php foreach ($degrees as $degree): ?>
                                    <div class="degree-item">
                                        <div class="degree-header">
                                            <h4><?= htmlspecialchars($degree['degree_name']) ?></h4>
                                            <span class="year"><?= htmlspecialchars($degree['passing_year']) ?></span>
                                        </div>
                                        <div class="institution">
                                            <i class="fas fa-university"></i>
                                            <?= htmlspecialchars($degree['institution']) ?>
                                        </div>
                                        <?php if (!empty($degree['specialization'])): ?>
                                            <div class="specialization">
                                                <i class="fas fa-graduation-cap"></i>
                                                <?= htmlspecialchars($degree['specialization']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-degrees">No educational information available</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <div class="availability-section">
                <h3>Available Appointments</h3>
                <?php
                // Get current month/year or from query parameters
                $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
                $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

                // Validate month/year
                if ($month < 1 || $month > 12) {
                    $month = date('m');
                }
                if ($year < date('Y') || $year > date('Y') + 2) {
                    $year = date('Y');
                }

                // Get first and last day of the month
                $first_day = mktime(0, 0, 0, $month, 1, $year);
                $last_day = mktime(0, 0, 0, $month + 1, 0, $year);
                $days_in_month = date('t', $first_day);
                $first_day_of_week = date('w', $first_day);

                // Get time slots for the month
                $slots_by_day = [];
                $start_date = date('Y-m-d', $first_day);
                $end_date = date('Y-m-d', $last_day);
                $patient_id = $_SESSION['user_id'];

                $conn = connectDB();

                // If user is a patient, exclude slots they've already booked
                if ($_SESSION['role'] === 'patient') {
                    $slot_stmt = $conn->prepare("
                        SELECT ts.id, ts.doctor_id, ts.start_time, ts.end_time, ts.status, ts.location, ts.capacity, ts.booked_count
                        FROM time_slots ts
                        WHERE ts.doctor_id = ? 
                        AND ts.start_time BETWEEN ? AND ? 
                        AND ts.status = 'available'
                        AND (ts.capacity IS NULL OR ts.booked_count < ts.capacity)
                        AND NOT EXISTS (
                            SELECT 1 FROM appointments a 
                            WHERE a.slot_id = ts.id AND a.patient_id = ?
                        )
                        ORDER BY ts.start_time ASC
                    ");

                    // Create full datetime strings as separate variables
                    $start_datetime = $start_date . " 00:00:00";
                    $end_datetime = $end_date . " 23:59:59";

                    $slot_stmt->bind_param("issi", $doctor_id, $start_datetime, $end_datetime, $patient_id);
                } else {
                    // For doctors and other roles, show all slots
                    $slot_stmt = $conn->prepare("
                        SELECT id, doctor_id, start_time, end_time, status, location, capacity, booked_count
                        FROM time_slots 
                        WHERE doctor_id = ? 
                        AND start_time BETWEEN ? AND ? 
                        AND status = 'available'
                        AND (capacity IS NULL OR booked_count < capacity)
                        ORDER BY start_time ASC
                    ");

                    // Create full datetime strings as separate variables
                    $start_datetime = $start_date . " 00:00:00";
                    $end_datetime = $end_date . " 23:59:59";

                    $slot_stmt->bind_param("iss", $doctor_id, $start_datetime, $end_datetime);
                }

                $slot_stmt->execute();
                $slot_result = $slot_stmt->get_result();

                while ($slot = $slot_result->fetch_assoc()) {
                    $day = date('j', strtotime($slot['start_time']));
                    if (!isset($slots_by_day[$day])) {
                        $slots_by_day[$day] = [];
                    }
                    $slots_by_day[$day][] = $slot;
                }

                // Generate month navigation links
                $prev_month = $month - 1;
                $prev_year = $year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }

                $next_month = $month + 1;
                $next_year = $year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }

                // Today's date for highlighting current day
                $today = date('j');
                $current_month = date('m');
                $current_year = date('Y');
                ?>

                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <a href="?id=<?= $doctor_id ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="nav-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div class="current-month"><?= date('F Y', $first_day) ?></div>
                            <a href="?id=<?= $doctor_id ?>&month=<?= $next_month ?>&year=<?= $next_year ?>" class="nav-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <!-- Day headers -->
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>

                        <!-- Empty cells for days before the first day of the month -->
                        <?php for ($i = 0; $i < $first_day_of_week; $i++): ?>
                            <div class="calendar-day empty-day"></div>
                        <?php endfor; ?>

                        <!-- Days of the month -->
                        <?php for ($day = 1; $day <= $days_in_month; $day++):
                            $is_today = ($day == $today && $month == $current_month && $year == $current_year);
                            $has_events = isset($slots_by_day[$day]) && !empty($slots_by_day[$day]);
                            $day_class = $is_today ? 'calendar-day today' : 'calendar-day';
                            $day_class .= $has_events ? ' has-events' : '';
                        ?>
                            <div class="<?= $day_class ?>" onclick="showDayEvents(<?= $day ?>, '<?= date('F j, Y', mktime(0, 0, 0, $month, $day, $year)) ?>')">
                                <div class="day-number"><?= $day ?></div>
                                <?php if ($has_events): ?>
                                    <div class="day-events">
                                        <?php foreach (array_slice($slots_by_day[$day], 0, 2) as $slot): ?>
                                            <div class="event"><?= date('g:i A', strtotime($slot['start_time'])) ?></div>
                                        <?php endforeach; ?>

                                        <?php if (count($slots_by_day[$day]) > 2): ?>
                                            <div class="event">+<?= count($slots_by_day[$day]) - 2 ?> more</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>

                        <!-- Empty cells for days after the last day of the month -->
                        <?php
                        $last_day_of_week = date('w', mktime(0, 0, 0, $month, $days_in_month, $year));
                        for ($i = $last_day_of_week + 1; $i < 7; $i++):
                        ?>
                            <div class="calendar-day empty-day"></div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Day Events Modal -->
                <div id="dayModal" class="day-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title" id="modalTitle">Available Appointments</h3>
                            <button class="modal-close" onclick="closeDayModal()">&times;</button>
                        </div>
                        <div class="modal-body" id="modalBody">
                            <!-- Appointment slots will be loaded here -->
                        </div>
                    </div>
                </div>

                <script>
                    // Store all slots by day for JavaScript access
                    const slotsByDay = <?= json_encode($slots_by_day) ?>;

                    function showDayEvents(day, dateString) {
                        const modal = document.getElementById('dayModal');
                        const modalTitle = document.getElementById('modalTitle');
                        const modalBody = document.getElementById('modalBody');

                        modalTitle.textContent = 'Appointments for ' + dateString;
                        modalBody.innerHTML = '';

                        // Filter slots to only show those with available capacity
                        const availableSlots = slotsByDay[day] ? slotsByDay[day].filter(slot => {
                            const capacity = slot.capacity || 20;
                            const booked = slot.booked_count || 0;
                            return booked < capacity;
                        }) : [];

                        if (availableSlots.length > 0) {
                            availableSlots.forEach(slot => {
                                const startTime = new Date(slot.start_time).toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                const endTime = new Date(slot.end_time).toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });

                                const slotElement = document.createElement('div');
                                slotElement.className = 'modal-slot';

                                // Calculate spaces left
                                const capacity = slot.capacity || 20;
                                const booked = slot.booked_count || 0;
                                const spacesLeft = capacity - booked;

                                slotElement.innerHTML = `
                                    <div>
                                        <div class="modal-slot-time">${startTime} - ${endTime}</div>
                                        <div class="modal-slot-location">
                                            <i class="fas fa-map-marker-alt"></i> ${slot.location}
                                            <br><small>${spacesLeft} of ${capacity} spots available</small>
                                        </div>
                                    </div>
                                    ${<?= $_SESSION['role'] === 'patient' ? 'true' : 'false' ?> ? 
                                    `<form method="POST" action="book_appointment.php">
                                        <input type="hidden" name="slot_id" value="${slot.id}">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        ${spacesLeft > 0 ? '<button type="submit" class="modal-book-btn">Book</button>' : '<span class="full-slot">Full</span>'}
                                    </form>` : 
                                    '<div class="slot-info"><span class="view-only">View Only</span></div>'}
                                `;

                                modalBody.appendChild(slotElement);
                            });
                        } else {
                            modalBody.innerHTML = '<p style="text-align: center; padding: 20px;">No available appointments for this day</p>';
                        }

                        modal.style.display = 'flex';
                    }

                    function closeDayModal() {
                        const modal = document.getElementById('dayModal');
                        modal.style.display = 'none';
                    }

                    // Close modal when clicking outside
                    window.onclick = function(event) {
                        const modal = document.getElementById('dayModal');
                        if (event.target === modal) {
                            closeDayModal();
                        }
                    }
                </script>
            </div>
        </div>

        <?php if (!empty($posts)): ?>
            <div class="section-card">
                <h2>Latest Medical Insights</h2>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                        <?php if ($post['image']): ?>
                            <img src="<?= htmlspecialchars($post['image']) ?>"
                                class="post-image"
                                alt="<?= htmlspecialchars($post['title']) ?>"
                                onerror="this.style.display='none'">
                        <?php endif; ?>
                        <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        <div class="post-footer">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="like-button <?= $post['user_liked'] ? 'liked' : '' ?>">
                                    <svg fill="<?php echo $post['user_liked'] ? 'currentColor' : '#000000'; ?>" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                        width="16px" height="16px" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve">
                                        <path d="M19,7c-0.6,0-1-0.4-1-1c0-2.2-1.8-4-4-4c-1.3,0-2.4,0.6-3.2,1.6c-0.4,0.5-1.2,0.5-1.6,0C8.4,2.6,7.3,2,6,2C3.8,2,2,3.8,2,6
        c0,0.6-0.4,1-1,1S0,6.6,0,6c0-3.3,2.7-6,6-6c1.5,0,2.9,0.6,4,1.5c1.1-1,2.5-1.5,4-1.5c3.3,0,6,2.7,6,6C20,6.6,19.6,7,19,7z" />
                                        <path d="M9.3,19.7c-0.1-0.1-3.2-2.8-5.7-6.1c-0.3-0.4-0.3-1.1,0.2-1.4c0.4-0.3,1.1-0.3,1.4,0.2c1.8,2.3,3.8,4.3,4.8,5.3
        c1-1,3.1-3,4.9-5.3c0.3-0.4,1-0.5,1.4-0.2c0.4,0.3,0.5,1,0.2,1.4c-2.6,3.3-5.6,6-5.8,6.1C10.3,20.1,9.7,20.1,9.3,19.7z" />
                                        <path d="M11,14C11,14,11,14,11,14c-0.4,0-0.7-0.2-0.9-0.6L7.9,9l-1,1.6C6.6,10.8,6.3,11,6,11H1c-0.6,0-1-0.4-1-1s0.4-1,1-1h4.5
        l1.7-2.6C7.4,6.1,7.7,6,8.1,6c0.4,0,0.7,0.2,0.8,0.6l2.2,4.5l1-1.6C12.4,9.2,12.7,9,13,9h6c0.6,0,1,0.4,1,1s-0.4-1,1-1h-5.5
        l-1.7,2.6C11.6,13.8,11.3,14,11,14z" />
                                    </svg>
                                    <?= $post['like_count'] ?> likes
                                </button>
                            </form>
                            <div class="post-meta">
                                <small><?= date('M j, Y', strtotime($post['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <h2>Patient Reviews</h2>

            <?php if ($_SESSION['role'] === 'patient' && !$has_reviewed): ?>
                <div class="review-form">
                    <h3>Share Your Experience</h3>
                    <?php if ($review_error): ?>
                        <div class="error-message"><?= $review_error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="rating-stars" id="ratingStars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <i class="fas fa-star rating-star" data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="selectedRating" required>
                        <textarea name="comment" placeholder="Tell us about your experience..."
                            rows="4" required></textarea>
                        <button type="submit" name="submit_review" class="btn-primary">
                            Submit Review
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <img src="<?= htmlspecialchars($review['profile_image'] ?: 'uploads/default_profile.png') ?>"
                            class="review-user"
                            alt="<?= htmlspecialchars($review['username']) ?>"
                            onerror="this.src='uploads/default_profile.png'">
                        <div>
                            <h4><?= htmlspecialchars($review['username']) ?></h4>
                            <div class="star-rating">
                                <?php
                                $fullStars = floor($review['rating']);
                                $halfStar = ($review['rating'] - $fullStars) >= 0.5;
                                for ($i = 0; $i < 5; $i++):
                                ?>
                                    <?php if ($i < $fullStars): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($halfStar && $i === $fullStars): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                            <small><?= date('M j, Y', strtotime($review['created_at'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-slash"></i>
                    <p>No reviews yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Star Rating Interaction
        const stars = document.querySelectorAll('.rating-star');
        const selectedRating = document.getElementById('selectedRating');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.rating;
                selectedRating.value = rating;

                stars.forEach((s, index) => {
                    const starValue = 5 - index;
                    s.classList.toggle('active', starValue <= rating);
                    s.style.transform = starValue <= rating ? 'scale(1.1)' : 'scale(1)';
                });
            });

            star.addEventListener('mouseover', () => {
                const hoverRating = star.dataset.rating;
                stars.forEach((s, index) => {
                    const starValue = 5 - index;
                    s.style.color = starValue <= hoverRating ? '#ffc107' : '#ddd';
                });
            });

            star.addEventListener('mouseout', () => {
                const currentRating = selectedRating.value || 0;
                stars.forEach((s, index) => {
                    const starValue = 5 - index;
                    s.style.color = starValue <= currentRating ? '#ffc107' : '#ddd';
                });
            });
        });
    </script>
</body>

</html>