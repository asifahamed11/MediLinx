<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #4CAF50;
            --background: #F5F9FF;
            --text: #2C3E50;
            --neumorphic-shadow: 8px 8px 16px #d9d9d9,
                -8px -8px 16px #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            background: var(--background);
            padding: 3rem;
            border-radius: 30px;
            box-shadow: var(--neumorphic-shadow);
            max-width: 900px;
            width: 100%;
            transform: translateY(20px);
            opacity: 0;
            animation: fadeInUp 0.8s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: var(--primary);
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .form-header p {
            text-align: center;
            color: var(--text);
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 15px;
            background: var(--background);
            box-shadow: inset 5px 5px 10px #d9d9d9,
                inset -5px -5px 10px #ffffff;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .input-group input:focus,
        .input-group textarea:focus,
        .input-group select:focus {
            box-shadow: inset 2px 2px 5px #d9d9d9,
                inset -2px -2px 5px #ffffff;
            outline: none;
        }

        .input-group label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text);
            pointer-events: none;
            transition: all 0.3s ease;
            background: var(--background);
            padding: 0 0.5rem;
        }

        .input-group input:focus~label,
        .input-group input:not(:placeholder-shown)~label,
        .input-group textarea:focus~label,
        .input-group textarea:not(:placeholder-shown)~label,
        .input-group select:focus~label,
        .input-group select:not(:placeholder-shown)~label {
            top: 0;
            font-size: 0.9rem;
            color: var(--primary);
        }

        .input-group textarea~label {
            top: 1.2rem;
        }

        .input-group textarea:focus~label,
        .input-group textarea:not(:placeholder-shown)~label {
            top: 0;
        }

        .file-input {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            background: var(--background);
            box-shadow: var(--neumorphic-shadow);
            cursor: pointer;
            transition: transform 0.3s ease;
            margin: 2rem 0;
        }

        .file-input:hover {
            transform: translateY(-3px);
        }

        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input label {
            display: block;
            padding: 1.5rem;
            text-align: center;
            color: var(--primary);
        }

        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--neumorphic-shadow);
            margin: 1rem auto;
            display: none;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 1.2rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 5px 5px 10px #d9d9d9,
                -5px -5px 10px #ffffff;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 8px 8px 15px #d9d9d9,
                -8px -8px 15px #ffffff;
            letter-spacing: 1px;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem;
                margin: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-user"></i> Patient Registration</h2>
        <div class="form-header">
        </div>

        <form action="registration.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="role" value="patient">

            <div class="form-grid">
                <div class="input-group">
                    <input type="text" id="username" name="username" required placeholder=" ">
                    <label for="username"><i class="fas fa-user"></i> Name</label>
                </div>

                <div class="input-group">
                    <input type="email" id="email" name="email" required placeholder=" ">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                </div>

                <div class="input-group">
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                </div>

                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder=" ">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                </div>

                <div class="input-group">
                    <input type="tel" id="phone" name="phone" placeholder=" ">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                </div>

                <div class="input-group">
                    <input type="date" id="date_of_birth" name="date_of_birth" required placeholder=" ">
                    <label for="date_of_birth"><i class="fas fa-calendar"></i> Date of Birth</label>
                </div>

                <div class="input-group">
                    <select id="gender" name="gender" required>
                        <option value="" disabled selected></option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                </div>

                <div class="input-group">
                    <textarea id="medical_history" name="medical_history" rows="3" placeholder=" "></textarea>
                    <label for="medical_history"><i class="fas fa-notes-medical"></i> Medical History</label>
                </div>
            </div>

            <div class="file-input">
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                <label for="profile_image">
                    <i class="fas fa-camera"></i> Upload Profile Photo
                </label>
                <img src="#" class="preview-image" alt="Profile Preview">
            </div>

            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>

    <script>
        // Image preview functionality
        const fileInput = document.querySelector('input[type="file"]');
        const previewImage = document.querySelector('.preview-image');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewImage.style.display = 'block';
                    previewImage.src = e.target.result;
                }

                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>