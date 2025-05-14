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
$success_message = '';
$error_message = '';

// Get post data
$query = "SELECT p.*, u.username as doctor_name, u.id as doctor_id 
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

// Get all doctors for possible reassignment
$doctors_query = "SELECT id, username FROM users WHERE role = 'doctor' ORDER BY username";
$doctors_result = mysqli_query($conn, $doctors_query);
$doctors = mysqli_fetch_all($doctors_result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['title']) || empty($_POST['content'])) {
            throw new Exception("Title and content are required");
        }

        // Start transaction
        $conn->begin_transaction();

        $current_image = $post['image'];
        $update_image = false;

        // Handle image upload if provided
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === 0) {
            $target_dir = "../uploads/posts/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
            $file_name = "post_" . $post_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $file_name;

            // Move uploaded file
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                // Update image path in database
                $current_image = "uploads/posts/" . $file_name;
                $update_image = true;
            } else {
                throw new Exception("Failed to upload image");
            }
        } else if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
            // Remove image
            $current_image = '';
            $update_image = true;
        }

        // Update post in database
        $update_query = "UPDATE posts SET 
                         title = ?, 
                         content = ?, 
                         doctor_id = ?,
                         updated_at = NOW()";

        // Add image to update if changed
        if ($update_image) {
            $update_query .= ", image = ?";
        }

        $update_query .= " WHERE id = ?";

        $update_stmt = $conn->prepare($update_query);

        if ($update_image) {
            $update_stmt->bind_param(
                'ssisi',
                $_POST['title'],
                $_POST['content'],
                $_POST['doctor_id'],
                $current_image,
                $post_id
            );
        } else {
            $update_stmt->bind_param(
                'ssi',
                $_POST['title'],
                $_POST['content'],
                $_POST['doctor_id'],
                $post_id
            );
        }

        $update_stmt->execute();

        // Commit transaction
        $conn->commit();
        $success_message = "Post updated successfully!";

        // Refresh post data
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Medilinx Admin</title>
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

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
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

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            height: 200px;
            resize: vertical;
        }

        .current-image {
            margin: 10px 0;
        }

        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .checkbox-group {
            margin: 10px 0;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #7f8c8d;
            color: white;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=posts" class="back-link">&larr; Back to Posts</a>
        <h1>Edit Post</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="doctor_id">Doctor</label>
                <select id="doctor_id" name="doctor_id" required>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>" <?php if ($doctor['id'] == $post['doctor_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($doctor['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Post Image</label>
                <?php if (!empty($post['image'])): ?>
                    <div class="current-image">
                        <p>Current image:</p>
                        <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="Current post image">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label for="remove_image" style="display: inline;">Remove current image</label>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No image attached to this post.</p>
                <?php endif; ?>
                <p style="margin-top: 10px;">Upload new image (optional):</p>
                <input type="file" name="post_image" accept="image/*">
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="view_post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>