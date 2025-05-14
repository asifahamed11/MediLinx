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

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: admin.php?tab=patients');
    exit;
}

$patient_id = intval($_GET['id']);
$success_message = '';
$error_message = '';

// Get patient data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=patients');
    exit;
}

$patient = $result->fetch_assoc();

// Get medical history
$medical_history = [];
$history_stmt = $conn->prepare("SELECT * FROM medical_history WHERE patient_id = ? ORDER BY date DESC");
if ($history_stmt) {
    $history_stmt->bind_param('i', $patient_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    if ($history_result) {
        $medical_history = $history_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        if (empty($_POST['username']) || empty($_POST['email'])) {
            throw new Exception("Username and email are required");
        }

        // Check if username or email exists but not for this patient
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->bind_param('ssi', $_POST['username'], $_POST['email'], $patient_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            throw new Exception("Username or email already exists");
        }

        // Start transaction
        $conn->begin_transaction();

        // Update patient profile
        $update_stmt = $conn->prepare("UPDATE users SET 
            username = ?, 
            email = ?, 
            full_name = ?, 
            date_of_birth = ?, 
            gender = ?, 
            phone = ?, 
            address = ?, 
            blood_type = ?, 
            emergency_contact = ? 
            WHERE id = ? AND role = 'patient'");

        $update_stmt->bind_param(
            'sssssssssi',
            $_POST['username'],
            $_POST['email'],
            $_POST['full_name'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['blood_type'],
            $_POST['emergency_contact'],
            $patient_id
        );

        $update_stmt->execute();

        // Update password if provided
        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pass_stmt->bind_param('si', $password_hash, $patient_id);
            $pass_stmt->execute();
        }

        // Handle profile image if uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $target_dir = "../uploads/patients/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = "patient_" . $patient_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $file_name;

            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Update profile image in database
                $img_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $image_path = "uploads/patients/" . $file_name;
                $img_stmt->bind_param('si', $image_path, $patient_id);
                $img_stmt->execute();
            } else {
                throw new Exception("Failed to upload profile image");
            }
        }

        // Check if medical_history table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'medical_history'");
        if ($table_check->num_rows == 0) {
            $conn->query("CREATE TABLE medical_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                condition_name VARCHAR(255) NOT NULL,
                details TEXT,
                date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }

        // Handle existing medical history: first delete existing history if delete=1 is set
        if (isset($_POST['history_id']) && is_array($_POST['history_id'])) {
            foreach ($_POST['history_id'] as $key => $id) {
                if (isset($_POST['delete_history'][$key]) && $_POST['delete_history'][$key] == 1) {
                    $delete_stmt = $conn->prepare("DELETE FROM medical_history WHERE id = ? AND patient_id = ?");
                    $delete_stmt->bind_param('ii', $id, $patient_id);
                    $delete_stmt->execute();
                } else if (!empty($id)) {
                    // Update existing history
                    $update_history = $conn->prepare("UPDATE medical_history SET 
                        condition_name = ?, 
                        details = ?, 
                        date = ? 
                        WHERE id = ? AND patient_id = ?");

                    $update_history->bind_param(
                        'sssii',
                        $_POST['existing_condition_name'][$key],
                        $_POST['existing_condition_details'][$key],
                        $_POST['existing_condition_date'][$key],
                        $id,
                        $patient_id
                    );

                    $update_history->execute();
                }
            }
        }

        // Add new medical history if provided
        if (isset($_POST['condition_name']) && is_array($_POST['condition_name'])) {
            $history_insert = $conn->prepare("INSERT INTO medical_history (patient_id, condition_name, details, date) VALUES (?, ?, ?, ?)");

            for ($i = 0; $i < count($_POST['condition_name']); $i++) {
                if (!empty($_POST['condition_name'][$i])) {
                    $history_insert->bind_param(
                        'isss',
                        $patient_id,
                        $_POST['condition_name'][$i],
                        $_POST['condition_details'][$i],
                        $_POST['condition_date'][$i]
                    );
                    $history_insert->execute();
                }
            }
        }

        // Commit transaction
        $conn->commit();
        $success_message = "Patient profile updated successfully!";

        // Refresh patient data
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        // Refresh medical history
        if ($history_stmt) {
            $history_stmt->execute();
            $history_result = $history_stmt->get_result();
            if ($history_result) {
                $medical_history = $history_result->fetch_all(MYSQLI_ASSOC);
            } else {
                $medical_history = [];
            }
        }
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
    <title>Edit Patient - Medilinx Admin</title>
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
        input[type="date"],
        input[type="tel"],
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

        .history-form {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .required-field::after {
            content: " *";
            color: #e74c3c;
        }

        h3 {
            color: #2c3e50;
            margin: 20px 0 15px 0;
        }

        .section {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .delete-checkbox {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }

        .delete-checkbox input {
            margin-right: 8px;
            width: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=patients" class="back-link">&larr; Back to Patients</a>
        <h1>Edit Patient</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="section">
                <h3>Account Information</h3>
                <div class="form-group">
                    <label for="username" class="required-field">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="required-field">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password">
                </div>
            </div>

            <div class="section">
                <h3>Personal Information</h3>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($patient['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($patient['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($patient['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($patient['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <select id="blood_type" name="blood_type">
                        <option value="">Select Blood Type</option>
                        <?php
                        $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach ($blood_types as $type) {
                            $selected = ($patient['blood_type'] ?? '') == $type ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" placeholder="Name and phone number" value="<?php echo htmlspecialchars($patient['emergency_contact'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="profile_image">Profile Image</label>
                    <?php if (!empty($patient['profile_image'])): ?>
                        <div>
                            <img src="../<?php echo htmlspecialchars($patient['profile_image']); ?>" alt="Current profile image" style="max-width: 150px; margin: 10px 0;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                </div>
            </div>

            <div class="section">
                <h3>Medical History</h3>

                <?php if (count($medical_history) > 0): ?>
                    <h4 style="margin-bottom: 15px;">Existing Medical History</h4>

                    <?php foreach ($medical_history as $index => $history): ?>
                        <div class="history-form">
                            <input type="hidden" name="history_id[]" value="<?php echo $history['id']; ?>">

                            <div class="form-group">
                                <label>Condition/Illness</label>
                                <input type="text" name="existing_condition_name[]" value="<?php echo htmlspecialchars($history['condition_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Details</label>
                                <textarea name="existing_condition_details[]"><?php echo htmlspecialchars($history['details']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Date Diagnosed</label>
                                <input type="date" name="existing_condition_date[]" value="<?php echo $history['date']; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="delete-checkbox">
                                <input type="checkbox" id="delete_history_<?php echo $index; ?>" name="delete_history[]" value="1">
                                <label for="delete_history_<?php echo $index; ?>" style="display: inline;">Delete this history record</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h4 style="margin: 20px 0 15px;">Add New Medical History</h4>
                <div id="medical-history-container">
                    <div class="history-form">
                        <div class="form-group">
                            <label>Condition/Illness</label>
                            <input type="text" name="condition_name[]">
                        </div>
                        <div class="form-group">
                            <label>Details</label>
                            <textarea name="condition_details[]"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date Diagnosed</label>
                            <input type="date" name="condition_date[]" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button type="button" class="btn btn-danger remove-history">Remove</button>
                    </div>
                </div>

                <button type="button" id="add-history" class="btn btn-secondary">Add More Medical History</button>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Add medical history form
        document.getElementById('add-history').addEventListener('click', function() {
            const container = document.getElementById('medical-history-container');
            const newHistory = document.createElement('div');
            newHistory.className = 'history-form';
            newHistory.innerHTML = `
                <div class="form-group">
                    <label>Condition/Illness</label>
                    <input type="text" name="condition_name[]">
                </div>
                <div class="form-group">
                    <label>Details</label>
                    <textarea name="condition_details[]"></textarea>
                </div>
                <div class="form-group">
                    <label>Date Diagnosed</label>
                    <input type="date" name="condition_date[]" max="${new Date().toISOString().split('T')[0]}">
                </div>
                <button type="button" class="btn btn-danger remove-history">Remove</button>
            `;
            container.appendChild(newHistory);

            // Add event listener to new remove button
            newHistory.querySelector('.remove-history').addEventListener('click', function() {
                container.removeChild(newHistory);
            });
        });

        // Remove medical history form
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-history')) {
                const historyForm = e.target.closest('.history-form');
                historyForm.parentNode.removeChild(historyForm);
            }
        });
    </script>
</body>

</html>