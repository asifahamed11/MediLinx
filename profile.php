<?php
// profile.php (updated design)
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #E76F51;
            --light-bg: #f8f9fa;
            --text: #2d3748;
            --text-light: #718096;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }

        .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .profile-image:hover {
            transform: scale(1.05) rotate(1deg);
        }

        .profile-name {
            font-size: 2.5rem;
            margin: 1rem 0 0.5rem;
            font-weight: 700;
        }

        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .profile-sidebar {
            border-right: 2px solid #eee;
        }

        .info-card {
            background: var(--light-bg);
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-item {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: background 0.3s ease;
        }

        .info-item:hover {
            background: rgba(42, 157, 143, 0.05);
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: var(--text);
            font-size: 0.95rem;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: var(--light-bg);
            padding: 0.5rem;
            border-radius: 0.75rem;
        }

        .tab-button {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .tab-button.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.3);
        }

        .tab-content {
            display: none;
            animation: tabContent 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes tabContent {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .edit-button {
            background: var(--primary);
            color: white;
            padding: 0.9rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .edit-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.3);
        }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
                padding: 1.5rem;
            }

            .profile-header {
                padding: 2rem 1rem;
            }

            .profile-name {
                font-size: 2rem;
            }

            .tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['profile_image'] ?: 'default-avatar.jpg'); ?>"
                 class="profile-image"
                 alt="Profile Picture">
            <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
            <div class="profile-role"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
            <a href="edit-profile.php" class="edit-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                </svg>
                Edit Profile
            </a>
        </div>

        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="info-card">
                    <h3 class="info-label">Contact Information</h3>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['address'] ?: 'N/A'); ?></div>
                    </div>
                </div>

                <?php if($user['role'] === 'doctor'): ?>
                    <div class="info-card">
                        <h3 class="info-label">Professional Information</h3>
                        <div class="info-item">
                            <div class="info-label">Specialty</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['specialty']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">License Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['medical_license_number']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-main">
                <div class="tabs">
                    <button class="tab-button active" data-tab="personal">Personal Info</button>
                    <?php if($user['role'] === 'doctor'): ?>
                        <button class="tab-button" data-tab="professional">Professional Details</button>
                    <?php else: ?>
                        <button class="tab-button" data-tab="medical">Medical History</button>
                    <?php endif; ?>
                </div>

                <div class="tab-content active" id="personal">
                    <div class="info-card">
                        <h3 class="info-label">Personal Details</h3>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['date_of_birth'] ?: 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: 'Not specified'); ?></div>
                        </div>
                    </div>
                </div>

                <?php if($user['role'] === 'doctor'): ?>
                    <div class="tab-content" id="professional">
                        <div class="info-card">
                            <h3 class="info-label">Professional Details</h3>
                            <div class="info-item">
                                <div class="info-label">Work Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['work_address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Consultation Hours</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['consultation_hours']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="tab-content" id="medical">
                        <div class="info-card">
                            <h3 class="info-label">Medical History</h3>
                            <div class="info-value" style="white-space: pre-wrap;">
                                <?php echo htmlspecialchars($user['medical_history'] ?: 'No medical history recorded'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.dataset.tab;
                document.querySelectorAll('.tab-button, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                button.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>