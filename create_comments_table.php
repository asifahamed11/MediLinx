<?php
session_start();
require_once 'config.php';

// Only allow access to administrators
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Access denied. This script can only be run by administrators.";
    exit;
}

// Connect to database
$conn = connectDB();

// Check if the post_comments table exists
$result = $conn->query("SHOW TABLES LIKE 'post_comments'");
if ($result->num_rows === 0) {
    // Create the post_comments table if it doesn't exist
    $sql = "CREATE TABLE post_comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'post_comments' created successfully.<br>";
    } else {
        echo "Error creating 'post_comments' table: " . $conn->error . "<br>";
    }
} else {
    echo "Table 'post_comments' already exists.<br>";
}

// Check if the post_likes table exists
$result = $conn->query("SHOW TABLES LIKE 'post_likes'");
if ($result->num_rows === 0) {
    // Create the post_likes table if it doesn't exist
    $sql = "CREATE TABLE post_likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY post_user_unique (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'post_likes' created successfully.<br>";
    } else {
        echo "Error creating 'post_likes' table: " . $conn->error . "<br>";
    }
} else {
    echo "Table 'post_likes' already exists.<br>";
}

echo "<p>Reminder: Delete this file after use as it presents a security risk if left on the server.</p>";

$conn->close();
