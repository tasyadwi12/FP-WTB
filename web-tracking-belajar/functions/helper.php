<?php
/**
 * Helper Functions - FIXED VERSION
 * File: functions/helper.php
 * General utility functions
 */

// ============================================
// REDIRECT FUNCTIONS
// ============================================

/**
 * Redirect to URL
 */
function redirect($url) {
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        } else {
            echo '<script>window.location.href="' . $url . '";</script>';
            exit;
        }
    }
    
    $full_url = BASE_URL . $url;
    
    if (!headers_sent()) {
        header('Location: ' . $full_url);
        exit;
    } else {
        echo '<script>window.location.href="' . $full_url . '";</script>';
        exit;
    }
}

/**
 * Redirect Back
 */
function redirectBack() {
    $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
    redirect($referer);
}

// ============================================
// STRING FUNCTIONS
// ============================================

/**
 * Clean Input (XSS Prevention)
 */
if (!function_exists('clean')) {
    function clean($data) {
        if (is_array($data)) {
            return array_map('clean', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Sanitize String - REMOVED DUPLICATE
 * Note: Fungsi sanitize() sudah ada di database.php
 * Kita hanya perlu e() untuk output escaping
 */

/**
 * Escape HTML Output
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Generate Slug
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate String
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $suffix;
}

/**
 * Generate Random String
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// ============================================
// DATE & TIME FUNCTIONS - FIXED
// ============================================

/**
 * Format Date - FIXED
 */
function formatDate($date, $format = null) {
    if (empty($date) || $date === '0000-00-00' || $date === null) {
        return '-';
    }
    $format = $format ?? DATE_FORMAT;
    return date($format, strtotime($date));
}

/**
 * Format DateTime - FIXED
 */
function formatDateTime($datetime, $format = null) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === null) {
        return '-';
    }
    $format = $format ?? DATETIME_FORMAT;
    return date($format, strtotime($datetime));
}

/**
 * Format Time - FIXED
 */
function formatTime($time, $format = null) {
    if (empty($time) || $time === null) {
        return '-';
    }
    $format = $format ?? TIME_FORMAT;
    return date($format, strtotime($time));
}

/**
 * Time Ago - FIXED
 */
function timeAgo($datetime) {
    // Check for null or empty values
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === null) {
        return 'Tidak ada data';
    }
    
    $timestamp = strtotime($datetime);
    
    // Check if timestamp is valid
    if ($timestamp === false) {
        return 'Tidak ada data';
    }
    
    $difference = time() - $timestamp;
    
    // If negative time (future date)
    if ($difference < 0) {
        return 'Baru saja';
    }
    
    $periods = [
        'tahun' => 31536000,
        'bulan' => 2592000,
        'minggu' => 604800,
        'hari' => 86400,
        'jam' => 3600,
        'menit' => 60,
        'detik' => 1
    ];
    
    foreach ($periods as $key => $value) {
        if ($difference >= $value) {
            $time = floor($difference / $value);
            return $time . ' ' . $key . ' yang lalu';
        }
    }
    
    return 'Baru saja';
}

/**
 * Get Current Date
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Get Current DateTime
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

// ============================================
// NUMBER FUNCTIONS
// ============================================

/**
 * Format Number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Format Currency (IDR)
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format Percentage
 */
function formatPercentage($value, $total) {
    if ($total == 0) return '0%';
    $percentage = ($value / $total) * 100;
    return number_format($percentage, 1) . '%';
}

/**
 * Calculate Percentage
 */
function calculatePercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 1);
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate Email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Phone
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10,13}$/', $phone);
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check if Request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if Request is GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Check if Request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

/**
 * Upload File
 */
