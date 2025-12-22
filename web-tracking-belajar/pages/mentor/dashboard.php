<?php
/**
 * Mentor Dashboard - Modern UI
 * File: pages/mentor/dashboard.php
 */

// Hanya define jika belum ada
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Web Tracking Belajar');
}

require_once '../../config/config.php';

// Check if user is logged in and is mentor
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit;
}

if (getUserRole() !== 'mentor') {
    header('Location: ' . BASE_URL . 'pages/' . getUserRole() . '/dashboard.php');
    exit;
}

// PENTING: Cek apakah header sudah di-include sebelumnya
if (!defined('HEADER_INCLUDED')) {
    define('HEADER_INCLUDED', true);
}

$mentor_id = getUserId();
$page_title = 'Dashboard Mentor';

// Get database connection
$db = getDB();

// Get Mentor Statistics
try {
    // Total siswa bimbingan
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM mentor_siswa WHERE mentor_id = :mentor_id");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $total_siswa = $stmt->fetch()['total'] ?? 0;
    
    // Siswa aktif (login 7 hari terakhir)
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ms.siswa_id) as aktif 
        FROM mentor_siswa ms
        INNER JOIN users u ON ms.siswa_id = u.user_id
        WHERE ms.mentor_id = :mentor_id 
        AND u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $siswa_aktif = $stmt->fetch()['aktif'] ?? 0;
    
    // Total penilaian yang diberikan
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM penilaian_mentor pm
        INNER JOIN progress_materi prog ON pm.progress_id = prog.progress_id
        INNER JOIN mentor_siswa ms ON prog.user_id = ms.siswa_id
        WHERE ms.mentor_id = :mentor_id
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $total_penilaian = $stmt->fetch()['total'] ?? 0;
    
    // Rata-rata progress siswa
    $stmt = $db->prepare("
        SELECT AVG(prog.persentase_selesai) as avg_progress
        FROM progress_materi prog
        INNER JOIN mentor_siswa ms ON prog.user_id = ms.siswa_id
        WHERE ms.mentor_id = :mentor_id
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $avg_progress = $stmt->fetch()['avg_progress'] ?? 0;
    
    $stats = [
        'total_siswa' => $total_siswa,
        'siswa_aktif' => $siswa_aktif,
        'total_penilaian' => $total_penilaian,
        'avg_siswa_progress' => round($avg_progress, 1)
    ];
    
} catch (Exception $e) {
    $stats = [
        'total_siswa' => 0,
        'siswa_aktif' => 0,
        'total_penilaian' => 0,
        'avg_siswa_progress' => 0
    ];
}

// Get Students List
try {
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.avatar,
            u.last_login,
            COUNT(DISTINCT pm.progress_id) as total_materi_progress,
            AVG(pm.persentase_selesai) as avg_progress
        FROM users u
        INNER JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        LEFT JOIN progress_materi pm ON u.user_id = pm.user_id
        WHERE ms.mentor_id = :mentor_id
        GROUP BY u.user_id
        ORDER BY u.last_login DESC
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $students = [];
}

// Generate Monthly Report
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT ms.siswa_id) as total_students,
            COUNT(DISTINCT pm.penilaian_id) as assessments_given,
            AVG(prog.persentase_selesai) as avg_student_progress
        FROM mentor_siswa ms
        LEFT JOIN progress_materi prog ON ms.siswa_id = prog.user_id
        LEFT JOIN penilaian_mentor pm ON prog.progress_id = pm.progress_id 
            AND MONTH(pm.created_at) = MONTH(CURRENT_DATE())
            AND YEAR(pm.created_at) = YEAR(CURRENT_DATE())
        WHERE ms.mentor_id = :mentor_id
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $recent_report = [
        'total_students' => $report['total_students'] ?? 0,
        'assessments_given' => $report['assessments_given'] ?? 0,
        'avg_student_progress' => round($report['avg_student_progress'] ?? 0, 1)
    ];
} catch (Exception $e) {
    $recent_report = [
        'total_students' => 0,
        'assessments_given' => 0,
        'avg_student_progress' => 0
    ];
}

// Get Recent Activities from Students
try {
    $stmt = $db->prepare("
        SELECT 
            ab.aktivitas_id,
            ab.aktivitas,
            ab.durasi_menit,
            ab.catatan,
            ab.created_at,
            u.full_name as siswa_name,
            m.judul as materi_judul
        FROM aktivitas_belajar ab
        INNER JOIN mentor_siswa ms ON ab.user_id = ms.siswa_id
        INNER JOIN users u ON ab.user_id = u.user_id
        LEFT JOIN materi m ON ab.materi_id = m.materi_id
        WHERE ms.mentor_id = :mentor_id
        ORDER BY ab.created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_activities = [];
}

// Get Students Needing Attention (low progress or inactive)
try {
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            u.full_name,
            u.avatar,
            COALESCE(AVG(pm.persentase_selesai), 0) as avg_progress,
            DATEDIFF(NOW(), u.last_login) as days_inactive
        FROM users u
        INNER JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        LEFT JOIN progress_materi pm ON u.user_id = pm.user_id
        WHERE ms.mentor_id = :mentor_id
        GROUP BY u.user_id
        HAVING avg_progress < 30 OR days_inactive > 7
        ORDER BY days_inactive DESC, avg_progress ASC
        LIMIT 5
    ");
    $stmt->execute(['mentor_id' => $mentor_id]);
    $students_need_attention = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $students_need_attention = [];
}

// Helper functions
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            return 'Baru saja';
        } elseif ($difference < 3600) {
            $mins = floor($difference / 60);
            return $mins . ' menit lalu';
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . ' jam lalu';
        } elseif ($difference < 604800) {
            $days = floor($difference / 86400);
            return $days . ' hari lalu';
        } else {
            return date('d M Y', $timestamp);
        }
    }
}

