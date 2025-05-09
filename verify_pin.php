<?php
session_start();
require_once 'config.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database configuration
    $servername = "localhost";
    $username_db = "root";
    $password_db = "";
    $dbname = "medilinx";

    try {
        // Create connection
        $conn = new mysqli($servername, $username_db, $password_db, $dbname);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed");
        }

        // Get and validate PIN
        $pin = trim($_POST['pin'] ?? '');

        if (!preg_match('/^[0-9]{6}$/', $pin)) {
            throw new Exception("Invalid PIN format");
        }

        // Set timezone
        date_default_timezone_set('Asia/Dhaka');
        $current_time = date("Y-m-d H:i:s");

        // Check PIN validity
        $stmt = $conn->prepare("SELECT id FROM users WHERE email_verification_pin = ? AND email_verified_at IS NULL");
        if (!$stmt) {
            throw new Exception("Database error");
        }

        $stmt->bind_param("s", $pin);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Update verification status
            $update_stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_pin = NULL WHERE id = ?");
            if (!$update_stmt) {
                throw new Exception("Database error");
            }

            $update_stmt->bind_param("i", $row['id']);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = ""; //Email verified successfully
                header("Location: dashboard.php");
                exit;
            } else {
                throw new Exception("Error updating verification status");
            }
        } else {
            throw new Exception("Invalid verification PIN");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($update_stmt)) $update_stmt->close();
        if (isset($conn)) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Email - MediLinx</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Roboto:wght@300;400;500&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --primary: #2a9d8f;
            --secondary: #264653;
            --accent: #e76f51;
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Roboto", sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
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
            z-index: 1;
        }

        h2 {
            font-family: "Lato", sans-serif;
            color: var(--secondary);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        input {
            width: 100%;
            padding: 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 4px 12px rgba(42, 157, 143, 0.2);
        }

        label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            background: var(--glass);
            padding: 0 0.5rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        input:focus~label,
        input:valid~label {
            top: 0;
            transform: translateY(-50%) scale(0.9);
            color: var(--primary);
        }

        button {
            background: linear-gradient(135deg, var(--primary) 0%, #21867a 100%);
            color: white;
            padding: 1.2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 157, 143, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        @keyframes formEntrance {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 2rem;
            }

            h2 {
                font-size: 2rem;
            }

            input {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Verify Email</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="input-group">
                <input
                    type="text"
                    id="pin"
                    name="pin"
                    pattern="[0-9]{6}"
                    title="Please enter a 6-digit PIN"
                    required />
                <label for="pin">Verification PIN</label>
            </div>
            <button type="submit">Verify Account</button>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Auto-focus input on load
            const pinInput = document.getElementById("pin");
            pinInput.focus();

            // Add floating label functionality
            pinInput.addEventListener("input", function() {
                if (this.value.length === 6) {
                    this.blur();
                }
            });
        });
    </script>
</body>

</html>