<?php
/**
 * Progress Page - Siswa - FINAL FIXED VERSION
 * File: pages/siswa/progress.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require siswa role
requireRole(ROLE_SISWA);

// Set page info
$page_title = 'Progress Belajar';
$current_page = 'progress';

$user_id = getUserId();

// Process Update Progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_progress') {
        $progress_id = $_POST['progress_id'] ?? 0;
        $persentase = $_POST['persentase_selesai'] ?? 0;
        $catatan = trim($_POST['catatan'] ?? '');
        
        if ($progress_id > 0) {
            $status = $persentase >= 100 ? 'selesai' : 'sedang_dipelajari';
            
            if ($persentase >= 100) {
                $sql = "UPDATE progress_materi SET 
                        persentase_selesai = :persentase,
                        status = :status,
                        catatan = :catatan,
                        tanggal_selesai = NOW(),
                        updated_at = NOW()
                        WHERE progress_id = :progress_id AND user_id = :user_id";
            } else {
                $sql = "UPDATE progress_materi SET 
                        persentase_selesai = :persentase,
                        status = :status,
                        catatan = :catatan,
                        updated_at = NOW()
                        WHERE progress_id = :progress_id AND user_id = :user_id";
            }
            
            if (execute($sql, [
                'persentase' => $persentase,
                'status' => $status,
                'catatan' => $catatan,
                'progress_id' => $progress_id,
                'user_id' => $user_id
            ])) {
                setFlash(SUCCESS, 'Progress berhasil diupdate!');
            } else {
                setFlash(ERROR, 'Gagal update progress!');
            }
        } else {
            setFlash(ERROR, 'Progress ID tidak valid!');
        }
        header('Location: progress.php');
        exit;
    }
    
    // FIXED: Simplified version matching target.php pattern
    if ($action === 'add_activity') {
        $materi_id = $_POST['materi_id'] ?? 0;
        $aktivitas = trim($_POST['aktivitas'] ?? '');
        $durasi = $_POST['durasi_menit'] ?? 0;
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $jenis_aktivitas = $_POST['jenis_aktivitas'] ?? 'membaca';
        $catatan_akt = trim($_POST['catatan_aktivitas'] ?? '');
        
        // Basic validation only
        if ($materi_id > 0 && !empty($aktivitas) && $durasi > 0) {
            // CRITICAL FIX: Match target.php pattern exactly
            $sql = "INSERT INTO aktivitas_belajar 
                    (user_id, materi_id, tanggal, aktivitas, durasi_menit, jenis_aktivitas, catatan) 
                    VALUES 
                    (:user_id, :materi_id, :tanggal, :aktivitas, :durasi, :jenis_aktivitas, :catatan)";
            
            if (execute($sql, [
                'user_id' => $user_id,
                'materi_id' => $materi_id,
                'tanggal' => $tanggal,
                'aktivitas' => $aktivitas,
                'durasi' => $durasi,
                'jenis_aktivitas' => $jenis_aktivitas,
                'catatan' => $catatan_akt
            ])) {
                setFlash(SUCCESS, 'Aktivitas belajar berhasil ditambahkan!');
                
                // Update progress_materi
                execute(
                    "UPDATE progress_materi SET updated_at = NOW() 
                     WHERE user_id = :user_id AND materi_id = :materi_id",
                    ['user_id' => $user_id, 'materi_id' => $materi_id]
                );
            } else {
                setFlash(ERROR, 'Gagal menambahkan aktivitas!');
            }
        } else {
            setFlash(ERROR, 'Mohon lengkapi semua data aktivitas!');
        }
        
        header('Location: progress.php');
        exit;
    }
}

// Get filter
$filter_materi = $_GET['materi'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get progress with mentor feedback
$sql = "SELECT 
    pm.*,
    m.judul as materi_judul,
    m.tingkat_kesulitan,
    m.estimasi_waktu as durasi_estimasi,
    k.nama_kategori,
    pn.nilai as nilai_mentor,
    pn.catatan as feedback_mentor
    FROM progress_materi pm
    JOIN materi m ON pm.materi_id = m.materi_id
    LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
    LEFT JOIN penilaian_mentor pn ON pm.progress_id = pn.progress_id
    WHERE pm.user_id = :user_id";

$params = ['user_id' => $user_id];

if (!empty($filter_materi)) {
    $sql .= " AND pm.materi_id = :materi_id";
    $params['materi_id'] = $filter_materi;
}

if (!empty($filter_status)) {
    $sql .= " AND pm.status = :status";
    $params['status'] = $filter_status;
}

$sql .= " ORDER BY pm.updated_at DESC";

$progress_result = query($sql, $params);
$progress_list = [];

if ($progress_result !== false) {
    $progress_list = $progress_result->fetchAll();
} else {
    if (DEBUG_MODE) {
        echo "<div class='alert alert-danger'>Error: Gagal mengambil data progress.</div>";
    }
}

// Get my learning materi for filter
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
    COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
    COUNT(CASE WHEN status = 'sedang_dipelajari' THEN 1 END) as sedang,
    AVG(persentase_selesai) as avg_progress
    FROM progress_materi 
    WHERE user_id = :user_id", ['user_id' => $user_id]);

$stats = [
    'total' => 0,
    'selesai' => 0,
    'sedang' => 0,
    'avg_progress' => 0
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
                <p class="text-muted">Pantau dan update progress pembelajaran Anda</p>
            </div>
            <?php if (!empty($my_materi)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#activityModal">
                    <i class="fas fa-plus me-2"></i>Tambah Aktivitas
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
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Materi</h6>
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
                                <h3 class="mb-0"><?php echo $stats['selesai']; ?></h3>
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
                                <h6 class="text-muted mb-1">Sedang</h6>
                                <h3 class="mb-0"><?php echo $stats['sedang']; ?></h3>
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
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Rata-rata</h6>
                                <h3 class="mb-0"><?php echo round($stats['avg_progress'], 1); ?>%</h3>
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
                            <label class="form-label">Filter Materi</label>
                            <select name="materi" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Materi</option>
                                <?php foreach ($my_materi as $mat): ?>
                                    <option value="<?php echo $mat['materi_id']; ?>" <?php echo $filter_materi == $mat['materi_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mat['judul']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="sedang_dipelajari" <?php echo $filter_status === 'sedang_dipelajari' ? 'selected' : ''; ?>>Sedang Dipelajari</option>
                                <option value="selesai" <?php echo $filter_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="progress.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Progress List -->
        <?php if (empty($progress_list)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada progress</h5>
                    <p class="text-muted">Mulai belajar materi untuk melihat progress Anda</p>
                    <a href="materi.php" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Lihat Materi
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($progress_list as $progress): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($progress['materi_judul']); ?></h5>
                                        <?php if ($progress['nama_kategori']): ?>
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
                                    <?php if ($progress['status'] === 'selesai'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-spinner me-1"></i>Sedang</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">Progress</small>
                                        <small class="fw-bold text-<?php echo $progress['persentase_selesai'] >= 100 ? 'success' : 'primary'; ?>">
                                            <?php echo $progress['persentase_selesai']; ?>%
                                        </small>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar <?php echo $progress['persentase_selesai'] >= 100 ? 'bg-success' : 'bg-primary'; ?>" 
                                             style="width: <?php echo $progress['persentase_selesai']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Timeline -->
                                <div class="mb-3">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        Mulai: <?php echo formatDate($progress['tanggal_mulai']); ?>
                                    </small>
                                    <?php if ($progress['tanggal_selesai']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            Selesai: <?php echo formatDate($progress['tanggal_selesai']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-sync me-1"></i>
                                        Update: <?php echo formatDateTime($progress['updated_at']); ?>
                                    </small>
                                </div>
                                
                                <!-- Notes -->
                                <?php if (!empty($progress['catatan'])): ?>
                                    <div class="alert alert-info py-2 mb-3">
                                        <small><strong>Catatan:</strong> <?php echo htmlspecialchars($progress['catatan']); ?></small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Mentor Feedback -->
                                <?php if ($progress['nilai_mentor']): ?>
                                    <div class="alert alert-success py-2 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small><strong>Nilai Mentor:</strong></small>
                                            <span class="badge bg-success fs-6"><?php echo $progress['nilai_mentor']; ?></span>
                                        </div>
                                        <?php if ($progress['feedback_mentor']): ?>
                                            <small class="text-muted d-block mt-1">
                                                <?php echo htmlspecialchars($progress['feedback_mentor']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Actions -->
                                <?php if ($progress['status'] !== 'selesai'): ?>
                                    <button class="btn btn-primary btn-sm w-100" 
                                            onclick="editProgress(<?php echo htmlspecialchars(json_encode($progress)); ?>)">
                                        <i class="fas fa-edit me-2"></i>Update Progress
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="progressForm">
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="progress_id" id="progress_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Update Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Materi</label>
                        <input type="text" class="form-control" id="materi_judul" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Persentase Selesai (%) <span class="text-danger">*</span></label>
                        <input type="number" name="persentase_selesai" id="persentase_selesai" 
                               class="form-control" min="0" max="100" required>
                        <small class="text-muted">Masukkan 100 untuk menandai materi selesai</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan Progress</label>
                        <textarea name="catatan" id="catatan" class="form-control" rows="3" 
                                  placeholder="Catat progress atau kendala yang dihadapi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<?php if (!empty($my_materi)): ?>
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_activity">
                
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Aktivitas Belajar</h5>
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
                        <label class="form-label">Jenis Aktivitas <span class="text-danger">*</span></label>
                        <select name="jenis_aktivitas" class="form-select" required>
                            <option value="membaca">Membaca</option>
                            <option value="latihan">Latihan</option>
                            <option value="ujian">Ujian</option>
                            <option value="review">Review</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Aktivitas <span class="text-danger">*</span></label>
                        <input type="text" name="aktivitas" class="form-control" 
                               placeholder="Contoh: Membaca materi, mengerjakan latihan..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durasi (menit) <span class="text-danger">*</span></label>
                        <input type="number" name="durasi_menit" class="form-control" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan_aktivitas" class="form-control" rows="3" 
                                  placeholder="Tambahkan catatan tentang aktivitas belajar ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Aktivitas</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function editProgress(data) {
    document.getElementById('progress_id').value = data.progress_id;
    document.getElementById('materi_judul').value = data.materi_judul;
    document.getElementById('persentase_selesai').value = data.persentase_selesai;
    document.getElementById('catatan').value = data.catatan || '';
    
    const modal = new bootstrap.Modal(document.getElementById('progressModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>