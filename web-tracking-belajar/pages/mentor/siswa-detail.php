<?php
/**
 * Siswa Detail Page - Mentor - COMPLETE FIXED VERSION
 * File: pages/mentor/siswa-detail.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require mentor role
requireRole(ROLE_MENTOR);

// Set page info
$page_title = 'Detail Siswa';
$current_page = 'siswa-list';

$mentor_id = getUserId();
$siswa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($siswa_id <= 0) {
    setFlash(ERROR, 'Siswa tidak ditemukan!');
    header('Location: siswa-list.php');
    exit;
}

try {
    // Verify this student is under this mentor
    $verify_result = query("SELECT * FROM mentor_siswa WHERE mentor_id = :mentor_id AND siswa_id = :siswa_id", [
        'mentor_id' => $mentor_id,
        'siswa_id' => $siswa_id
    ]);

    if (!$verify_result) {
        throw new Exception("Query failed untuk verifikasi mentor-siswa");
    }

    $verify = $verify_result->fetch();

    if (!$verify) {
        setFlash(ERROR, 'Anda tidak memiliki akses ke siswa ini!');
        header('Location: siswa-list.php');
        exit;
    }

    // Get Student Info
    $student_result = query("SELECT * FROM users WHERE user_id = :id AND role = 'siswa'", ['id' => $siswa_id]);
    
    if (!$student_result) {
        throw new Exception("Query failed untuk data siswa");
    }
    
    $student = $student_result->fetch();

    if (!$student) {
        setFlash(ERROR, 'Siswa tidak ditemukan!');
        header('Location: siswa-list.php');
        exit;
    }

    // Get Progress Statistics
    $progress_stats_result = query("SELECT 
        COUNT(*) as total_materi,
        COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
        COUNT(CASE WHEN status = 'sedang_dipelajari' THEN 1 END) as sedang,
        COUNT(CASE WHEN status = 'belum_mulai' THEN 1 END) as belum
        FROM progress_materi 
        WHERE user_id = :user_id", ['user_id' => $siswa_id]);
    
    if (!$progress_stats_result) {
        throw new Exception("Query failed untuk progress statistics");
    }
    
    $progress_stats = $progress_stats_result->fetch();
    
    // Set default values if null
    $progress_stats['total_materi'] = $progress_stats['total_materi'] ?? 0;
    $progress_stats['selesai'] = $progress_stats['selesai'] ?? 0;
    $progress_stats['sedang'] = $progress_stats['sedang'] ?? 0;
    $progress_stats['belum'] = $progress_stats['belum'] ?? 0;

    // Get Activity Statistics
    $activity_stats_result = query("SELECT 
        COUNT(*) as total_aktivitas,
        COALESCE(SUM(durasi_menit), 0) as total_durasi,
        MAX(tanggal) as last_activity
        FROM aktivitas_belajar 
        WHERE user_id = :user_id", ['user_id' => $siswa_id]);
    
    if (!$activity_stats_result) {
        throw new Exception("Query failed untuk activity statistics");
    }
    
    $activity_stats = $activity_stats_result->fetch();
    
    // Set default values
    $activity_stats['total_aktivitas'] = $activity_stats['total_aktivitas'] ?? 0;
    $activity_stats['total_durasi'] = $activity_stats['total_durasi'] ?? 0;
    $activity_stats['last_activity'] = $activity_stats['last_activity'] ?? null;

    // Get Target Statistics
    $target_stats_result = query("SELECT 
        COUNT(*) as total_target,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 END) as overdue
        FROM target_belajar 
        WHERE user_id = :user_id", ['user_id' => $siswa_id]);
    
    if (!$target_stats_result) {
        throw new Exception("Query failed untuk target statistics");
    }
    
    $target_stats = $target_stats_result->fetch();
    
    // Set default values
    $target_stats['total_target'] = $target_stats['total_target'] ?? 0;
    $target_stats['completed'] = $target_stats['completed'] ?? 0;
    $target_stats['in_progress'] = $target_stats['in_progress'] ?? 0;
    $target_stats['overdue'] = $target_stats['overdue'] ?? 0;

    // Get Recent Progress
    $recent_progress_result = query("SELECT 
        pm.*,
        m.judul as materi_judul,
        m.tingkat_kesulitan,
        k.nama_kategori
        FROM progress_materi pm
        JOIN materi m ON pm.materi_id = m.materi_id
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        WHERE pm.user_id = :user_id
        ORDER BY pm.updated_at DESC
        LIMIT 10", ['user_id' => $siswa_id]);
    
    if (!$recent_progress_result) {
        $recent_progress = [];
    } else {
        $recent_progress = $recent_progress_result->fetchAll();
    }

    // Get Recent Activity
    $recent_activity_result = query("SELECT 
        ab.*,
        m.judul as materi_judul
        FROM aktivitas_belajar ab
        LEFT JOIN materi m ON ab.materi_id = m.materi_id
        WHERE ab.user_id = :user_id
        ORDER BY ab.tanggal DESC, ab.created_at DESC
        LIMIT 10", ['user_id' => $siswa_id]);
    
    if (!$recent_activity_result) {
        $recent_activity = [];
    } else {
        $recent_activity = $recent_activity_result->fetchAll();
    }

    // Get Active Targets
    $active_targets_result = query("SELECT * FROM target_belajar 
        WHERE user_id = :user_id 
        AND status IN ('pending', 'in_progress')
        ORDER BY deadline ASC
        LIMIT 5", ['user_id' => $siswa_id]);
    
    if (!$active_targets_result) {
        $active_targets = [];
    } else {
        $active_targets = $active_targets_result->fetchAll();
    }

    // Calculate progress percentage
    $progress_percentage = $progress_stats['total_materi'] > 0 
        ? round(($progress_stats['selesai'] / $progress_stats['total_materi']) * 100) 
        : 0;

} catch (Exception $e) {
    setFlash(ERROR, 'Terjadi kesalahan: ' . $e->getMessage());
    header('Location: siswa-list.php');
    exit;
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Back Button -->
        <div class="mb-3">
            <a href="siswa-list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Siswa
            </a>
        </div>
        
        <!-- Student Profile Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <?php 
                        $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                        if (isset($student['avatar']) && !empty($student['avatar'])) {
                            $avatar_path = ROOT_PATH . 'uploads/avatars/' . $student['avatar'];
                            if (file_exists($avatar_path)) {
                                $avatar_url = BASE_URL . 'uploads/avatars/' . $student['avatar'];
                            }
                        }
                        ?>
                        <img src="<?php echo $avatar_url; ?>" 
                             class="rounded-circle mb-3" 
                             width="120" height="120"
                             style="object-fit: cover;"
                             alt="Avatar"
                             onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                    </div>
                    <div class="col-md-6">
                        <h3 class="mb-2"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                        <p class="text-muted mb-2">@<?php echo htmlspecialchars($student['username']); ?></p>
                        <div class="mb-2">
                            <i class="fas fa-envelope me-2 text-muted"></i>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <?php if (!empty($student['phone'])): ?>
                            <div class="mb-2">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <span><?php echo htmlspecialchars($student['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <i class="fas fa-calendar me-2 text-muted"></i>
                            <span>Bergabung: <?php echo formatDate($student['created_at']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted mb-3">Progress Keseluruhan</h6>
                            <div class="position-relative d-inline-block">
                                <svg width="150" height="150">
                                    <circle cx="75" cy="75" r="60" fill="none" stroke="#e9ecef" stroke-width="12"/>
                                    <circle cx="75" cy="75" r="60" fill="none" stroke="#10b981" stroke-width="12"
                                            stroke-dasharray="<?php echo 2 * 3.14159 * 60; ?>"
                                            stroke-dashoffset="<?php echo 2 * 3.14159 * 60 * (1 - $progress_percentage / 100); ?>"
                                            transform="rotate(-90 75 75)"
                                            stroke-linecap="round"/>
                                </svg>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <h2 class="mb-0"><?php echo $progress_percentage; ?>%</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Materi</h6>
                                <h3 class="mb-0"><?php echo $progress_stats['total_materi']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Selesai</h6>
                                <h3 class="mb-0"><?php echo $progress_stats['selesai']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 text-warning rounded p-3">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Jam</h6>
                                <h3 class="mb-0"><?php echo round($activity_stats['total_durasi'] / 60, 1); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 text-info rounded p-3">
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Target Aktif</h6>
                                <h3 class="mb-0"><?php echo $target_stats['in_progress']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Progress -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress Terbaru</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($recent_progress)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada progress materi</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_progress as $progress): ?>
                                    <div class="list-group-item px-0 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($progress['materi_judul']); ?></h6>
                                                <div class="mb-2">
                                                    <?php if (!empty($progress['nama_kategori'])): ?>
                                                        <span class="badge bg-light text-dark me-1">
                                                            <?php echo htmlspecialchars($progress['nama_kategori']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-<?php 
                                                        echo $progress['tingkat_kesulitan'] === 'pemula' ? 'success' : 
                                                            ($progress['tingkat_kesulitan'] === 'menengah' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($progress['tingkat_kesulitan']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <?php echo getStatusBadge($progress['status']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($progress['persentase_selesai'] > 0): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Progress</small>
                                                    <small class="fw-bold"><?php echo $progress['persentase_selesai']; ?>%</small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success" 
                                                         style="width: <?php echo $progress['persentase_selesai']; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo timeAgo($progress['updated_at']); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Aktivitas Terbaru</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($recent_activity)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada aktivitas</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="list-group-item px-0 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['aktivitas']); ?></h6>
                                                <?php if (!empty($activity['materi_judul'])): ?>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-book me-1"></i>
                                                        <?php echo htmlspecialchars($activity['materi_judul']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo formatDate($activity['tanggal']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $activity['durasi_menit']; ?> menit
                                                    </small>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst($activity['jenis_aktivitas']); ?>
                                                    </span>
                                                </div>
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
        
        <!-- Active Targets -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Target Aktif</h5>
            </div>
            <div class="card-body">
                <?php if (empty($active_targets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada target aktif</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($active_targets as $target): ?>
                            <?php
                            $is_overdue = strtotime($target['deadline']) < time();
                            $days_left = ceil((strtotime($target['deadline']) - time()) / (60 * 60 * 24));
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border-start border-<?php 
                                    echo $is_overdue ? 'danger' : ($days_left <= 3 ? 'warning' : 'success'); 
                                ?> border-4 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-flag me-2"></i>
                                                    <?php echo htmlspecialchars($target['judul']); ?>
                                                </h6>
                                                <?php if (!empty($target['deskripsi'])): ?>
                                                    <p class="text-muted small mb-2">
                                                        <?php echo htmlspecialchars($target['deskripsi']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="mb-2">
                                                    <?php echo getStatusBadge($target['status']); ?>
                                                    <span class="badge bg-<?php 
                                                        echo $target['prioritas'] === 'tinggi' ? 'danger' : 
                                                            ($target['prioritas'] === 'sedang' ? 'warning' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst($target['prioritas']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Deadline: <strong><?php echo formatDate($target['deadline']); ?></strong>
                                            </small>
                                            <div class="mt-2">
                                                <?php if (!$is_overdue): ?>
                                                    <span class="badge bg-<?php echo $days_left <= 3 ? 'warning' : 'info'; ?>">
                                                        <i class="fas fa-hourglass-half me-1"></i>
                                                        <?php echo $days_left; ?> hari lagi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Terlambat
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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

<?php include '../../includes/footer.php'; ?>