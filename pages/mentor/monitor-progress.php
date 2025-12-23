<?php
/**
 * Mentor - Monitor Student Progress - FIXED AVATAR
 * File: pages/mentor/monitor-progress.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';
require_once '../../config/youtube_config.php';
require_once '../../includes/youtube_helper.php';

requireRole(ROLE_MENTOR);

$page_title = 'Monitor Progress Siswa';
$current_page = 'monitor-progress';

$mentor_id = getUserId();

// Get filter
$filter_siswa = $_GET['siswa'] ?? '';
$filter_materi = $_GET['materi'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get mentor's students
try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT DISTINCT u.user_id, u.full_name, u.email, u.avatar
        FROM users u
        JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        WHERE ms.mentor_id = ? AND ms.status = 'active'
        ORDER BY u.full_name
    ");
    $stmt->execute([$mentor_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $students = [];
    error_log("Error: " . $e->getMessage());
}

// Get all materi
try {
    $db = getDB();
    $stmt = $db->query("SELECT materi_id, judul FROM materi WHERE is_active = 1 ORDER BY judul");
    $materi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $materi_list = [];
}

// Get progress data
try {
    $db = getDB();
    
    $sql = "SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.avatar,
            m.materi_id,
            m.judul as materi_judul,
            m.tingkat_kesulitan,
            m.video_duration,
            m.video_thumbnail,
            p.progress_id,
            p.status,
            p.persentase_selesai,
            p.waktu_tonton,
            p.last_position,
            p.completed,
            p.tanggal_mulai,
            p.tanggal_selesai,
            p.updated_at as last_activity
            FROM users u
            JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
            LEFT JOIN progress_materi p ON u.user_id = p.user_id
            LEFT JOIN materi m ON p.materi_id = m.materi_id
            WHERE ms.mentor_id = ? AND ms.status = 'active' AND m.is_active = 1";
    
    $params = [$mentor_id];
    
    if (!empty($filter_siswa)) {
        $sql .= " AND u.user_id = ?";
        $params[] = $filter_siswa;
    }
    
    if (!empty($filter_materi)) {
        $sql .= " AND m.materi_id = ?";
        $params[] = $filter_materi;
    }
    
    if (!empty($filter_status)) {
        if ($filter_status === 'belum_mulai') {
            $sql .= " AND (p.status IS NULL OR p.status = 'belum_mulai')";
        } else {
            $sql .= " AND p.status = ?";
            $params[] = $filter_status;
        }
    }
    
    $sql .= " ORDER BY u.full_name, p.updated_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $progress_data = [];
    error_log("Error: " . $e->getMessage());
}

// Get statistics
try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT u.user_id) as total_students,
            COUNT(DISTINCT CASE WHEN p.status = 'sedang_dipelajari' THEN u.user_id END) as active_students,
            COUNT(DISTINCT p.materi_id) as total_materi_learned,
            SUM(CASE WHEN p.completed = 1 THEN 1 ELSE 0 END) as total_completed,
            SUM(p.waktu_tonton) as total_watch_time,
            AVG(p.persentase_selesai) as avg_completion
        FROM users u
        JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        LEFT JOIN progress_materi p ON u.user_id = p.user_id
        WHERE ms.mentor_id = ? AND ms.status = 'active'
    ");
    
    $stmt->execute([$mentor_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = [
        'total_students' => 0,
        'active_students' => 0,
        'total_materi_learned' => 0,
        'total_completed' => 0,
        'total_watch_time' => 0,
        'avg_completion' => 0
    ];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="mb-4">
            <p class="text-muted">Pantau pembelajaran siswa secara real-time</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Siswa</h6>
                                <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
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
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Siswa Aktif</h6>
                                <h3 class="mb-0"><?php echo $stats['active_students']; ?></h3>
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
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Materi Selesai</h6>
                                <h3 class="mb-0"><?php echo $stats['total_completed']; ?></h3>
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
                                <h6 class="text-muted mb-1">Total Waktu</h6>
                                <h3 class="mb-0"><?php echo formatDuration($stats['total_watch_time'] ?? 0); ?></h3>
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
                    <div class="col-md-4">
                        <label class="form-label">Filter Siswa</label>
                        <select name="siswa" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>" 
                                        <?php echo $filter_siswa == $student['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Filter Materi</label>
                        <select name="materi" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Materi</option>
                            <?php foreach ($materi_list as $materi): ?>
                                <option value="<?php echo $materi['materi_id']; ?>" 
                                        <?php echo $filter_materi == $materi['materi_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($materi['judul']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="belum_mulai" <?php echo $filter_status === 'belum_mulai' ? 'selected' : ''; ?>>Belum Mulai</option>
                            <option value="sedang_dipelajari" <?php echo $filter_status === 'sedang_dipelajari' ? 'selected' : ''; ?>>Sedang Belajar</option>
                            <option value="selesai" <?php echo $filter_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Progress Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Detail Progress</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Siswa</th>
                                <th>Materi</th>
                                <th>Tingkat</th>
                                <th>Progress</th>
                                <th>Waktu Tonton</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($progress_data)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">Tidak ada data progress</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($progress_data as $row): ?>
                                    <tr>
                                        <!-- Student Info - FIXED AVATAR PATH -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                // FIXED: Correct avatar path
                                                $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                if (!empty($row['avatar'])) {
                                                    $avatar_path = ROOT_PATH . 'uploads/avatars/' . $row['avatar'];
                                                    if (file_exists($avatar_path)) {
                                                        $avatar_url = BASE_URL . 'uploads/avatars/' . $row['avatar'];
                                                    }
                                                }
                                                ?>
                                                <img src="<?php echo $avatar_url; ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="40" 
                                                     height="40"
                                                     style="object-fit: cover;"
                                                     alt="Avatar"
                                                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Materi Info -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($row['video_thumbnail'])): ?>
                                                    <img src="<?php echo htmlspecialchars($row['video_thumbnail']); ?>" 
                                                         class="rounded me-2" 
                                                         width="60" 
                                                         height="40"
                                                         style="object-fit: cover;"
                                                         alt="Thumbnail">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($row['materi_judul']); ?></strong>
                                                    <?php if ($row['video_duration']): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo formatDuration($row['video_duration']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Difficulty -->
                                        <td>
                                            <span class="badge <?php 
                                                echo $row['tingkat_kesulitan'] === 'pemula' ? 'bg-success' : 
                                                    ($row['tingkat_kesulitan'] === 'menengah' ? 'bg-warning' : 'bg-danger'); 
                                            ?>">
                                                <?php echo ucfirst($row['tingkat_kesulitan']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Progress -->
                                        <td>
                                            <div style="min-width: 150px;">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">Progress</small>
                                                    <small><strong><?php echo $row['persentase_selesai'] ?? 0; ?>%</strong></small>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar <?php echo ($row['persentase_selesai'] ?? 0) >= 90 ? 'bg-success' : 'bg-primary'; ?>" 
                                                         style="width: <?php echo $row['persentase_selesai'] ?? 0; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Watch Time -->
                                        <td>
                                            <strong><?php echo formatDuration($row['waktu_tonton'] ?? 0); ?></strong>
                                            <?php if ($row['video_duration']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    dari <?php echo formatDuration($row['video_duration']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td>
                                            <?php 
                                            $status = $row['status'] ?? 'belum_mulai';
                                            $status_class = $status === 'selesai' ? 'success' : 
                                                          ($status === 'sedang_dipelajari' ? 'info' : 'secondary');
                                            $status_text = $status === 'selesai' ? 'Selesai' : 
                                                         ($status === 'sedang_dipelajari' ? 'Belajar' : 'Belum Mulai');
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                            
                                            <?php if ($row['completed']): ?>
                                                <br>
                                                <small class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Completed
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Last Activity -->
                                        <td>
                                            <?php if (!empty($row['last_activity'])): ?>
                                                <small class="text-muted">
                                                    <?php 
                                                    $last = strtotime($row['last_activity']);
                                                    $diff = time() - $last;
                                                    
                                                    if ($diff < 3600) {
                                                        echo floor($diff / 60) . ' menit lalu';
                                                    } elseif ($diff < 86400) {
                                                        echo floor($diff / 3600) . ' jam lalu';
                                                    } else {
                                                        echo date('d/m/Y', $last);
                                                    }
                                                    ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewDetail(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                        title="View Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(data) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Informasi Siswa</h6>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Nama</th>
                        <td>${data.full_name}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>${data.email}</td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Informasi Materi</h6>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">Judul</th>
                        <td>${data.materi_judul}</td>
                    </tr>
                    <tr>
                        <th>Tingkat</th>
                        <td><span class="badge bg-secondary">${data.tingkat_kesulitan}</span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <hr>
        
        <h6 class="text-muted mb-3">Progress Detail</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <h3 class="mb-1">${data.persentase_selesai || 0}%</h3>
                        <small class="text-muted">Completion</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <h3 class="mb-1">${formatSeconds(data.waktu_tonton || 0)}</h3>
                        <small class="text-muted">Watch Time</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <h3 class="mb-1">${data.status === 'selesai' ? '✓' : (data.status === 'sedang_dipelajari' ? '⏳' : '○')}</h3>
                        <small class="text-muted">Status</small>
                    </div>
                </div>
            </div>
        </div>
        
        ${data.tanggal_mulai ? `
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Tanggal Mulai</small>
                    <p><strong>${new Date(data.tanggal_mulai).toLocaleDateString('id-ID')}</strong></p>
                </div>
                ${data.tanggal_selesai ? `
                    <div class="col-md-6">
                        <small class="text-muted">Tanggal Selesai</small>
                        <p><strong>${new Date(data.tanggal_selesai).toLocaleDateString('id-ID')}</strong></p>
                    </div>
                ` : ''}
            </div>
        ` : ''}
    `;
    
    document.getElementById('detailContent').innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

function formatSeconds(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    } else {
        return `${m}:${s.toString().padStart(2, '0')}`;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>