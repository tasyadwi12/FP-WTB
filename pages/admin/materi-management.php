<?php
/**
 * Materi Management with YouTube Integration
 * File: pages/admin/materi-management.php (MODIFIED)
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';
require_once '../../config/youtube_config.php';
require_once '../../includes/youtube_helper.php';

requireRole(ROLE_ADMIN);

$page_title = 'Manajemen Materi';
$current_page = 'materi-management';

// Process Actions
$action = $_GET['action'] ?? '';
$materi_id = $_GET['id'] ?? 0;

// Delete Materi
if ($action === 'delete' && $materi_id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM materi WHERE materi_id = ?");
        if ($stmt->execute([$materi_id])) {
            setFlash(SUCCESS, 'Materi berhasil dihapus!');
        } else {
            setFlash(ERROR, 'Gagal menghapus materi!');
        }
    } catch (Exception $e) {
        setFlash(ERROR, 'Error: ' . $e->getMessage());
    }
    header('Location: materi-management.php');
    exit;
}

// Add/Edit Materi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materi_id = $_POST['materi_id'] ?? 0;
    $kategori_id = $_POST['kategori_id'] ?? 0;
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tingkat_kesulitan = trim($_POST['tingkat_kesulitan'] ?? 'pemula');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    
    if (empty($judul) || empty($deskripsi)) {
        setFlash(ERROR, 'Judul dan deskripsi wajib diisi!');
    } else {
        try {
            $db = getDB();
            $current_user_id = getUserId();
            
            // Extract YouTube video ID and fetch info
            $youtube_video_id = null;
            $video_duration = null;
            $video_thumbnail = null;
            
            if (!empty($youtube_url)) {
                $youtube_video_id = extractYouTubeID($youtube_url);
                
                if ($youtube_video_id) {
                    $videoInfo = fetchYouTubeVideoInfo($youtube_video_id);
                    
                    if ($videoInfo['success']) {
                        $video_duration = $videoInfo['duration'];
                        $video_thumbnail = $videoInfo['thumbnail'];
                        
                        // Auto-fill title if empty from video
                        if (empty($judul)) {
                            $judul = $videoInfo['title'];
                        }
                    } else {
                        setFlash(ERROR, 'Gagal fetch video info: ' . $videoInfo['error']);
                        header('Location: materi-management.php');
                        exit;
                    }
                }
            }
            
            if ($materi_id > 0) {
                // Update existing materi
                $sql = "UPDATE materi SET 
                        kategori_id = ?,
                        judul = ?,
                        deskripsi = ?,
                        tingkat_kesulitan = ?,
                        youtube_url = ?,
                        youtube_video_id = ?,
                        video_duration = ?,
                        video_thumbnail = ?,
                        estimasi_waktu = ?,
                        updated_at = NOW()
                        WHERE materi_id = ?";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $kategori_id > 0 ? $kategori_id : null,
                    $judul,
                    $deskripsi,
                    $tingkat_kesulitan,
                    $youtube_url ?: null,
                    $youtube_video_id,
                    $video_duration,
                    $video_thumbnail,
                    $video_duration ? ceil($video_duration / 60) : null, // Convert to minutes
                    $materi_id
                ]);
                
                if ($result) {
                    setFlash(SUCCESS, 'Materi berhasil diupdate!');
                    header('Location: materi-management.php');
                    exit;
                } else {
                    setFlash(ERROR, 'Gagal mengupdate materi!');
                }
            } else {
                // Create new materi
                $sql = "INSERT INTO materi 
                        (kategori_id, judul, deskripsi, tingkat_kesulitan, youtube_url, youtube_video_id, video_duration, video_thumbnail, estimasi_waktu, urutan, is_active, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $kategori_id > 0 ? $kategori_id : null,
                    $judul,
                    $deskripsi,
                    $tingkat_kesulitan,
                    $youtube_url ?: null,
                    $youtube_video_id,
                    $video_duration,
                    $video_thumbnail,
                    $video_duration ? ceil($video_duration / 60) : null,
                    $current_user_id
                ]);
                
                if ($result) {
                    setFlash(SUCCESS, 'Materi baru berhasil ditambahkan!');
                    header('Location: materi-management.php');
                    exit;
                } else {
                    setFlash(ERROR, 'Gagal menambahkan materi!');
                }
            }
        } catch (Exception $e) {
            setFlash(ERROR, 'Database Error: ' . $e->getMessage());
        }
    }
}

// Get filters
$filter_kategori = $_GET['kategori'] ?? '';
$filter_tingkat = $_GET['tingkat'] ?? '';
$search = $_GET['search'] ?? '';

// Get materi list
try {
    $db = getDB();
    
    $sql = "SELECT 
            m.*,
            IFNULL(k.nama_kategori, 'Tanpa Kategori') as nama_kategori 
            FROM materi m
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id";
    
    $where_clauses = [];
    $params = [];
    
    if (!empty($filter_kategori)) {
        $where_clauses[] = "m.kategori_id = ?";
        $params[] = $filter_kategori;
    }
    
    if (!empty($filter_tingkat)) {
        $where_clauses[] = "m.tingkat_kesulitan = ?";
        $params[] = $filter_tingkat;
    }
    
    if (!empty($search)) {
        $where_clauses[] = "(m.judul LIKE ? OR m.deskripsi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY m.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $db->query($sql);
    }
    
    $materi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $materi_list = [];
    error_log("Error fetching materi: " . $e->getMessage());
}

// Get categories
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM kategori_materi ORDER BY nama_kategori");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get statistics
try {
    $db = getDB();
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN tingkat_kesulitan = 'pemula' THEN 1 ELSE 0 END) as pemula,
        SUM(CASE WHEN tingkat_kesulitan = 'menengah' THEN 1 ELSE 0 END) as menengah,
        SUM(CASE WHEN tingkat_kesulitan = 'lanjut' THEN 1 ELSE 0 END) as lanjut,
        SUM(CASE WHEN youtube_video_id IS NOT NULL THEN 1 ELSE 0 END) as with_video
        FROM materi");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total' => 0, 'pemula' => 0, 'menengah' => 0, 'lanjut' => 0, 'with_video' => 0];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted">Kelola materi pembelajaran dengan YouTube video</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#materiModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Tambah Materi
            </button>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
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
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-3">
                                    <i class="fab fa-youtube fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">With Video</h6>
                                <h3 class="mb-0"><?php echo $stats['with_video']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Pemula</h6>
                        <h3 class="mb-0"><?php echo $stats['pemula']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Menengah</h6>
                        <h3 class="mb-0"><?php echo $stats['menengah']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Lanjut</h6>
                        <h3 class="mb-0"><?php echo $stats['lanjut']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter & Search -->
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
                    <div class="col-md-3">
                        <select name="tingkat" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Tingkat</option>
                            <option value="pemula" <?php echo $filter_tingkat === 'pemula' ? 'selected' : ''; ?>>Pemula</option>
                            <option value="menengah" <?php echo $filter_tingkat === 'menengah' ? 'selected' : ''; ?>>Menengah</option>
                            <option value="lanjut" <?php echo $filter_tingkat === 'lanjut' ? 'selected' : ''; ?>>Lanjut</option>
                        </select>
                    </div>
                    <div class="col-md-4">
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
                            <i class="fas fa-book fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada materi</h5>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($materi_list as $materi): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <?php if (!empty($materi['video_thumbnail'])): ?>
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($materi['video_thumbnail']); ?>" 
                                         class="card-img-top" 
                                         style="height: 200px; object-fit: cover;"
                                         alt="Video thumbnail">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger">
                                            <i class="fab fa-youtube me-1"></i>Video
                                        </span>
                                    </div>
                                    <?php if ($materi['video_duration']): ?>
                                        <div class="position-absolute bottom-0 end-0 m-2">
                                            <span class="badge bg-dark">
                                                <?php echo formatVideoDuration($materi['video_duration']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge <?php 
                                        echo $materi['tingkat_kesulitan'] === 'pemula' ? 'bg-success' : 
                                            ($materi['tingkat_kesulitan'] === 'menengah' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php echo ucfirst($materi['tingkat_kesulitan']); ?>
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   onclick="editMateri(<?php echo htmlspecialchars(json_encode($materi)); ?>); return false;">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" 
                                                   href="?action=delete&id=<?php echo $materi['materi_id']; ?>"
                                                   onclick="return confirm('Yakin hapus materi ini?')">
                                                    <i class="fas fa-trash me-2"></i>Hapus
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($materi['judul']); ?></h5>
                                <p class="card-text text-muted small">
                                    <?php 
                                    $desc = $materi['deskripsi'] ?? '';
                                    echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc); 
                                    ?>
                                </p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($materi['nama_kategori']); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $materi['estimasi_waktu'] ?? 0; ?> menit
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($materi['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- Materi Modal -->
<div class="modal fade" id="materiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="materiForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Materi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="materi_id" id="materi_id">
                    
                    <!-- YouTube URL -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fab fa-youtube text-danger me-1"></i>
                            YouTube Video URL
                        </label>
                        <div class="input-group">
                            <input type="url" name="youtube_url" id="youtube_url" class="form-control" 
                                   placeholder="https://www.youtube.com/watch?v=...">
                            <button class="btn btn-outline-secondary" type="button" onclick="fetchVideoInfo()">
                                <i class="fas fa-sync-alt me-1"></i>Fetch Info
                            </button>
                        </div>
                        <small class="form-text text-muted">Opsional. Paste YouTube URL dan klik "Fetch Info" untuk auto-fill data video.</small>
                        <div id="videoInfoLoading" class="text-center mt-2" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <small class="text-muted ms-2">Fetching video info...</small>
                        </div>
                        <div id="videoInfoPreview" class="mt-2" style="display: none;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="judul" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" id="kategori_id" class="form-select">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['kategori_id']; ?>">
                                        <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tingkat Kesulitan</label>
                            <select name="tingkat_kesulitan" id="tingkat_kesulitan" class="form-select">
                                <option value="pemula">Pemula</option>
                                <option value="menengah">Menengah</option>
                                <option value="lanjut">Lanjut</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('materiForm').reset();
    document.getElementById('materi_id').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Materi Baru';
    document.getElementById('videoInfoPreview').style.display = 'none';
}

function editMateri(materi) {
    document.getElementById('materi_id').value = materi.materi_id;
    document.getElementById('judul').value = materi.judul;
    document.getElementById('deskripsi').value = materi.deskripsi;
    document.getElementById('kategori_id').value = materi.kategori_id || '';
    document.getElementById('tingkat_kesulitan').value = materi.tingkat_kesulitan;
    document.getElementById('youtube_url').value = materi.youtube_url || '';
    document.getElementById('modalTitle').textContent = 'Edit Materi';
    
    const modal = new bootstrap.Modal(document.getElementById('materiModal'));
    modal.show();
}

async function fetchVideoInfo() {
    const url = document.getElementById('youtube_url').value;
    
    if (!url) {
        alert('Masukkan YouTube URL terlebih dahulu');
        return;
    }
    
    const loading = document.getElementById('videoInfoLoading');
    const preview = document.getElementById('videoInfoPreview');
    
    loading.style.display = 'block';
    preview.style.display = 'none';
    
    try {
        const response = await fetch('../../api/youtube/fetch_video_info.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: url })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Auto-fill form
            if (!document.getElementById('judul').value) {
                document.getElementById('judul').value = data.title;
            }
            if (!document.getElementById('deskripsi').value) {
                document.getElementById('deskripsi').value = data.description.substring(0, 200);
            }
            
            // Show preview
            preview.innerHTML = `
                <div class="alert alert-success">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <img src="${data.thumbnail}" class="img-fluid rounded" alt="Thumbnail">
                        </div>
                        <div class="col-md-9">
                            <h6 class="mb-1">${data.title}</h6>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${data.duration_formatted} | 
                                <i class="fas fa-eye me-1"></i>${data.view_count.toLocaleString()} views
                            </small>
                        </div>
                    </div>
                </div>
            `;
            preview.style.display = 'block';
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Fetch error: ' + error.message);
    } finally {
        loading.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>