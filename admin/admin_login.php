<?php
session_start();
require_once 'config.php';

// Redirect all requests to main login page
header('Location: ../login.php');
exit;

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Connect to database and check admin credentials
        $conn = connectDB();

        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            // Verify password hash
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_id'] = $admin['id'];

                header('Location: admin.php');
                exit;
            }
        }

        $error = 'Invalid username or password';
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medilinx Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-light: #d2e3fc;
            --text-dark: #202124;
            --text-light: #5f6368;
            --danger-color: #ea4335;
            --success-color: #34a853;
            --transition: all 0.3s ease;

            /* Animation Variables */
            --animation-slow: 0.6s;
            --animation-medium: 0.4s;
            --animation-fast: 0.2s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Arial', sans-serif;
        }

        /* Animation keyframes */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes borderGlow {
            0% {
                box-shadow: 0 0 0 rgba(26, 115, 232, 0);
            }

            50% {
                box-shadow: 0 0 10px rgba(26, 115, 232, 0.5);
            }

            100% {
                box-shadow: 0 0 0 rgba(26, 115, 232, 0);
            }
        }

        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-image: linear-gradient(45deg, #f8f9fa 25%, #f2f2f2 25%, #f2f2f2 50%, #f8f9fa 50%, #f8f9fa 75%, #f2f2f2 75%, #f2f2f2);
            background-size: 60px 60px;
            animation: backgroundSlide 60s linear infinite;
        }

        @keyframes backgroundSlide {
            0% {
                background-position: 0 0;
            }

            100% {
                background-position: 1000px 500px;
            }
        }

        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            animation: scaleIn var(--animation-medium) cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), #0d47a1);
            opacity: 0.8;
        }

        .login-container:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeUp var(--animation-medium) ease-out;
        }

        .logo h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 5px;
            position: relative;
            display: inline-block;
        }

        .logo h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            border-radius: 3px;
        }

        .logo p {
            color: var(--text-light);
            font-size: 16px;
            animation: fadeUp calc(var(--animation-medium) + 0.1s) ease-out;
        }

        .form-group {
            margin-bottom: 24px;
            animation: fadeUp calc(var(--animation-medium) + 0.2s) ease-out;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.1s;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 14px;
            animation: slideInRight calc(var(--animation-medium) + 0.1s) ease-out;
        }

        .input-with-icon {
            position: relative;
            transition: var(--transition);
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: var(--transition);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            transform: translateY(-2px);
        }

        .form-group input:focus+i {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        .btn-login {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            animation: fadeUp calc(var(--animation-medium) + 0.3s) ease-out;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }

        .btn-login:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }

            100% {
                transform: scale(50, 50);
                opacity: 0;
            }
        }

        .btn-login:hover {
            background-color: #0d47a1;
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.4);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        .error-message {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--danger-color);
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shakeX 0.5s cubic-bezier(.36, .07, .19, .97) both;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }

        @keyframes shakeX {

            10%,
            90% {
                transform: translateX(-1px);
            }

            20%,
            80% {
                transform: translateX(2px);
            }

            30%,
            50%,
            70% {
                transform: translateX(-3px);
            }

            40%,
            60% {
                transform: translateX(3px);
            }
        }

        .error-message i {
            font-size: 20px;
            color: var(--danger-color);
            animation: pulse 2s infinite;
        }

        .setup-link {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--text-light);
            animation: fadeUp calc(var(--animation-medium) + 0.4s) ease-out;
        }

        .setup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .setup-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }

        .setup-link a:hover::after {
            width: 100%;
        }

        .setup-link a:hover {
            color: #0d47a1;
        }

        .setup-link a i {
            transition: transform 0.3s ease;
        }

        .setup-link a:hover i {
            transform: translateX(3px);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-light);
            animation: fadeUp calc(var(--animation-medium) + 0.3s) ease-out;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #dadce0;
        }

        .divider span {
            padding: 0 15px;
            font-size: 14px;
        }

        /* Responsive Animation Adjustments */
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
            }

            .btn-login:hover {
                transform: translateY(-1px);
            }
        }

        /* Disable animations for users who prefer reduced motion */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <h1>MediLinx</h1>
            <p>Admin Portal Access</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required autocomplete="off"
                        placeholder="Enter your username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required
                        placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Login to Dashboard
            </button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="setup-link">
            <p>First time setup? <a href="setup_admin.php">Create admin account <i class="fas fa-arrow-right"></i></a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus animation for input fields
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = '';
                });
            });

            // Button ripple effect
            const loginBtn = document.querySelector('.btn-login');
            loginBtn.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;

                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '1px';
                ripple.style.height = '1px';
                ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
                ripple.style.borderRadius = '50%';
                ripple.style.transform = 'scale(0)';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.animation = 'ripple 0.6s linear';

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });

            // Show password toggle
            const passwordField = document.getElementById('password');
            const eyeIcon = document.createElement('i');
            eyeIcon.className = 'fas fa-eye';
            eyeIcon.style.position = 'absolute';
            eyeIcon.style.right = '15px';
            eyeIcon.style.top = '50%';
            eyeIcon.style.transform = 'translateY(-50%)';
            eyeIcon.style.cursor = 'pointer';
            eyeIcon.style.color = '#5f6368';
            eyeIcon.style.transition = 'all 0.2s ease';

            passwordField.parentElement.appendChild(eyeIcon);

            eyeIcon.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.className = 'fas fa-eye-slash';
                } else {
                    passwordField.type = 'password';
                    this.className = 'fas fa-eye';
                }
                this.style.color = '#1a73e8';
                setTimeout(() => {
                    this.style.color = '#5f6368';
                }, 300);
            });

            // Auto hide error message after 5 seconds
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transform = 'translateY(-10px)';
                    errorMessage.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>