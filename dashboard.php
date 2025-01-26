<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Database configuration
$servername = "localhost";
$username_db = "root"; // Replace with your MySQL username
$password_db = "";     // Replace with your MySQL password
$dbname = "user_authentication";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role, username, profile_image, email_verified_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role, $username, $profile_image, $email_verified_at);
$stmt->fetch();
$stmt->close();
$conn->close();

// Check if email is verified
if ($email_verified_at === NULL) {
    header("Location: pin_verification.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>
        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" width="150">
        <p>Role: <?php echo htmlspecialchars($role); ?></p>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>