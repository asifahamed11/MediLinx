<?php
// login.php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #E76F51;
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('https://img.freepik.com/free-vector/clean-medical-background_53876-97927.jpg?t=st=1738517715~exp=1738521315~hmac=368b8cb5c29b6bea18135d8045cb4eb8ab09652f55132e9b0bb942de9c98ab33&w=900');
            opacity: 0.23;
            background-repeat: no-repeat;
            z-index: 0;
        }

        .container {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 500px;
            transform: translateY(20px);
            opacity: 0;
            animation: formEntrance 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }

        h2 {
            font-family: 'Lato', sans-serif;
            color: var(--secondary);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            text-align: center;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease-in-out;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            transition: all 0.3s ease;
            pointer-events: none;
            background: var(--glass);
            padding: 0 0.5rem;
        }

        input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus,
        input:valid {
            border-color: var(--primary);
            outline: none;
        }

        input:focus ~ label,
        input:valid ~ label {
            top: 0;
            transform: translateY(-50%) scale(0.9);
            color: var(--primary);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, #21867a 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
        }

        .register-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--accent);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .register-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        @keyframes formEntrance {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        @media (max-width: 480px) {
            .container {
                padding: 2rem;
                width: 95%;
            }
            
            h2 {
                font-size: 2rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome Back</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form action="process_login.php" method="post">
            <div class="form-group">
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <div class="register-link">
            <a href="index.php">Need an account? Sign up</a>
        </div>
    </div>
</body>
</html>