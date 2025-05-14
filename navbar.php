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
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15),
            0 8px 30px rgba(0, 0, 0, 0.12);
        animation: navbarSlideDown 0.8s var(--transition-bounce);
    }

    /* Add subtle texture to navbar */
    .navbar::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.02' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.4;
        pointer-events: none;
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
        letter-spacing: -0.02em;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-brand svg {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        transition: all 0.4s var(--transition-bounce);
    }

    .nav-brand::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 97%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        transition: transform 0.6s var(--transition-smooth);
        transform: skewX(-15deg);
    }

    .nav-brand:hover {
        transform: translateX(3px) scale(1.05);
        text-shadow: 0 0 12px rgba(255, 255, 255, 0.7);
    }

    .nav-brand:hover svg {
        transform: rotate(-10deg) scale(1.1);
        filter: drop-shadow(0 2px 8px rgba(42, 157, 143, 0.5));
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
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    .nav-link svg {
        transition: transform 0.4s var(--transition-bounce);
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    }

    .nav-link span {
        position: relative;
        transition: all 0.3s var(--transition-bounce);
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
        box-shadow: 0 0 8px rgba(42, 157, 143, 0.5);
    }

    .nav-link:hover {
        opacity: 1;
        color: var(--light);
    }

    .nav-link:hover span {
        transform: translateY(-2px);
        text-shadow: 0 0 12px rgba(255, 255, 255, 0.4);
    }

    .nav-link:hover svg {
        transform: translateY(-3px) scale(1.15);
        filter: drop-shadow(0 3px 5px rgba(0, 0, 0, 0.3));
    }

    .nav-link:hover::after {
        width: 100%;
    }

    /* Profile icon container */
    .profile-icon-container {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2px;
        position: relative;
        transition: all 0.4s var(--transition-bounce);
        border-radius: 50%;
    }

    .profile-icon-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(42, 157, 143, 0.2);
        border-radius: 50%;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s var(--transition-smooth);
    }

    .dropdown-toggle:hover .profile-icon-container {
        transform: translateY(-2px);
    }

    .dropdown-toggle:hover .profile-icon-container::after,
    .dropdown-toggle.active .profile-icon-container::after {
        opacity: 1;
        transform: scale(1.2);
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
        box-shadow: 0 2px 6px rgba(231, 111, 81, 0.5);
    }

    @keyframes badgePulse {
        0% {
            box-shadow: 0 0 0 0 rgba(231, 111, 81, 0.7);
            transform: scale(0.95);
        }

        70% {
            box-shadow: 0 0 0 8px rgba(231, 111, 81, 0);
            transform: scale(1.05);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(231, 111, 81, 0);
            transform: scale(0.95);
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
        font-weight: 600;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .nav-link.active::after {
        background: linear-gradient(90deg, var(--accent), #F8A27A);
        height: 3px;
        width: 100%;
        box-shadow: 0 0 10px rgba(231, 111, 81, 0.7);
    }

    .nav-link.active svg {
        color: var(--accent);
        filter: drop-shadow(0 2px 4px rgba(231, 111, 81, 0.3));
        transform: translateY(-2px);
    }

    /* Mobile Menu */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.5rem;
        transition: all 0.3s var(--transition-bounce);
    }

    .mobile-menu-toggle svg {
        transition: all 0.3s var(--transition-bounce);
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    }

    .mobile-menu-toggle:hover svg {
        transform: scale(1.1);
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .navbar {
            padding: 0 1.25rem;
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
            width: 75%;
            height: 100vh;
            background: linear-gradient(135deg, #1a2f38 0%, var(--secondary) 100%);
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2rem;
            transition: right 0.5s var(--transition-smooth);
            gap: 2rem;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-links::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }

        .nav-links.active {
            right: 0;
            animation: mobileMenuSlideIn 0.5s var(--transition-bounce);
        }

        @keyframes mobileMenuSlideIn {
            from {
                transform: translateX(20px);
                opacity: 0.8;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
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
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        display: inline-block;
        margin-left: 0.3em;
        font-size: 0.75em;
        transition: all 0.3s var(--transition-bounce);
        position: relative;
        top: 0;
        opacity: 0.7;
    }

    .dropdown-toggle.active::after,
    .dropdown-toggle:hover::after {
        transform: translateY(3px);
        opacity: 1;
        text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        display: none;
        min-width: 220px;
        padding: 0.75rem 0;
        margin: 0.125rem 0 0;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15),
            0 5px 15px rgba(0, 0, 0, 0.08),
            0 0 0 1px rgba(0, 0, 0, 0.03);
        animation: dropdownFadeIn 0.4s cubic-bezier(0.25, 0.1, 0.25, 1.3);
        transform-origin: top center;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    @supports (backdrop-filter: blur(10px)) {
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-15px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @supports (filter: blur(5px)) {
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-15px) scale(0.98);
                filter: blur(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
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
        transition: all 0.3s var(--transition-bounce);
        opacity: 0;
        transform: translateY(-8px);
        position: relative;
        overflow: hidden;
    }

    .dropdown-item::before {
        content: '';
        position: absolute;
        left: -100%;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(42, 157, 143, 0.08), transparent);
        transition: left 0.5s var(--transition-smooth);
    }

    .dropdown-menu.show .dropdown-item {
        animation: dropdownItemFadeIn 0.4s forwards;
    }

    .dropdown-menu.show .dropdown-item:nth-child(1) {
        animation-delay: 0.05s;
    }

    .dropdown-menu.show .dropdown-item:nth-child(2) {
        animation-delay: 0.1s;
    }

    .dropdown-menu.show .dropdown-item:nth-child(3) {
        animation-delay: 0.15s;
    }

    .dropdown-menu.show .dropdown-item:nth-child(4) {
        animation-delay: 0.2s;
    }

    .dropdown-menu.show .dropdown-item:nth-child(5) {
        animation-delay: 0.25s;
    }

    @keyframes dropdownItemFadeIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-item:hover {
        background-color: rgba(42, 157, 143, 0.08);
        color: var(--primary);
        transform: translateX(5px);
        box-shadow: inset 3px 0 0 var(--primary);
    }

    .dropdown-item.active {
        background-color: rgba(42, 157, 143, 0.12);
        color: var(--primary);
        font-weight: 600;
        box-shadow: inset 3px 0 0 var(--accent);
    }

    .dropdown-item:hover::before {
        left: 100%;
    }

    .dropdown-item i {
        color: var(--primary);
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }

    .dropdown-item:hover i {
        transform: scale(1.15);
        color: var(--primary);
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
            backdrop-filter: none;
            border: 1px solid rgba(255, 255, 255, 0.05);
            animation: mobileDropdownFadeIn 0.3s ease;
        }

        @keyframes mobileDropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            color: white;
            padding: 0.75rem 1rem;
            opacity: 1;
            transform: none;
        }

        .dropdown-menu.show .dropdown-item {
            animation: none;
            opacity: 1;
            transform: none;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            box-shadow: none;
            transform: none;
        }

        .dropdown-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: none;
        }

        .dropdown-item i {
            color: rgba(255, 255, 255, 0.8);
        }

        .dropdown-toggle::after {
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-icon-container {
            padding: 0;
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
                <a href="javascript:void(0)" class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['profile.php', 'appointments.php', 'manage_time_slots.php', 'create_post.php']) ? 'active' : ''; ?>" onclick="toggleProfileDropdown(event)">
                    <div class="profile-icon-container">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
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

        // Initialize dropdowns
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        const profileDropdown = document.getElementById('profileDropdown');

        // Set initial state to ensure CSS works properly
        if (profileDropdown && profileDropdown.classList.contains('show')) {
            dropdownToggle.classList.add('active');
        }

        // Close profile dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                    const toggle = document.querySelector('.dropdown-toggle');
                    if (toggle) toggle.classList.remove('active');
                }
            }
        });
    });

    // Profile dropdown toggle
    function toggleProfileDropdown(event) {
        if (event) event.preventDefault();
        const dropdown = document.getElementById('profileDropdown');
        const dropdownToggle = document.querySelector('.dropdown-toggle');

        if (dropdown && dropdownToggle) {
            dropdown.classList.toggle('show');
            dropdownToggle.classList.toggle('active');
        }
    }
</script>