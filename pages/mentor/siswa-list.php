<?php
/**
 * Siswa List Page - Mentor - COMPLETE FIXED VERSION
 * File: pages/mentor/siswa-list.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require mentor role
requireRole(ROLE_MENTOR);

// Set page info
$page_title = 'Daftar Siswa';
$current_page = 'siswa-list';

$mentor_id = getUserId();

// Process Actions
$action = $_GET['action'] ?? '';
$siswa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Assign Siswa to Mentor - FIXED
if ($action === 'assign' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = isset($_POST['siswa_id']) ? intval($_POST['siswa_id']) : 0;
    
    if ($siswa_id > 0) {
        try {
            // Check if already assigned
            $check_result = query("SELECT * FROM mentor_siswa WHERE mentor_id = :mentor_id AND siswa_id = :siswa_id", [
                'mentor_id' => $mentor_id,
                'siswa_id' => $siswa_id
            ]);
            
            if (!$check_result) {
                throw new Exception("Query check failed");
            }
            
            $existing = $check_result->fetch();
            
            if ($existing) {
                setFlash(WARNING, 'Siswa sudah terdaftar sebagai bimbingan Anda!');
            } else {
                // FIXED: Tambahkan assigned_date yang required
                $sql = "INSERT INTO mentor_siswa (mentor_id, siswa_id, assigned_date, status) 
                        VALUES (:mentor_id, :siswa_id, CURDATE(), 'active')";
                
                $result = execute($sql, [
                    'mentor_id' => $mentor_id,
                    'siswa_id' => $siswa_id
                ]);
                
                if ($result) {
                    setFlash(SUCCESS, 'Siswa berhasil ditambahkan ke bimbingan Anda!');
                } else {
                    setFlash(ERROR, 'Gagal menambahkan siswa. Silakan coba lagi.');
                }
            }
        } catch (Exception $e) {
            setFlash(ERROR, 'Gagal menambahkan siswa: ' . $e->getMessage());
        }
    } else {
        setFlash(ERROR, 'Siswa tidak valid. Silakan pilih siswa.');
    }
    
    header('Location: siswa-list.php');
    exit;
}

// Remove Siswa from Mentor
if ($action === 'remove' && $siswa_id > 0) {
    try {
        $sql = "DELETE FROM mentor_siswa WHERE mentor_id = :mentor_id AND siswa_id = :siswa_id";
        $result = execute($sql, ['mentor_id' => $mentor_id, 'siswa_id' => $siswa_id]);
        
        if ($result) {
            setFlash(SUCCESS, 'Siswa berhasil dihapus dari bimbingan Anda!');
        } else {
            setFlash(ERROR, 'Gagal menghapus siswa. Silakan coba lagi.');
        }
    } catch (Exception $e) {
        setFlash(ERROR, 'Gagal menghapus siswa: ' . $e->getMessage());
    }
    header('Location: siswa-list.php');
    exit;
}

// Get filter
$search = $_GET['search'] ?? '';

// Get My Students with Progress - OPTIMIZED QUERY
$sql = "SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.phone,
    u.avatar,
    ms.tanggal_assign,
    ms.assigned_date
    FROM users u
    INNER JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
    WHERE ms.mentor_id = :mentor_id
    AND u.role = 'siswa'
    AND ms.status = 'active'";

$params = ['mentor_id' => $mentor_id];

if (!empty($search)) {
    $sql .= " AND (u.username LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY ms.tanggal_assign DESC";

try {
    $result = query($sql, $params);
    
    if (!$result) {
        throw new Exception("Query failed untuk daftar siswa");
    }
    
    $my_students = $result->fetchAll();
    
    // Get additional stats for each student
    foreach ($my_students as &$student) {
        // Get progress count
        $progress_result = query("SELECT 
            COUNT(DISTINCT materi_id) as total_materi,
            COUNT(DISTINCT CASE WHEN status = 'selesai' THEN materi_id END) as materi_selesai,
            COUNT(DISTINCT CASE WHEN status = 'sedang_dipelajari' THEN materi_id END) as materi_progress
            FROM progress_materi 
            WHERE user_id = :user_id", ['user_id' => $student['user_id']]);
        
        if ($progress_result) {
            $progress = $progress_result->fetch();
            $student['total_materi'] = $progress['total_materi'] ?? 0;
            $student['materi_selesai'] = $progress['materi_selesai'] ?? 0;
            $student['materi_progress'] = $progress['materi_progress'] ?? 0;
        } else {
            $student['total_materi'] = 0;
            $student['materi_selesai'] = 0;
            $student['materi_progress'] = 0;
        }
        
        // Get activity stats
        $activity_result = query("SELECT 
            MAX(tanggal) as last_activity,
            COALESCE(SUM(durasi_menit), 0) as total_durasi
            FROM aktivitas_belajar 
            WHERE user_id = :user_id", ['user_id' => $student['user_id']]);
        
        if ($activity_result) {
            $activity = $activity_result->fetch();
            $student['last_activity'] = $activity['last_activity'] ?? null;
            $student['total_durasi'] = $activity['total_durasi'] ?? 0;
        } else {
            $student['last_activity'] = null;
            $student['total_durasi'] = 0;
        }
    }
    unset($student); // Break reference
    
} catch (Exception $e) {
    $my_students = [];
    setFlash(ERROR, 'Gagal mengambil data siswa: ' . $e->getMessage());
}

// Get Available Students (not assigned to this mentor)
try {
    $available_result = query("SELECT u.user_id, u.username, u.full_name, u.email
        FROM users u
        WHERE u.role = 'siswa'
        AND u.is_active = 1
        AND u.user_id NOT IN (
            SELECT siswa_id FROM mentor_siswa WHERE mentor_id = :mentor_id AND status = 'active'
        )
        ORDER BY u.full_name", ['mentor_id' => $mentor_id]);
    
    $available_students = $available_result ? $available_result->fetchAll() : [];
} catch (Exception $e) {
    $available_students = [];
}

// Statistics
$stats = [
    'total_siswa' => count($my_students),
    'total_aktif' => 0,
    'total_materi_selesai' => 0,
    'avg_progress' => 0
];

foreach ($my_students as $student) {
    if (isset($student['last_activity']) && $student['last_activity'] && strtotime($student['last_activity']) > strtotime('-7 days')) {
        $stats['total_aktif']++;
    }
    $stats['total_materi_selesai'] += $student['materi_selesai'] ?? 0;
}

if ($stats['total_siswa'] > 0) {
    $stats['avg_progress'] = round($stats['total_materi_selesai'] / $stats['total_siswa'], 1);
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted">Kelola siswa yang Anda bimbing</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                <i class="fas fa-user-plus me-2"></i>Tambah Siswa
            </button>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : ($flash['type'] === WARNING ? 'warning' : 'danger'); ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Siswa</h6>
                                <h3 class="mb-0"><?php echo $stats['total_siswa']; ?></h3>
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
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Aktif (7 Hari)</h6>
                                <h3 class="mb-0"><?php echo $stats['total_aktif']; ?></h3>
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
                                    <i class="fas fa-graduation-cap fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Selesai</h6>
                                <h3 class="mb-0"><?php echo $stats['total_materi_selesai']; ?></h3>
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
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Rata-rata</h6>
                                <h3 class="mb-0"><?php echo $stats['avg_progress']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Cari siswa (nama, username, email)..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Students List -->
        <div class="row">
            <?php if (empty($my_students)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada siswa bimbingan</h5>
                            <p class="text-muted">Klik tombol "Tambah Siswa" untuk menambahkan siswa</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fas fa-user-plus me-2"></i>Tambah Siswa Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($my_students as $student): ?>
                    <?php
                    $is_active = isset($student['last_activity']) && $student['last_activity'] && strtotime($student['last_activity']) > strtotime('-7 days');
                    $total = $student['total_materi'] ?? 0;
                    $selesai = $student['materi_selesai'] ?? 0;
                    $progress_percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
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
                                         class="rounded-circle me-3" 
                                         width="60" height="60"
                                         style="object-fit: cover;"
                                         alt="Avatar"
                                         onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                                        <p class="text-muted small mb-1">@<?php echo htmlspecialchars($student['username']); ?></p>
                                        <?php if ($is_active): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Tidak Aktif
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">Progress Materi</small>
                                        <small class="fw-bold"><?php echo $progress_percentage; ?>%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $progress_percentage; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <h6 class="mb-0 text-primary"><?php echo $total; ?></h6>
                                            <small class="text-muted">Materi</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <h6 class="mb-0 text-success"><?php echo $selesai; ?></h6>
                                            <small class="text-muted">Selesai</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <h6 class="mb-0 text-warning"><?php echo $student['materi_progress'] ?? 0; ?></h6>
                                            <small class="text-muted">Progress</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-clock me-1"></i>
                                        Total Belajar: <strong><?php echo round(($student['total_durasi'] ?? 0) / 60, 1); ?> jam</strong>
                                    </small>
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        Bergabung: <?php 
                                        // Use assigned_date if available, otherwise tanggal_assign
                                        $join_date = $student['assigned_date'] ?? $student['tanggal_assign'];
                                        echo $join_date ? date('d M Y', strtotime($join_date)) : '-';
                                        ?>
                                    </small>
                                    <?php if (isset($student['last_activity']) && $student['last_activity']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-history me-1"></i>
                                            Terakhir: <?php echo date('d M Y', strtotime($student['last_activity'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="siswa-detail.php?id=<?php echo $student['user_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-2"></i>Lihat Detail
                                    </a>
                                    <a href="?action=remove&id=<?php echo $student['user_id']; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Yakin hapus siswa ini dari bimbingan Anda?')">
                                        <i class="fas fa-user-times me-2"></i>Hapus dari Bimbingan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- Assign Student Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?action=assign" id="assignForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Tambah Siswa Bimbingan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($available_students)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tidak ada siswa tersedia.</strong><br>
                            Semua siswa sudah terdaftar sebagai bimbingan Anda atau tidak ada siswa yang terdaftar di sistem.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">
                                Pilih Siswa <span class="text-danger">*</span>
                            </label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($available_students as $student): ?>
                                    <option value="<?php echo $student['user_id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']); ?> 
                                        (@<?php echo htmlspecialchars($student['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-users me-1"></i>
                                Total <?php echo count($available_students); ?> siswa tersedia
                            </div>
                        </div>
                        
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Siswa yang dipilih akan masuk ke daftar bimbingan Anda dan Anda dapat mulai memantau progress belajar mereka.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <?php if (!empty($available_students)): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Tambah Siswa
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('assignForm')?.addEventListener('submit', function(e) {
    const siswaId = document.getElementById('siswa_id')?.value;
    if (!siswaId) {
        e.preventDefault();
        alert('Silakan pilih siswa terlebih dahulu!');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>