<?php
/**
 * History Page - Siswa
 * File: pages/siswa/history.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require siswa role
requireRole(ROLE_SISWA);

// Set page info
$page_title = 'Riwayat Aktivitas';
$current_page = 'history';

$user_id = getUserId();

// Process Delete Activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_activity') {
        $aktivitas_id = $_POST['aktivitas_id'] ?? 0;
        
        if ($aktivitas_id > 0) {
            $sql = "DELETE FROM aktivitas_belajar WHERE aktivitas_id = :aktivitas_id AND user_id = :user_id";
            
            if (execute($sql, ['aktivitas_id' => $aktivitas_id, 'user_id' => $user_id])) {
                setFlash(SUCCESS, 'Aktivitas berhasil dihapus!');
            }
        }
        header('Location: history.php');
        exit;
    }
}

// Get filters
$filter_materi = $_GET['materi'] ?? '';
$filter_tanggal = $_GET['tanggal'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with error handling
$sql = "SELECT 
    ab.*,
    m.judul as materi_judul,
    m.tingkat_kesulitan,
    k.nama_kategori
    FROM aktivitas_belajar ab
    JOIN materi m ON ab.materi_id = m.materi_id
    LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
    WHERE ab.user_id = :user_id";

$params = ['user_id' => $user_id];

if (!empty($filter_materi)) {
    $sql .= " AND ab.materi_id = :materi_id";
    $params['materi_id'] = $filter_materi;
}

if (!empty($filter_tanggal)) {
    $sql .= " AND DATE(ab.tanggal) = :tanggal";
    $params['tanggal'] = $filter_tanggal;
}

if (!empty($search)) {
    $sql .= " AND ab.aktivitas LIKE :search";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY ab.tanggal DESC, ab.created_at DESC";

// Execute query with error handling
$history_result = query($sql, $params);
$history_list = [];

if ($history_result !== false) {
    $history_list = $history_result->fetchAll();
} else {
    if (DEBUG_MODE) {
        echo "<div class='alert alert-danger'>Error: Gagal mengambil data riwayat. Periksa struktur tabel aktivitas_belajar, materi, dan kategori_materi.</div>";
    }
}

// Get my learning materi for filter with error handling
$materi_result = query("SELECT DISTINCT m.materi_id, m.judul 
    FROM materi m
    JOIN aktivitas_belajar ab ON m.materi_id = ab.materi_id
    WHERE ab.user_id = :user_id
    ORDER BY m.judul", ['user_id' => $user_id]);

$my_materi = [];
if ($materi_result !== false) {
    $my_materi = $materi_result->fetchAll();
}

// Statistics with error handling
$stats_result = query("SELECT 
    COUNT(*) as total_aktivitas,
    SUM(durasi_menit) as total_durasi,
    COUNT(DISTINCT materi_id) as total_materi,
    COUNT(DISTINCT DATE(tanggal)) as total_hari
    FROM aktivitas_belajar 
    WHERE user_id = :user_id", ['user_id' => $user_id]);

$stats = [
    'total_aktivitas' => 0,
    'total_durasi' => 0,
    'total_materi' => 0,
    'total_hari' => 0
];

if ($stats_result !== false) {
    $stats = $stats_result->fetch();
}

// Recent 7 days activity with error handling
$recent_result = query("SELECT 
    DATE(tanggal) as tanggal,
    COUNT(*) as jumlah_aktivitas,
    SUM(durasi_menit) as total_durasi
    FROM aktivitas_belajar
    WHERE user_id = :user_id AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY tanggal DESC", ['user_id' => $user_id]);

$recent_activity = [];
if ($recent_result !== false) {
    $recent_activity = $recent_result->fetchAll();
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="mb-4">
            <p class="text-muted">Lihat semua aktivitas belajar yang telah Anda lakukan</p>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Aktivitas</h6>
                                <h3 class="mb-0"><?php echo $stats['total_aktivitas']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Waktu</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_durasi'] / 60, 1); ?> jam</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 text-warning rounded p-3">
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Materi Dipelajari</h6>
                                <h3 class="mb-0"><?php echo $stats['total_materi']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 text-info rounded p-3">
                                    <i class="fas fa-calendar-check fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Hari Aktif</h6>
                                <h3 class="mb-0"><?php echo $stats['total_hari']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent 7 Days Activity -->
        <?php if (!empty($recent_activity)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Aktivitas 7 Hari Terakhir</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aktivitas</th>
                                    <th class="text-end">Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $day): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($day['tanggal'])); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $day['jumlah_aktivitas']; ?> aktivitas</span>
                                        </td>
                                        <td class="text-end"><?php echo $day['total_durasi']; ?> menit</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filter & Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Materi</label>
                        <select name="materi" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Materi</option>
                            <?php foreach ($my_materi as $mat): ?>
                                <option value="<?php echo $mat['materi_id']; ?>" <?php echo $filter_materi == $mat['materi_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mat['judul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-select" 
                               value="<?php echo htmlspecialchars($filter_tanggal); ?>" 
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Cari Aktivitas</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama aktivitas..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- History List -->
        <?php if (empty($history_list)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada riwayat aktivitas</h5>
                    <p class="text-muted">Mulai catat aktivitas belajar Anda</p>
                    <a href="progress.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Aktivitas
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Group by date -->
            <?php 
                $grouped = [];
                foreach ($history_list as $item) {
                    $date = date('Y-m-d', strtotime($item['tanggal']));
                    if (!isset($grouped[$date])) {
                        $grouped[$date] = [];
                    }
                    $grouped[$date][] = $item;
                }
            ?>
            
            <?php foreach ($grouped as $date => $activities): ?>
                <div class="mb-4">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-calendar me-2"></i>
                        <?php echo date('d F Y', strtotime($date)); ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo count($activities); ?> aktivitas</span>
                    </h6>
                    
                    <div class="row">
                        <?php foreach ($activities as $activity): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['materi_judul']); ?></h6>
                                                <?php if ($activity['nama_kategori']): ?>
                                                    <span class="badge bg-light text-dark me-1">
                                                        <?php echo htmlspecialchars($activity['nama_kategori']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-<?php 
                                                    echo $activity['tingkat_kesulitan'] === 'pemula' ? 'success' : 
                                                        ($activity['tingkat_kesulitan'] === 'menengah' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($activity['tingkat_kesulitan']); ?>
                                                </span>
                                            </div>
                                            <form method="POST" class="ms-2" onsubmit="return confirm('Hapus aktivitas ini?')">
                                                <input type="hidden" name="action" value="delete_activity">
                                                <input type="hidden" name="aktivitas_id" value="<?php echo $activity['aktivitas_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <p class="mb-2"><?php echo htmlspecialchars($activity['aktivitas']); ?></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo $activity['durasi_menit']; ?> menit
                                            </small>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
</div>

<?php include '../../includes/footer.php'; ?>