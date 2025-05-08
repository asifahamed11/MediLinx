<?php
// view_post.php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: posts.php');
    exit();
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = connectDB();

// Get post details
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_image, u.specialty, 
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
    EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked 
    FROM posts p 
    JOIN users u ON p.doctor_id = u.id 
    WHERE p.id = ?
");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if post exists
if ($result->num_rows === 0) {
    header('Location: posts.php');
    exit();
}

$post = $result->fetch_assoc();

// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        if ($post['user_liked']) {
            // Unlike the post
            $like_stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $like_stmt->bind_param("ii", $post_id, $user_id);
            $like_stmt->execute();
        } else {
            // Like the post
            $like_stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $like_stmt->bind_param("ii", $post_id, $user_id);
            $like_stmt->execute();
        }

        // Redirect to avoid form resubmission
        header("Location: view_post.php?id=$post_id");
        exit();
    }
}

// Format date helper function
function formatDate($date)
{
    $timestamp = strtotime($date);
    return date('F j, Y \a\t g:i a', $timestamp);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - MediLinx</title>
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
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .post-container {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .post-header {
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .post-title {
            color: var(--secondary);
            font-size: 2rem;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .post-author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .post-author-info {
            flex: 1;
        }

        .author-name {
            color: var(--secondary);
            font-weight: 600;
            font-size: 1.1rem;
            display: block;
        }

        .author-specialty {
            color: #718096;
            font-size: 0.9rem;
        }

        .post-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
        }

        .post-content {
            padding: 2rem;
            font-size: 1.1rem;
            white-space: pre-line;
        }

        .post-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .like-button {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .like-button:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .like-icon {
            color: #cbd5e0;
            transition: all 0.3s ease;
        }

        .like-icon.liked {
            color: #e53e3e;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--secondary);
            gap: 0.75rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .post-title {
                font-size: 1.5rem;
            }

            .post-content {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <a href="posts.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Posts
        </a>

        <div class="post-container">
            <div class="post-header">
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>

                <div class="post-meta">
                    <img src="<?php echo !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'uploads/default_profile.png'; ?>"
                        alt="<?php echo htmlspecialchars($post['username']); ?>"
                        class="post-author-image"
                        onerror="this.src='uploads/default_profile.png'">

                    <div class="post-author-info">
                        <span class="author-name">Dr. <?php echo htmlspecialchars($post['username']); ?></span>
                        <span class="author-specialty"><?php echo htmlspecialchars($post['specialty']); ?></span>
                    </div>

                    <span class="post-date"><?php echo formatDate($post['created_at']); ?></span>
                </div>
            </div>

            <?php if (!empty($post['image'])): ?>
                <img src="<?php echo htmlspecialchars($post['image']); ?>"
                    alt="<?php echo htmlspecialchars($post['title']); ?>"
                    class="post-image">
            <?php endif; ?>

            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <div class="post-footer">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="like">
                    <button type="submit" class="like-button">
                        <i class="fas fa-heart like-icon <?php echo $post['user_liked'] ? 'liked' : ''; ?>"></i>
                        <?php echo $post['like_count']; ?> <?php echo $post['like_count'] === 1 ? 'like' : 'likes'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>