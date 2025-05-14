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
    header('Location: admin.php?tab=reviews');
    exit;
}

$review_id = intval($_GET['id']);

// Get review data
$query = "SELECT r.*, 
                 p.username as patient_name, p.email as patient_email,
                 d.username as doctor_name, d.specialty as doctor_specialty
          FROM reviews r
          JOIN users p ON r.patient_id = p.id
          JOIN users d ON r.doctor_id = d.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $review_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin.php?tab=reviews');
    exit;
}

$review = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Review - Medilinx Admin</title>
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

        h1,
        h2,
        h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
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

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h3 {
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .info-row {
            margin-bottom: 15px;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            min-width: 200px;
        }

        .info-value {
            flex-grow: 1;
        }

        .rating {
            font-size: 24px;
            color: #f39c12;
            margin-bottom: 15px;
        }

        .rating span {
            display: inline-block;
            margin-right: 2px;
        }

        .review-comment {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-top: 15px;
            font-style: italic;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: #3498db;
        }

        .btn-secondary {
            background-color: #7f8c8d;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .delete-form {
            display: inline-block;
        }

        .btn-danger {
            background-color: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="admin.php?tab=reviews" class="back-link">&larr; Back to Reviews</a>

        <h1>Review Details</h1>

        <div class="section">
            <h3>Review Information</h3>

            <div class="rating">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $review['rating']) {
                        echo '<span>★</span>';
                    } else {
                        echo '<span>☆</span>';
                    }
                }
                ?>
                <span style="font-size: 16px; color: #2c3e50; margin-left: 10px;"><?php echo $review['rating']; ?> out of 5</span>
            </div>

            <div class="review-comment">
                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
            </div>

            <div class="info-row" style="margin-top: 15px;">
                <div class="info-label">Date Posted:</div>
                <div class="info-value"><?php echo date('F j, Y h:i A', strtotime($review['created_at'])); ?></div>
            </div>
        </div>

        <div class="section">
            <h3>Doctor Information</h3>

            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($review['doctor_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Specialty:</div>
                <div class="info-value"><?php echo htmlspecialchars($review['doctor_specialty'] ?? 'Not specified'); ?></div>
            </div>

            <a href="view_doctor.php?id=<?php echo $review['doctor_id']; ?>" class="btn btn-primary">View Doctor Profile</a>
        </div>

        <div class="section">
            <h3>Patient Information</h3>

            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($review['patient_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($review['patient_email']); ?></div>
            </div>

            <a href="view_patient.php?id=<?php echo $review['patient_id']; ?>" class="btn btn-primary">View Patient Profile</a>
        </div>

        <div class="actions">
            <a href="admin.php?tab=reviews" class="btn btn-secondary">Back to Reviews</a>

            <form method="POST" action="admin.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this review?');">
                <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                <button type="submit" name="delete_review" class="btn btn-danger">Delete Review</button>
            </form>
        </div>
    </div>
</body>

</html>