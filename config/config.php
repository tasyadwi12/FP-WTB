<?php
/**
 * Application Configuration
 * File: config/config.php
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Web Tracking Belajar');
}

// ============================================
// APPLICATION SETTINGS
// ============================================

// Base URL (sesuaikan dengan folder project Anda)
define('BASE_URL', 'http://localhost/web-tracking-belajar/');

// Folder Paths
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('ASSETS_PATH', BASE_URL . 'assets/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// ============================================
// SESSION SETTINGS
// ============================================

define('SESSION_NAME', 'tracking_session');
define('SESSION_LIFETIME', 3600 * 24); // 24 jam

// ============================================
// SECURITY SETTINGS
// ============================================

define('SECURE_KEY', 'your_secret_key_here_change_this'); // Ganti dengan random string
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 menit

// ============================================
// PAGINATION SETTINGS
// ============================================

define('ITEMS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// ============================================
// DATE & TIME SETTINGS
// ============================================

date_default_timezone_set('Asia/Jakarta');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('TIME_FORMAT', 'H:i');

// ============================================
// EMAIL SETTINGS (Optional - untuk fitur email)
// ============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_password');
define('SMTP_FROM', 'noreply@tracking.com');
define('SMTP_FROM_NAME', APP_NAME);

// ============================================
// ERROR SETTINGS
// ============================================

// Development Mode
define('DEBUG_MODE', true); // Set FALSE untuk production

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/error.log');
}

// ============================================
// ROLE CONSTANTS
// ============================================

define('ROLE_ADMIN', 'admin');
define('ROLE_MENTOR', 'mentor');
define('ROLE_SISWA', 'siswa');

// ============================================
// STATUS CONSTANTS
// ============================================

// Status Materi
define('STATUS_BELUM_MULAI', 'belum_mulai');
define('STATUS_SEDANG', 'sedang_dipelajari');
define('STATUS_SELESAI', 'selesai');

// Status Target
define('TARGET_PENDING', 'pending');
define('TARGET_PROGRESS', 'in_progress');
define('TARGET_COMPLETED', 'completed');
define('TARGET_CANCELLED', 'cancelled');

// ============================================
// HELPER CONSTANTS
// ============================================

define('SUCCESS', 'success');
define('ERROR', 'error');
define('WARNING', 'warning');
define('INFO', 'info');

// ============================================
// AUTO LOAD REQUIREMENTS
// ============================================

// Load Database Config
require_once ROOT_PATH . 'config/database.php';

// Load Session Config
require_once ROOT_PATH . 'config/session.php';

// Load Helper Functions
if (file_exists(ROOT_PATH . 'functions/helper.php')) {
    require_once ROOT_PATH . 'functions/helper.php';
}

// ============================================
// APPLICATION STARTUP
// ============================================

// Start Session
startSession();

// Initialize Database Connection
$db = Database::getInstance();

// Check if database is connected
if (!$db->testConnection()) {
    die('Database connection failed. Please check your configuration.');
}


?>