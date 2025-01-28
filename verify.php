<?php
// verify.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['verify_email'])) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Account - MediLinx</title>
    <style>
        /* Using same root variables and base styles */
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
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .verification-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }

        .verification-input input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 0 5px;
        }

        .verification-input input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .message {
            color: #65676b;
            margin: 20px 0;
        }

        .error {
            color: var(--error-color);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Account</h2>
        <p class="message">We've sent a verification PIN to:<br>
        <strong><?php echo htmlspecialchars($_SESSION['verify_email']); ?></strong></p>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <form action="process_verification.php" method="post">
            <div class="verification-input">
                <input type="text" maxlength="6" name="pin" pattern="[0-9]{6}" 
                       title="Please enter 6 digits" required>
            </div>
            <button type="submit" class="btn">Verify Account</button>
        </form>
    </div>

    <script>
        // Auto-focus first input and handle input
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="pin"]');
            input.focus();
        });
    </script>
</body>
</html>