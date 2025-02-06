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

    @keyframes gradientFlow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    body {
      font-family: 'Roboto', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 50%, #e0f2f1 100%);
      background-size: 200% 200%;
      animation: gradientFlow 15s ease infinite;
      position: relative;
      overflow-x: hidden;
    }

    @keyframes float {
      0% { transform: translateY(0px) translateX(0px); }
      50% { transform: translateY(-20px) translateX(10px); }
      100% { transform: translateY(0px) translateX(0px); }
    }

    body::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background: url('https://img.freepik.com/free-vector/clean-medical-background_53876-97927.jpg?t=st=1738517715~exp=1738521315~hmac=368b8cb5c29b6bea18135d8045cb4eb8ab09652f55132e9b0bb942de9c98ab33&w=900');
      background-repeat: no-repeat;
      background-size: cover;
      opacity: 0;
      z-index: 0;
      animation: fadeInBackground 1.5s ease-out 0.5s forwards,
                 float 6s ease-in-out infinite;
    }

    @keyframes fadeInBackground {
      to { opacity: 0.23; }
    }

    /* Top Left Health Content Button */
    .health-content-button {
      position: fixed;
      top: 2rem;
      left: 2rem;
      padding: 0.8rem 1.5rem;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid var(--accent);
      border-radius: 25px;
      color: var(--accent);
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      backdrop-filter: blur(8px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .health-content-button:hover {
      background: var(--accent);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(231, 111, 81, 0.2);
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

    @keyframes glowText {
      0% { text-shadow: 0 0 0 rgba(38, 70, 83, 0); }
      50% { text-shadow: 0 0 20px rgba(38, 70, 83, 0.3); }
      100% { text-shadow: 0 0 0 rgba(38, 70, 83, 0); }
    }

    h1 {
      font-family: 'Lato', sans-serif;
      font-weight: 700;
      font-size: 4rem;
      color: var(--secondary);
      margin-bottom: 1.5rem;
      animation: titleEntrance 1s cubic-bezier(0.17, 0.84, 0.44, 1) forwards, glowText 3s ease-in-out infinite;
      line-height: 1.2;
      opacity: 0;
      transform: translateY(30px);
    }

    @keyframes titleEntrance {
      0% { opacity: 0; transform: translateY(30px) scale(0.95); }
      50% { opacity: 0.5; transform: translateY(-10px) scale(1.02); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .subtitle {
      font-size: 1.5rem;
      color: var(--secondary);
      margin-bottom: 3rem;
      max-width: 600px;
      opacity: 0;
      transform: translateY(20px);
      animation: subtitleEntrance 0.8s cubic-bezier(0.17, 0.84, 0.44, 1) 0.5s forwards;
    }

    @keyframes subtitleEntrance {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .role-buttons {
      display: flex;
      gap: 2rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      justify-content: center;
      opacity: 0;
      transform: translateY(20px);
      animation: buttonsEntrance 0.8s cubic-bezier(0.17, 0.84, 0.44, 1) 1s forwards;
    }

    @keyframes buttonsEntrance {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .btn {
      padding: 1.5rem 3rem;
      border: none;
      border-radius: 15px;
      font-size: 1.1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.5s cubic-bezier(0.17, 0.84, 0.44, 1);
      position: relative;
      overflow: hidden;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.8rem;
    }

    @keyframes buttonGlow {
      0% { box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); }
      50% { box-shadow: 0 0 20px rgba(0, 0, 0, 0.4); }
      100% { box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); }
    }

    .btn:hover {
      transform: translateY(-5px) scale(1.02);
      animation: buttonGlow 2s infinite;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      transform: translate(-50%, -50%) scale(0);
      transition: transform 0.5s cubic-bezier(0.17, 0.84, 0.44, 1);
    }

    .btn:hover::before {
      transform: translate(-50%, -50%) scale(1.5);
    }

    @keyframes gradientAnimation {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .btn-patient {
      background: linear-gradient(-45deg, var(--primary), #21867a, #2A9D8F, #21867a);
      background-size: 300% 300%;
      color: white;
      animation: gradientAnimation 5s ease infinite;
    }

    .btn-doctor {
      background: linear-gradient(-45deg, var(--accent), #d84315, #E76F51, #d84315);
      background-size: 300% 300%;
      color: white;
      animation: gradientAnimation 5s ease infinite;
    }

    /* Login Button Animation */
    .login-button {
      position: fixed;
      top: 2rem;
      right: 2rem;
      padding: 0.8rem 1.5rem;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid var(--primary);
      border-radius: 25px;
      color: var(--primary);
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      backdrop-filter: blur(8px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .login-button:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(42, 157, 143, 0.2);
    }

    .login-button svg {
      width: 20px;
      height: 20px;
      transition: transform 0.3s ease;
    }

    .login-button:hover svg {
      transform: translateX(3px);
    }

    @keyframes buttonPop {
      0% { transform: scale(0.8); opacity: 0; }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); opacity: 1; }
    }

    .login-button,.health-content-button {
      animation: buttonPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    
    @media (max-width: 768px) {
      h1 { font-size: 3rem; }
      .subtitle { font-size: 1.2rem; }
      .btn { padding: 1rem 2rem; font-size: 1rem; }
      .login-button {
        top: 1rem;
        right: 1rem;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
      }
      .health-content-button {
        top: 1rem;
        left: 1rem;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 480px) {
      h1 { font-size: 2.5rem; }
      .role-buttons {
        flex-direction: column;
        width: 100%;
      }
      .btn {
        width: 100%;
        justify-content: center;
      }
      .login-button {
        position: fixed;
        top: auto;
        bottom: 1rem;
        right: 50%;
        transform: translateX(50%);
        width: calc(100% - 2rem);
        justify-content: center;
      }
      .login-button:hover { transform: translateX(50%) translateY(-2px); }
      .health-content-button {
        position: fixed;
        top: 1rem;
        left: 50%;
        transform: translateX(-50%);
      }
    }
  </style>
</head>
<body>
  <!-- Health Content Button (Top Left) -->
  <a href="health_tips.php" class="health-content-button">Health Content</a>

  <!-- Login Button (Top Right) -->
  <a href="login.php" class="login-button">
    <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
      <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
    </svg>
    Sign In
  </a>

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
  </div>
</body>
</html>
