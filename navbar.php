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

// Fetch unread notifications count
$unread_query = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_query->bind_param("i", $_SESSION['user_id']);
$unread_query->execute();
$unread_result = $unread_query->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['count'];
?>
<style>
    :root {
        --navbar-height: 70px;
        --primary: #2A9D8F;
        --secondary: #264653;
        --accent: #E76F51;
        --light: #F8F9FA;
        --transition-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
    }

    .navbar {
        background: linear-gradient(135deg, var(--secondary) 0%, #1a2f38 100%);
        padding: 0 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        animation: navbarSlideDown 0.8s var(--transition-bounce);
    }

    @keyframes navbarSlideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
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
        transition: all 0.4s var(--transition-bounce);
        position: relative;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        overflow: hidden;
    }

    .nav-brand::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        transition: transform 0.6s var(--transition-smooth);
        transform: skewX(-15deg);
    }

    .nav-brand:hover {
        transform: translateX(3px) scale(1.05);
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
    }

    .nav-brand:hover::before {
        transform: skewX(-15deg) translateX(200%);
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
        padding: 0.75rem 0.5rem;
        transition: all 0.3s var(--transition-smooth);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        opacity: 0.85;
    }

    .nav-link svg {
        transition: transform 0.4s var(--transition-bounce);
    }

    .nav-link span {
        position: relative;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary);
        transition: width 0.4s var(--transition-bounce);
        transform-origin: left center;
        border-radius: 2px;
    }

    .nav-link:hover {
        opacity: 1;
        color: var(--light);
    }

    .nav-link:hover svg {
        transform: translateY(-3px) scale(1.15);
    }

    .nav-link:hover::after {
        width: 100%;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(42, 157, 143, 0.6); }
        70% { box-shadow: 0 0 0 10px rgba(42, 157, 143, 0); }
        100% { box-shadow: 0 0 0 0 rgba(42, 157, 143, 0); }
    }

    .nav-button {
        background: linear-gradient(135deg, var(--primary) 0%, #2AC8B8 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.4s var(--transition-bounce);
        box-shadow: 0 4px 15px rgba(42, 157, 143, 0.3);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .nav-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.7s var(--transition-smooth);
    }

    .nav-button:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(42, 157, 143, 0.5);
    }

    .nav-button:hover::before {
        left: 100%;
    }

    .nav-button:active {
        transform: translateY(-2px);
    }

    .nav-button svg {
        transition: transform 0.3s var(--transition-bounce);
    }

    .nav-button:hover svg {
        transform: rotate(90deg);
    }

    /* Notification Badge */
    .notification-container {
        position: relative;
    }

    .badge {
        position: absolute;
        top: -20px;
        right: 0px;
        background-color: var(--accent);
        color: white;
        border-radius: 50%;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        animation: badgePulse 2s infinite;
        box-shadow: 0 0 0 rgba(231, 111, 81, 0.4);
    }

    @keyframes badgePulse {
        0% { box-shadow: 0 0 0 0 rgba(231, 111, 81, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(231, 111, 81, 0); }
        100% { box-shadow: 0 0 0 0 rgba(231, 111, 81, 0); }
    }

    .notification-icon {
        position: relative;
        display: inline-flex;
    }

    .nav-icon {
        transition: all 0.3s ease;
    }

    /* Current page indicator */
    .nav-link.active {
        opacity: 1;
    }

    .nav-link.active::after {
        width: 100%;
        background: var(--accent);
        height: 3px;
    }

    .nav-link.active svg {
        color: var(--accent);
    }

    /* Mobile Menu */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.5rem;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .navbar {
            padding: 0 1rem;
        }

        .nav-container {
            position: relative;
        }

        .mobile-menu-toggle {
            display: block;
            z-index: 1100;
        }

        .nav-links {
            position: fixed;
            top: 0;
            right: -100%;
            width: 70%;
            height: 100vh;
            background: linear-gradient(135deg, #1a2f38 0%, var(--secondary) 100%);
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            transition: right 0.5s var(--transition-smooth);
            gap: 2rem;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }

        .nav-links.active {
            right: 0;
        }

        .nav-link {
            font-size: 1.2rem;
            text-align: center;
            width: 100%;
            justify-content: center;
        }

        .nav-link::after {
            bottom: -5px;
        }

        .nav-brand {
            font-size: 1.4rem;
            z-index: 1100;
        }

        .nav-button {
            width: 100%;
            justify-content: center;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            visibility: hidden;
            opacity: 0;
            transition: all 0.5s var(--transition-smooth);
            z-index: 999;
        }

        .overlay.active {
            visibility: visible;
            opacity: 1;
        }
    }
</style>

<div class="overlay" id="overlay"></div>

<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
            MediLinx
        </a>
        
        <button class="mobile-menu-toggle" id="menuToggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <div class="nav-links" id="navLinks">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Find Doctors</span>
            </a>
            
            <a href="posts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
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

            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profile</span>
            </a>

            <a href="notifications.php" class="nav-link notification-container <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                <div class="notification-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="nav-icon">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                <span>Notifications</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    const overlay = document.getElementById('overlay');
    
    menuToggle.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Change menu icon when opened
        if (navLinks.classList.contains('active')) {
            menuToggle.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>`;
        } else {
            menuToggle.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>`;
        }
    });
    
    // Close menu when clicking overlay
    overlay.addEventListener('click', function() {
        navLinks.classList.remove('active');
        overlay.classList.remove('active');
        menuToggle.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>`;
    });
    
    // Add entry animations for navbar items
    const navItems = document.querySelectorAll('.nav-link, .nav-button');
    navItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(-10px)';
        item.style.transition = `all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) ${0.1 + index * 0.1}s`;
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 100);
    });
    
    // Highlight current page in navbar
    const currentPage = window.location.pathname.split('/').pop();
    const pageNavLinks = document.querySelectorAll('.nav-link');
    
    pageNavLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>