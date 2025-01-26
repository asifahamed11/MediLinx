<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="container">
        <h2>Patient Registration</h2>
        <form action="register.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="role" value="patient">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="phone">Phone Number (optional):</label>
            <input type="text" id="phone" name="phone">

            <label for="date_of_birth">Date of Birth:</label>
            <input type="date" id="date_of_birth" name="date_of_birth">

            <label for="gender">Gender:</label>
            <select id="gender" name="gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label for="address">Address (optional):</label>
            <input type="text" id="address" name="address">

            <label for="medical_history">Medical History/Conditions (optional):</label>
            <textarea id="medical_history" name="medical_history" rows="4"></textarea>

            <label for="profile_image">Profile Picture (optional):</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">

            <button type="submit" class="btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.html">Login here</a>.</p>
    </div>
</body>
</html>