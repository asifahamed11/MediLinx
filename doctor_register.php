<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Registration</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="container">
        <h2>Doctor Registration</h2>
        <form action="register.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="role" value="doctor">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" required>

            <label for="date_of_birth">Date of Birth (optional):</label>
            <input type="date" id="date_of_birth" name="date_of_birth">

            <label for="gender">Gender:</label>
            <select id="gender" name="gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label for="specialty">Specialty/Field of Expertise:</label>
            <input type="text" id="specialty" name="specialty" required>

            <label for="degrees_and_certifications">Degrees and Certifications:</label>
            <textarea id="degrees_and_certifications" name="degrees_and_certifications" rows="4" required></textarea>

            <label for="years_of_experience">Years of Experience:</label>
            <input type="number" id="years_of_experience" name="years_of_experience" min="0" required>

            <label for="medical_license_number">Medical License Number:</label>
            <input type="text" id="medical_license_number" name="medical_license_number" required>

            <label for="work_address">Work Address/Clinic Location:</label>
            <input type="text" id="work_address" name="work_address" required>

            <label for="available_consultation_hours">Available Consultation Hours:</label>
            <textarea id="available_consultation_hours" name="available_consultation_hours" rows="4" required></textarea>

            <label for="languages_spoken">Languages Spoken (optional):</label>
            <input type="text" id="languages_spoken" name="languages_spoken">

            <label for="profile_image">Profile Picture (optional):</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">

            <label for="professional_biography">Professional Biography (optional):</label>
            <textarea id="professional_biography" name="professional_biography" rows="4"></textarea>

            <button type="submit" class="btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.html">Login here</a>.</p>
    </div>
</body>
</html>