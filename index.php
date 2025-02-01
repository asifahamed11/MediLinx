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
            background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%232A9D8F" d="M49.8,-35.3C62.5,-21.3,68.7,2.7,61.7,20.2C54.7,37.7,34.5,48.6,13.6,54.3C-7.4,60,-28.9,60.4,-42.1,50.3C-55.3,40.1,-60.1,19.4,-58.4,0.8C-56.7,-17.8,-48.5,-35.5,-36.2,-49.7C-23.9,-63.9,-7.5,-74.5,10.1,-79.8C27.7,-85.1,55.4,-85.1,68.6,-72.4C81.8,-59.7,80.5,-34.3,77.3,-12.9C74.1,8.5,69,25.9,59.1,38.8C49.2,51.7,34.5,60.1,19.3,65.4C4.1,70.7,-11.6,72.9,-25.6,68.8C-39.6,64.7,-51.9,54.4,-61.5,41.3C-71.1,28.3,-78,12.6,-76.4,-1.8C-74.8,-16.3,-64.7,-32.6,-52.3,-46.1C-39.8,-59.6,-25,-70.3,-6.4,-67.3C12.2,-64.3,24.4,-47.6,37.1,-34.8C49.8,-22,63,-13,66.2,1.7C69.4,16.4,62.6,32.8,52.4,45.1C42.2,57.4,28.6,65.6,14.5,70.3C0.4,75,-14.2,76.2,-24.1,70.2C-34,64.2,-39.2,51,-45.7,38.2C-52.2,25.3,-60,12.7,-61.4,-1.4C-62.8,-15.5,-57.8,-30.9,-48.3,-42.2C-38.8,-53.5,-24.8,-60.6,-9.7,-56.9C5.5,-53.2,11,-38.7,20.9,-28.5C30.8,-18.3,45,-12.5,55.2,3.7C65.4,19.9,71.5,42.8,66.7,56.3C61.9,69.8,46.2,73.9,31.8,71.1C17.5,68.4,4.5,58.7,-7.6,50.2C-19.7,41.6,-30.4,34.1,-40.6,25.9C-50.8,17.7,-60.5,8.8,-61.9,-1.4C-63.4,-11.6,-56.7,-23.3,-48.5,-35.1C-40.3,-47,-30.6,-59.1,-17.6,-65.7C-4.7,-72.3,11.6,-73.4,24.3,-67.1C37,-60.8,46.1,-47.1,55.7,-34.4C65.3,-21.6,75.3,-9.8,77.8,3.4C80.3,16.6,75.2,33.2,65.6,44.4C56,55.6,41.9,61.4,28.7,64.9C15.5,68.3,3.1,69.5,-8.5,65.8C-20,62.1,-40.1,53.5,-50.1,40.4C-60.2,27.3,-60.4,9.7,-57.9,-7.8C-55.4,-25.3,-50.3,-42.5,-40.3,-53.9C-30.3,-65.3,-15.2,-70.8,0.6,-71.8C16.3,-72.8,32.6,-69.2,44.2,-59.1C55.8,-49,62.7,-32.4,66.5,-16.2C70.3,0,71,15.8,66.7,28.9C62.4,42,53.1,52.4,41.7,59.2C30.3,66,16.7,69.2,3.5,66.7C-9.7,64.3,-19.3,56.2,-30.3,48.7C-41.3,41.2,-53.7,34.3,-61.3,23.7C-68.9,13.1,-71.7,-1.3,-67.7,-13.2C-63.7,-25.1,-52.9,-34.6,-42.1,-44.5C-31.3,-54.4,-20.6,-64.8,-7.2,-63.3C6.2,-61.7,12.4,-48.3,21.9,-38.1C31.4,-27.9,44.2,-20.9,55.2,-11.5C66.2,-2.1,75.4,9.7,77.6,23.8C79.8,37.9,75,54.3,64.8,63.6C54.6,72.9,38.9,75.1,25.2,74.5C11.5,73.8,-0.3,70.2,-13.2,65.4C-26.1,60.5,-40,54.4,-49.1,44.1C-58.3,33.8,-62.6,19.3,-63.2,5.4C-63.8,-8.5,-60.6,-21.8,-53.8,-33.1C-47,-44.4,-36.5,-53.7,-24.5,-59.1C-12.5,-64.5,1,-66,14.1,-63.4C27.2,-60.8,39.8,-54.1,49.8,-44.3Z" transform="translate(100 100)" /></svg>');
            opacity: 0.1;
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