<?php
session_start();
require_once 'config.php';

// Redirect if not logged in or not a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$doctor_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $conn = connectDB();

    // Validate input
    if (empty($title) || empty($content)) {
        $error = "Title and content are required";
    } else {
        $image_path = '';

        // Handle image upload if provided
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/posts/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_temp = $_FILES['post_image']['tmp_name'];
            $file_name = time() . '_' . $_FILES['post_image']['name'];
            $file_path = $upload_dir . $file_name;

            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file_temp);

            if (!in_array($file_type, $allowed_types)) {
                $error = "Only image files (JPG, PNG, GIF, WEBP) are allowed";
            } elseif ($_FILES['post_image']['size'] > 5000000) { // 5MB limit
                $error = "File size must be less than 5MB";
            } elseif (move_uploaded_file($file_temp, $file_path)) {
                $image_path = $file_path;
            } else {
                $error = "Error uploading file";
            }
        }

        if (empty($error)) {
            // Insert post into database
            $stmt = $conn->prepare("INSERT INTO posts (doctor_id, title, content, image, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $doctor_id, $title, $content, $image_path);

            if ($stmt->execute()) {
                $success = "Post created successfully!";
                // Clear form data after successful submission
                $title = '';
                $content = '';
            } else {
                $error = "Error creating post: " . $conn->error;
            }
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - MediLinx</title>
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
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem 1.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            margin-bottom: 2rem;
            animation: slideUp 0.5s ease-out;
        }

        .card-header {
            margin-bottom: 2rem;
        }

        .card-title {
            color: var(--secondary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .card-subtitle {
            color: #6c757d;
            font-size: 1rem;
            margin-top: 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.15);
            outline: none;
        }

        textarea {
            min-height: 200px;
            resize: vertical;
        }

        .file-upload {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(42, 157, 143, 0.03);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-icon {
            margin-right: 1rem;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .preview-container {
            margin: 1rem 0;
            display: none;
        }

        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 157, 143, 0.3);
        }

        .btn-secondary {
            background: #f1f3f5;
            color: #495057;
            border: none;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            color: #236c3a;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            color: #bd2130;
        }

        .footer-note {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Create New Medical Post</h1>
                <p class="card-subtitle">Share your medical insights and knowledge with patients</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="content">Post Content</label>
                    <textarea id="content" name="content" required><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Featured Image (Optional)</label>
                    <label for="post_image" class="file-upload">
                        <span class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </span>
                        <div>
                            <strong>Click to upload an image</strong>
                            <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #6c757d;">
                                Supported formats: JPG, PNG, GIF, WEBP (Max size: 5MB)
                            </p>
                        </div>
                        <input type="file" id="post_image" name="post_image" accept="image/*">
                    </label>

                    <div id="previewContainer" class="preview-container">
                        <img id="previewImage" class="preview-image" src="#" alt="Preview">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="submit_post" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Publish Post
                    </button>
                </div>
            </form>
        </div>

        <div class="footer-note">
            <p>Your posts will be visible on your doctor profile and may help patients better understand medical concepts.</p>
        </div>
    </div>

    <script>
        // Image preview functionality
        const postImage = document.getElementById('post_image');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');

        postImage.addEventListener('change', function() {
            const file = this.files[0];

            if (file) {
                const reader = new FileReader();

                reader.addEventListener('load', function() {
                    previewImage.setAttribute('src', this.result);
                    previewContainer.style.display = 'block';
                });

                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });
    </script>
</body>

</html>