if (!function_exists('formatDuration')) {
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
}

// Include header & sidebar
include_once '../../includes/header.php';
include_once '../../includes/sidebar.php';
?>

<!-- Include Dashboard Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard-styles.css">

<!-- Additional Custom Styles -->
<style>
/* Override untuk mentor - mengikuti tema hijau siswa */
.welcome-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.25) !important;
}

.welcome-card::before {
    background: rgba(255, 255, 255, 0.1) !important;
}

.activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.8rem;
    color: var(--gray-600);
}

.activity-meta > span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.activity-meta i {
    font-size: 0.75rem;
    opacity: 0.7;
}

.student-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.attention-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 20px;
    height: 20px;
    background: #f59e0b;
    border-radius: 50%;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.attention-badge i {
    font-size: 0.6rem;
    color: white;
}

.student-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--gray-50);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.student-item:hover {
    background: var(--gray-100);
    transform: translateX(5px);
}

.student-info {
    flex: 1;
}

.student-name {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 2px;
}

.student-status {
    font-size: 0.8rem;
    color: var(--gray-600);
}
</style>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h2 class="welcome-title">Selamat Datang, <?php echo getUserFullName(); ?>! 👨‍🏫</h2>
                            <p class="welcome-subtitle">Pantau perkembangan siswa bimbingan Anda dan berikan arahan terbaik untuk kesuksesan mereka.</p>
                        </div>
                        <div class="welcome-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-primary">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['total_siswa']; ?></h3>
                        <p class="stat-label">Total Siswa Bimbingan</p>
                        <div class="stat-badge">
                            <i class="fas fa-user-graduate"></i> Students
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['siswa_aktif']; ?></h3>
                        <p class="stat-label">Siswa Aktif</p>
                        <div class="stat-badge">
                            <i class="fas fa-calendar-week"></i> 7 Hari
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['total_penilaian']; ?></h3>
                        <p class="stat-label">Total Penilaian</p>
                        <div class="stat-badge">
                            <i class="fas fa-award"></i> Assessments
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-info">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo number_format($stats['avg_siswa_progress'], 1); ?>%</h3>
                        <p class="stat-label">Rata-rata Progress</p>
                        <div class="stat-badge">
                            <i class="fas fa-percentage"></i> Average
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Overview & Monthly Report -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h5 class="mb-0">Performa Siswa</h5>
                            </div>
                            <a href="siswa-list.php" class="btn btn-sm btn-primary-soft">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <h6>Belum Ada Siswa</h6>
                                <p class="text-muted small">Siswa bimbingan akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <canvas id="studentPerformanceChart" height="80"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-3">
                <div class="modern-card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h5 class="mb-0">Laporan Bulan Ini</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="progress-stat primary mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="progress-stat-label">Total Siswa</span>
                                <span class="progress-stat-number"><?php echo $recent_report['total_students']; ?></span>
                            </div>
                            <div class="progress modern-progress">
                                <div class="progress-bar bg-gradient-primary" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-stat success mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="progress-stat-label">Penilaian Diberikan</span>
                                <span class="progress-stat-number"><?php echo $recent_report['assessments_given']; ?></span>
                            </div>
                            <div class="progress modern-progress">
                                <div class="progress-bar bg-gradient-success" 
                                     style="width: <?php echo ($recent_report['assessments_given'] / max($recent_report['total_students'], 1)) * 100; ?>%">
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress-stat warning mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="progress-stat-label">Rata-rata Progress</span>
                                <span class="progress-stat-number"><?php echo number_format($recent_report['avg_student_progress'], 1); ?>%</span>
                            </div>
                            <div class="progress modern-progress">
                                <div class="progress-bar bg-gradient-warning" 
                                     style="width: <?php echo $recent_report['avg_student_progress']; ?>%">
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <a href="laporan.php" class="btn btn-primary w-100">
                            <i class="fas fa-file-alt me-2"></i>Lihat Laporan Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students & Activities -->
        <div class="row mb-4">
            <!-- Recent Student Activities -->
            <div class="col-lg-6 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h5 class="mb-0">Aktivitas Siswa Terbaru</h5>
                            </div>
                            <a href="bimbingan.php" class="btn btn-sm btn-primary-soft">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h6>Belum Ada Aktivitas</h6>
                                <p class="text-muted small">Aktivitas siswa akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-marker"></div>
                                        <div class="activity-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <h6 class="activity-title mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo e($activity['siswa_name']); ?>
                                                    </h6>
                                                    <?php if ($activity['materi_judul']): ?>
                                                        <p class="activity-note mb-2">
                                                            <span class="badge bg-light text-dark">
                                                                <?php echo e($activity['materi_judul']); ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($activity['catatan']): ?>
                                                        <p class="activity-note mb-0">
                                                            <i class="fas fa-comment-dots me-1"></i>
                                                            <?php echo e(substr($activity['catatan'], 0, 80)) . (strlen($activity['catatan']) > 80 ? '...' : ''); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="activity-time">
                                                    <?php echo timeAgo($activity['created_at']); ?>
                                                </span>
                                            </div>
                                            <div class="activity-meta">
                                                <span>
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo formatDuration($activity['durasi_menit']); ?>
                                                </span>
                                                <span class="badge bg-primary">
                                                    <?php echo e($activity['aktivitas']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Students Need Attention -->
            <div class="col-lg-6 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h5 class="mb-0">Perlu Perhatian</h5>
                            </div>
                            <?php if (!empty($students_need_attention)): ?>
                            <span class="badge badge-warning">
                                <?php echo count($students_need_attention); ?> siswa
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students_need_attention)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h6>Semua Siswa On Track!</h6>
                                <p class="text-muted small">Tidak ada siswa yang memerlukan perhatian khusus</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($students_need_attention as $student): ?>
                                    <div class="student-item">
                                        <div style="position: relative;">
                                            <?php 
                                            $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                            if ($student['avatar'] && file_exists(ROOT_PATH . 'uploads/avatars/' . $student['avatar'])) {
                                                $avatar_url = BASE_URL . 'uploads/avatars/' . $student['avatar'];
                                            }
                                            ?>
                                            <img src="<?php echo $avatar_url; ?>" 
                                                 alt="Avatar" 
                                                 class="student-avatar"
                                                 onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                            <div class="attention-badge">
                                                <i class="fas fa-exclamation"></i>
                                            </div>
                                        </div>
                                        <div class="student-info">
                                            <div class="student-name"><?php echo e($student['full_name']); ?></div>
                                            <div class="student-status">
                                                <?php if ($student['days_inactive'] > 7): ?>
                                                    <i class="fas fa-clock text-danger"></i> 
                                                    Tidak aktif <?php echo $student['days_inactive']; ?> hari
                                                <?php else: ?>
                                                    <i class="fas fa-chart-line text-warning"></i> 
                                                    Progress rendah (<?php echo number_format($student['avg_progress'], 1); ?>%)
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="siswa-detail.php?id=<?php echo $student['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student List Preview -->
        <?php if (!empty($students)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="mb-0">Daftar Siswa Bimbingan</h5>
                            </div>
                            <a href="siswa-list.php" class="btn btn-sm btn-primary-soft">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Email</th>
                                        <th class="text-center">Progress</th>
                                        <th class="text-center">Materi</th>
                                        <th class="text-center">Login Terakhir</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($students, 0, 5) as $student): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                    if ($student['avatar'] && file_exists(ROOT_PATH . 'uploads/avatars/' . $student['avatar'])) {
                                                        $avatar_url = BASE_URL . 'uploads/avatars/' . $student['avatar'];
                                                    }
                                                    ?>
                                                    <img src="<?php echo $avatar_url; ?>" 
                                                         alt="Avatar" 
                                                         class="rounded-circle me-2"
                                                         width="40" height="40"
                                                         onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                    <div>
                                                        <div class="fw-semibold"><?php echo e($student['full_name']); ?></div>
                                                        <small class="text-muted">@<?php echo e($student['username']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo e($student['email']); ?></td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px; min-width: 80px;">
                                                    <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $student['avg_progress'] ?? 0; ?>%"
                                                         aria-valuenow="<?php echo $student['avg_progress'] ?? 0; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo number_format($student['avg_progress'] ?? 0, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $student['total_materi_progress']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted"><?php echo timeAgo($student['last_login']); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <a href="siswa-detail.php?id=<?php echo $student['user_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="quick-actions-grid">
                    <a href="siswa-list.php" class="quick-action-card primary">
                        <div class="quick-action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Daftar Siswa</h6>
                            <p>Kelola siswa bimbingan</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="bimbingan.php" class="quick-action-card success">
                        <div class="quick-action-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Bimbingan</h6>
                            <p>Berikan feedback & arahan</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="laporan.php" class="quick-action-card warning">
                        <div class="quick-action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Laporan</h6>
                            <p>Lihat laporan lengkap</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="penilaian.php" class="quick-action-card info">
                        <div class="quick-action-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Penilaian</h6>
                            <p>Beri nilai & feedback</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($students)): ?>
// Student Performance Chart
const ctx = document.getElementById('studentPerformanceChart');
if (ctx) {
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: [<?php echo '"' . implode('","', array_map(function($s) { return substr($s['full_name'], 0, 15); }, array_slice($students, 0, 10))) . '"'; ?>],
            datasets: [{
                label: 'Progress (%)',
                data: [<?php echo implode(',', array_map(function($s) { return $s['avg_progress'] ?? 0; }, array_slice($students, 0, 10))); ?>],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>