<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Establish database connection
$conn = connectDB();

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch degrees data if user is a doctor
$degrees = [];
if ($user['role'] === 'doctor') {
    $degreeStmt = $conn->prepare("SELECT * FROM degrees WHERE doctor_id = ?");
    $degreeStmt->bind_param("i", $user_id);
    $degreeStmt->execute();
    $degrees = $degreeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $degreeStmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        // Sanitize inputs
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $medical_history = filter_input(INPUT_POST, 'medical_history', FILTER_SANITIZE_STRING);
        $specialty = filter_input(INPUT_POST, 'specialty', FILTER_SANITIZE_STRING);
        $work_address = filter_input(INPUT_POST, 'work_address', FILTER_SANITIZE_STRING);
        $available_consultation_hours = filter_input(INPUT_POST, 'available_consultation_hours', FILTER_SANITIZE_STRING);
        $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $languages_spoken = filter_input(INPUT_POST, 'languages_spoken', FILTER_SANITIZE_STRING);
        $professional_biography = filter_input(INPUT_POST, 'professional_biography', FILTER_SANITIZE_STRING);

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = "uploads/profile_images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = uniqid() . '-' . basename($_FILES['profile_image']['name']);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validate image
            $check = getimagesize($_FILES['profile_image']['tmp_name']);
            if ($check === false) {
                $error = "File is not an image.";
            } elseif ($_FILES['profile_image']['size'] > 500000) {
                $error = "File too large (max 500KB)";
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $error = "Only JPG, JPEG, PNG & GIF allowed";
            } else {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = $target_file;
                    
                    // Delete old profile image if exists
                    if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                        unlink($user['profile_image']);
                    }
                } else {
                    $error = "Error uploading image";
                }
            }
        }

        if (empty($error)) {
            // Handle doctor degrees
            if ($user['role'] === 'doctor') {
                // Delete existing degrees
                $deleteStmt = $conn->prepare("DELETE FROM degrees WHERE doctor_id = ?");
                $deleteStmt->bind_param("i", $user_id);
                $deleteStmt->execute();

                // Insert updated degrees
                if (isset($_POST['degree_name'])) {
                    $degreeStmt = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['degree_name'] as $index => $degreeName) {
                        if (!empty($degreeName)) {
                            $institution = $_POST['institution'][$index];
                            $year = $_POST['passing_year'][$index];
                            $degreeStmt->bind_param("issi", $user_id, $degreeName, $institution, $year);
                            $degreeStmt->execute();
                        }
                    }
                }
            }
            
            // Build update query based on user role
            if ($user['role'] === 'doctor') {
                $query = "UPDATE users SET 
                    phone = ?,
                    address = ?,
                    specialty = ?,
                    work_address = ?,
                    available_consultation_hours = ?,
                    date_of_birth = ?,
                    gender = ?,
                    languages_spoken = ?,
                    professional_biography = ?,
                    profile_image = COALESCE(?, profile_image)
                    WHERE id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssssssi",
                    $phone,
                    $address,
                    $specialty,
                    $work_address,
                    $available_consultation_hours,
                    $date_of_birth,
                    $gender,
                    $languages_spoken,
                    $professional_biography,
                    $profile_image,
                    $user_id
                );
            } else {
                $query = "UPDATE users SET 
                    phone = ?,
                    address = ?,
                    medical_history = ?,
                    date_of_birth = ?,
                    gender = ?,
                    profile_image = COALESCE(?, profile_image)
                    WHERE id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssi",
                    $phone,
                    $address,
                    $medical_history,
                    $date_of_birth,
                    $gender,
                    $profile_image,
                    $user_id
                );
            }

            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // Refresh degrees data if user is a doctor
                if ($user['role'] === 'doctor') {
                    $degreeStmt = $conn->prepare("SELECT * FROM degrees WHERE doctor_id = ?");
                    $degreeStmt->bind_param("i", $user_id);
                    $degreeStmt->execute();
                    $degrees = $degreeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $degreeStmt->close();
                }
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-light: #8ECDC5;
            --primary-dark: #21867a;
            --secondary: #264653;
            --accent: #E76F51;
            --accent-light: #F4A261;
            --light-bg: #f8f9fa;
            --text: #2d3748;
            --text-light: #718096;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            min-height: 100vh;
            padding: 2rem;
            color: var(--text);
            line-height: 1.6;
        }

        .edit-container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeInUp 0.8s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
        }

        @keyframes fadeInUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .floating-particles span {
            position: absolute;
            width: 30px;
            height: 30px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 12s infinite linear;
            opacity: 0.4;
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.4;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }

        .edit-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 3.5rem 2rem 2.5rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .edit-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(30deg);
        }

        .edit-header h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.8rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .edit-header p {
            font-weight: 300;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='1' d='M0,128L60,138.7C120,149,240,171,360,170.7C480,171,600,149,720,149.3C840,149,960,171,1080,170.7C1200,171,1320,149,1380,138.7L1440,128L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-repeat: no-repeat;
        }

        .edit-form {
            padding: 2.5rem 2rem;
            position: relative;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--secondary);
            font-weight: 500;
            font-size: 0.95rem;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.2);
            background-color: #fff;
        }

        .form-input::placeholder {
            color: #cbd5e0;
        }

        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }

        .image-upload {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .current-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.5s ease;
            cursor: pointer;
        }

        .current-image:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(38, 70, 83, 0.5);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-image-container:hover .image-overlay {
            opacity: 1;
        }

        .upload-icon {
            color: white;
            font-size: 2rem;
        }

        .file-input {
            display: none;
        }

        .upload-label {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.85rem 1.8rem;
            background: var(--primary);
            color: white;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        }

        .upload-label:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(42, 157, 143, 0.4);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-dark));
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(42, 157, 143, 0.4);
        }

        .btn-secondary {
            background: #f0f4f8;
            color: var(--text);
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 1.2rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--secondary);
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-light);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 80px;
            height: 2px;
            background-color: var(--primary);
        }

        .degree-entry {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1.2rem;
            align-items: center;
            padding: 1rem;
            background-color: #f9fafb;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .degree-entry:hover {
            background-color: #f0f9ff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(231, 111, 81, 0.3);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, var(--accent), var(--accent));
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(231, 111, 81, 0.4);
        }

        .btn-remove {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-remove:hover {
            background: #fca5a5;
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(153, 27, 27, 0.2);
        }

        .tab-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.8rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 500;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .edit-container {
                margin: 1rem;
                border-radius: 1rem;
            }
            
            .degree-entry {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                padding: 1rem;
            }
            
            .btn-remove {
                margin-left: auto;
            }
            
            .tab {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Custom tooltip styling */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.5rem 1rem;
            background-color: var(--secondary);
            color: white;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            visibility: visible;
            bottom: calc(100% + 10px);
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Loading indicator for form submission */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(42, 157, 143, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="floating-particles">
            <?php for ($i = 0; $i < 8; $i++): ?>
                <span style="left: <?php echo rand(0, 100); ?>%; top: <?php echo rand(0, 100); ?>%; width: <?php echo rand(10, 30); ?>px; height: <?php echo rand(10, 30); ?>px; animation-delay: <?php echo $i * 0.5; ?>s; animation-duration: <?php echo rand(8, 15); ?>s;"></span>
            <?php endfor; ?>
        </div>
        
        <div class="edit-header">
            <h1 class="animate__animated animate__fadeInDown">Update Your Profile</h1>
            <p class="animate__animated animate__fadeIn animate__delay-1s">
                <?php echo $user['role'] === 'doctor' ? 'Keep your professional information current to better serve your patients.' : 'Maintain your health information up-to-date for better medical care.'; ?>
            </p>
            <div class="wave"></div>
        </div>

        <form id="profileForm" class="edit-form" method="POST" enctype="multipart/form-data">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="image-upload animate__animated animate__fadeIn animate__delay-1s">
                <div class="profile-image-container">
                    <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'uploads/profile_images/default-profile.png'; ?>" 
                         alt="Profile Image"
                         class="current-image" 
                         id="profileImg">
                    <div class="image-overlay" onclick="document.getElementById('fileInput').click()">
                        <div class="upload-icon">📸</div>
                    </div>
                </div>
                <input type="file" name="profile_image" id="fileInput" class="file-input" accept="image/*">
                <label for="fileInput" class="upload-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="margin-right: 8px;" viewBox="0 0 16 16">
                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                    </svg>
                    Change Photo
                </label>
            </div>

            <div class="tab-container animate__animated animate__fadeIn animate__delay-1s">
                <button type="button" class="tab active" data-tab="personal">Personal Info</button>
                <?php if ($user['role'] === 'doctor'): ?>
    <button type="button" class="tab" data-tab="professional">Professional Info</button>
    <button type="button" class="tab" data-tab="education">Education</button>
<?php endif; ?>
</div>

<div id="personal" class="tab-content active">
    <h3 class="section-title">Personal Information</h3>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Your contact number">
        </div>

        <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-input">
                <option value="">Select Gender</option>
                <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-input" placeholder="Your residential address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>

        <?php if ($user['role'] === 'patient'): ?>
        <div class="form-group full-width">
            <label class="form-label">Medical History</label>
            <textarea name="medical_history" class="form-input" placeholder="Please provide relevant medical history information"><?php echo htmlspecialchars($user['medical_history'] ?? ''); ?></textarea>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($user['role'] === 'doctor'): ?>
<div id="professional" class="tab-content">
    <h3 class="section-title">Professional Information</h3>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Specialty</label>
            <input type="text" name="specialty" class="form-input" value="<?php echo htmlspecialchars($user['specialty'] ?? ''); ?>" placeholder="Your medical specialty">
        </div>

        <div class="form-group">
            <label class="form-label">Languages Spoken</label>
            <input type="text" name="languages_spoken" class="form-input" value="<?php echo htmlspecialchars($user['languages_spoken'] ?? ''); ?>" placeholder="E.g. English, Spanish, French">
        </div>

        <div class="form-group full-width">
            <label class="form-label">Work Address</label>
            <textarea name="work_address" class="form-input" placeholder="Your clinic or hospital address"><?php echo htmlspecialchars($user['work_address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Consultation Hours</label>
            <textarea name="available_consultation_hours" class="form-input" placeholder="E.g. Mon-Fri: 9AM-5PM, Sat: 9AM-1PM"><?php echo htmlspecialchars($user['available_consultation_hours'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Professional Biography</label>
            <textarea name="professional_biography" class="form-input" placeholder="A brief description of your professional experience and approach" rows="5"><?php echo htmlspecialchars($user['professional_biography'] ?? ''); ?></textarea>
        </div>
    </div>
</div>

<div id="education" class="tab-content">
    <h3 class="section-title">Education & Qualifications</h3>
    <div id="degrees-container">
        <?php if (empty($degrees)): ?>
            <div class="degree-entry animate__animated animate__fadeIn">
                <input type="text" name="degree_name[]" class="form-input" placeholder="Degree Name (e.g. MD, MBBS)" required>
                <input type="text" name="institution[]" class="form-input" placeholder="Institution" required>
                <input type="number" name="passing_year[]" class="form-input" placeholder="Year" min="1950" max="<?php echo date('Y'); ?>" required>
                <button type="button" class="btn-remove" onclick="removeDegree(this)" data-tooltip="Remove degree">×</button>
            </div>
        <?php else: ?>
            <?php foreach ($degrees as $index => $degree): ?>
                <div class="degree-entry animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.1; ?>s">
                    <input type="text" name="degree_name[]" class="form-input" placeholder="Degree Name" 
                           value="<?= htmlspecialchars($degree['degree_name']) ?>" required>
                    <input type="text" name="institution[]" class="form-input" placeholder="Institution" 
                           value="<?= htmlspecialchars($degree['institution']) ?>" required>
                    <input type="number" name="passing_year[]" class="form-input" placeholder="Year" 
                           value="<?= $degree['passing_year'] ?>" min="1950" max="<?php echo date('Y'); ?>" required>
                    <button type="button" class="btn-remove" onclick="removeDegree(this)" data-tooltip="Remove degree">×</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" class="btn-add" onclick="addDegreeField()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
        </svg>
        Add Another Degree
    </button>
</div>
<?php endif; ?>

<div class="button-group animate__animated animate__fadeIn animate__delay-1s">
    <a href="profile.php" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back to Profile
    </a>
    <button type="submit" class="btn btn-primary" id="saveBtn">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
        </svg>
        Save Changes
    </button>
</div>

<div class="form-footer">
    <p>Last updated: <?php echo date('F j, Y'); ?></p>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    const [file] = e.target.files;
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImg').src = e.target.result;
            document.getElementById('profileImg').classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                document.getElementById('profileImg').classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        }
        reader.readAsDataURL(file);
    }
});

function addDegreeField() {
    const container = document.getElementById('degrees-container');
    const entry = document.createElement('div');
    entry.className = 'degree-entry animate__animated animate__fadeIn';
    entry.innerHTML = `
        <input type="text" name="degree_name[]" class="form-input" placeholder="Degree Name (e.g. MD, MBBS)" required>
        <input type="text" name="institution[]" class="form-input" placeholder="Institution" required>
        <input type="number" name="passing_year[]" class="form-input" placeholder="Year" min="1950" max="${new Date().getFullYear()}" required>
        <button type="button" class="btn-remove" onclick="removeDegree(this)" data-tooltip="Remove degree">×</button>
    `;
    container.appendChild(entry);
}

function removeDegree(button) {
    const degreeEntry = button.parentElement;
    degreeEntry.classList.add('animate__animated', 'animate__fadeOut');
    setTimeout(() => {
        degreeEntry.remove();
    }, 500);
}

// Tab functionality
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active class from all tabs and tab content
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Show corresponding tab content
        const tabContent = document.getElementById(this.dataset.tab);
        tabContent.classList.add('active');
    });
});

// Form submission loading indicator
document.getElementById('profileForm').addEventListener('submit', function() {
    document.getElementById('loadingOverlay').classList.add('active');
    document.getElementById('saveBtn').disabled = true;
    document.getElementById('saveBtn').innerHTML = 'Saving...';
});

// Alert auto-dismiss
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.classList.add('animate__animated', 'animate__fadeOut');
        setTimeout(() => {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);
</script>
</form>
</div>
</body>
</html>