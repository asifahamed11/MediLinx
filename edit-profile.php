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

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = "uploads/profile_images/";
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
            // Add this inside the if (empty($error)) block, before the existing UPDATE query
            if ($user['role'] === 'doctor') {
                // Delete existing degrees
                $deleteStmt = $conn->prepare("DELETE FROM degrees WHERE doctor_id = ?");
                $deleteStmt->bind_param("i", $user_id);
                $deleteStmt->execute();

                // Insert updated degrees
                if (isset($_POST['degree_name'])) {
                    $degreeStmt = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['degree_name'] as $index => $degreeName) {
                        $institution = $_POST['institution'][$index];
                        $year = $_POST['passing_year'][$index];
                        $degreeStmt->bind_param("issi", $user_id, $degreeName, $institution, $year);
                        $degreeStmt->execute();
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
                    profile_image = COALESCE(?, profile_image)
                    WHERE id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssssi",
                    $phone,
                    $address,
                    $specialty,
                    $work_address,
                    $available_consultation_hours,
                    $date_of_birth,
                    $gender,
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
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-dark: #21867a;
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
            padding: 2rem;
        }

        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .edit-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .edit-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }

        .edit-header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .edit-form {
            padding: 2.5rem 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.2);
        }

        .image-upload {
            text-align: center;
            margin: 2rem 0;
        }

        .current-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .current-image:hover {
            transform: scale(1.05);
        }

        .file-input {
            display: none;
        }

        .upload-label {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-label:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 157, 143, 0.3);
        }

        .btn-secondary {
            background: #f0f4f8;
            color: var(--text);
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fca5a5;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .edit-container {
                margin: 1rem;
                border-radius: 1rem;
            }
        }
        
        .degree-entry {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .btn-remove {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
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
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h1>Update Your Profile</h1>
        </div>

        <form class="edit-form" method="POST" enctype="multipart/form-data">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="image-upload">
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         class="current-image"
                         onclick="document.getElementById('fileInput').click()">
                <?php endif; ?>
                <input type="file" name="profile_image" id="fileInput" class="file-input">
                <label for="fileInput" class="upload-label">
                    📸 Change Photo
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-input">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <?php if ($user['role'] === 'doctor'): ?>
                    <div class="form-group">
                        <label class="form-label">Specialty</label>
                        <input type="text" name="specialty" class="form-input" value="<?php echo htmlspecialchars($user['specialty']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Work Address</label>
                        <textarea name="work_address" class="form-input"><?php echo htmlspecialchars($user['work_address']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Degrees</label>
                        <div id="degrees-container">
                            <?php foreach ($degrees as $index => $degree): ?>
                                <div class="degree-entry">
                                    <input type="text" name="degree_name[]" class="form-input" placeholder="Degree Name" 
                                           value="<?= htmlspecialchars($degree['degree_name']) ?>" required>
                                    <input type="text" name="institution[]" class="form-input" placeholder="Institution" 
                                           value="<?= htmlspecialchars($degree['institution']) ?>" required>
                                    <input type="number" name="passing_year[]" class="form-input" placeholder="Year" 
                                           value="<?= $degree['passing_year'] ?>" required>
                                    <button type="button" class="btn-remove" onclick="removeDegree(this)">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addDegreeField()">
                            + Add Degree
                        </button>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Medical History</label>
                        <textarea name="medical_history" class="form-input"><?php echo htmlspecialchars($user['medical_history']); ?></textarea>
                    </div>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <a href="profile.php" class="btn btn-secondary">← Done</a>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const [file] = e.target.files;
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.current-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        function addDegreeField() {
            const container = document.getElementById('degrees-container');
            const entry = document.createElement('div');
            entry.className = 'degree-entry';
            entry.innerHTML = `
                <input type="text" name="degree_name[]" class="form-input" placeholder="Degree Name" required>
                <input type="text" name="institution[]" class="form-input" placeholder="Institution" required>
                <input type="number" name="passing_year[]" class="form-input" placeholder="Year" required>
                <button type="button" class="btn-remove" onclick="removeDegree(this)">×</button>
            `;
            container.appendChild(entry);
        }

        function removeDegree(button) {
            button.parentElement.remove();
        }
    </script>
</body>
</html>