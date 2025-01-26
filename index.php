<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }

        .role-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        .btn:first-child {
            background: #3498db;
        }

        .btn:last-child {
            background: #2ecc71;
        }

        .btn:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to the Registration Portal</h1>
        <p>Please select your role to proceed with registration:</p>
        <div class="role-buttons">
            <a href="patient_register.php" class="btn">Patient</a>
            <a href="doctor_register.php" class="btn">Doctor</a>
        </div>
    </div>
</body>
</html>