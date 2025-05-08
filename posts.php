<?php
// posts.php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$conn = connectDB();

// Fetch all doctors for the filter
$doctors_stmt = $conn->prepare("SELECT id, username, specialty FROM users WHERE role = 'doctor' ORDER BY username");
$doctors_stmt->execute();
$doctors = $doctors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build the query based on filters
$query = "SELECT p.*, u.username, u.profile_image, u.specialty, 
         (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
         EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked 
         FROM posts p 
         JOIN users u ON p.doctor_id = u.id 
         WHERE 1=1";
$params = [$_SESSION['user_id']];
$types = "i";

if ($doctor_id > 0) {
    $query .= " AND p.doctor_id = ?";
    $params[] = $doctor_id;
    $types .= "i";
}

if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $query .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Helper function to format date
function formatDate($date)
{
    $timestamp = strtotime($date);
    return date('M j, Y', $timestamp);
}

// Helper function to truncate text
function truncateText($text, $length = 150)
{
    if (strlen($text) <= $length) return $text;
    $truncated = substr($text, 0, strpos($text, ' ', $length));
    return $truncated ? $truncated . '...' : substr($text, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Posts - MediLinx</title>
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            color: var(--secondary);
            font-size: 2rem;
            margin: 0;
        }

        .filter-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            min-width: 180px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        .search-form {
            display: flex;
            position: relative;
        }

        .search-input {
            padding: 0.75rem 1rem;
            padding-right: 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            min-width: 250px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        .search-button {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            background: none;
            border: none;
            padding: 0 1rem;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .search-button:hover {
            color: var(--primary);
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .post-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .post-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .post-title {
            color: var(--secondary);
            font-size: 1.25rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }

        .post-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .post-author-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid var(--primary);
        }

        .post-author-info {
            font-size: 0.9rem;
        }

        .author-name {
            color: var(--secondary);
            font-weight: 600;
            display: block;
        }

        .author-specialty {
            color: #718096;
            font-size: 0.8rem;
        }

        .post-excerpt {
            color: #4a5568;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            flex: 1;
        }

        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .post-date {
            color: #718096;
            font-size: 0.85rem;
        }

        .post-likes {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
            font-size: 0.9rem;
        }

        .like-icon {
            color: #cbd5e0;
            transition: all 0.3s ease;
        }

        .like-icon.liked {
            color: var(--accent);
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

        .read-more {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s ease;
        }

        .read-more:hover {
            color: var(--secondary);
            gap: 0.5rem;
        }

        .no-posts {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .no-posts-icon {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .no-posts-text {
            font-size: 1.2rem;
            color: #718096;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-container {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Medical Posts</h1>

            <div class="filter-container">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Search posts..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <?php if ($doctor_id): ?>
                        <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <form action="" method="get" id="doctorFilterForm">
                    <select name="doctor_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Doctors</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_id == $doctor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['username']); ?> - <?php echo htmlspecialchars($doctor['specialty']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($search): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </form>

                <?php if ($doctor_id || $search): ?>
                    <a href="posts.php" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'doctor'): ?>
                    <a href="create_post.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Post
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($posts)): ?>
            <div class="no-posts">
                <div class="no-posts-icon">
                    <i class="fas fa-file-medical-alt"></i>
                </div>
                <h2 class="no-posts-text">No posts found</h2>
                <p>Try adjusting your search or filters to find what you're looking for.</p>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                        <?php endif; ?>

                        <div class="post-content">
                            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>

                            <div class="post-meta">
                                <img src="<?php echo !empty($post['profile_image']) ? htmlspecialchars($post['profile_image']) : 'uploads/default_profile.png'; ?>"
                                    alt="<?php echo htmlspecialchars($post['username']); ?>"
                                    class="post-author-image"
                                    onerror="this.src='uploads/default_profile.png'">

                                <div class="post-author-info">
                                    <span class="author-name">Dr. <?php echo htmlspecialchars($post['username']); ?></span>
                                    <span class="author-specialty"><?php echo htmlspecialchars($post['specialty']); ?></span>
                                </div>
                            </div>

                            <div class="post-excerpt">
                                <?php echo nl2br(htmlspecialchars(truncateText($post['content'], 200))); ?>
                            </div>

                            <div class="post-footer">
                                <span class="post-date"><?php echo formatDate($post['created_at']); ?></span>

                                <div class="post-likes">
                                    <i class="fas fa-heart like-icon <?php echo $post['user_liked'] ? 'liked' : ''; ?>"></i>
                                    <?php echo $post['like_count']; ?> likes
                                </div>
                            </div>

                            <a href="view_post.php?id=<?php echo $post['id']; ?>" class="read-more">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit the doctor filter form when selection changes
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('doctorFilterForm');
            if (filterForm) {
                const select = filterForm.querySelector('select');
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
        });
    </script>
</body>

</html>