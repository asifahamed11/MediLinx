<?php
// posts.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = connectDB();

// Get user role
$user_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle like/unlike
if (isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $check_stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Unlike
        $delete_stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $post_id, $user_id);
        $delete_stmt->execute();
    } else {
        // Like
        $like_stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $like_stmt->bind_param("ii", $post_id, $user_id);
        $like_stmt->execute();
    }
    
    header('Location: posts.php');
    exit();
}

// Get posts with doctor info and like counts
$query = "SELECT p.*, u.username, u.profile_image, u.specialty,
          (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
          EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
          FROM posts p
          JOIN users u ON p.doctor_id = u.id
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Posts - MediLinx</title>
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
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            margin: 0;
        }

        .posts-container {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease-out;
        }

        .post-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
            cursor: pointer;
        }

        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .doctor-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin-right: 1.25rem;
            object-fit: cover;
            border: 3px solid var(--primary);
            transition: transform 0.3s ease;
        }

        .doctor-avatar:hover {
            transform: scale(1.05);
        }

        .doctor-info {
            flex-grow: 1;
        }

        .doctor-name {
            font-weight: 700;
            color: var(--secondary);
            text-decoration: none;
            font-size: 1.1rem;
            position: relative;
            display: inline-block;
        }

        .doctor-name::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .doctor-name:hover::after {
            width: 100%;
        }

        .doctor-specialty {
            font-size: 0.875rem;
            color: var(--primary);
            font-weight: 500;
            background: rgba(42, 157, 143, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }

        .post-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 1.5rem 0;
            color: var(--secondary);
            line-height: 1.3;
            position: relative;
            padding-left: 1.5rem;
        }

        .post-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 70%;
            background: var(--gradient);
            border-radius: 4px;
        }

        .post-content {
            color: var(--text);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .post-image {
            width: 100%;
            object-fit: cover;
            border-radius: 1rem;
            margin: 1.5rem 0;
            display: block;
            animation: imageLoad 0.6s ease-out;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
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
            justify-content: center;
            border-radius: 30px;
            overflow: visible;
            transition: all 0.3s ease;
        }

        .like-button:hover {
            background: rgba(42, 157, 143, 0.15);
            transform: scale(1.05);
        }

        .like-button.liked {
            color:rgb(234, 59, 59);
            background: rgba(255, 0, 0, 0.1);
        }

        .like-button.liked svg {
            animation: heartBounce 0.6s ease;
        }

        .post-date {
            font-size: 0.875rem;
            color: var(--text);
            opacity: 0.7;
            font-weight: 500;
        }

        .create-post-button {
            display: inline-flex;
            align-items: center;
            background: var(--gradient);
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            text-decoration: none;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
            border: none;
            cursor: pointer;
        }

        .create-post-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(42, 157, 143, 0.4);
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

        @keyframes heartBounce {
            0% { transform: scale(1); }
            30% { transform: scale(1.3); }
            50% { transform: scale(0.9); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
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

        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
            min-height: 200px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .post-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .post-card:nth-child(odd) {
            animation-delay: 0.2s;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="posts-container">
        <?php foreach ($posts as $post): ?>
        <div class="post-card">
            <div class="post-header">
                <img src="<?php echo htmlspecialchars($post['profile_image'] ?: 'uploads/default_profile.png'); ?>" 
                     alt="Doctor's profile" 
                     class="doctor-avatar"
                     onerror="this.src='uploads/default_profile.png'">
                <div class="doctor-info">
                    <a href="uploads/default_profile.png?id=<?php echo $post['doctor_id']; ?>" class="doctor-name">
                        <?php echo htmlspecialchars($post['username']); ?>
                    </a>
                    <div class="doctor-specialty"><?php echo htmlspecialchars($post['specialty']); ?></div>
                </div>
            </div>

            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
            <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
            
            <?php if ($post['image']): ?>
            <div class="loading-skeleton">
                <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="Post image" 
                     class="post-image"
                     onload="this.previousElementSibling.style.display='none'"
                     onerror="this.style.display='none'; this.previousElementSibling.style.display='none'">
            </div>
            <?php endif; ?>

            <div class="post-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="like-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                    <svg fill="<?php echo $post['user_liked'] ? 'currentColor' : '#000000'; ?>"  version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
     width="18px" height="18px" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve">
    <path d="M19,7c-0.6,0-1-0.4-1-1c0-2.2-1.8-4-4-4c-1.3,0-2.4,0.6-3.2,1.6c-0.4,0.5-1.2,0.5-1.6,0C8.4,2.6,7.3,2,6,2C3.8,2,2,3.8,2,6
        c0,0.6-0.4,1-1,1S0,6.6,0,6c0-3.3,2.7-6,6-6c1.5,0,2.9,0.6,4,1.5c1.1-1,2.5-1.5,4-1.5c3.3,0,6,2.7,6,6C20,6.6,19.6,7,19,7z"/>
    <path d="M9.3,19.7c-0.1-0.1-3.2-2.8-5.7-6.1c-0.3-0.4-0.3-1.1,0.2-1.4c0.4-0.3,1.1-0.3,1.4,0.2c1.8,2.3,3.8,4.3,4.8,5.3
        c1-1,3.1-3,4.9-5.3c0.3-0.4,1-0.5,1.4-0.2c0.4,0.3,0.5,1,0.2,1.4c-2.6,3.3-5.6,6-5.8,6.1C10.3,20.1,9.7,20.1,9.3,19.7z"/>
    <path d="M11,14C11,14,11,14,11,14c-0.4,0-0.7-0.2-0.9-0.6L7.9,9l-1,1.6C6.6,10.8,6.3,11,6,11H1c-0.6,0-1-0.4-1-1s0.4-1,1-1h4.5
        l1.7-2.6C7.4,6.1,7.7,6,8.1,6c0.4,0,0.7,0.2,0.8,0.6l2.2,4.5l1-1.6C12.4,9.2,12.7,9,13,9h6c0.6,0,1,0.4,1,1s-0.4,1-1,1h-5.5
        l-1.7,2.6C11.6,13.8,11.3,14,11,14z"/>
</svg>
                        <?php echo $post['like_count']; ?> likes
                    </button>
                </form>
                <span class="post-date">
                    <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($posts)): ?>
        <div class="post-card">
            <p style="text-align: center; color: var(--text); padding: 2rem;">🌟 No posts available yet. Check back later!</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>