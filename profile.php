<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Establish database connection
$conn = connectDB();

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Close the connection
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2A9D8F;
            --secondary: #264653;
            --accent: #E76F51;
            --glass: rgba(255, 255, 255, 0.95);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 50%, #e0f2f1 100%);
            min-height: 100vh;
            padding: 2rem;
            margin: 0;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: var(--glass);
            border-radius: 20px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            padding: 3rem;
            text-align: center;
            color: white;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 1rem;
            object-fit: cover;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .profile-name {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .profile-sidebar {
            border-right: 2px solid rgba(0, 0, 0, 0.1);
            padding-right: 2rem;
        }

        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .info-card h3 {
            color: var(--secondary);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .info-item:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary);
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-value {
            color: #555;
        }

        .edit-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-button {
            padding: 1rem 2rem;
            border: none;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        .medical-history {
            white-space: pre-wrap;
            line-height: 1.6;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                border-right: none;
                padding-right: 0;
                border-bottom: 2px solid rgba(0, 0, 0, 0.1);
                padding-bottom: 2rem;
            }

            .profile-header {
                padding: 2rem;
            }

            .profile-name {
                font-size: 2rem;
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
                    <h3>Contact Information</h3>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></span>
                    </div>
                </div>

                <?php if($user['role'] === 'doctor'): ?>
                <div class="info-card">
                    <h3>Professional Info</h3>
                    <div class="info-item">
                        <span class="info-label">Specialty</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['specialty']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">License Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['medical_license_number']); ?></span>
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
                        <h3>Personal Details</h3>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['date_of_birth'] ?: 'Not provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['gender'] ?: 'Not specified'); ?></span>
                        </div>
                    </div>
                </div>

                <?php if($user['role'] === 'doctor'): ?>
                <div class="tab-content" id="professional">
                    <div class="info-card">
                        <h3>Work Information</h3>
                        <div class="info-item">
                            <span class="info-label">Work Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['work_address']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Consultation Hours</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['consultation_hours']); ?></span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="tab-content" id="medical">
                    <div class="info-card">
                        <h3>Medical History</h3>
                        <div class="medical-history">
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
                
                // Remove active class from all buttons and content
                document.querySelectorAll('.tab-button, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>