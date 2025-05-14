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

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: admin.php?tab=posts');
    exit;
}

$post_id = intval($_GET['id']);

// Get post data
$query = "SELECT p.*, u.username as doctor_name, u.specialty, u.id as doctor_id 
          FROM posts p
          JOIN users u ON p.doctor_id = u.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=posts');
    exit;
}

$post = $result->fetch_assoc();

// Get number of likes for the post
$likes_query = "SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?";
$likes_stmt = $conn->prepare($likes_query);
$likes_stmt->bind_param('i', $post_id);
$likes_stmt->execute();
$likes_result = $likes_stmt->get_result();
$likes_data = $likes_result->fetch_assoc();
$like_count = $likes_data ? $likes_data['like_count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post - Medilinx Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h3 {
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .info-row {
            margin-bottom: 15px;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            min-width: 200px;
        }

        .info-value {
            flex-grow: 1;
        }

        .post-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            line-height: 1.6;
            margin-top: 15px;
        }

        .post-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 15px;
            display: block;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: #3498db;
        }

        .btn-secondary {
            background-color: #7f8c8d;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .delete-form {
            display: inline-block;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .like-info {
            display: inline-flex;
            align-items: center;
            margin-right: 20px;
            color: #e74c3c;
        }

        .like-info i {
            margin-right: 5px;
            color: #e74c3c;
        }

        .like-count {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=posts" class="back-link">&larr; Back to Posts</a>

        <h1><?php echo htmlspecialchars($post['title']); ?></h1>

        <div class="section">
            <h3>Post Information</h3>

            <div class="info-row">
                <div class="info-label">Created By:</div>
                <div class="info-value">Dr. <?php echo htmlspecialchars($post['doctor_name']); ?> (<?php echo htmlspecialchars($post['specialty'] ?? 'No specialty'); ?>)</div>
            </div>

            <div class="info-row">
                <div class="info-label">Date Published:</div>
                <div class="info-value"><?php echo date('F j, Y h:i A', strtotime($post['created_at'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Last Updated:</div>
                <div class="info-value"><?php echo date('F j, Y h:i A', strtotime($post['updated_at'])); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Engagement:</div>
                <div class="info-value">
                    <span class="like-info">
                        <i class="fa fa-heart"></i> <span class="like-count"><?php echo $like_count; ?></span> likes
                    </span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Post Content</h3>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <?php if (!empty($post['image'])): ?>
                <h3 style="margin-top: 20px;">Attached Image</h3>
                <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Doctor Information</h3>
            <div class="info-row">
                <div class="info-label">Doctor Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($post['doctor_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Specialty:</div>
                <div class="info-value"><?php echo htmlspecialchars($post['specialty'] ?? 'Not specified'); ?></div>
            </div>

            <a href="view_doctor.php?id=<?php echo $post['doctor_id']; ?>" class="btn btn-primary">View Doctor Profile</a>
        </div>

        <div class="actions">
            <a href="admin.php?tab=posts" class="btn btn-secondary">Back to Posts</a>

            <a href="edit_post.php?id=<?php echo $post_id; ?>" class="btn btn-primary">Edit Post</a>

            <form method="POST" action="admin.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this post?');">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <button type="submit" name="delete_post" class="btn btn-danger">Delete Post</button>
            </form>
        </div>
    </div>
</body>

</html>