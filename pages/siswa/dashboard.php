<?php
/**
 * Siswa Dashboard - Fixed Dynamic Data
 * File: pages/siswa/dashboard.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';
require_once '../../functions/siswa_functions.php';

// Helper functions (jika belum ada di config)
if (!function_exists('truncate')) {
    function truncate($text, $length = 100, $ellipsis = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        $truncated = substr($text, 0, $length);
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }
        return $truncated . $ellipsis;
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

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatTanggalIndo')) {
    function formatTanggalIndo($date, $with_time = false) {
        if (!$date) return '-';
        $bulan = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        $timestamp = strtotime($date);
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

if (!function_exists('getActivityIcon')) {
    function getActivityIcon($activity) {
        $activity_lower = strtolower($activity);
        if (strpos($activity_lower, 'baca') !== false) {
            return 'fa-book-reader';
        } elseif (strpos($activity_lower, 'video') !== false) {
            return 'fa-video';
        } elseif (strpos($activity_lower, 'latihan') !== false) {
            return 'fa-pen';
        } elseif (strpos($activity_lower, 'quiz') !== false) {
            return 'fa-clipboard-check';
        } elseif (strpos($activity_lower, 'diskusi') !== false) {
            return 'fa-comments';
        } else {
            return 'fa-book';
        }
    }
}

// Require login & siswa role
requireLogin();
requireRole(ROLE_SISWA);

$siswa_id = getUserId();
$page_title = 'Dashboard Siswa';

// Get dashboard data
$stats = getSiswaDashboardStats($siswa_id);
$progress_summary = getSiswaProgressSummary($siswa_id);

// ===== FIXED: DYNAMIC UPCOMING TARGETS (7 DAYS) =====
try {
    $upcoming_targets_query = "
        SELECT 
            tb.target_id,
            tb.judul,
            tb.deskripsi,
            tb.deadline,
            tb.status,
            k.nama_kategori,
            DATEDIFF(tb.deadline, CURDATE()) as days_left
        FROM target_belajar tb
        LEFT JOIN kategori_materi k ON tb.kategori_id = k.kategori_id
        WHERE tb.user_id = :user_id
        AND tb.status != 'completed'
        AND tb.deadline >= CURDATE()
        AND tb.deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY tb.deadline ASC
        LIMIT 5
    ";
    $upcoming_targets_result = query($upcoming_targets_query, ['user_id' => $siswa_id]);
    $upcoming_targets = $upcoming_targets_result ? $upcoming_targets_result->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $upcoming_targets = [];
}

// ===== FIXED: DYNAMIC OVERDUE TARGETS =====
try {
    $overdue_targets_query = "
        SELECT 
            tb.target_id,
            tb.judul,
            tb.deadline,
            DATEDIFF(CURDATE(), tb.deadline) as days_overdue
        FROM target_belajar tb
        WHERE tb.user_id = :user_id
        AND tb.status != 'completed'
        AND tb.deadline < CURDATE()
        ORDER BY tb.deadline ASC
    ";
    $overdue_targets_result = query($overdue_targets_query, ['user_id' => $siswa_id]);
    $overdue_targets = $overdue_targets_result ? $overdue_targets_result->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $overdue_targets = [];
}

// ===== DYNAMIC RECENT ACTIVITIES (5 LATEST) =====
try {
    $recent_activities_query = "
        SELECT 
            ab.aktivitas_id,
            ab.aktivitas,
            ab.durasi_menit as durasi_belajar,
            ab.catatan,
            ab.tanggal,
            ab.created_at,
            m.judul as materi_judul,
            k.nama_kategori
        FROM aktivitas_belajar ab
        LEFT JOIN materi m ON ab.materi_id = m.materi_id
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        WHERE ab.user_id = :user_id
        ORDER BY ab.created_at DESC
        LIMIT 5
    ";
    $recent_activities_result = query($recent_activities_query, ['user_id' => $siswa_id]);
    $recent_activities = $recent_activities_result ? $recent_activities_result->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $recent_activities = [];
}

// ===== DYNAMIC RECOMMENDED MATERI =====
try {
    // Get materi yang belum selesai atau belum dimulai
    $recommended_materi_query = "
        SELECT 
            m.materi_id,
            m.judul,
            m.deskripsi,
            k.nama_kategori,
            COALESCE(pm.persentase_selesai, 0) as progress
        FROM materi m
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id AND pm.user_id = :user_id
        WHERE m.is_active = 1
        AND (pm.persentase_selesai IS NULL OR pm.persentase_selesai < 100)
        ORDER BY 
            CASE 
                WHEN pm.persentase_selesai > 0 THEN 0
                ELSE 1
            END,
            pm.last_accessed DESC,
            m.created_at DESC
        LIMIT 3
    ";
    $recommended_materi_result = query($recommended_materi_query, ['user_id' => $siswa_id]);
    $recommended_materi = $recommended_materi_result ? $recommended_materi_result->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    $recommended_materi = [];
}

// ===== DYNAMIC STREAK CALCULATION =====
// Calculate learning streak (consecutive days)
try {
    $streak_query = "
        SELECT COUNT(DISTINCT DATE(tanggal)) as streak_days
        FROM aktivitas_belajar
        WHERE user_id = :user_id
        AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY tanggal DESC
    ";
    $streak_result = queryOne($streak_query, ['user_id' => $siswa_id]);
    
    // Calculate consecutive streak
    $consecutive_streak = 0;
    $check_date = date('Y-m-d');
    
    for ($i = 0; $i < 30; $i++) {
        $activity_check = queryOne("
            SELECT COUNT(*) as has_activity
            FROM aktivitas_belajar
            WHERE user_id = :user_id
            AND DATE(tanggal) = :check_date
        ", [
            'user_id' => $siswa_id,
            'check_date' => $check_date
        ]);
        
        if ($activity_check['has_activity'] > 0) {
            $consecutive_streak++;
            $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
        } else {
            break;
        }
    }
    
    $learning_streak = $consecutive_streak;
    
    // Get streak message
    if ($learning_streak >= 30) {
        $streak_message = "Luar biasa! Kamu legend! 🏆";
        $streak_emoji = "🔥";
    } elseif ($learning_streak >= 14) {
        $streak_message = "Hebat! Pertahankan semangat!";
        $streak_emoji = "🌟";
    } elseif ($learning_streak >= 7) {
        $streak_message = "Bagus! Terus konsisten ya!";
        $streak_emoji = "⭐";
    } elseif ($learning_streak >= 3) {
        $streak_message = "Pertahankan momentum belajarmu!";
        $streak_emoji = "💪";
    } elseif ($learning_streak > 0) {
        $streak_message = "Awal yang baik! Terus semangat!";
        $streak_emoji = "👍";
    } else {
        $streak_message = "Yuk mulai belajar hari ini!";
        $streak_emoji = "📚";
    }
    
} catch (Exception $e) {
    $learning_streak = 0;
    $streak_message = "Yuk mulai belajar hari ini!";
    $streak_emoji = "📚";
}

// ===== FIXED: DYNAMIC MATERI STATS =====
// Get detailed materi statistics
try {
    // Total materi available
    $total_materi_query = "SELECT COUNT(*) as total FROM materi WHERE is_active = 1";
    $total_materi_result = queryOne($total_materi_query);
    $total_materi = $total_materi_result['total'] ?? 0;
    
    // Materi completed (100% progress)
    $materi_selesai_query = "
        SELECT COUNT(*) as total
        FROM progress_materi
        WHERE user_id = :user_id
        AND persentase_selesai >= 100
    ";
    $materi_selesai_result = queryOne($materi_selesai_query, ['user_id' => $siswa_id]);
    $materi_selesai = $materi_selesai_result['total'] ?? 0;
    
    // Materi in progress (1-99% progress)
    $materi_progress_query = "
        SELECT COUNT(*) as total
        FROM progress_materi
        WHERE user_id = :user_id
        AND persentase_selesai > 0
        AND persentase_selesai < 100
    ";
    $materi_progress_result = queryOne($materi_progress_query, ['user_id' => $siswa_id]);
    $materi_progress = $materi_progress_result['total'] ?? 0;
    
    // Materi not started yet
    $materi_belum_mulai = $total_materi - $materi_selesai - $materi_progress;
    
    // Calculate overall progress percentage
    if ($total_materi > 0) {
        $progress_percentage = round(($materi_selesai / $total_materi) * 100, 1);
    } else {
        $progress_percentage = 0;
    }
    
    // FIXED: Active targets using correct column names
    $target_aktif_query = "
        SELECT COUNT(*) as total
        FROM target_belajar
        WHERE user_id = :user_id
        AND status != 'completed'
    ";
    $target_aktif_result = queryOne($target_aktif_query, ['user_id' => $siswa_id]);
    $target_aktif = $target_aktif_result['total'] ?? 0;
    
    // Override stats with calculated values
    $stats['total_materi'] = $total_materi;
    $stats['materi_selesai'] = $materi_selesai;
    $stats['materi_progress'] = $materi_progress;
    $stats['materi_belum_mulai'] = $materi_belum_mulai;
    $stats['progress_percentage'] = $progress_percentage;
    $stats['target_aktif'] = $target_aktif;
    
} catch (Exception $e) {
    // Keep default stats from getSiswaDashboardStats
    $materi_belum_mulai = 0;
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Include Dashboard Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard-styles.css">

<!-- Additional Custom Styles for Activities -->
<style>
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

.activity-category {
    padding: 2px 8px;
    background: var(--primary-light);
    color: var(--primary-dark);
    border-radius: 6px;
    font-weight: 500;
}

.activity-date {
    color: var(--gray-500);
}

.activity-note {
    color: var(--gray-600);
    margin-bottom: 0;
    line-height: 1.5;
}

.activity-note .badge {
    font-weight: 500;
    padding: 4px 8px;
}

.activity-title {
    color: var(--dark-color);
    font-weight: 600;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}

.activity-title i {
    color: var(--primary-color);
    font-size: 0.9rem;
}

.target-subtitle {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.target-subtitle i {
    font-size: 0.75rem;
}

.target-subtitle .ms-2 {
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
                            <h2 class="welcome-title">Selamat Datang Kembali! 👋</h2>
                            <p class="welcome-subtitle">Halo, <strong><?php echo htmlspecialchars(getUserFullName()); ?></strong>! Semangat belajar hari ini, jangan lupa cek progress dan target kamu.</p>
                        </div>
                        <div class="welcome-icon">
                            <i class="fas fa-graduation-cap"></i>
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
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['total_materi']; ?></h3>
                        <p class="stat-label">Total Materi</p>
                        <div class="stat-badge">
                            <i class="fas fa-layer-group"></i> Tersedia
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['materi_selesai']; ?></h3>
                        <p class="stat-label">Materi Selesai</p>
                        <div class="stat-badge">
                            <i class="fas fa-trophy"></i> 
                            <?php echo $total_materi > 0 ? round(($stats['materi_selesai'] / $total_materi) * 100) : 0; ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-spinner fa-pulse"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['materi_progress']; ?></h3>
                        <p class="stat-label">Sedang Dipelajari</p>
                        <div class="stat-badge">
                            <i class="fas fa-clock"></i> Progress
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-info">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $stats['target_aktif']; ?></h3>
                        <p class="stat-label">Target Aktif</p>
                        <div class="stat-badge">
                            <i class="fas fa-flag"></i> Goals
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h5 class="mb-0">Progress Belajar</h5>
                            </div>
                            <span class="bg-primary-soft px-3 py-1 rounded-pill">
                                <?php echo $stats['progress_percentage']; ?>% Complete
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="progress-overview mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="progress-label">Progress Keseluruhan</span>
                                <span class="progress-percentage"><?php echo $stats['progress_percentage']; ?>%</span>
                            </div>
                            <div class="progress modern-progress">
                                <div class="progress-bar bg-gradient-primary" 
                                     role="progressbar" 
                                     style="width: <?php echo $stats['progress_percentage']; ?>%"
                                     aria-valuenow="<?php echo $stats['progress_percentage']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="progress-stat success">
                                    <div class="progress-stat-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h4 class="progress-stat-number"><?php echo $stats['materi_selesai']; ?></h4>
                                    <p class="progress-stat-label">Selesai</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="progress-stat warning">
                                    <div class="progress-stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <h4 class="progress-stat-number"><?php echo $stats['materi_progress']; ?></h4>
                                    <p class="progress-stat-label">Sedang Belajar</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="progress-stat secondary">
                                    <div class="progress-stat-icon">
                                        <i class="fas fa-hourglass-start"></i>
                                    </div>
                                    <h4 class="progress-stat-number"><?php echo $materi_belum_mulai; ?></h4>
                                    <p class="progress-stat-label">Belum Mulai</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-3">
                <div class="modern-card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h5 class="mb-0">Streak Belajar</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="streak-display">
                            <div class="streak-icon">
                                <i class="fas fa-fire-alt"></i>
                            </div>
                            <h2 class="streak-number"><?php echo $learning_streak; ?></h2>
                            <p class="streak-label">Hari Berturut-turut</p>
                        </div>
                        <div class="streak-message">
                            <span style="font-size: 1.2rem; margin-right: 8px;"><?php echo $streak_emoji; ?></span>
                            <span><?php echo $streak_message; ?></span>
                        </div>
                        
                        <?php if ($learning_streak > 0): ?>
                        <div class="mt-3 pt-3" style="border-top: 1px solid var(--gray-200);">
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fas fa-calendar-check me-1"></i> Hari ini</span>
                                <?php
                                $today_activity = queryOne("
                                    SELECT COUNT(*) as count
                                    FROM aktivitas_belajar
                                    WHERE user_id = :user_id
                                    AND DATE(tanggal) = CURDATE()
                                ", ['user_id' => $siswa_id]);
                                ?>
                                <span class="badge bg-success">
                                    <?php echo $today_activity['count'] > 0 ? '✓ Sudah belajar' : 'Belum belajar'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Targets & Activities -->
        <div class="row mb-4">
            <!-- Upcoming Targets -->
            <div class="col-lg-6 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h5 class="mb-0">Target Minggu Ini</h5>
                            </div>
                            <a href="target.php" class="btn btn-sm btn-primary-soft">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_targets)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <h6>Belum Ada Target</h6>
                                <p class="text-muted small mb-3">Buat target belajar untuk minggu ini</p>
                                <a href="target.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Buat Target
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="target-list">
                                <?php foreach ($upcoming_targets as $target): ?>
                                    <div class="target-item">
                                        <div class="target-checkbox">
                                            <?php if ($target['status'] === 'completed'): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php elseif ($target['status'] === 'in_progress'): ?>
                                                <i class="fas fa-circle-half-stroke text-warning"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-secondary"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="target-content">
                                            <h6 class="target-title"><?php echo e($target['judul']); ?></h6>
                                            <p class="target-subtitle">
                                                <?php if (isset($target['nama_kategori']) && $target['nama_kategori']): ?>
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo e($target['nama_kategori']); ?>
                                                <?php endif; ?>
                                                <?php if ($target['deadline']): ?>
                                                    <span class="ms-2">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo formatTanggalIndo($target['deadline']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <span class="badge <?php echo $target['days_left'] <= 1 ? 'badge-danger' : ($target['days_left'] <= 3 ? 'badge-warning' : 'badge-info'); ?>">
                                            <i class="fas fa-clock"></i> 
                                            <?php 
                                            if ($target['days_left'] == 0) {
                                                echo 'Hari ini';
                                            } elseif ($target['days_left'] == 1) {
                                                echo 'Besok';
                                            } else {
                                                echo $target['days_left'] . ' hari';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="col-lg-6 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h5 class="mb-0">Aktivitas Terakhir</h5>
                            </div>
                            <a href="history.php" class="btn btn-sm btn-primary-soft">
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
                                <p class="text-muted small">Mulai belajar untuk mencatat aktivitas</p>
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
                                                        <?php if (isset($activity['materi_judul']) && $activity['materi_judul']): ?>
                                                            <i class="fas <?php echo getActivityIcon($activity['aktivitas'] ?? 'belajar'); ?> me-1"></i>
                                                            <?php echo e($activity['materi_judul']); ?>
                                                        <?php else: ?>
                                                            <i class="fas fa-book me-1"></i>
                                                            Aktivitas Belajar
                                                        <?php endif; ?>
                                                    </h6>
                                                    <?php if (isset($activity['aktivitas']) && $activity['aktivitas']): ?>
                                                        <p class="activity-note mb-2">
                                                            <span class="badge bg-light text-dark">
                                                                <?php echo e($activity['aktivitas']); ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (isset($activity['catatan']) && $activity['catatan']): ?>
                                                        <p class="activity-note mb-2">
                                                            <i class="fas fa-comment-dots me-1"></i>
                                                            <?php echo e(truncate($activity['catatan'], 80)); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="activity-time">
                                                    <?php echo timeAgo($activity['created_at']); ?>
                                                </span>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="activity-duration">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo formatDuration($activity['durasi_belajar'] ?? 0); ?>
                                                </span>
                                                <?php if (isset($activity['nama_kategori']) && $activity['nama_kategori']): ?>
                                                    <span class="activity-category">
                                                        <i class="fas fa-tag"></i>
                                                        <?php echo e($activity['nama_kategori']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($activity['tanggal'])): ?>
                                                    <span class="activity-date">
                                                        <i class="fas fa-calendar-day"></i>
                                                        <?php echo formatTanggalIndo($activity['tanggal']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Targets Alert -->
        <?php if (!empty($overdue_targets)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert-modern alert-warning">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <h6 class="alert-title">Target Terlambat!</h6>
                        <p class="alert-text">Kamu memiliki <strong><?php echo count($overdue_targets); ?> target</strong> yang sudah melewati deadline. <a href="target.php" class="alert-link">Cek sekarang <i class="fas fa-arrow-right ms-1"></i></a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommended Materi -->
        <?php if (!empty($recommended_materi)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h5 class="mb-0">Materi Rekomendasi untuk Kamu</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($recommended_materi as $materi): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="materi-card">
                                        <div class="materi-header">
                                            <div class="materi-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <span class="materi-category"><?php echo e($materi['nama_kategori'] ?? 'Umum'); ?></span>
                                        </div>
                                        <div class="materi-body">
                                            <h6 class="materi-title"><?php echo e($materi['judul']); ?></h6>
                                            <p class="materi-desc"><?php echo e(truncate($materi['deskripsi'] ?? 'Materi pembelajaran', 80)); ?></p>
                                        </div>
                                        <div class="materi-footer">
                                            <a href="materi.php?id=<?php echo $materi['materi_id']; ?>" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-play me-1"></i> Mulai Belajar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
                    <a href="materi-list.php" class="quick-action-card primary">
                        <div class="quick-action-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Lihat Materi</h6>
                            <p>Jelajahi semua materi belajar</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="progress.php" class="quick-action-card success">
                        <div class="quick-action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Progress Saya</h6>
                            <p>Tracking kemajuan belajar</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="target.php" class="quick-action-card warning">
                        <div class="quick-action-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Target Belajar</h6>
                            <p>Atur & pantau target</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="history.php" class="quick-action-card info">
                        <div class="quick-action-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Riwayat</h6>
                            <p>Aktivitas belajar kamu</p>
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

<?php include '../../includes/footer.php'; ?>