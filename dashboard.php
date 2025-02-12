<?php
session_start();
require_once 'config.php';

// Verify user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Helper function to sanitize input
function sanitizeInput($input) {
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
$languages = isset($_GET['languages']) ? array_map('sanitizeInput', (array)$_GET['languages']) : [];
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
          (SELECT COUNT(*) FROM reviews WHERE doctor_id = u.id) as review_count
          FROM users u 
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
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - MediLinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A9D8F;
            --primary-dark: #238177;
            --secondary: #264653;
            --accent: #E76F51;
            --light-bg: #f8f9fa;
            --text: #2d3748;
            --text-light: #718096;
            --gradient: linear-gradient(135deg, var(--primary) 0%, #2AC8B8 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: var(--light-bg);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-section {
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: var(--gradient);
        }

        .search-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .search-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .search-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            position: relative;
        }

        .search-group {
            position: relative;
        }

        .search-input {
            width: 85%;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(42, 157, 143, 0.1);
            border-radius: 1rem;
            font-size: 1rem;
            transition: var(--transition);
            background: rgba(42, 157, 143, 0.03);
            color: var(--text);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(42, 157, 143, 0.15);
            outline: none;
        }

        .search-input::placeholder {
            color: var(--text-light);
        }

        .search-button {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-dark) 0%,rgb(36, 175, 161) 100%);
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 1rem 0;
        }

        .doctor-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
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

        .doctor-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .doctor-card:hover .doctor-image {
            transform: scale(1.05);
        }

        .doctor-content {
            padding: 1.5rem;
        }

        .doctor-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .doctor-specialty {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .doctor-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0.5rem 0;
            color: var(--secondary);
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }

        .doctor-rating span {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .doctor-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .doctor-detail-item i {
            color: var(--primary);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .view-profile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 90%;
            padding: 1rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            margin-top: auto;
        }

        .view-profile-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%,rgb(32, 149, 137) 100%);
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .no-results svg {
            width: 80px;
            height: 80px;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .no-results h3 {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--primary);
            border-left-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .search-section {
                padding: 1.5rem;
            }

            .search-header h1 {
                font-size: 2rem;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .doctor-image-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="search-section">
            <div class="search-header">
                <h1>Find Your Healthcare Specialist</h1>
                <p>Connect with trusted medical professionals in your area</p>
            </div>
            
            <form class="search-form" method="GET">
                <div class="search-group">
                    <input type="text" 
                           name="specialty" 
                           id="specialtyInput" 
                           placeholder="Search by specialty (e.g., Cardiologist)" 
                           class="search-input" 
                           value="<?= htmlspecialchars($specialty) ?>" 
                           autocomplete="off">
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
                        <option value="4.5" <?= $rating == 4.5 ? 'selected' : '' ?>>★★★★½ & Up</option>
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
        </div>

        <div class="doctors-grid">
            <?php if (!empty($doctors)): ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-card">
                        <?php if (($doctor['avg_rating'] ?? 0) >= 4.5): ?>
                            <div class="doctor-badge">Top Rated</div>
                        <?php endif; ?>

                        <div class="doctor-image-container">
                            <img src="<?= htmlspecialchars($doctor['profile_image'] ?: 'uploads/default_profile.png') ?>" 
                                 class="doctor-image" 
                                 alt="Dr. <?= htmlspecialchars($doctor['username']) ?>"
                                 onerror="this.src='uploads/default_profile.png'">
                        </div>

                        <div class="doctor-content">
                            <div class="doctor-specialty">
                                <?= htmlspecialchars($doctor['specialty'] ?? 'General Practitioner') ?>
                            </div>

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
                                <span>(<?= number_format($doctor['avg_rating'] ?? 0, 1) ?>)</span>
                            </div>

                            <h3 class="doctor-name">Dr. <?= htmlspecialchars($doctor['username']) ?></h3>

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

                                <?php if (!empty($doctor['languages_spoken'])): ?>
                                    <div class="doctor-detail-item">
                                        <i class="fas fa-language"></i>
                                        <span><?= htmlspecialchars($doctor['languages_spoken']) ?></span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-4h2v2h-2v-2zm0-2V7h2v7h-2z"/>
                    </svg>
                    <h3>No Doctors Found</h3>
                    <p>Try adjusting your search criteria or expanding your search area</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        // Show loading overlay on form submit
        document.querySelector('form').addEventListener('submit', () => {
            document.querySelector('.loading-overlay').style.display = 'flex';
        });

        // Handle specialty autocomplete
        const specialtyInput = document.getElementById('specialtyInput');
        const specialties = <?= json_encode($specialties) ?>;
        
        specialtyInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const matchingSpecialties = specialties.filter(specialty => 
                specialty.toLowerCase().includes(searchTerm)
            );

            // Update datalist options
            const datalist = document.getElementById('specialties-list') || 
                           document.createElement('datalist');
            datalist.id = 'specialties-list';
            datalist.innerHTML = matchingSpecialties
                .map(specialty => `<option value="${specialty}">`)
                .join('');

            if (!document.getElementById('specialties-list')) {
                document.body.appendChild(datalist);
                specialtyInput.setAttribute('list', 'specialties-list');
            }
        });

        // Handle image loading errors
        document.querySelectorAll('.doctor-image').forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'uploads/default_profile.png';
                this.classList.add('image-error');
            });
        });
    </script>
</body>
</html>