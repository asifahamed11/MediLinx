<?php
// patient_register.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration - MediLinx</title>
    <style>
        :root {
            --primary-color: #1877f2;
            --secondary-color: #42b72a;
            --bg-color: #f0f2f5;
            --text-color: #1c1e21;
            --error-color: #ed4956;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: var(--bg-color);
            padding: 40px 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 20px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .optional {
            color: #65676b;
            font-size: 14px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Join as a Patient</h2>
        <form action="registration.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="role" value="patient">
            
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
                    <label for="phone">Phone Number <span class="optional">(Optional)</span></label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="medical_history">Medical History <span class="optional">(Optional)</span></label>
                <textarea id="medical_history" name="medical_history" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label for="profile_image">Profile Picture <span class="optional">(Optional)</span></label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
            </div>

            <button type="submit" class="btn">Create Account</button>
        </form>
        <div class="login-link">
            <a href="login.php">Already have an account? Log in</a>
        </div>
    </div>
</body>
</html>