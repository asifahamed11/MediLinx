<?php
// doctor_register.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Registration - MediLinx</title>
    <style>
    :root {
        --primary-color: #00517C;    /* Deep medical blue */
        --secondary-color: #4CAF50;  /* Soft green */
        --bg-color: #F5F9FF;        /* Light blue-tinted background */
        --text-color: #2C3E50;      /* Dark blue-gray text */
        --accent-color: #E3F2FD;    /* Light blue accent */
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    body {
        background: var(--bg-color);
        padding: 40px 20px;
        background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .container {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0, 81, 124, 0.1);
        max-width: 800px;
        margin: 0 auto;
    }

    h2 {
        color: var(--primary-color);
        margin-bottom: 30px;
        text-align: center;
        font-size: 32px;
        position: relative;
        padding-bottom: 15px;
    }

    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--secondary-color);
        border-radius: 2px;
    }

    .section-title {
        color: var(--primary-color);
        margin: 25px 0 15px;
        font-size: 20px;
        padding-left: 15px;
        border-left: 4px solid var(--secondary-color);
    }

    input, select, textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
        background-color: #FAFBFC;
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 81, 124, 0.1);
        background-color: white;
    }

    .btn {
        background: linear-gradient(135deg, var(--primary-color), #0077B6);
        color: white;
        padding: 14px;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0, 81, 124, 0.2);
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 81, 124, 0.3);
    }

    .login-link {
        margin-top: 25px;
        text-align: center;
    }

    .login-link a {
        color: var(--primary-color);
        font-weight: 600;
        text-decoration: none;
        transition: color 0.3s;
    }

    .form-group label {
        color: var(--text-color);
        font-weight: 500;
        margin-bottom: 8px;
        display: block;
    }

    /* Add medical icon decorations */
    .container::before {
        content: '⚕';
        position: absolute;
        top: -30px;
        left: 20px;
        font-size: 60px;
        color: var(--primary-color);
        opacity: 0.1;
    }
</style>

</head>
<body>
    <div class="container">
        <h2>Join as a Healthcare Professional</h2>
        <form action="registration.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="role" value="doctor">
            
            <!-- Basic Information -->
            <h3 class="section-title">Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>

            <!-- Professional Information -->
            <h3 class="section-title">Professional Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="specialty">Medical Specialty</label>
                    <input type="text" id="specialty" name="specialty" required>
                </div>
                <div class="form-group">
                    <label for="medical_license">Medical License Number</label>
                    <input type="text" id="medical_license" name="medical_license" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="work_address">Clinic/Hospital Address</label>
                    <input type="text" id="work_address" name="work_address" required>
                </div>
                <div class="form-group">
                    <label for="experience">Years of Experience</label>
                    <input type="number" id="experience" name="experience" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label for="consultation_hours">Consultation Hours</label>
                <textarea id="consultation_hours" name="consultation_hours" rows="2" 
                          placeholder="e.g., Mon-Fri: 9AM-5PM" required></textarea>
            </div>

            <!-- Contact Information -->
            <h3 class="section-title">Contact & Security</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="profile_image">Profile Picture</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn">Create Professional Account</button>
        </form>
        <div class="login-link">
            <a href="login.php">Already have an account? Log in</a>
        </div>
    </div>
</body>
</html>