<?php
/**
 * Siswa - Materi List with YouTube
 * File: pages/siswa/materi-list.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';
require_once '../../config/youtube_config.php';
require_once '../../includes/youtube_helper.php';

requireRole(ROLE_SISWA);

$page_title = 'Daftar Materi';
$current_page = 'materi-list';

$user_id = getUserId();

// Get filters
$filter_kategori = $_GET['kategori'] ?? '';
$filter_tingkat = $_GET['tingkat'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Get materi with progress
try {
    $db = getDB();
    
    $sql = "SELECT 
            m.*,
            k.nama_kategori,
            p.progress_id,
            p.status as progress_status,
            p.persentase_selesai,
            p.waktu_tonton,
            p.last_position,
            p.completed,
            p.tanggal_mulai,
            p.tanggal_selesai
            FROM materi m
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            LEFT JOIN progress_materi p ON m.materi_id = p.materi_id AND p.user_id = ?
            WHERE m.is_active = 1";
    
    $params = [$user_id];
    
    if (!empty($filter_kategori)) {
        $sql .= " AND m.kategori_id = ?";
        $params[] = $filter_kategori;
    }
    
    if (!empty($filter_tingkat)) {
        $sql .= " AND m.tingkat_kesulitan = ?";
        $params[] = $filter_tingkat;
    }
    
    if (!empty($filter_status)) {
        if ($filter_status === 'belum_mulai') {
            $sql .= " AND (p.status IS NULL OR p.status = 'belum_mulai')";
        } else {
            $sql .= " AND p.status = ?";
            $params[] = $filter_status;
        }
    }
    
    if (!empty($search)) {
        $sql .= " AND (m.judul LIKE ? OR m.deskripsi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY m.urutan ASC, m.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $materi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $materi_list = [];
    error_log("Error: " . $e->getMessage());
}

// Get categories
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM kategori_materi ORDER BY nama_kategori");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get stats
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT m.materi_id) as total_materi,
        COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN m.materi_id END) as selesai,
        COUNT(DISTINCT CASE WHEN p.status = 'sedang_dipelajari' THEN m.materi_id END) as sedang,
        SUM(p.waktu_tonton) as total_waktu
        FROM materi m
        LEFT JOIN progress_materi p ON m.materi_id = p.materi_id AND p.user_id = ?
        WHERE m.is_active = 1
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total_materi' => 0, 'selesai' => 0, 'sedang' => 0, 'total_waktu' => 0];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="mb-4">
            <p class="text-muted">Pilih materi untuk mulai belajar</p>
        </div>
        
        <!-- Stats Cards -->
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
                                <h6 class="text-muted mb-1">Sedang Belajar</h6>
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
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Waktu</h6>
                                <h3 class="mb-0"><?php echo formatDuration($stats['total_waktu']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="kategori" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['kategori_id']; ?>" <?php echo $filter_kategori == $cat['kategori_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="tingkat" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Tingkat</option>
                            <option value="pemula" <?php echo $filter_tingkat === 'pemula' ? 'selected' : ''; ?>>Pemula</option>
                            <option value="menengah" <?php echo $filter_tingkat === 'menengah' ? 'selected' : ''; ?>>Menengah</option>
                            <option value="lanjut" <?php echo $filter_tingkat === 'lanjut' ? 'selected' : ''; ?>>Lanjut</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="belum_mulai" <?php echo $filter_status === 'belum_mulai' ? 'selected' : ''; ?>>Belum Mulai</option>
                            <option value="sedang_dipelajari" <?php echo $filter_status === 'sedang_dipelajari' ? 'selected' : ''; ?>>Sedang Belajar</option>
                            <option value="selesai" <?php echo $filter_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Cari materi..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Materi Grid -->
        <div class="row">
            <?php if (empty($materi_list)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada materi tersedia</h5>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($materi_list as $materi): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            
                            <!-- Thumbnail -->
                            <?php if (!empty($materi['video_thumbnail'])): ?>
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($materi['video_thumbnail']); ?>" 
                                         class="card-img-top" 
                                         style="height: 200px; object-fit: cover;"
                                         alt="Video thumbnail">
                                    
                                    <!-- Video Badge -->
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-danger">
                                            <i class="fab fa-youtube me-1"></i>Video
                                        </span>
                                    </div>
                                    
                                    <!-- Duration -->
                                    <?php if ($materi['video_duration']): ?>
                                        <div class="position-absolute bottom-0 end-0 m-2">
                                            <span class="badge bg-dark">
                                                <?php echo formatVideoDuration($materi['video_duration']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Progress Overlay -->
                                    <?php if (!empty($materi['persentase_selesai'])): ?>
                                        <div class="position-absolute bottom-0 start-0 w-100">
                                            <div class="progress" style="height: 4px; border-radius: 0;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo $materi['persentase_selesai']; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <!-- Badges -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge <?php 
                                        echo $materi['tingkat_kesulitan'] === 'pemula' ? 'bg-success' : 
                                            ($materi['tingkat_kesulitan'] === 'menengah' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php echo ucfirst($materi['tingkat_kesulitan']); ?>
                                    </span>
                                    
                                    <?php if (!empty($materi['progress_status'])): ?>
                                        <span class="badge <?php 
                                            echo $materi['progress_status'] === 'selesai' ? 'bg-success' : 
                                                ($materi['progress_status'] === 'sedang_dipelajari' ? 'bg-info' : 'bg-secondary'); 
                                        ?>">
                                            <?php 
                                            echo $materi['progress_status'] === 'selesai' ? 'Selesai' : 
                                                ($materi['progress_status'] === 'sedang_dipelajari' ? 'Sedang Belajar' : 'Belum Mulai'); 
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Title -->
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($materi['judul']); ?></h5>
                                
                                <!-- Description -->
                                <p class="card-text text-muted small mb-3">
                                    <?php 
                                    $desc = $materi['deskripsi'] ?? '';
                                    echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc); 
                                    ?>
                                </p>
                                
                                <!-- Category -->
                                <div class="mb-3">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($materi['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                                    </span>
                                </div>
                                
                                <!-- Progress Bar -->
                                <?php if (!empty($materi['persentase_selesai'])): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Progress</small>
                                            <small class="text-muted"><?php echo $materi['persentase_selesai']; ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?php echo $materi['persentase_selesai']; ?>%">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Button -->
                                <a href="watch-materi.php?id=<?php echo $materi['materi_id']; ?>" 
                                   class="btn btn-primary w-100">
                                    <?php if (empty($materi['progress_status']) || $materi['progress_status'] === 'belum_mulai'): ?>
                                        <i class="fas fa-play me-2"></i>Mulai Belajar
                                    <?php elseif ($materi['progress_status'] === 'selesai'): ?>
                                        <i class="fas fa-redo me-2"></i>Tonton Lagi
                                    <?php else: ?>
                                        <i class="fas fa-play me-2"></i>Lanjutkan
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include '../../includes/footer.php'; ?>