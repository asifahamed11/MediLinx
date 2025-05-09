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
        }

        body {
            background: var(--light-bg);
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
            font-size: 1.8rem;
            color: var(--secondary);
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
            font-weight: 600;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60%;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .search-section {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
            transition: var(--transition);
        }

        .search-section:hover {
            box-shadow: var(--shadow-xl);
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
            opacity: 0.3;
        }

        .search-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .search-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .search-header p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .search-group {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(29, 122, 140, 0.2);
            outline: none;
        }

        .search-input::placeholder {
            color: var(--text-lighter);
            font-size: 0.95rem;
        }

        .search-button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1rem;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .search-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            transition: width 0.4s ease-in-out;
            z-index: -1;
        }

        .search-button:hover::before {
            width: 100%;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .search-button:active {
            transform: translateY(0);
        }

        .search-button i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .search-button:hover i {
            transform: translateX(3px);
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
            gap: 2rem;
            padding: 0.5rem 0;
        }

        .doctor-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            border: 1px solid var(--border);
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .doctor-image-container {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
        }

        .doctor-image-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 1;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .doctor-card:hover .doctor-image-container::after {
            opacity: 1;
        }

        .doctor-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .doctor-card:hover .doctor-image {
            transform: scale(1.08);
        }

        .doctor-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 240px);
        }

        .doctor-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 3rem;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            z-index: 2;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(233, 79, 55, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(233, 79, 55, 0);
            }
        }

        .doctor-specialty {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .doctor-name {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--secondary);
            letter-spacing: -0.3px;
            transition: var(--transition);
        }

        .doctor-card:hover .doctor-name {
            color: var(--primary);
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1.25rem;
        }

        .doctor-rating i {
            color: #FFB400;
        }

        .doctor-rating span {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-left: 0.35rem;
        }

        .doctor-review-count {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-left: auto;
        }

        .doctor-details {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-bottom: 1.75rem;
            flex-grow: 1;
        }

        .doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .doctor-detail-item:hover {
            color: var(--text);
        }

        .doctor-detail-item i {
            color: var(--primary);
            font-size: 1rem;
            width: 22px;
            text-align: center;
            opacity: 0.9;
            transition: var(--transition);
        }

        .doctor-detail-item:hover i {
            opacity: 1;
            transform: scale(1.1);
        }

        .view-profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 1rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 122, 140, 0.3);
        }

        .view-profile-btn i {
            transition: transform 0.3s ease;
        }

        .view-profile-btn:hover i {
            transform: translateX(4px);
        }

        .doctor-languages {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
        }

        .language-tag {
            background: rgba(29, 122, 140, 0.08);
            color: var(--primary);
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            transition: var(--transition);
        }

        .language-tag:hover {
            background: rgba(29, 122, 140, 0.15);
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            animation: fadeIn 0.8s ease-out;
        }

        .no-results svg {
            width: 100px;
            height: 100px;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .no-results h3 {
            color: var(--secondary);
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .no-results p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 1.5rem;
            font-size: 1.1rem;
        }

        .reset-search-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--light-bg);
            color: var(--primary);
            border: 2px solid var(--primary-light);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .reset-search-btn:hover {
            background: var(--primary-light);
            color: white;
            transform: translateY(-2px);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(8px);
        }

        .loading-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(29, 122, 140, 0.1);
            border-left-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        .loading-text {
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 500;
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
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            z-index: 99;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
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
                padding: 1.75rem 1.25rem;
            }

            .search-header h1 {
                font-size: 2rem;
            }

            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 1.5rem;
            }

            .doctor-image-container {
                height: 200px;
            }
        }

        @media (max-width: 576px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .search-header h1 {
                font-size: 1.75rem;
            }

            .doctor-card {
                max-width: 400px;
                margin: 0 auto;
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
            document.querySelector('.loading-overlay').style.display = 'flex';
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