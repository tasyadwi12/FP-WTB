<?php
/**
 * Bimbingan Page - Mentor - FIXED VERSION
 * File: pages/mentor/bimbingan.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require mentor role
requireRole(ROLE_MENTOR);

// Set page info
$page_title = 'Penilaian & Bimbingan';
$current_page = 'bimbingan';

$mentor_id = getUserId();

// Process Penilaian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_penilaian') {
        $progress_id = intval($_POST['progress_id'] ?? 0);
        $nilai = intval($_POST['nilai'] ?? 0);
        $catatan = trim($_POST['catatan'] ?? '');
        
        if ($progress_id > 0 && $nilai >= 0 && $nilai <= 100) {
            try {
                // Get progress details to get siswa_id and materi_id
                $progress_result = query("SELECT user_id, materi_id FROM progress_materi WHERE progress_id = :progress_id", [
                    'progress_id' => $progress_id
                ]);
                
                if (!$progress_result) {
                    throw new Exception("Gagal mengambil data progress");
                }
                
                $progress_data = $progress_result->fetch();
                
                if (!$progress_data) {
                    throw new Exception("Progress tidak ditemukan");
                }
                
                // Check if already assessed
                $check_result = query("SELECT * FROM penilaian_mentor WHERE progress_id = :progress_id AND mentor_id = :mentor_id", [
                    'progress_id' => $progress_id,
                    'mentor_id' => $mentor_id
                ]);
                
                $check = $check_result ? $check_result->fetch() : null;
                
                if ($check) {
                    // Update existing
                    $sql = "UPDATE penilaian_mentor 
                            SET nilai = :nilai, catatan = :catatan, tanggal_penilaian = CURDATE()
                            WHERE penilaian_id = :id";
                    
                    $result = execute($sql, [
                        'nilai' => $nilai,
                        'catatan' => $catatan,
                        'id' => $check['penilaian_id']
                    ]);
                    
                    if ($result) {
                        setFlash(SUCCESS, 'Penilaian berhasil diupdate!');
                    } else {
                        setFlash(ERROR, 'Gagal mengupdate penilaian');
                    }
                } else {
                    // Insert new
                    $sql = "INSERT INTO penilaian_mentor (progress_id, mentor_id, siswa_id, materi_id, nilai, catatan, tanggal_penilaian) 
                            VALUES (:progress_id, :mentor_id, :siswa_id, :materi_id, :nilai, :catatan, CURDATE())";
                    
                    $result = execute($sql, [
                        'progress_id' => $progress_id,
                        'mentor_id' => $mentor_id,
                        'siswa_id' => $progress_data['user_id'],
                        'materi_id' => $progress_data['materi_id'],
                        'nilai' => $nilai,
                        'catatan' => $catatan
                    ]);
                    
                    if ($result) {
                        setFlash(SUCCESS, 'Penilaian berhasil ditambahkan!');
                    } else {
                        setFlash(ERROR, 'Gagal menambahkan penilaian');
                    }
                }
            } catch (Exception $e) {
                setFlash(ERROR, 'Gagal menyimpan penilaian: ' . $e->getMessage());
            }
        } else {
            setFlash(ERROR, 'Data penilaian tidak valid');
        }
        
        header('Location: bimbingan.php');
        exit;
    }
}

// Get filter
$filter_siswa = intval($_GET['siswa'] ?? 0);
$filter_status = $_GET['status'] ?? '';

try {
    // Get Progress Data for Assessment
    $sql = "SELECT 
        pm.progress_id,
        pm.user_id,
        pm.materi_id,
        pm.status,
        pm.persentase_selesai,
        pm.tanggal_selesai,
        u.full_name as siswa_nama,
        u.avatar as siswa_avatar,
        m.judul as materi_judul,
        m.tingkat_kesulitan,
        k.nama_kategori
        FROM progress_materi pm
        JOIN users u ON pm.user_id = u.user_id
        JOIN materi m ON pm.materi_id = m.materi_id
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        WHERE u.user_id IN (SELECT siswa_id FROM mentor_siswa WHERE mentor_id = :mentor_id)
        AND pm.status = 'selesai'";

    $params = ['mentor_id' => $mentor_id];

    if ($filter_siswa > 0) {
        $sql .= " AND u.user_id = :siswa_id";
        $params['siswa_id'] = $filter_siswa;
    }

    $sql .= " ORDER BY pm.tanggal_selesai DESC";

    $result = query($sql, $params);
    
    if (!$result) {
        throw new Exception("Query failed untuk progress list");
    }
    
    $progress_list = $result->fetchAll();
    
    // Get penilaian for each progress
    foreach ($progress_list as &$progress) {
        $penilaian_result = query("SELECT * FROM penilaian_mentor 
            WHERE progress_id = :progress_id AND mentor_id = :mentor_id", [
            'progress_id' => $progress['progress_id'],
            'mentor_id' => $mentor_id
        ]);
        
        if ($penilaian_result) {
            $penilaian = $penilaian_result->fetch();
            if ($penilaian) {
                $progress['penilaian_id'] = $penilaian['penilaian_id'];
                $progress['nilai'] = $penilaian['nilai'];
                $progress['catatan'] = $penilaian['catatan'];
                $progress['tanggal_penilaian'] = $penilaian['tanggal_penilaian'];
            } else {
                $progress['penilaian_id'] = null;
                $progress['nilai'] = null;
                $progress['catatan'] = null;
                $progress['tanggal_penilaian'] = null;
            }
        } else {
            $progress['penilaian_id'] = null;
            $progress['nilai'] = null;
            $progress['catatan'] = null;
            $progress['tanggal_penilaian'] = null;
        }
    }
    
    // Apply status filter
    if ($filter_status === 'dinilai') {
        $progress_list = array_filter($progress_list, function($p) {
            return $p['penilaian_id'] !== null;
        });
    } elseif ($filter_status === 'belum') {
        $progress_list = array_filter($progress_list, function($p) {
            return $p['penilaian_id'] === null;
        });
    }
    
    // Get My Students for filter
    $students_result = query("SELECT u.user_id, u.full_name 
        FROM users u
        JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        WHERE ms.mentor_id = :mentor_id
        ORDER BY u.full_name", ['mentor_id' => $mentor_id]);
    
    $my_students = $students_result ? $students_result->fetchAll() : [];
    
} catch (Exception $e) {
    $progress_list = [];
    $my_students = [];
    setFlash(ERROR, 'Gagal mengambil data: ' . $e->getMessage());
}

// Statistics
$stats = [
    'total' => count($progress_list),
    'dinilai' => 0,
    'belum_dinilai' => 0,
    'avg_nilai' => 0
];

$total_nilai = 0;
foreach ($progress_list as $progress) {
    if ($progress['penilaian_id']) {
        $stats['dinilai']++;
        $total_nilai += intval($progress['nilai']);
    } else {
        $stats['belum_dinilai']++;
    }
}

if ($stats['dinilai'] > 0) {
    $stats['avg_nilai'] = round($total_nilai / $stats['dinilai'], 1);
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="mb-4">
            <p class="text-muted">Beri penilaian untuk materi yang telah diselesaikan siswa</p>
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
                                    <i class="fas fa-tasks fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Materi Selesai</h6>
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
                                <h6 class="text-muted mb-1">Sudah Dinilai</h6>
                                <h3 class="mb-0"><?php echo $stats['dinilai']; ?></h3>
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
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Belum Dinilai</h6>
                                <h3 class="mb-0"><?php echo $stats['belum_dinilai']; ?></h3>
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
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Rata-rata Nilai</h6>
                                <h3 class="mb-0"><?php echo $stats['avg_nilai']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Filter Siswa</label>
                        <select name="siswa" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($my_students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>" <?php echo $filter_siswa == $student['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Status Penilaian</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="belum" <?php echo $filter_status === 'belum' ? 'selected' : ''; ?>>Belum Dinilai</option>
                            <option value="dinilai" <?php echo $filter_status === 'dinilai' ? 'selected' : ''; ?>>Sudah Dinilai</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="bimbingan.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Progress List -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($progress_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada materi yang perlu dinilai</h5>
                        <p class="text-muted">Materi yang sudah diselesai siswa akan muncul di sini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Siswa</th>
                                    <th>Materi</th>
                                    <th>Tingkat</th>
                                    <th>Selesai</th>
                                    <th class="text-center">Progress</th>
                                    <th class="text-center">Nilai</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progress_list as $progress): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                if (isset($progress['siswa_avatar']) && $progress['siswa_avatar']) {
                                                    $avatar_path = ROOT_PATH . 'uploads/avatars/' . $progress['siswa_avatar'];
                                                    if (file_exists($avatar_path)) {
                                                        $avatar_url = BASE_URL . 'uploads/avatars/' . $progress['siswa_avatar'];
                                                    }
                                                }
                                                ?>
                                                <img src="<?php echo $avatar_url; ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="32" height="32"
                                                     style="object-fit: cover;"
                                                     alt="Avatar"
                                                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                <strong><?php echo htmlspecialchars($progress['siswa_nama']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($progress['materi_judul']); ?>
                                                <?php if (!empty($progress['nama_kategori'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($progress['nama_kategori']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $progress['tingkat_kesulitan'] === 'pemula' ? 'success' : 
                                                    ($progress['tingkat_kesulitan'] === 'menengah' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($progress['tingkat_kesulitan']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($progress['tanggal_selesai']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $progress['persentase_selesai']; ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($progress['nilai'] !== null): ?>
                                                <span class="badge bg-<?php echo $progress['nilai'] >= 75 ? 'success' : ($progress['nilai'] >= 60 ? 'warning' : 'danger'); ?> fs-6">
                                                    <?php echo $progress['nilai']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($progress['penilaian_id']): ?>
                                                <span class="badge bg-success">Dinilai</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick='showPenilaianModal(<?php echo json_encode($progress, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                <i class="fas fa-<?php echo $progress['penilaian_id'] ? 'edit' : 'plus'; ?> me-1"></i>
                                                <?php echo $progress['penilaian_id'] ? 'Edit' : 'Nilai'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Penilaian Modal -->
<div class="modal fade" id="penilaianModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="penilaianForm">
                <input type="hidden" name="action" value="add_penilaian">
                <input type="hidden" name="progress_id" id="progress_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Penilaian Materi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Siswa</label>
                        <input type="text" class="form-control" id="siswa_nama" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Materi</label>
                        <input type="text" class="form-control" id="materi_judul" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nilai (0-100) <span class="text-danger">*</span></label>
                        <input type="number" name="nilai" id="nilai" class="form-control" min="0" max="100" required>
                        <div class="form-text">
                            <span class="badge bg-success">≥75: A</span>
                            <span class="badge bg-warning">60-74: B</span>
                            <span class="badge bg-danger">&lt;60: C</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan / Feedback</label>
                        <textarea name="catatan" id="catatan" class="form-control" rows="4" placeholder="Berikan feedback untuk siswa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Penilaian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showPenilaianModal(data) {
    document.getElementById('progress_id').value = data.progress_id;
    document.getElementById('siswa_nama').value = data.siswa_nama;
    document.getElementById('materi_judul').value = data.materi_judul;
    document.getElementById('nilai').value = data.nilai || '';
    document.getElementById('catatan').value = data.catatan || '';
    
    const modal = new bootstrap.Modal(document.getElementById('penilaianModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>