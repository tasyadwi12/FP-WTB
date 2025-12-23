<?php
/**
 * Header Template - Modern Green Soft Theme
 * File: includes/header.php
 */

// Pastikan sudah ada session
if (!isset($_SESSION)) {
    session_start();
}

// Get current user info
$current_user_id = getUserId();
$current_user_name = getUserFullName();
$current_user_role = getUserRole();

// Get user avatar - FIXED VERSION
$user_avatar = $_SESSION['avatar'] ?? null;
$avatar_url = ASSETS_PATH . 'img/default-avatar.png';

if ($user_avatar && file_exists(ROOT_PATH . 'uploads/avatars/' . $user_avatar)) {
    $avatar_url = BASE_URL . 'uploads/avatars/' . $user_avatar;
}

// Role display with modern colors
$role_label = '';
$role_badge = '';
$role_color = '';
switch ($current_user_role) {
    case 'admin':
        $role_label = 'Administrator';
        $role_badge = 'bg-gradient-danger';
        $role_color = '#dc3545';
        break;
    case 'mentor':
        $role_label = 'Mentor';
        $role_badge = 'bg-gradient-success';
        $role_color = '#10b981';
        break;
    case 'siswa':
        $role_label = 'Siswa';
        $role_badge = 'bg-gradient-primary';
        $role_color = '#10b981';
        break;
}

// Get current time for greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Selamat Pagi';
    $greeting_icon = '🌅';
} elseif ($hour < 15) {
    $greeting = 'Selamat Siang';
    $greeting_icon = '☀️';
} elseif ($hour < 18) {
    $greeting = 'Selamat Sore';
    $greeting_icon = '🌤️';
} else {
    $greeting = 'Selamat Malam';
    $greeting_icon = '🌙';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_PATH; ?>img/logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Main CSS Variables & Base Styles -->
    <style>
        :root {
            /* Green Soft Theme Colors */
            --primary-color: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary-color: #34d399;
            --accent-color: #6ee7b7;
            
            /* Neutral Colors */
            --dark-color: #0f172a;
            --dark-secondary: #1e293b;
            --gray-800: #1f2937;
            --gray-700: #374151;
            --gray-600: #4b5563;
            --gray-500: #6b7280;
            --gray-400: #9ca3af;
            --gray-300: #d1d5db;
            --gray-200: #e5e7eb;
            --gray-100: #f3f4f6;
            --gray-50: #f9fafb;
            
            /* Layout */
            --sidebar-width: 280px;
            --navbar-height: 70px;
            
            /* Effects */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            overflow-x: hidden;
            color: var(--gray-800);
            font-size: 15px;
            line-height: 1.6;
        }
        
        /* Navbar Top - Enhanced Modern Design */
        .navbar-top {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: var(--navbar-height);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            z-index: 1000;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
        
        .navbar-top::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--primary-color) 50%, transparent 100%);
            opacity: 0.3;
        }
        
        .navbar-top.sidebar-collapsed {
            left: 80px;
        }
        
        @media (max-width: 768px) {
            .navbar-top {
                left: 0;
            }
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .sidebar-toggle {
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(209, 250, 229, 0.5) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 11px;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-toggle::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-toggle:hover::before {
            opacity: 1;
        }
        
        .sidebar-toggle i {
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.25);
            border-color: var(--primary-color);
        }
        
        .sidebar-toggle:hover i {
            color: white;
        }
        
        .sidebar-toggle:active {
            transform: translateY(0);
        }
        
        .page-header-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            letter-spacing: -0.03em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-subtitle {
            font-size: 0.8rem;
            color: var(--gray-500);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .greeting-icon {
            font-size: 1rem;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* Current Time Display */
        .current-time {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(209, 250, 229, 0.3) 100%);
            border-radius: 10px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .current-time i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .time-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        /* User Profile Dropdown - Enhanced */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 14px 6px 6px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.03) 100%);
            border: 1.5px solid rgba(16, 185, 129, 0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .user-profile::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .user-profile:hover::before {
            opacity: 0.05;
        }
        
        .user-profile:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.2);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            position: relative;
            z-index: 1;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            position: relative;
            z-index: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.875rem;
        }
        
        .user-role {
            font-size: 0.7rem;
            margin-top: 2px;
        }
        
        .user-role .badge {
            padding: 3px 9px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.65rem;
            letter-spacing: 0.02em;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: 280px;
            margin-top: var(--navbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--navbar-height));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 80px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Modern Card Styles */
        .card {
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            background: white;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--gray-200);
            padding: 18px 22px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 22px;
        }
        
        /* Modern Buttons */
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }
        
        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .navbar-top {
            animation: slideInDown 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-top {
                left: 0;
                padding: 0 16px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 14px;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .page-subtitle {
                display: none;
            }
            
            .user-info {
                display: none;
            }
            
            .current-time {
                display: none;
            }
            
            .user-profile {
                padding: 4px;
                min-width: 40px;
            }
            
            .sidebar-toggle {
                width: 36px;
                height: 36px;
            }
        }
    </style>
    
    <!-- Include Dashboard Styles CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard-styles.css">
    
    <!-- Additional Custom CSS -->
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    
    <!-- Top Navbar - Enhanced Modern Design -->
    <nav class="navbar-top" id="navbar">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-header-info">
                <h1 class="page-title">
                    <?php echo $page_title ?? 'Dashboard'; ?>
                </h1>
                <div class="page-subtitle">
                    <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
                    <span><?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $current_user_name)[0]); ?>!</span>
                </div>
            </div>
        </div>
        
        <div class="navbar-right">
            <!-- Current Time Display -->
            <div class="current-time d-none d-md-flex">
                <i class="fas fa-clock"></i>
                <span class="time-text" id="currentTime"></span>
            </div>
            
            <!-- User Profile -->
            <div class="user-profile" title="Profile Menu">
                <img src="<?php echo $avatar_url; ?>" 
                     alt="Avatar" 
                     class="user-avatar"
                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($current_user_name); ?></span>
                    <span class="user-role">
                        <span class="badge <?php echo $role_badge; ?>"><?php echo $role_label; ?></span>
                    </span>
                </div>
            </div>
        </div>
    </nav>
    
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);
    </script>