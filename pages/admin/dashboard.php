<?php
/**
 * Admin Dashboard - Modern Green Theme
 * File: pages/admin/dashboard.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

$page_title = 'Dashboard Admin';
$current_page = 'dashboard';

// Pagination Settings
$activities_per_page = 10;
$users_per_page = 10;

$activity_page = isset($_GET['activity_page']) ? (int)$_GET['activity_page'] : 1;
$user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;

$activity_page = max(1, $activity_page);
$user_page = max(1, $user_page);

$activity_offset = ($activity_page - 1) * $activities_per_page;
$user_offset = ($user_page - 1) * $users_per_page;

// Get Statistics
try {
    // Total Users by Role
    $total_siswa = queryOne("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'")['total'] ?? 0;
    $total_mentor = queryOne("SELECT COUNT(*) as total FROM users WHERE role = 'mentor'")['total'] ?? 0;
    $total_admin = queryOne("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")['total'] ?? 0;
    
    // Total Materi
    $total_materi = queryOne("SELECT COUNT(*) as total FROM materi WHERE is_active = 1")['total'] ?? 0;
    
    // Active Users (last 7 days)
    $active_users = queryOne("SELECT COUNT(DISTINCT user_id) as total FROM aktivitas_belajar WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['total'] ?? 0;
    
    // Total Progress
    $total_progress = queryOne("SELECT COUNT(*) as total FROM progress_materi")['total'] ?? 0;
    
    // Average Progress Percentage
    $avg_progress = queryOne("SELECT AVG(persentase_selesai) as avg FROM progress_materi")['avg'] ?? 0;
    
    // Count Total Activities for Pagination
    $total_activities_result = queryOne("SELECT COUNT(*) as total FROM aktivitas_belajar");
    $total_activities = $total_activities_result['total'] ?? 0;
    $total_activity_pages = ceil($total_activities / $activities_per_page);
    
    // Recent Activities (With Pagination)
    $recent_activities_result = query("
        SELECT 
            ab.aktivitas_id,
            ab.user_id,
            ab.materi_id,
            ab.aktivitas,
            ab.durasi_menit,
            ab.tanggal,
            ab.created_at,
            u.full_name,
            u.username,
            u.avatar,
            m.judul as materi_judul
        FROM aktivitas_belajar ab
        JOIN users u ON ab.user_id = u.user_id
        LEFT JOIN materi m ON ab.materi_id = m.materi_id
        ORDER BY ab.created_at DESC
        LIMIT :limit OFFSET :offset
    ", [
        'limit' => $activities_per_page,
        'offset' => $activity_offset
    ]);
    $recent_activities = $recent_activities_result ? $recent_activities_result->fetchAll() : [];
    
    // Count Total Users for Pagination
    $total_users_result = queryOne("SELECT COUNT(*) as total FROM users");
    $total_users = $total_users_result['total'] ?? 0;
    $total_user_pages = ceil($total_users / $users_per_page);
    
    // Recent Users (With Pagination)
    $recent_users_result = query("
        SELECT 
            user_id,
            username,
            email,
            full_name,
            role,
            avatar,
            created_at
        FROM users 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ", [
        'limit' => $users_per_page,
        'offset' => $user_offset
    ]);
    $recent_users = $recent_users_result ? $recent_users_result->fetchAll() : [];
    
    // Materi Statistics
    $materi_stats_result = query("
        SELECT 
            k.nama_kategori,
            COUNT(m.materi_id) as total_materi,
            AVG(pm.persentase_selesai) as avg_progress
        FROM kategori_materi k
        LEFT JOIN materi m ON k.kategori_id = m.kategori_id
        LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id
        GROUP BY k.kategori_id
        ORDER BY total_materi DESC
    ");
    $materi_stats = $materi_stats_result ? $materi_stats_result->fetchAll() : [];
    
} catch (Exception $e) {
    // Set default values if error
    $total_siswa = 0;
    $total_mentor = 0;
    $total_admin = 0;
    $total_materi = 0;
    $active_users = 0;
    $total_progress = 0;
    $avg_progress = 0;
    $recent_activities = [];
    $recent_users = [];
    $materi_stats = [];
    $total_activity_pages = 0;
    $total_user_pages = 0;
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Include Dashboard Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard-styles.css">

<!-- Additional Custom Styles -->
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

/* Pagination Styling */
.pagination-sm .page-link {
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
    margin: 0 2px;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.pagination-sm .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.pagination-sm .page-link:hover {
    color: var(--primary-color);
    background-color: var(--primary-light);
    border-color: var(--primary-color);
}

/* Info Item Styling */
.info-item {
    padding: 16px;
    background: var(--gray-50);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
}
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h2 class="welcome-title">Selamat Datang, <?php echo getUserFullName(); ?>! 👋</h2>
                            <p class="welcome-subtitle">Kelola dan pantau sistem pembelajaran Anda dengan mudah dan efisien.</p>
                        </div>
                        <div class="welcome-icon">
                            <i class="fas fa-user-shield"></i>
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
                        <h3 class="stat-number"><?php echo $total_siswa; ?></h3>
                        <p class="stat-label">Total Siswa</p>
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
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $total_mentor; ?></h3>
                        <p class="stat-label">Total Mentor</p>
                        <div class="stat-badge">
                            <i class="fas fa-user-tie"></i> Mentors
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card stat-warning">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-details">
                        <h3 class="stat-number"><?php echo $total_materi; ?></h3>
                        <p class="stat-label">Total Materi</p>
                        <div class="stat-badge">
                            <i class="fas fa-layer-group"></i> Materials
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
                        <h3 class="stat-number"><?php echo number_format($avg_progress, 1); ?>%</h3>
                        <p class="stat-label">Rata-rata Progress</p>
                        <div class="stat-badge">
                            <i class="fas fa-percentage"></i> Average
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts & Info System -->
        <div class="row mb-4">
            <!-- Chart -->
            <div class="col-lg-8 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h5 class="mb-0">Statistik Materi per Kategori</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materi_stats)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h6>Belum Ada Data</h6>
                                <p class="text-muted small">Statistik materi akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <canvas id="materiChart" height="80"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Info System -->
            <div class="col-lg-4 mb-3">
                <div class="modern-card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h5 class="mb-0">Info Sistem</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-users me-2"></i>User Aktif (7 Hari)
                                </span>
                                <span class="badge bg-success"><?php echo $active_users; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-tasks me-2"></i>Total Progress
                                </span>
                                <span class="badge bg-primary"><?php echo $total_progress; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-user-shield me-2"></i>Total Admin
                                </span>
                                <span class="badge bg-danger"><?php echo $total_admin; ?></span>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="d-grid gap-2">
                            <a href="settings.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-cog me-2"></i>Pengaturan Sistem
                            </a>
                            <a href="laporan.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-alt me-2"></i>Lihat Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity & Users -->
        <div class="row mb-4">
            <!-- Recent Activity -->
            <div class="col-lg-7 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h5 class="mb-0">Aktivitas Terbaru</h5>
                            </div>
                            <span class="bg-primary-soft px-3 py-1 rounded-pill">
                                <?php echo $total_activities; ?> Total
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_activities)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h6>Belum Ada Aktivitas</h6>
                                <p class="text-muted small">Aktivitas pengguna akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Aktivitas</th>
                                            <th>Materi</th>
                                            <th>Durasi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php 
                                                        $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                        if (!empty($activity['avatar']) && file_exists(ROOT_PATH . 'uploads/avatars/' . $activity['avatar'])) {
                                                            $avatar_url = BASE_URL . 'uploads/avatars/' . $activity['avatar'];
                                                        }
                                                        ?>
                                                        <img src="<?php echo $avatar_url; ?>" 
                                                             alt="Avatar" 
                                                             class="rounded-circle me-2"
                                                             width="40" height="40"
                                                             style="object-fit: cover; border: 2px solid #e5e7eb;"
                                                             onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                        <div>
                                                            <div class="fw-semibold"><?php echo e($activity['full_name'] ?? 'N/A'); ?></div>
                                                            <small class="text-muted">@<?php echo e($activity['username'] ?? 'N/A'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo e($activity['aktivitas'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($activity['materi_judul'])): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo e($activity['materi_judul']); ?>">
                                                            <?php echo e($activity['materi_judul']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $activity['durasi_menit'] ?? 0; ?> menit
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination for Activities -->
                    <?php if ($total_activity_pages > 1): ?>
                        <div class="card-footer bg-white border-top">
                            <nav aria-label="Activity pagination">
                                <ul class="pagination pagination-sm mb-0 justify-content-center">
                                    <?php if ($activity_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?activity_page=<?php echo $activity_page - 1; ?>&user_page=<?php echo $user_page; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $activity_page - 2);
                                    $end = min($total_activity_pages, $activity_page + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i == $activity_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?activity_page=<?php echo $i; ?>&user_page=<?php echo $user_page; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($activity_page < $total_activity_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?activity_page=<?php echo $activity_page + 1; ?>&user_page=<?php echo $user_page; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Halaman <?php echo $activity_page; ?> dari <?php echo $total_activity_pages; ?> 
                                    (Total <?php echo $total_activities; ?> aktivitas)
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="col-lg-5 mb-3">
                <div class="modern-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="header-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <h5 class="mb-0">User Terbaru</h5>
                            </div>
                            <span class="bg-primary-soft px-3 py-1 rounded-pill">
                                <?php echo $total_users; ?> Total
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_users)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <h6>Belum Ada User Baru</h6>
                                <p class="text-muted small">User baru akan muncul di sini</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="list-group-item px-0 border-0 mb-2" style="background: var(--gray-50); border-radius: 10px; padding: 12px 16px !important;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                if (!empty($user['avatar']) && file_exists(ROOT_PATH . 'uploads/avatars/' . $user['avatar'])) {
                                                    $avatar_url = BASE_URL . 'uploads/avatars/' . $user['avatar'];
                                                }
                                                ?>
                                                <img src="<?php echo $avatar_url; ?>" 
                                                     alt="Avatar" 
                                                     class="rounded-circle me-3"
                                                     width="40" height="40"
                                                     style="object-fit: cover; border: 2px solid #fff;"
                                                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                <div>
                                                    <div class="fw-semibold"><?php echo e($user['full_name'] ?? 'N/A'); ?></div>
                                                    <small class="text-muted"><?php echo e($user['email'] ?? 'N/A'); ?></small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <?php echo getRoleBadge($user['role'] ?? 'siswa'); ?>
                                                <small class="text-muted d-block mt-1">
                                                    <?php echo timeAgo($user['created_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination for Users -->
                    <?php if ($total_user_pages > 1): ?>
                        <div class="card-footer bg-white border-top">
                            <nav aria-label="User pagination">
                                <ul class="pagination pagination-sm mb-0 justify-content-center">
                                    <?php if ($user_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?activity_page=<?php echo $activity_page; ?>&user_page=<?php echo $user_page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $user_page - 2);
                                    $end = min($total_user_pages, $user_page + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i == $user_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?activity_page=<?php echo $activity_page; ?>&user_page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($user_page < $total_user_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?activity_page=<?php echo $activity_page; ?>&user_page=<?php echo $user_page + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Halaman <?php echo $user_page; ?> dari <?php echo $total_user_pages; ?>
                                    (Total <?php echo $total_users; ?> users)
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="quick-actions-grid">
                    <a href="user-management.php" class="quick-action-card primary">
                        <div class="quick-action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Kelola User</h6>
                            <p>Tambah, edit, hapus user</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="materi-management.php" class="quick-action-card success">
                        <div class="quick-action-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Kelola Materi</h6>
                            <p>Tambah, edit materi belajar</p>
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
                            <h6>Lihat Laporan</h6>
                            <p>Report & analytics</p>
                        </div>
                        <div class="quick-action-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </a>
                    
                    <a href="settings.php" class="quick-action-card info">
                        <div class="quick-action-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Pengaturan</h6>
                            <p>Konfigurasi sistem</p>
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
<?php if (!empty($materi_stats)): ?>
// Materi Chart
const ctx = document.getElementById('materiChart');
if (ctx) {
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($materi_stats, 'nama_kategori')); ?>,
            datasets: [{
                label: 'Jumlah Materi',
                data: <?php echo json_encode(array_column($materi_stats, 'total_materi')); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgba(16, 185, 129, 1)',
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
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
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