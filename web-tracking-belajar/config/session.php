<?php
/**
 * Session Management - FIXED (No Redirect Loop)
 * File: config/session.php
 */

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

/**
 * Start Session
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically (every 5 minutes)
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 300) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
        
        return true;
    }
    return true;
}

/**
 * Destroy All Sessions - FIXED VERSION (No Regenerate Warning)
 */
function destroySession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    session_destroy();
    session_write_close();
}

/**
 * Logout User
 */
function logout() {
    destroySession();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - FIXED: No redirect loop
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Save requested URL untuk redirect setelah login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        
        header('Location: ' . BASE_URL . 'pages/auth/login.php');
        exit;
    }
}

/**
 * Require specific role - FIXED: Prevent redirect loop
 */
function requireRole($required_role) {
    // Pastikan user sudah login dulu
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'pages/auth/login.php');
        exit;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    
    // Kalau role tidak sesuai, redirect ke dashboard role user
    if ($user_role !== $required_role) {
        // IMPORTANT: Jangan redirect kalau sudah di dashboard yang benar!
        // Check current URL to prevent loop
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        $target_dashboard = 'pages/' . $user_role . '/dashboard.php';
        
        // Kalau belum di dashboard yang sesuai role, baru redirect
        if (strpos($current_path, $target_dashboard) === false) {
            header('Location: ' . BASE_URL . $target_dashboard);
            exit;
        }
    }
    
    // Kalau role sudah sesuai, lanjutkan eksekusi
    // TIDAK ADA REDIRECT di sini!
}

/**
 * Get User ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get Username
 */
function getUsername() {
    return $_SESSION['username'] ?? '';
}

/**
 * Get Full Name
 */
function getUserFullName() {
    return $_SESSION['full_name'] ?? 'User';
}

/**
 * Get User Email
 */
function getUserEmail() {
    return $_SESSION['email'] ?? '';
}

/**
 * Get User Role
 */
function getUserRole() {
    return $_SESSION['role'] ?? '';
}

/**
 * Get User Avatar
 */
function getUserAvatar() {
    $avatar = $_SESSION['avatar'] ?? null;
    
    if ($avatar && $avatar !== 'default-avatar.png' && file_exists(ROOT_PATH . 'uploads/avatars/' . $avatar)) {
        return BASE_URL . 'uploads/avatars/' . $avatar;
    }
    
    return BASE_URL . 'assets/img/default-avatar.png';
}

/**
 * Check if user has role
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

/**
 * Check if user is mentor
 */
function isMentor() {
    return hasRole(ROLE_MENTOR);
}

/**
 * Check if user is siswa
 */
function isSiswa() {
    return hasRole(ROLE_SISWA);
}

// Start session automatically
startSession();
?>