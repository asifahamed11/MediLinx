<?php
session_start();
require_once 'config.php';

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
    <title>Dr. <?= htmlspecialchars($doctor['username']) ?> - MediLinx</title>
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
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .doctor-profile {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 3rem;
            align-items: start;
        }

        .profile-sidebar {
            position: sticky;
            top: 2rem;
            text-align: center;
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
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
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
                position: static;
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
            0% { transform: scale(1); }
            30% { transform: scale(1.3); }
            50% { transform: scale(0.9); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
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

        .institution, .specialization {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            margin-top: 0.5rem;
        }

        .institution i, .specialization i {
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
                         alt="Dr. <?= htmlspecialchars($doctor['username']) ?>"
                         onerror="this.src='uploads/default_profile.png'">
                    <div class="rating-badge">
                        <i class="fas fa-star"></i>
                        <?= number_format($avg_rating, 1) ?>
                    </div>
                </div>

                <div class="profile-info">
                    <h1>Dr. <?= htmlspecialchars($doctor['username']) ?></h1>
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
                <div class="availability-section">
    <h3>Available Appointments</h3>
    <?php
    $slotStmt = $conn->prepare("SELECT * FROM time_slots 
        WHERE doctor_id = ? 
        AND status = 'available'
        AND start_time > NOW()
        ORDER BY start_time");
    $slotStmt->bind_param("i", $doctor_id);
    $slotStmt->execute();
    $slots = $slotStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($slots)): ?>
        <div class="time-slots">
            <?php foreach ($slots as $slot): ?>
                <div class="time-slot-card">
                    <div class="slot-time">
                        <?= date('M j, Y g:i A', strtotime($slot['start_time'])) ?>
                        - <?= date('g:i A', strtotime($slot['end_time'])) ?>
                    </div>
                    <div class="slot-location">
                        <?= htmlspecialchars($slot['location']) ?>
                    </div>
                    <form method="POST" action="book_appointment.php">
                        <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                        <button type="submit" class="book-btn">Book Now</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-slots">No available time slots at the moment</p>
    <?php endif; ?>
</div>
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
                                <svg fill="<?php echo $post['user_liked'] ? 'currentColor' : '#000000'; ?>"  version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
     width="16px" height="16px" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve">
    <path d="M19,7c-0.6,0-1-0.4-1-1c0-2.2-1.8-4-4-4c-1.3,0-2.4,0.6-3.2,1.6c-0.4,0.5-1.2,0.5-1.6,0C8.4,2.6,7.3,2,6,2C3.8,2,2,3.8,2,6
        c0,0.6-0.4,1-1,1S0,6.6,0,6c0-3.3,2.7-6,6-6c1.5,0,2.9,0.6,4,1.5c1.1-1,2.5-1.5,4-1.5c3.3,0,6,2.7,6,6C20,6.6,19.6,7,19,7z"/>
    <path d="M9.3,19.7c-0.1-0.1-3.2-2.8-5.7-6.1c-0.3-0.4-0.3-1.1,0.2-1.4c0.4-0.3,1.1-0.3,1.4,0.2c1.8,2.3,3.8,4.3,4.8,5.3
        c1-1,3.1-3,4.9-5.3c0.3-0.4,1-0.5,1.4-0.2c0.4,0.3,0.5,1,0.2,1.4c-2.6,3.3-5.6,6-5.8,6.1C10.3,20.1,9.7,20.1,9.3,19.7z"/>
    <path d="M11,14C11,14,11,14,11,14c-0.4,0-0.7-0.2-0.9-0.6L7.9,9l-1,1.6C6.6,10.8,6.3,11,6,11H1c-0.6,0-1-0.4-1-1s0.4-1,1-1h4.5
        l1.7-2.6C7.4,6.1,7.7,6,8.1,6c0.4,0,0.7,0.2,0.8,0.6l2.2,4.5l1-1.6C12.4,9.2,12.7,9,13,9h6c0.6,0,1,0.4,1,1s-0.4,1-1,1h-5.5
        l-1.7,2.6C11.6,13.8,11.3,14,11,14z"/>
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
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-alt' ?>"></i>
                                <?php endfor; ?>
                                <span>(<?= number_format($review['rating'], 1) ?>)</span>
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