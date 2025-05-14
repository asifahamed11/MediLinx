<?php
require_once 'config.php';

// Default admin credentials
$default_username = 'admin';
$default_email = 'admin@medilinx.com';
$default_password = 'admin123'; // This should be changed after first login

// Connect to database
$conn = connectDB();

// Create uploads directory if it doesn't exist
$uploads_dir = "../uploads";
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Create doctors directory if it doesn't exist
$doctors_dir = "../uploads/doctors";
if (!file_exists($doctors_dir)) {
    mkdir($doctors_dir, 0777, true);
}

// Create default doctor profile image if it doesn't exist
$default_image = "../uploads/doctors/default.png";
if (!file_exists($default_image)) {
    // Create a simple default image
    $img = imagecreatetruecolor(150, 150);
    $bg_color = imagecolorallocate($img, 240, 240, 240);
    $text_color = imagecolorallocate($img, 100, 100, 100);

    // Fill the background
    imagefilledrectangle($img, 0, 0, 149, 149, $bg_color);

    // Add text
    imagestring($img, 5, 30, 70, "Doctor", $text_color);

    // Save the image
    imagepng($img, $default_image);
    imagedestroy($img);
}

// Check if admin user already exists
$check_query = "SELECT id FROM users WHERE role = 'admin'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // No admin exists, create one
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    $insert_query = "INSERT INTO users (username, email, password, role, created_at) 
                     VALUES (?, ?, ?, 'admin', NOW())";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('sss', $default_username, $default_email, $hashed_password);

    if ($stmt->execute()) {
        echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px;'>";
        echo "<h3>Admin User Created Successfully</h3>";
        echo "<p>Username: <strong>$default_username</strong></p>";
        echo "<p>Password: <strong>$default_password</strong></p>";
        echo "<p><strong>Important:</strong> Please change this password immediately after logging in.</p>";
        echo "<p><a href='admin_login.php' style='display: inline-block; margin-top: 15px; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>";
        echo "<h3>Error Creating Admin User</h3>";
        echo "<p>" . $stmt->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background-color: #cce5ff; color: #004085; border-radius: 5px;'>";
    echo "<h3>Admin User Already Exists</h3>";
    echo "<p>An admin user is already set up in the system.</p>";
    echo "<p><a href='admin_login.php' style='display: inline-block; margin-top: 15px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "</div>";
}

mysqli_close($conn);
