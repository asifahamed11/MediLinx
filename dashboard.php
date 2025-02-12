<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Get user info with prepared statement
$user_stmt = $conn->prepare("SELECT role, username, profile_image, specialty FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Extract user data
$role = $user['role'];
$username = $user['username'];
$profile_image = $user['profile_image'];
$specialty = $user['specialty'];

// Get latest posts with user info and like counts
$posts_query = "SELECT p.*, u.username, u.profile_image, u.specialty, 
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
                EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
                FROM posts p 
                JOIN users u ON p.doctor_id = u.id 
                ORDER BY p.created_at DESC LIMIT 5";
                
$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->bind_param("i", $user_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();
$latest_posts = $posts_result->fetch_all(MYSQLI_ASSOC);
$posts_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 0;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            animation: fadeIn 0.6s ease-out;
        }

        .welcome-card {
            background: var(--gradient);
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(42, 157, 143, 0.2);
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 3rem;
            color: white;
            transform: translateY(0);
            transition: transform 0.3s ease;
            animation: cardEnter 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .welcome-card:hover {
            transform: translateY(-4px);
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .welcome-text h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .role-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
            display: inline-block;
        }

        .recent-posts {
            background: white;
            padding: 2rem;
            border-radius: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .posts-grid {
            display: grid;
            gap: 2rem;
            margin-top: 2rem;
        }

        .post-card {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
            animation: cardEnter 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .doctor-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .doctor-name {
            color: var(--secondary);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .doctor-name:hover {
            color: var(--primary);
        }

        .post-date {
            color: var(--text);
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .post-content {
            color: var(--text);
            line-height: 1.6;
            margin: 1rem 0;
            opacity: 0.9;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .like-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 500;
        }

        .read-more {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s ease;
        }

        .read-more:hover {
            opacity: 0.8;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        @media (max-width: 768px) {
            .welcome-card {
                flex-direction: column;
                text-align: center;
                padding: 2rem;
                gap: 1.5rem;
            }

            .profile-image {
                width: 100px;
                height: 100px;
            }

            .welcome-text h1 {
                font-size: 2rem;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="welcome-card">
            <img src="<?php echo htmlspecialchars($profile_image ?: 'uploads/default_profile.png'); ?>" 
                 alt="Profile" 
                 class="profile-image"
                 onerror="this.src='uploads/default_profile.png'">
            <div class="welcome-text">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
                <div class="role-badge">
                    <?php echo ucfirst(htmlspecialchars($role)); ?> Account
                </div>
            </div>
        </div>

        <div class="recent-posts">
            <h2 style="font-size: 1.75rem; color: var(--secondary); margin-bottom: 1rem;">Latest Medical Updates</h2>
            <div class="posts-grid">
                <?php if (!empty($latest_posts)): ?>
                    <?php foreach ($latest_posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="doctor-info">
                                    <img src="<?php echo htmlspecialchars($post['profile_image']) ?: 'uploads/default_profile.png'; ?>" 
                                         class="doctor-avatar"
                                         alt="Doctor avatar"
                                         onerror="this.src='uploads/default_profile.png'">
                                    <div>
                                        <a href="profile.php?id=<?php echo htmlspecialchars($post['doctor_id']); ?>" class="doctor-name">
                                            Dr. <?php echo htmlspecialchars($post['username']); ?>
                                        </a>
                                        <div class="doctor-specialty">
                                            <?php echo htmlspecialchars($post['specialty']); ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="post-date">
                                    <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-content">
                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>...
                            </p>
                            <div class="post-actions">
                                <div class="like-count <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                    <svg width="18" height="18" viewBox="0 0 48 48" fill="none">
                                        <!-- Updated handshake SVG path here -->
                                        <path d="M14.5397 20.0186C12.8522 17.9434 11.2675 17.4979 9.78564 18.6821C7.5629 20.4583 6.92453 26.6496 8.71324 32.1086C10.502 37.5676 13.9801 45.0017 21.0016 45.0017C28.0231 45.0017 29.684 37.5222 32.5485 33.0001C35.413 28.478 36.9285 24.1152 34.1208 18.6821" 
                                              stroke="currentColor" 
                                              stroke-width="4"/>
                                    </svg>
                                    <?php echo $post['like_count']; ?> Likes
                                </div>
                                <a href="posts.php?id=<?php echo htmlspecialchars($post['id']); ?>" class="read-more">
                                    Read Article
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="post-card empty-state">
                        <p>No recent posts available. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>