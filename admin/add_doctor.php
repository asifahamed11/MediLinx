<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Connect to database
$conn = connectDB();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Username, email, and password are required");
        }

        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param('ss', $_POST['username'], $_POST['email']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            throw new Exception("Username or email already exists");
        }

        // Start transaction
        $conn->begin_transaction();

        // Hash password
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insert new doctor
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role, specialty, 
            medical_license_number, years_of_experience, languages_spoken, work_address, 
            consultation_hours, professional_biography, created_at) 
            VALUES (?, ?, ?, 'doctor', ?, ?, ?, ?, ?, ?, ?, NOW())");

        $insert_stmt->bind_param(
            'sssssssss',
            $_POST['username'],
            $_POST['email'],
            $password_hash,
            $_POST['specialty'],
            $_POST['medical_license_number'],
            $_POST['years_of_experience'],
            $_POST['languages_spoken'],
            $_POST['work_address'],
            $_POST['consultation_hours'],
            $_POST['professional_biography']
        );

        $insert_stmt->execute();
        $doctor_id = $conn->insert_id;

        // Handle profile image if uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $target_dir = "../uploads/doctors/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = "doctor_" . $doctor_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $file_name;

            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Update profile image in database
                $img_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $image_path = "uploads/doctors/" . $file_name;
                $img_stmt->bind_param('si', $image_path, $doctor_id);
                $img_stmt->execute();
            } else {
                throw new Exception("Failed to upload profile image");
            }
        }

        // Add degrees if provided
        if (isset($_POST['degree_name']) && is_array($_POST['degree_name'])) {
            $degree_insert = $conn->prepare("INSERT INTO degrees (doctor_id, degree_name, institution, passing_year) VALUES (?, ?, ?, ?)");

            for ($i = 0; $i < count($_POST['degree_name']); $i++) {
                if (!empty($_POST['degree_name'][$i]) && !empty($_POST['institution'][$i])) {
                    $degree_insert->bind_param(
                        'issi',
                        $doctor_id,
                        $_POST['degree_name'][$i],
                        $_POST['institution'][$i],
                        $_POST['passing_year'][$i]
                    );
                    $degree_insert->execute();
                }
            }
        }

        // Commit transaction
        $conn->commit();
        $success_message = "Doctor added successfully!";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor - Medilinx Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #7f8c8d;
            color: white;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .degree-container {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .degree-form {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .degree-form:last-child {
            border-bottom: none;
        }

        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=doctors" class="back-link">&larr; Back to Doctors</a>
        <h1>Add New Doctor</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <h3>Basic Information</h3>
            <div class="form-group">
                <label for="username" class="required-field">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email" class="required-field">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password" class="required-field">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="specialty">Specialty</label>
                <input type="text" id="specialty" name="specialty">
            </div>

            <div class="form-group">
                <label for="medical_license_number">Medical License Number</label>
                <input type="text" id="medical_license_number" name="medical_license_number">
            </div>

            <div class="form-group">
                <label for="years_of_experience">Years of Experience</label>
                <input type="number" id="years_of_experience" name="years_of_experience" min="0" value="0">
            </div>

            <div class="form-group">
                <label for="languages_spoken">Languages Spoken</label>
                <input type="text" id="languages_spoken" name="languages_spoken" placeholder="e.g. English, Spanish">
            </div>

            <div class="form-group">
                <label for="work_address">Work Address</label>
                <textarea id="work_address" name="work_address"></textarea>
            </div>

            <div class="form-group">
                <label for="consultation_hours">Consultation Hours</label>
                <textarea id="consultation_hours" name="consultation_hours" placeholder="e.g. Mon-Fri: 9:00 AM - 5:00 PM"></textarea>
            </div>

            <div class="form-group">
                <label for="professional_biography">Professional Biography</label>
                <textarea id="professional_biography" name="professional_biography"></textarea>
            </div>

            <div class="form-group">
                <label for="profile_image">Profile Image</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
            </div>

            <h3>Education</h3>
            <div id="degrees-container">
                <div class="degree-form">
                    <div class="form-group">
                        <label>Degree</label>
                        <input type="text" name="degree_name[]">
                    </div>
                    <div class="form-group">
                        <label>Institution</label>
                        <input type="text" name="institution[]">
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="passing_year[]" min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                    <button type="button" class="btn btn-danger remove-degree">Remove</button>
                </div>
            </div>

            <button type="button" id="add-degree" class="btn btn-secondary">Add Degree</button>

            <hr style="margin: 20px 0;">

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Add Doctor</button>
                <a href="admin.php?tab=doctors" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Add degree form
        document.getElementById('add-degree').addEventListener('click', function() {
            const container = document.getElementById('degrees-container');
            const newDegree = document.createElement('div');
            newDegree.className = 'degree-form';
            newDegree.innerHTML = `
                <div class="form-group">
                    <label>Degree</label>
                    <input type="text" name="degree_name[]">
                </div>
                <div class="form-group">
                    <label>Institution</label>
                    <input type="text" name="institution[]">
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="passing_year[]" min="1900" max="${new Date().getFullYear()}">
                </div>
                <button type="button" class="btn btn-danger remove-degree">Remove</button>
            `;
            container.appendChild(newDegree);

            // Add event listener to new remove button
            newDegree.querySelector('.remove-degree').addEventListener('click', function() {
                container.removeChild(newDegree);
            });
        });

        // Remove degree form
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-degree')) {
                const degreeForm = e.target.parentNode;
                degreeForm.parentNode.removeChild(degreeForm);
            }
        });
    </script>
</body>

</html>