function uploadFile($file, $destination = 'uploads/', $allowed_types = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File terlalu besar (max 5MB)'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $allowed = $allowed_types ?? ALLOWED_EXTENSIONS;
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $destination . $filename;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

/**
 * Delete File
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get File Size
 */
function getFileSize($filepath) {
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    return '0 B';
}

// ============================================
// ARRAY FUNCTIONS
// ============================================

/**
 * Get Array Value
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Array to Select Options
 */
function arrayToOptions($array, $selected = null) {
    $html = '';
    foreach ($array as $value => $label) {
        $isSelected = ($value == $selected) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '" ' . $isSelected . '>' . htmlspecialchars($label) . '</option>';
    }
    return $html;
}

// ============================================
// PAGINATION FUNCTIONS
// ============================================

/**
 * Generate Pagination
 */
function generatePagination($total_items, $items_per_page, $current_page, $base_url) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// ============================================
// DEBUG FUNCTIONS
// ============================================

/**
 * Debug Print
 */
function dd($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Dump Variable
 */
function dump($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}

// ============================================
// RESPONSE FUNCTIONS
// ============================================

/**
 * JSON Response
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Success Response
 */
function successResponse($message, $data = null) {
    jsonResponse(true, $message, $data);
}

/**
 * Error Response
 */
function errorResponse($message, $data = null) {
    jsonResponse(false, $message, $data);
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Hash Password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify Password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate Token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// ============================================
// STATUS BADGE FUNCTIONS
// ============================================

/**
 * Get Status Badge
 */
function getStatusBadge($status) {
    $badges = [
        'belum_mulai' => '<span class="badge bg-secondary">Belum Mulai</span>',
        'sedang_dipelajari' => '<span class="badge bg-primary">Sedang Dipelajari</span>',
        'selesai' => '<span class="badge bg-success">Selesai</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'in_progress' => '<span class="badge bg-info">In Progress</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        'active' => '<span class="badge bg-success">Aktif</span>',
        'inactive' => '<span class="badge bg-danger">Nonaktif</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get Role Badge
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="badge bg-danger">Admin</span>',
        'mentor' => '<span class="badge bg-primary">Mentor</span>',
        'siswa' => '<span class="badge bg-info">Siswa</span>',
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">' . ucfirst($role) . '</span>';
}

/**
 * Get Progress Color
 */
function getProgressColor($percentage) {
    if ($percentage >= 80) return 'success';
    if ($percentage >= 50) return 'warning';
    return 'danger';
}

// ============================================
// FLASH MESSAGE FUNCTIONS
// ============================================

/**
 * Set Flash Message
 */
function setFlash($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Check if Flash Message exists
 */
function hasFlash() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['flash']);
}

/**
 * Get Flash Message and clear it
 */
function getFlash() {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (hasFlash()) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// FORMAT DURATION FUNCTION
// ============================================

/**
 * Format Duration (minutes to hours)
 */
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' menit';
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($mins == 0) {
        return $hours . ' jam';
    }
    
    return $hours . ' jam ' . $mins . ' menit';
}
/**
 * Format Video Duration (seconds to MM:SS or HH:MM:SS)
 * Khusus untuk tampilan durasi video YouTube
 */
function formatVideoDuration($seconds) {
    if ($seconds <= 0) return '0:00';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%d:%02d:%02d", $hours, $minutes, $secs);
    } else {
        return sprintf("%d:%02d", $minutes, $secs);
    }
}

/**
 * Format tanggal Indonesia - ADDITIONAL
 */
if (!function_exists('formatTanggalIndo')) {
    function formatTanggalIndo($date, $with_time = false) {
        if (!$date || empty($date) || $date === '0000-00-00') return '-';
        
        $bulan = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return '-';
        
        $tgl = date('d', $timestamp);
        $bln = $bulan[(int)date('m', $timestamp)];
        $thn = date('Y', $timestamp);
        
        $result = $tgl . ' ' . $bln . ' ' . $thn;
        
        if ($with_time) {
            $result .= ' ' . date('H:i', $timestamp);
        }
        
        return $result;
    }
}

/**
 * Get activity icon based on activity type - ADDITIONAL
 */
if (!function_exists('getActivityIcon')) {
    function getActivityIcon($activity) {
        $activity_lower = strtolower($activity ?? '');
        
        if (strpos($activity_lower, 'baca') !== false || strpos($activity_lower, 'membaca') !== false) {
            return 'fa-book-reader';
        } elseif (strpos($activity_lower, 'video') !== false || strpos($activity_lower, 'menonton') !== false) {
            return 'fa-video';
        } elseif (strpos($activity_lower, 'latihan') !== false || strpos($activity_lower, 'praktik') !== false) {
            return 'fa-pen';
        } elseif (strpos($activity_lower, 'quiz') !== false || strpos($activity_lower, 'ujian') !== false) {
            return 'fa-clipboard-check';
        } elseif (strpos($activity_lower, 'diskusi') !== false) {
            return 'fa-comments';
        } else {
            return 'fa-book';
        }
    }
}
?>