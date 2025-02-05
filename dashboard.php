<?php
session_start();
require_once 'config.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role, username, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role, $username, $profile_image);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body {
            background: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #1877f2;
            margin-bottom: 20px;
        }

        img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 10px;
        }

        a {
            color: #1877f2;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>
        <img src="<?php 
            $image_path = htmlspecialchars($profile_image);
            echo !empty($image_path) ? $image_path : 'uploads/default_profile.png'; 
        ?>" alt="Profile Image" onerror="this.src='uploads/default_profile.png'">
        <p>Role: <?php echo htmlspecialchars($role); ?></p>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>