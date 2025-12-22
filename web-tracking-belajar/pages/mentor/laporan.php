<?php
/**
 * Laporan Page - Mentor (FIXED)
 * File: pages/mentor/laporan.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require mentor role
requireRole(ROLE_MENTOR);

// Set page info
$page_title = 'Laporan Bimbingan';
$current_page = 'laporan';

$mentor_id = getUserId();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get My Students Statistics - SIMPLIFIED
try {
    $student_stats_result = query("SELECT 
        u.user_id,
        u.full_name,
        u.avatar
        FROM users u
        INNER JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        WHERE ms.mentor_id = :mentor_id
        ORDER BY u.full_name", ['mentor_id' => $mentor_id]);
    
    $student_stats = $student_stats_result ? $student_stats_result->fetchAll() : [];
    
    // Get stats for each student
    foreach ($student_stats as &$student) {
        // Progress stats
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
        
        // Activity stats
        $activity_result = query("SELECT SUM(durasi_menit) as total_durasi 
            FROM aktivitas_belajar 
            WHERE user_id = :user_id 
            AND tanggal BETWEEN :start_date AND :end_date", [
            'user_id' => $student['user_id'],
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        if ($activity_result) {
            $activity = $activity_result->fetch();
            $student['total_durasi'] = $activity['total_durasi'] ?? 0;
        } else {
            $student['total_durasi'] = 0;
        }
        
        // Penilaian stats
        $penilaian_result = query("SELECT 
            AVG(pn.nilai) as avg_nilai,
            COUNT(DISTINCT pn.penilaian_id) as jumlah_penilaian
            FROM penilaian_mentor pn
            JOIN progress_materi pm ON pn.progress_id = pm.progress_id
            WHERE pm.user_id = :user_id AND pn.mentor_id = :mentor_id", [
            'user_id' => $student['user_id'],
            'mentor_id' => $mentor_id
        ]);
        
        if ($penilaian_result) {
            $penilaian = $penilaian_result->fetch();
            $student['avg_nilai'] = $penilaian['avg_nilai'] ?? 0;
            $student['jumlah_penilaian'] = $penilaian['jumlah_penilaian'] ?? 0;
        } else {
            $student['avg_nilai'] = 0;
            $student['jumlah_penilaian'] = 0;
        }
    }
} catch (Exception $e) {
    $student_stats = [];
    setFlash(ERROR, 'Gagal mengambil data siswa: ' . $e->getMessage());
}

// Overall Statistics
$overall = [
    'total_siswa' => count($student_stats),
    'total_materi_selesai' => 0,
    'total_penilaian' => 0,
    'avg_nilai_keseluruhan' => 0,
    'total_jam' => 0
];

$total_nilai = 0;
$count_nilai = 0;

foreach ($student_stats as $stat) {
    $overall['total_materi_selesai'] += $stat['materi_selesai'];
    $overall['total_penilaian'] += $stat['jumlah_penilaian'];
    $overall['total_jam'] += $stat['total_durasi'];
    
    if ($stat['avg_nilai']) {
        $total_nilai += $stat['avg_nilai'];
        $count_nilai++;
    }
}

if ($count_nilai > 0) {
    $overall['avg_nilai_keseluruhan'] = round($total_nilai / $count_nilai, 1);
}

$overall['total_jam'] = round($overall['total_jam'] / 60, 1);

// Progress by Status
try {
    $progress_by_status_result = query("SELECT 
        pm.status,
        COUNT(*) as jumlah
        FROM progress_materi pm
        WHERE pm.user_id IN (SELECT siswa_id FROM mentor_siswa WHERE mentor_id = :mentor_id)
        AND pm.tanggal_mulai BETWEEN :start_date AND :end_date
        GROUP BY pm.status", [
        'mentor_id' => $mentor_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    $progress_by_status = $progress_by_status_result ? $progress_by_status_result->fetchAll() : [];
} catch (Exception $e) {
    $progress_by_status = [];
}

// Recent Assessments
try {
    $recent_assessments_result = query("SELECT 
        pn.*,
        u.full_name as siswa_nama,
        m.judul as materi_judul
        FROM penilaian_mentor pn
        JOIN progress_materi pm ON pn.progress_id = pm.progress_id
        JOIN users u ON pm.user_id = u.user_id
        JOIN materi m ON pm.materi_id = m.materi_id
        WHERE pn.mentor_id = :mentor_id
        AND pn.tanggal_penilaian BETWEEN :start_date AND :end_date
        ORDER BY pn.tanggal_penilaian DESC
        LIMIT 10", [
        'mentor_id' => $mentor_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    $recent_assessments = $recent_assessments_result ? $recent_assessments_result->fetchAll() : [];
} catch (Exception $e) {
    $recent_assessments = [];
}

// Active Students (last 7 days)
try {
    $active_students_result = query("SELECT 
        u.user_id,
        u.full_name,
        COUNT(DISTINCT DATE(ab.tanggal)) as active_days,
        SUM(ab.durasi_menit) as total_durasi
        FROM users u
        INNER JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
        JOIN aktivitas_belajar ab ON u.user_id = ab.user_id
        WHERE ms.mentor_id = :mentor_id
        AND ab.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY u.user_id
        ORDER BY active_days DESC, total_durasi DESC
        LIMIT 5", ['mentor_id' => $mentor_id]);
    
    $active_students = $active_students_result ? $active_students_result->fetchAll() : [];
} catch (Exception $e) {
    $active_students = [];
}

// Prepare chart data
$chart_labels = [];
$chart_data = [];
foreach ($progress_by_status as $status) {
    if ($status['status'] === 'belum_mulai') {
        $chart_labels[] = 'Belum Mulai';
    } elseif ($status['status'] === 'sedang_dipelajari') {
        $chart_labels[] = 'Sedang';
    } else {
        $chart_labels[] = 'Selesai';
    }
    $chart_data[] = $status['jumlah'];
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
                <p class="text-muted">Overview performa siswa bimbingan Anda</p>
            </div>
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Date Range Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Overall Statistics -->
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
                                <h3 class="mb-0"><?php echo $overall['total_siswa']; ?></h3>
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
                                <h6 class="text-muted mb-1">Materi Selesai</h6>
                                <h3 class="mb-0"><?php echo $overall['total_materi_selesai']; ?></h3>
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
                                <h6 class="text-muted mb-1">Total Jam</h6>
                                <h3 class="mb-0"><?php echo $overall['total_jam']; ?></h3>
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
                                <h3 class="mb-0"><?php echo $overall['avg_nilai_keseluruhan']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Performa Siswa</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Selesai</th>
                                        <th class="text-center">Progress</th>
                                        <th class="text-center">Jam</th>
                                        <th class="text-center">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($student_stats)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Tidak ada data siswa</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($student_stats as $stat): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php 
                                                        $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                        if (isset($stat['avatar']) && $stat['avatar']) {
                                                            $avatar_path = ROOT_PATH . 'uploads/avatars/' . $stat['avatar'];
                                                            if (file_exists($avatar_path)) {
                                                                $avatar_url = BASE_URL . 'uploads/avatars/' . $stat['avatar'];
                                                            }
                                                        }
                                                        ?>
                                                        <img src="<?php echo $avatar_url; ?>" 
                                                             class="rounded-circle me-2" 
                                                             width="32" height="32"
                                                             style="object-fit: cover;"
                                                             alt="Avatar"
                                                             onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                        <?php echo htmlspecialchars($stat['full_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center"><span class="badge bg-primary"><?php echo $stat['total_materi']; ?></span></td>
                                                <td class="text-center"><span class="badge bg-success"><?php echo $stat['materi_selesai']; ?></span></td>
                                                <td class="text-center"><span class="badge bg-warning"><?php echo $stat['materi_progress']; ?></span></td>
                                                <td class="text-center"><?php echo round($stat['total_durasi'] / 60, 1); ?>h</td>
                                                <td class="text-center">
                                                    <?php if ($stat['avg_nilai']): ?>
                                                        <span class="badge bg-<?php echo $stat['avg_nilai'] >= 75 ? 'success' : ($stat['avg_nilai'] >= 60 ? 'warning' : 'danger'); ?>">
                                                            <?php echo round($stat['avg_nilai'], 1); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
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
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Status Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($chart_labels)): ?>
                            <canvas id="progressChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Siswa Aktif (7 Hari)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_students)): ?>
                            <p class="text-muted text-center py-3">Tidak ada aktivitas</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($active_students as $student): ?>
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo $student['active_days']; ?> hari aktif</small>
                                        </div>
                                        <span class="badge bg-success"><?php echo round($student['total_durasi'] / 60, 1); ?>h</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Assessments -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Penilaian Terbaru</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_assessments)): ?>
                    <p class="text-muted text-center py-4">Belum ada penilaian dalam periode ini</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Siswa</th>
                                    <th>Materi</th>
                                    <th class="text-center">Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_assessments as $assessment): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($assessment['tanggal_penilaian'])); ?></td>
                                        <td><?php echo htmlspecialchars($assessment['siswa_nama']); ?></td>
                                        <td><?php echo htmlspecialchars($assessment['materi_judul']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $assessment['nilai'] >= 75 ? 'success' : ($assessment['nilai'] >= 60 ? 'warning' : 'danger'); ?> fs-6">
                                                <?php echo $assessment['nilai']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($assessment['catatan']): ?>
                                                <?php echo htmlspecialchars(substr($assessment['catatan'], 0, 50)); ?>
                                                <?php if (strlen($assessment['catatan']) > 50) echo '...'; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php if (!empty($chart_labels)): ?>
<script>
// Progress Chart
const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: [
                'rgb(209, 213, 219)',
                'rgb(251, 191, 36)',
                'rgb(34, 197, 94)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>