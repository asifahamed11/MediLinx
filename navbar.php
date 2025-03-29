<?php
// navbar.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = connectDB();
$nav_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$nav_stmt->bind_param("i", $_SESSION['user_id']);
$nav_stmt->execute();
$nav_result = $nav_stmt->get_result();
$nav_user = $nav_result->fetch_assoc();
?>
<style>
    :root {
        --navbar-height: 70px;
        --primary: #2A9D8F;
        --secondary: #264653;
        --accent: #E76F51;
    }

    .navbar {
        background: linear-gradient(135deg, var(--secondary) 0%, #2a3b45 100%);
        padding: 0 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        animation: navbarSlide 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes navbarSlide {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: var(--navbar-height);
    }

    .nav-brand {
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: transform 0.3s ease;
    }

    .nav-brand:hover {
        transform: translateX(5px);
    }

    .nav-links {
        display: flex;
        gap: 2.5rem;
        align-items: center;
    }

    .nav-link {
        color: white;
        text-decoration: none;
        position: relative;
        padding: 0.5rem 0;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--primary);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-link:hover::after {
        width: 100%;
        left: 0;
    }

    .nav-button {
        background: linear-gradient(135deg, var(--primary) 0%, #2AC8B8 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
    }

    .nav-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(42, 157, 143, 0.4);
    }

    .nav-button:active {
        transform: translateY(0);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .nav-container {
            padding: 0 1rem;
        }

        .nav-links {
            gap: 1.5rem;
        }

        .nav-link span {
            display: none;
        }

        .nav-brand {
            font-size: 1.5rem;
        }

        .nav-button {
            padding: 0.6rem 1rem;
        }
    }
</style>


<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">

        MediLinx
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Find Doctors</span>
            </a>
            
            <a href="posts.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Posts</span>
            </a>

            <?php if($nav_user['role'] === 'doctor'): ?>
                <a href="create-post.php" class="nav-button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Create Post</span>
                </a>
            <?php endif; ?>

            <a href="profile.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profile</span>
            </a>

            <a href="logout.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>