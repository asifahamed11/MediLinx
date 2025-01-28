<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediLinx - Healthcare Portal</title>
    <style>
        :root {
            --primary-color: #1877f2;
            --secondary-color: #42b72a;
            --bg-color: #f0f2f5;
            --text-color: #1c1e21;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 32px;
        }

        .subtitle {
            color: #65676b;
            margin-bottom: 30px;
            font-size: 17px;
        }

        .role-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .btn-patient {
            background: var(--primary-color);
        }

        .btn-doctor {
            background: var(--secondary-color);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .login-link {
            margin-top: 30px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to MediLinx</h1>
        <p class="subtitle">Connect with healthcare professionals and manage your medical journey</p>
        <div class="role-buttons">
            <a href="patient_register.php" class="btn btn-patient">Join as Patient</a>
            <a href="doctor_register.php" class="btn btn-doctor">Join as Doctor</a>
        </div>
        <p style="margin-top: 20px;">
            <a href="login.php" class="login-link">Already have an account?</a>
        </p>
    </div>
</body>
</html>