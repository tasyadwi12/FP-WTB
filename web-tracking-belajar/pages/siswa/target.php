<?php
/**
 * Target Page - Siswa - FIXED VERSION
 * File: pages/siswa/target.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require siswa role
requireRole(ROLE_SISWA);

// Set page info
$page_title = 'Target Belajar';
$current_page = 'target';

$user_id = getUserId();

// Process Actions (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_target') {
        $materi_id = $_POST['materi_id'] ?? 0;
        $target_detail = trim($_POST['target_detail'] ?? '');
        $deadline = $_POST['deadline'] ?? '';
        
        if ($materi_id > 0 && !empty($target_detail) && !empty($deadline)) {
            // Get materi info for judul
            $materi = queryOne("SELECT judul, kategori_id FROM materi WHERE materi_id = :materi_id", 
                ['materi_id' => $materi_id]);
            
            if ($materi) {
                // FIXED: Use correct column names matching database
                $sql = "INSERT INTO target_belajar 
                        (user_id, judul, deskripsi, kategori_id, deadline, status) 
                        VALUES 
                        (:user_id, :judul, :deskripsi, :kategori_id, :deadline, 'pending')";
                
                if (execute($sql, [
                    'user_id' => $user_id,
                    'judul' => 'Target: ' . $materi['judul'],
                    'deskripsi' => $target_detail,
                    'kategori_id' => $materi['kategori_id'],
                    'deadline' => $deadline
                ])) {
                    setFlash(SUCCESS, 'Target belajar berhasil ditambahkan!');
                } else {
                    setFlash(ERROR, 'Gagal menambahkan target!');
                }
            } else {
                setFlash(ERROR, 'Materi tidak ditemukan!');
            }
        } else {
            setFlash(ERROR, 'Mohon lengkapi semua data!');
        }
        header('Location: target.php');
        exit;
    }
    
    if ($action === 'update_status') {
        $target_id = $_POST['target_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($target_id > 0 && !empty($status)) {
            // FIXED: Add completed_at when status is completed
            if ($status === 'completed') {
                $sql = "UPDATE target_belajar 
                        SET status = :status, completed_at = NOW(), progress_persen = 100 
                        WHERE target_id = :target_id AND user_id = :user_id";
            } else {
                $sql = "UPDATE target_belajar 
                        SET status = :status 
                        WHERE target_id = :target_id AND user_id = :user_id";
            }
            
            if (execute($sql, [
                'status' => $status,
                'target_id' => $target_id,
                'user_id' => $user_id
            ])) {
                setFlash(SUCCESS, 'Status target berhasil diupdate!');
            } else {
                setFlash(ERROR, 'Gagal update status target!');
            }
        }
        header('Location: target.php');
        exit;
    }
    
    if ($action === 'delete_target') {
        $target_id = $_POST['target_id'] ?? 0;
        
        if ($target_id > 0) {
            $sql = "DELETE FROM target_belajar WHERE target_id = :target_id AND user_id = :user_id";
            
            if (execute($sql, ['target_id' => $target_id, 'user_id' => $user_id])) {
                setFlash(SUCCESS, 'Target berhasil dihapus!');
            } else {
                setFlash(ERROR, 'Gagal menghapus target!');
            }
        }
        header('Location: target.php');
        exit;
    }
}

// Get filters
$filter_status = $_GET['status'] ?? '';
$filter_materi = $_GET['materi'] ?? '';

// FIXED: Update query to match actual database structure
$sql = "SELECT 
    tb.*,
    k.nama_kategori
    FROM target_belajar tb
    LEFT JOIN kategori_materi k ON tb.kategori_id = k.kategori_id
    WHERE tb.user_id = :user_id";

$params = ['user_id' => $user_id];

if (!empty($filter_status)) {
    $sql .= " AND tb.status = :status";
    $params['status'] = $filter_status;
}

if (!empty($filter_materi)) {
    $sql .= " AND tb.kategori_id = :kategori_id";
    $params['kategori_id'] = $filter_materi;
}

$sql .= " ORDER BY tb.deadline ASC";

// Execute query with error handling
$target_result = query($sql, $params);
$target_list = [];

if ($target_result !== false) {
    $target_list = $target_result->fetchAll();
} else {
    if (DEBUG_MODE) {
        echo "<div class='alert alert-danger'>Error: Gagal mengambil data target.</div>";
    }
}

// Get my learning materi for dropdown
$materi_result = query("SELECT DISTINCT m.materi_id, m.judul 
    FROM materi m
    JOIN progress_materi pm ON m.materi_id = pm.materi_id
    WHERE pm.user_id = :user_id
    ORDER BY m.judul", ['user_id' => $user_id]);

$my_materi = [];
if ($materi_result !== false) {
    $my_materi = $materi_result->fetchAll();
}

// Statistics
$stats_result = query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 END) as overdue
    FROM target_belajar 
    WHERE user_id = :user_id", ['user_id' => $user_id]);

$stats = [
    'total' => 0,
    'completed' => 0,
    'in_progress' => 0,
    'pending' => 0,
    'overdue' => 0
];

if ($stats_result !== false) {
    $stats = $stats_result->fetch();
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
                <p class="text-muted">Tetapkan dan pantau target pembelajaran Anda</p>
            </div>
            <?php if (!empty($my_materi)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#targetModal">
                    <i class="fas fa-plus me-2"></i>Tambah Target
                </button>
            <?php endif; ?>
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
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Target</h6>
                                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
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
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Selesai</h6>
                                <h3 class="mb-0"><?php echo $stats['completed']; ?></h3>
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
                                    <i class="fas fa-spinner fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Progress</h6>
                                <h3 class="mb-0"><?php echo $stats['in_progress']; ?></h3>
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
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-3">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Terlambat</h6>
                                <h3 class="mb-0"><?php echo $stats['overdue']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <?php if (!empty($my_materi)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Filter Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">&nbsp;</label>
                            <a href="target.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo me-2"></i>Reset Filter
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Target List -->
        <?php if (empty($target_list)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bullseye fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada target</h5>
                    <p class="text-muted">Tetapkan target belajar untuk mencapai tujuan Anda</p>
                    <?php if (!empty($my_materi)): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#targetModal">
                            <i class="fas fa-plus me-2"></i>Tambah Target
                        </button>
                    <?php else: ?>
                        <a href="materi.php" class="btn btn-primary">
                            <i class="fas fa-book me-2"></i>Mulai Belajar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($target_list as $target): ?>
                    <?php 
                        $is_overdue = strtotime($target['deadline']) < time() && $target['status'] !== 'completed';
                        $days_left = ceil((strtotime($target['deadline']) - time()) / (60 * 60 * 24));
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($target['judul']); ?></h5>
                                        <?php if ($target['nama_kategori']): ?>
                                            <span class="badge bg-light text-dark me-1">
                                                <?php echo htmlspecialchars($target['nama_kategori']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($target['status'] === 'completed'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Selesai</span>
                                    <?php elseif ($target['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning"><i class="fas fa-spinner me-1"></i>Progress</span>
                                    <?php elseif ($is_overdue): ?>
                                        <span class="badge bg-danger"><i class="fas fa-exclamation me-1"></i>Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Pending</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($target['deskripsi']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Deadline: <?php echo date('d M Y', strtotime($target['deadline'])); ?>
                                        <?php if (!$is_overdue && $target['status'] !== 'completed'): ?>
                                            <span class="badge bg-info ms-2"><?php echo $days_left; ?> hari lagi</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <?php if ($target['status'] !== 'completed'): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST" class="flex-fill">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="target_id" value="<?php echo $target['target_id']; ?>">
                                            <?php if ($target['status'] === 'pending'): ?>
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="btn btn-warning btn-sm w-100">
                                                    <i class="fas fa-play me-1"></i>Mulai
                                                </button>
                                            <?php elseif ($target['status'] === 'in_progress'): ?>
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                    <i class="fas fa-check me-1"></i>Selesai
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Hapus target ini?')">
                                            <input type="hidden" name="action" value="delete_target">
                                            <input type="hidden" name="target_id" value="<?php echo $target['target_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Add Target Modal -->
<?php if (!empty($my_materi)): ?>
<div class="modal fade" id="targetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create_target">
                
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Target Belajar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Materi <span class="text-danger">*</span></label>
                        <select name="materi_id" class="form-select" required>
                            <option value="">Pilih Materi</option>
                            <?php foreach ($my_materi as $mat): ?>
                                <option value="<?php echo $mat['materi_id']; ?>">
                                    <?php echo htmlspecialchars($mat['judul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Detail Target <span class="text-danger">*</span></label>
                        <textarea name="target_detail" class="form-control" rows="3" 
                                  placeholder="Contoh: Selesaikan 5 latihan soal..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="deadline" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>