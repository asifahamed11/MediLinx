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
        from {
            transform: translateY(-100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
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
        0% {
            box-shadow: 0 0 0 0 rgba(42, 157, 143, 0.6);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(42, 157, 143, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(42, 157, 143, 0);
        }
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
        0% {
            box-shadow: 0 0 0 0 rgba(231, 111, 81, 0.7);
        }

        70% {
            box-shadow: 0 0 0 8px rgba(231, 111, 81, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(231, 111, 81, 0);
        }
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

    /* Dropdown styles */
    .dropdown {
        position: relative;
    }

    .dropdown-toggle {
        position: relative;
    }

    .dropdown-toggle::after {
        content: '';
        display: inline-block;
        margin-left: 0.3em;
        vertical-align: middle;
        border-top: 0.3em solid;
        border-right: 0.3em solid transparent;
        border-bottom: 0;
        border-left: 0.3em solid transparent;
        position: relative;
        top: 0.1em;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        display: none;
        min-width: 220px;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        animation: dropdownFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: top center;
        overflow: hidden;
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        clear: both;
        font-weight: 500;
        color: var(--secondary);
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: rgba(42, 157, 143, 0.08);
        color: var(--primary);
        transform: translateX(3px);
    }

    .dropdown-item.active {
        background-color: rgba(42, 157, 143, 0.12);
        color: var(--primary);
        font-weight: 600;
    }

    .dropdown-item i {
        color: var(--primary);
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }

    .dropdown-item:hover i {
        transform: scale(1.1);
    }

    @media (max-width: 768px) {
        .dropdown-menu {
            position: static;
            box-shadow: none;
            width: 100%;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
        }

        .dropdown-item {
            color: white;
            padding: 0.75rem 1rem;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .dropdown-item i {
            color: rgba(255, 255, 255, 0.8);
        }

        .dropdown-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="posts.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Posts</span>
            </a>

            <div class="dropdown">
                <a href="javascript:void(0)" class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['profile.php', 'appointments.php', 'manage_time_slots.php', 'create_post.php']) ? 'active' : ''; ?>" onclick="toggleProfileDropdown()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Profile</span>
                </a>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="appointments.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                    <?php if ($_SESSION['role'] == 'patient'): ?>
                        <a href="calendar_booking.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'calendar_booking.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-plus"></i> Book Appointment
                        </a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'doctor'): ?>
                        <a href="manage_time_slots.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_time_slots.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Manage Time Slots
                        </a>
                        <a href="create_post.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'active' : ''; ?>">
                            <i class="fas fa-edit"></i> Create Post
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="notifications.php" class="nav-link notification-container <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <div class="notification-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="nav-icon">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if ($unread_count > 0): ?>
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

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function() {
                navLinks.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Close profile dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });
    });

    // Profile dropdown toggle
    function toggleProfileDropdown() {
        document.getElementById('profileDropdown').classList.toggle('show');
    }
</script>