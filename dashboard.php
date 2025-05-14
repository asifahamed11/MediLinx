<?php
session_start();
require_once 'config.php';

// Verify user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Helper function to sanitize input
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$conn = connectDB();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION['user_id'];

// Get current user info
$user_stmt = $conn->prepare("SELECT role, username, profile_image FROM users WHERE id = ?");
if (!$user_stmt) {
    die("Preparation failed: " . $conn->error);
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Sanitize and validate filter parameters
$specialty = isset($_GET['specialty']) ? sanitizeInput($_GET['specialty']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$experience = isset($_GET['experience']) ? (int)$_GET['experience'] : '';
$rating = isset($_GET['rating']) ? (float)$_GET['rating'] : '';

// Get available specialties
$specialties_query = "SELECT DISTINCT specialty FROM users WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty";
$specialties_result = $conn->query($specialties_query);
$specialties = [];
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row['specialty'];
}

// Build doctor query
$query = "SELECT u.*,
COALESCE((SELECT AVG(rating) FROM reviews WHERE doctor_id = u.id), 0) as avg_rating,
(SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count,
GROUP_CONCAT(DISTINCT d.degree_name SEPARATOR ', ') as degrees
FROM users u
LEFT JOIN degrees d ON u.id = d.doctor_id
WHERE u.role = 'doctor'";

$params = [];
$types = '';

if (!empty($specialty)) {
    $query .= " AND u.specialty LIKE ?";
    $params[] = "%$specialty%";
    $types .= 's';
}

if (!empty($location)) {
    $query .= " AND u.work_address LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

if (!empty($experience)) {
    $query .= " AND u.years_of_experience >= ?";
    $params[] = $experience;
    $types .= 'i';
}

$query .= " GROUP BY u.id";

if (!empty($rating)) {
    $query .= " HAVING avg_rating >= ?";
    $params[] = $rating;
    $types .= 'd';
}

$query .= " ORDER BY avg_rating DESC, review_count DESC";

// Execute doctor query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #1D7A8C;
            --primary-light: #23949C;
            --primary-dark: #156573;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --secondary: #144E5A;
            --secondary-light: #1D7A8C;
            --accent: #E94F37;
            --accent-light: #FF6F59;
            --success: #27AE60;
            --light-bg: #F5F9FB;
            --card-bg: #FFFFFF;
            --text: #2D3B45;
            --text-light: #546E7A;
            --text-lighter: #90A4AE;
            --border: #E1EDF2;
            --shadow-sm: 0 2px 10px rgba(29, 122, 140, 0.05);
            --shadow-md: 0 4px 20px rgba(29, 122, 140, 0.1);
            --shadow-lg: 0 10px 30px rgba(29, 122, 140, 0.15);
            --shadow-xl: 0 15px 40px rgba(29, 122, 140, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 0.5rem;
            --radius-md: 1rem;
            --radius-lg: 1.5rem;
            --transition-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        body {
            background-color: var(--light-bg);
            background-image:
                radial-gradient(at 80% 0%, hsla(189, 65%, 90%, 0.3) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355, 65%, 90%, 0.2) 0px, transparent 50%),
                radial-gradient(at 80% 100%, hsla(176, 65%, 90%, 0.3) 0px, transparent 50%);
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        * {
            box-sizing: border-box;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
            font-weight: 700;
            letter-spacing: -0.02em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60%;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(29, 122, 140, 0.3);
        }

        .search-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow:
                0 10px 30px rgba(29, 122, 140, 0.1),
                0 1px 1px rgba(255, 255, 255, 0.5) inset,
                0 -1px 1px rgba(255, 255, 255, 0.3) inset;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .search-section:hover {
            box-shadow:
                0 15px 40px rgba(29, 122, 140, 0.15),
                0 1px 1px rgba(255, 255, 255, 0.5) inset,
                0 -1px 1px rgba(255, 255, 255, 0.3) inset;
            transform: translateY(-5px);
        }

        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
            z-index: 1;
        }

        .search-section::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 70%);
            opacity: 0;
            transform: translate(-30%, -30%);
            transition: opacity 0.8s ease;
            pointer-events: none;
            z-index: 0;
        }

        .search-section:hover::after {
            opacity: 0.4;
        }

        .search-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .search-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.8rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            text-shadow: 0 2px 5px rgba(29, 122, 140, 0.1);
            position: relative;
            display: inline-block;
        }

        .search-header h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            width: 40px;
            height: 3px;
            background: var(--primary-gradient);
            transform: translateX(-50%);
            border-radius: 3px;
        }

        .search-header p {
            color: var(--text-light);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 1.5rem auto 0;
            line-height: 1.6;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            position: relative;
            z-index: 1;
            margin-top: 2rem;
        }

        .search-group {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1.25rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s var(--transition-bounce);
            background: rgba(255, 255, 255, 0.8);
            color: var(--text);
            box-shadow: var(--shadow-sm), 0 1px 2px rgba(255, 255, 255, 0.5) inset;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(29, 122, 140, 0.2), 0 1px 2px rgba(255, 255, 255, 0.5) inset;
            outline: none;
            transform: translateY(-2px);
        }

        .search-input::placeholder {
            color: var(--text-lighter);
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .search-button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.25rem 2.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            box-shadow: 0 4px 15px rgba(29, 122, 140, 0.3), 0 1px 2px rgba(255, 255, 255, 0.3) inset;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            text-transform: uppercase;
        }

        .search-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
            transition: height 0.4s ease-in-out;
            z-index: -1;
        }

        .search-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.6);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.4s var(--transition-bounce);
            z-index: -1;
        }

        .search-button:hover::before {
            height: 100%;
        }

        .search-button:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .search-button:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 25px rgba(29, 122, 140, 0.4), 0 1px 2px rgba(255, 255, 255, 0.3) inset;
            letter-spacing: 0.5px;
        }

        .search-button:active {
            transform: translateY(-2px) scale(0.98);
        }

        .search-button i {
            font-size: 1.2rem;
            transition: all 0.4s var(--transition-bounce);
        }

        .search-button:hover i {
            transform: translateX(4px) scale(1.1) rotate(15deg);
            color: rgba(255, 255, 255, 0.9);
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .results-count {
            color: var(--text);
            font-size: 1.1rem;
        }

        .results-count span {
            color: var(--primary);
            font-weight: 600;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding: 0.5rem 0;
        }

        .doctor-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow:
                0 20px 30px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            transition: all 0.4s var(--transition-bounce);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.7);
            height: 100%;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .doctor-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow:
                0 30px 60px rgba(0, 0, 0, 0.1),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .doctor-image-container {
            position: relative;
            width: 100%;
            height: 260px;
            overflow: hidden;
        }

        .doctor-image-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 1;
            opacity: 0.8;
            transition: opacity 0.5s ease;
        }

        .doctor-card:hover .doctor-image-container::after {
            opacity: 1;
            height: 70%;
        }

        .doctor-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1s ease;
            transform-origin: center;
        }

        .doctor-card:hover .doctor-image {
            transform: scale(1.1);
        }

        .doctor-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 260px);
            position: relative;
            z-index: 2;
        }

        .doctor-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            width: 80%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(29, 122, 140, 0.2), transparent);
        }

        .doctor-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 3rem;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(233, 79, 55, 0.4);
            z-index: 2;
            animation: pulse 2s infinite;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0.7);
                transform: scale(0.95);
            }

            70% {
                box-shadow: 0 0 0 12px rgba(233, 79, 55, 0);
                transform: scale(1.05);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0);
                transform: scale(0.95);
            }
        }

        .doctor-specialty {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            position: relative;
            display: inline-block;
            padding-bottom: 6px;
        }

        .doctor-specialty::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 3px;
        }

        .doctor-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--secondary);
            letter-spacing: -0.5px;
            transition: all 0.3s var(--transition-bounce);
        }

        .doctor-card:hover .doctor-name {
            color: var(--primary);
            transform: translateY(-2px);
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1.5rem;
        }

        .doctor-rating i {
            color: #FFB400;
            text-shadow: 0 1px 3px rgba(255, 180, 0, 0.3);
        }

        .doctor-rating span {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-left: 0.35rem;
            font-weight: 500;
        }

        .doctor-review-count {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-left: auto;
            opacity: 0.8;
            background: rgba(29, 122, 140, 0.08);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            transition: all 0.3s ease;
        }

        .doctor-card:hover .doctor-review-count {
            background: rgba(29, 122, 140, 0.15);
        }

        .doctor-details {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 2rem;
            flex-grow: 1;
            padding-top: 0.5rem;
        }

        .doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: all 0.3s var(--transition-bounce);
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(29, 122, 140, 0.1);
        }

        .doctor-detail-item:last-child {
            border-bottom: none;
        }

        .doctor-detail-item:hover {
            color: var(--text);
            transform: translateX(5px);
        }

        .doctor-detail-item i {
            color: var(--primary);
            font-size: 1.1rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0.9;
            transition: all 0.3s var(--transition-bounce);
            background: rgba(29, 122, 140, 0.1);
            border-radius: 50%;
            padding: 1rem;
        }

        .doctor-detail-item:hover i {
            opacity: 1;
            transform: scale(1.1) rotate(5deg);
            background: rgba(29, 122, 140, 0.2);
            box-shadow: 0 0 0 4px rgba(29, 122, 140, 0.05);
        }

        .view-profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1.25rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 1rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(29, 122, 140, 0.3);
        }

        .view-profile-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        .view-profile-btn:hover::before {
            left: 100%;
        }

        .view-profile-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(29, 122, 140, 0.4);
        }

        .view-profile-btn i {
            transition: transform 0.4s var(--transition-bounce);
            font-size: 1.1rem;
        }

        .view-profile-btn:hover i {
            transform: translateX(6px);
        }

        .doctor-languages {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: -0.5rem;
            margin-bottom: 1.25rem;
        }

        .language-tag {
            background: rgba(29, 122, 140, 0.08);
            color: var(--primary);
            font-size: 0.85rem;
            padding: 0.35rem 0.85rem;
            border-radius: 2rem;
            transition: all 0.3s var(--transition-bounce);
            border: 1px solid rgba(29, 122, 140, 0.1);
        }

        .language-tag:hover {
            background: rgba(29, 122, 140, 0.15);
            transform: translateY(-3px);
            box-shadow: 0 3px 8px rgba(29, 122, 140, 0.1);
            border-color: rgba(29, 122, 140, 0.2);
        }

        .no-results {
            text-align: center;
            padding: 5rem 2rem;
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            box-shadow:
                0 20px 30px rgba(0, 0, 0, 0.05),
                0 1px 1px rgba(255, 255, 255, 0.7) inset;
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .no-results svg {
            width: 120px;
            height: 120px;
            color: var(--primary-light);
            margin-bottom: 2rem;
            opacity: 0.8;
            filter: drop-shadow(0 3px 6px rgba(29, 122, 140, 0.2));
            animation: floatAnimation 3s ease-in-out infinite;
        }

        @keyframes floatAnimation {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0);
            }
        }

        .no-results h3 {
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .no-results p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 2rem;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .applied-filters {
            background: rgba(29, 122, 140, 0.05);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .applied-filters p {
            margin: 0 0 1rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .applied-filters ul {
            margin: 0;
            padding: 0;
            list-style-type: none;
            text-align: left;
        }

        .applied-filters li {
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(29, 122, 140, 0.1);
            color: var(--text-light);
        }

        .applied-filters li:last-child {
            border-bottom: none;
        }

        .reset-search-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s var(--transition-bounce);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 8px 20px rgba(29, 122, 140, 0.3);
        }

        .reset-search-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        .reset-search-btn:hover::before {
            left: 100%;
        }

        .reset-search-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(29, 122, 140, 0.4);
        }

        .reset-search-btn i {
            transition: transform 0.4s var(--transition-bounce);
            font-size: 1.1rem;
        }

        .reset-search-btn:hover i {
            transform: rotate(360deg);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.5s ease;
        }

        .loading-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: pulseScale 2s infinite;
            position: relative;
            overflow: hidden;
        }

        .loading-content::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            transform: translateX(-100%) skewX(-20deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%) skewX(-20deg);
            }
        }

        @keyframes pulseScale {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            }

            50% {
                transform: scale(1.03);
                box-shadow: 0 20px 60px rgba(29, 122, 140, 0.2);
            }
        }

        .loading-spinner {
            width: 70px;
            height: 70px;
            border: 4px solid rgba(29, 122, 140, 0.1);
            border-radius: 50%;
            position: relative;
            margin-bottom: 1.5rem;
        }

        .loading-spinner::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: var(--primary);
            border-bottom-color: var(--primary-light);
            animation: spin 1.5s linear infinite;
        }

        .loading-spinner::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            bottom: 4px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-left-color: var(--accent);
            border-right-color: var(--accent-light);
            animation: spin 2s linear infinite reverse;
        }

        .loading-text {
            font-size: 1.25rem;
            color: var(--primary);
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .loading-text::after {
            content: '...';
            position: absolute;
            right: -20px;
            animation: ellipsis 1.5s infinite;
        }

        @keyframes ellipsis {
            0% {
                content: '.';
            }

            33% {
                content: '..';
            }

            66% {
                content: '...';
            }

            100% {
                content: '.';
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Suggestion Dropdown */
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            box-shadow: var(--shadow-md);
            z-index: 10;
            display: none;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .suggestion-item:hover {
            background: rgba(29, 122, 140, 0.08);
        }

        /* Filter Pills */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .filter-pill {
            background: rgba(29, 122, 140, 0.1);
            color: var(--primary);
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .filter-pill:hover {
            background: rgba(29, 122, 140, 0.2);
        }

        .filter-pill i {
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-pill i:hover {
            color: var(--accent);
            transform: scale(1.1);
        }

        /* Animation for doctor cards */
        .animate-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 160px;
            background-color: var(--secondary);
            color: white;
            text-align: center;
            border-radius: var(--radius-sm);
            padding: 0.5rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
            box-shadow: var(--shadow-md);
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--secondary) transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary-gradient);
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s var(--transition-bounce);
            box-shadow: 0 5px 15px rgba(29, 122, 140, 0.3);
            z-index: 99;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(20px);
        }

        .back-to-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(rgba(255, 255, 255, 0.2), transparent);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(29, 122, 140, 0.4);
        }

        .back-to-top:hover::before {
            opacity: 1;
        }

        .back-to-top i {
            font-size: 1.25rem;
            transition: transform 0.3s var(--transition-bounce);
        }

        .back-to-top:hover i {
            transform: translateY(-3px);
        }

        @media (max-width: 1200px) {
            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1.5rem 1rem;
            }

            .search-section {
                padding: 2rem 1.5rem;
            }

            .search-header h1 {
                font-size: 2rem;
            }

            .search-header p {
                font-size: 1rem;
            }

            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 1.5rem;
            }

            .doctor-image-container {
                height: 220px;
            }

            .doctor-content {
                padding: 1.5rem;
                height: calc(100% - 220px);
            }

            .doctor-name {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .search-header h1 {
                font-size: 1.75rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .doctor-card {
                max-width: 400px;
                margin: 0 auto;
            }

            .search-button {
                padding: 1rem 1.5rem;
            }

            .search-input {
                padding: 1rem 1.25rem;
            }

            .no-results {
                padding: 3rem 1.5rem;
            }

            .no-results svg {
                width: 80px;
                height: 80px;
            }

            .no-results h3 {
                font-size: 1.5rem;
            }

            .no-results p {
                font-size: 1rem;
            }
        }
    </style>

</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="dashboard-container">
        <h1 class="page-title">Find Your Doctor</h1>

        <div class="search-section">
            <div class="wave-bg"></div>

            <div class="search-header animate__animated animate__fadeIn">
                <h1>Find Your Healthcare Specialist</h1>
                <p>Connect with trusted medical professionals tailored to your healthcare needs</p>
            </div>

            <form class="search-form" method="GET" id="doctorSearchForm">
                <div class="search-group">
                    <input type="text"
                        name="specialty"
                        id="specialtyInput"
                        placeholder="Search by specialty (e.g., Cardiologist)"
                        class="search-input"
                        value="<?= htmlspecialchars($specialty) ?>"
                        autocomplete="off">
                    <div class="suggestions-dropdown" id="specialtySuggestions"></div>
                </div>

                <div class="search-group">
                    <input type="text"
                        name="location"
                        placeholder="Location (City or ZIP)"
                        class="search-input"
                        value="<?= htmlspecialchars($location) ?>">
                </div>

                <div class="search-group">
                    <select name="experience" class="search-input">
                        <option value="">Years of Experience</option>
                        <option value="5" <?= $experience == 5 ? 'selected' : '' ?>>5+ Years</option>
                        <option value="10" <?= $experience == 10 ? 'selected' : '' ?>>10+ Years</option>
                        <option value="15" <?= $experience == 15 ? 'selected' : '' ?>>15+ Years</option>
                        <option value="20" <?= $experience == 20 ? 'selected' : '' ?>>20+ Years</option>
                    </select>
                </div>

                <div class="search-group">
                    <select name="rating" class="search-input">
                        <option value="">Minimum Rating</option>
                        <option value="4.5" <?= $rating == 4.5 ? 'selected' : '' ?>>4.5 Stars</option>
                        <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>★★★★ & Up</option>
                        <option value="3.5" <?= $rating == 3.5 ? 'selected' : '' ?>>★★★½ & Up</option>
                        <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>★★★ & Up</option>
                    </select>
                </div>

                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                    Find Doctors
                </button>
            </form>

            <?php if (!empty($specialty) || !empty($location) || !empty($experience) || !empty($rating)): ?>
                <div class="active-filters">
                    <?php if (!empty($specialty)): ?>
                        <div class="filter-pill">
                            <span>Specialty: <?= htmlspecialchars($specialty) ?></span>
                            <i class="fas fa-times remove-filter" data-filter="specialty"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($location)): ?>
                        <div class="filter-pill">
                            <span>Location: <?= htmlspecialchars($location) ?></span>
                            <i class="fas fa-times remove-filter" data-filter="location"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($experience)): ?>
                        <div class="filter-pill">
                            <span><?= htmlspecialchars($experience) ?>+ Years Experience</span>
                            <i class="fas fa-times remove-filter" data-filter="experience"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($rating)): ?>
                        <div class="filter-pill">
                            <span>Rating: <?= htmlspecialchars($rating) ?>+</span>
                            <i class="fas fa-times remove-filter" data-filter="rating"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($doctors)): ?>
            <div class="results-info">
                <div class="results-count">Found <span><?= count($doctors) ?></span> healthcare professionals</div>
            </div>
        <?php endif; ?>

        <div class="doctors-grid">
            <?php if (!empty($doctors)): ?>
                <?php foreach ($doctors as $index => $doctor): ?>
                    <div class="doctor-card animate-card" style="animation-delay: <?= $index * 0.1 ?>s">
                        <?php if (($doctor['avg_rating'] ?? 0) >= 4.5): ?>
                            <div class="doctor-badge">Top Rated</div>
                        <?php endif; ?>

                        <div class="doctor-image-container">
                            <img src="<?= htmlspecialchars($doctor['profile_image'] ?: 'uploads/default_profile.png') ?>"
                                class="doctor-image"
                                alt="<?= htmlspecialchars($doctor['username']) ?>"
                                onerror="this.src='uploads/default_profile.png'">
                        </div>

                        <div class="doctor-content">
                            <div class="doctor-specialty">
                                <?= htmlspecialchars($doctor['specialty'] ?? 'General Practitioner') ?>
                            </div>

                            <h3 class="doctor-name"><?= htmlspecialchars($doctor['username']) ?></h3>

                            <div class="doctor-rating">
                                <?php
                                $rating = $doctor['avg_rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($rating >= $i):
                                        echo '<i class="fas fa-star"></i>';
                                    elseif ($rating >= $i - 0.5):
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    else:
                                        echo '<i class="far fa-star"></i>';
                                    endif;
                                endfor;
                                ?>
                                <span><?= number_format($doctor['avg_rating'] ?? 0, 1) ?></span>

                                <div class="doctor-review-count">
                                    <?= $doctor['review_count'] ?? 0 ?> reviews
                                </div>
                            </div>

                            <?php if (!empty($doctor['degrees'])): ?>
                                <div class="doctor-languages">
                                    <?php
                                    $degreeList = explode(', ', $doctor['degrees']);
                                    foreach ($degreeList as $degree):
                                        if (!empty(trim($degree))):
                                    ?>
                                            <span class="language-tag"><?= htmlspecialchars($degree) ?></span>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="doctor-details">
                                <?php if (!empty($doctor['work_address'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($doctor['work_address']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($doctor['years_of_experience'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-briefcase-medical"></i>
                                        <span><?= htmlspecialchars($doctor['years_of_experience']) ?> Years Experience</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($doctor['education'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?= htmlspecialchars($doctor['education']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="doctor-profile.php?id=<?= $doctor['id'] ?>" class="view-profile-btn">
                                View Profile
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3>No Doctors Found</h3>
                    <p>We couldn't find any healthcare professionals matching your criteria.</p>
                    <?php if (!empty($specialty) || !empty($location) || !empty($experience) || !empty($rating)): ?>
                        <div class="applied-filters">
                            <p>Applied filters:</p>
                            <ul>
                                <?php if (!empty($specialty)): ?>
                                    <li>Specialty: <?= htmlspecialchars($specialty) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($location)): ?>
                                    <li>Location: <?= htmlspecialchars($location) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($experience)): ?>
                                    <li>Minimum Experience: <?= htmlspecialchars($experience) ?>+ years</li>
                                <?php endif; ?>
                                <?php if (!empty($rating)): ?>
                                    <li>Minimum Rating: <?= htmlspecialchars($rating) ?>+ stars</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <a href="find-doctors.php" class="reset-search-btn">
                        <i class="fas fa-redo"></i>
                        Reset Search
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-to-top" id="backToTop">
            <i class="fas fa-arrow-up"></i>
        </div>

        <div class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-text">Searching for doctors...</div>
            </div>
        </div>
    </div>

    <script>
        // Show loading overlay on form submit
        document.getElementById('doctorSearchForm').addEventListener('submit', function() {
            const loadingOverlay = document.querySelector('.loading-overlay');
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.opacity = '0';

            // Trigger a reflow
            void loadingOverlay.offsetWidth;

            // Start fading in
            loadingOverlay.style.opacity = '1';
        });

        // Handle specialty autocomplete
        const specialtyInput = document.getElementById('specialtyInput');
        const specialtySuggestions = document.getElementById('specialtySuggestions');
        const specialties = <?= json_encode($specialties) ?>;

        specialtyInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            if (searchTerm.length < 2) {
                specialtySuggestions.style.display = 'none';
                return;
            }

            const matchingSpecialties = specialties.filter(specialty =>
                specialty.toLowerCase().includes(searchTerm)
            );

            if (matchingSpecialties.length > 0) {
                specialtySuggestions.innerHTML = '';
                matchingSpecialties.forEach(specialty => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = specialty;
                    div.addEventListener('click', function() {
                        specialtyInput.value = specialty;
                        specialtySuggestions.style.display = 'none';
                        document.getElementById('doctorSearchForm').submit();
                    });
                    specialtySuggestions.appendChild(div);
                });
                specialtySuggestions.style.display = 'block';
            } else {
                specialtySuggestions.style.display = 'none';
            }
        });

        // Close suggestions on click outside
        document.addEventListener('click', function(e) {
            if (e.target !== specialtyInput && !specialtySuggestions.contains(e.target)) {
                specialtySuggestions.style.display = 'none';
            }
        });

        // Handle removing filters
        document.querySelectorAll('.remove-filter').forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                const filterName = this.getAttribute('data-filter');
                document.querySelector(`[name="${filterName}"]`).value = '';
                document.getElementById('doctorSearchForm').submit();
            });
        });

        // Animation for doctor cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-card').forEach(card => {
            observer.observe(card);
        });

        // Back to top button
        const backToTopButton = document.getElementById('backToTop');

        if (backToTopButton) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('visible');
                } else {
                    backToTopButton.classList.remove('visible');
                }
            });

            backToTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    </script>
</body>

</html>