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
            background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%232A9D8F" d="M49.8,-35.3C62.5,-21.3,68.7,2.7,61.7,20.2C54.7,37.7,34.5,48.6,13.6,54.3C-7.4,60,-28.9,60.4,-42.1,50.3C-55.3,40.1,-60.1,19.4,-58.4,0.8C-56.7,-17.8,-48.5,-35.5,-36.2,-49.7C-23.9,-63.9,-7.5,-74.5,10.1,-79.8C27.7,-85.1,55.4,-85.1,68.6,-72.4C81.8,-59.7,80.5,-34.3,77.3,-12.9C74.1,8.5,69,25.9,59.1,38.8C49.2,51.7,34.5,60.1,19.3,65.4C4.1,70.7,-11.6,72.9,-25.6,68.8C-39.6,64.7,-51.9,54.4,-61.5,41.3C-71.1,28.3,-78,12.6,-76.4,-1.8C-74.8,-16.3,-64.7,-32.6,-52.3,-46.1C-39.8,-59.6,-25,-70.3,-6.4,-67.3C12.2,-64.3,24.4,-47.6,37.1,-34.8C49.8,-22,63,-13,66.2,1.7C69.4,16.4,62.6,32.8,52.4,45.1C42.2,57.4,28.6,65.6,14.5,70.3C0.4,75,-14.2,76.2,-24.1,70.2C-34,64.2,-39.2,51,-45.7,38.2C-52.2,25.3,-60,12.7,-61.4,-1.4C-62.8,-15.5,-57.8,-30.9,-48.3,-42.2C-38.8,-53.5,-24.8,-60.6,-9.7,-56.9C5.5,-53.2,11,-38.7,20.9,-28.5C30.8,-18.3,45,-12.5,55.2,3.7C65.4,19.9,71.5,42.8,66.7,56.3C61.9,69.8,46.2,73.9,31.8,71.1C17.5,68.4,4.5,58.7,-7.6,50.2C-19.7,41.6,-30.4,34.1,-40.6,25.9C-50.8,17.7,-60.5,8.8,-61.9,-1.4C-63.4,-11.6,-56.7,-23.3,-48.5,-35.1C-40.3,-47,-30.6,-59.1,-17.6,-65.7C-4.7,-72.3,11.6,-73.4,24.3,-67.1C37,-60.8,46.1,-47.1,55.7,-34.4C65.3,-21.6,75.3,-9.8,77.8,3.4C80.3,16.6,75.2,33.2,65.6,44.4C56,55.6,41.9,61.4,28.7,64.9C15.5,68.3,3.1,69.5,-8.5,65.8C-20,62.1,-40.1,53.5,-50.1,40.4C-60.2,27.3,-60.4,9.7,-57.9,-7.8C-55.4,-25.3,-50.3,-42.5,-40.3,-53.9C-30.3,-65.3,-15.2,-70.8,0.6,-71.8C16.3,-72.8,32.6,-69.2,44.2,-59.1C55.8,-49,62.7,-32.4,66.5,-16.2C70.3,0,71,15.8,66.7,28.9C62.4,42,53.1,52.4,41.7,59.2C30.3,66,16.7,69.2,3.5,66.7C-9.7,64.3,-19.3,56.2,-30.3,48.7C-41.3,41.2,-53.7,34.3,-61.3,23.7C-68.9,13.1,-71.7,-1.3,-67.7,-13.2C-63.7,-25.1,-52.9,-34.6,-42.1,-44.5C-31.3,-54.4,-20.6,-64.8,-7.2,-63.3C6.2,-61.7,12.4,-48.3,21.9,-38.1C31.4,-27.9,44.2,-20.9,55.2,-11.5C66.2,-2.1,75.4,9.7,77.6,23.8C79.8,37.9,75,54.3,64.8,63.6C54.6,72.9,38.9,75.1,25.2,74.5C11.5,73.8,-0.3,70.2,-13.2,65.4C-26.1,60.5,-40,54.4,-49.1,44.1C-58.3,33.8,-62.6,19.3,-63.2,5.4C-63.8,-8.5,-60.6,-21.8,-53.8,-33.1C-47,-44.4,-36.5,-53.7,-24.5,-59.1C-12.5,-64.5,1,-66,14.1,-63.4C27.2,-60.8,39.8,-54.1,49.8,-44.3Z" transform="translate(100 100)" /></svg>');
            opacity: 0.1;
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