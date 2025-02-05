<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediLinx - Healthcare Portal</title>
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
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('https://img.freepik.com/free-vector/clean-medical-background_53876-97927.jpg?t=st=1738517715~exp=1738521315~hmac=368b8cb5c29b6bea18135d8045cb4eb8ab09652f55132e9b0bb942de9c98ab33&w=900');
            background-repeat: no-repeat;
            opacity: .23;
            z-index: 0;
        }


        .container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            z-index: 1;
        }

        h1 {
            font-family: 'Lato', sans-serif;
            font-weight: 700;
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            animation: slideIn 1s ease-out;
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 3rem;
            max-width: 600px;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.3s forwards;
        }

        .role-buttons {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 1.5rem 3rem;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.5s ease;
        }

        .btn:hover:before {
            transform: translate(-50%, -50%) scale(1);
        }

        .btn-patient {
            background: linear-gradient(135deg, var(--primary) 0%, #21867a 100%);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-doctor {
            background: linear-gradient(135deg, var(--accent) 0%, #d84315 100%);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .login-link {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            transition: color 0.3s ease;
        }

        .login-link:after {
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

        .login-link:hover:after {
            transform: scaleX(1);
            transform-origin: left;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 3rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 2.5rem;
            }
            
            .role-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to MediLinx</h1>
        <p class="subtitle">Connect with healthcare professionals and manage your medical journey</p>
        <div class="role-buttons">
            <a href="patient_register.php" class="btn btn-patient">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 5C13.66 5 15 6.34 15 8C15 9.66 13.66 11 12 11C10.34 11 9 9.66 9 8C9 6.34 10.34 5 12 5ZM12 19.2C9.5 19.2 7.29 17.92 6 15.98C6.03 13.99 10 12.9 12 12.9C13.99 12.9 17.97 13.99 18 15.98C16.71 17.92 14.5 19.2 12 19.2Z" fill="white"/>
                </svg>
                Join as Patient
            </a>
            <a href="doctor_register.php" class="btn btn-doctor">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 6C13.1 6 14 6.9 14 8C14 9.1 13.1 10 12 10C10.9 10 10 9.1 10 8C10 6.9 10.9 6 12 6ZM12 15C14.7 15 17.8 16.29 18 17V18H6V17.01C6.2 16.29 9.3 15 12 15ZM12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4ZM12 13C9.33 13 4 14.34 4 17V20H20V17C20 14.34 14.67 13 12 13Z" fill="white"/>
                </svg>
                Join as Doctor
            </a>
        </div>
        <p style="margin-top: 2rem; opacity: 0; animation: fadeIn 0.5s ease-out 0.6s forwards;">
            <a href="login.php" class="login-link">Already have an account? Sign In</a>
        </p>
    </div>
</body>
</html>