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
        $consultation_hours = filter_input(INPUT_POST, 'consultation_hours', FILTER_SANITIZE_STRING);
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
            // Build update query based on user role
            if ($user['role'] === 'doctor') {
                $query = "UPDATE users SET 
                    phone = ?,
                    address = ?,
                    specialty = ?,
                    work_address = ?,
                    consultation_hours = ?,
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
                    $consultation_hours,
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
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MediLinx</title>
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

        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--glass);
            border-radius: 20px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .edit-header {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .edit-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .submit-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .cancel-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }

        .submit-button:hover, .cancel-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .file-input {
            margin-bottom: 1rem;
        }

        .current-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 4px solid white;
            box-shadow: var(--shadow);
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <h1>Edit Profile</h1>
        </div>

        <form class="edit-form" method="POST" enctype="multipart/form-data">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label class="form-label">Profile Picture</label>
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Current Profile Picture" class="current-image">
                <?php endif; ?>
                <input type="file" name="profile_image" accept="image/*" class="file-input">
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-input"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-input">
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
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
                    <label class="form-label">Consultation Hours</label>
                    <textarea name="consultation_hours" class="form-input"><?php echo htmlspecialchars($user['consultation_hours']); ?></textarea>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Medical History</label>
                    <textarea name="medical_history" class="form-input"><?php echo htmlspecialchars($user['medical_history']); ?></textarea>
                </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="profile.php" class="cancel-button">Cancel</a>
                <button type="submit" class="submit-button">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>