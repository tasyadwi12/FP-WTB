<?php
/**
 * Laporan Page - Admin
 * File: pages/admin/laporan.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

// Set page info
$page_title = 'Laporan & Statistik';
$current_page = 'laporan';

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Overall Statistics
$overall_stats = query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
    (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentor,
    (SELECT COUNT(*) FROM materi) as total_materi,
    (SELECT COUNT(*) FROM progress_materi WHERE status = 'selesai') as total_selesai
")->fetch();

// Progress Statistics by Status
$progress_stats = query("SELECT 
    status,
    COUNT(*) as jumlah
    FROM progress_materi
    WHERE tanggal_mulai BETWEEN :start_date AND :end_date
    GROUP BY status", [
    'start_date' => $start_date,
    'end_date' => $end_date
])->fetchAll();

// Top Active Students
$top_students = query("SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(DISTINCT pm.materi_id) as total_materi,
    COUNT(CASE WHEN pm.status = 'selesai' THEN 1 END) as selesai,
    SUM(ab.durasi_menit) as total_durasi
    FROM users u
    LEFT JOIN progress_materi pm ON u.user_id = pm.user_id
    LEFT JOIN aktivitas_belajar ab ON u.user_id = ab.user_id 
        AND ab.tanggal BETWEEN :start_date AND :end_date
    WHERE u.role = 'siswa'
    GROUP BY u.user_id
    ORDER BY total_materi DESC, selesai DESC
    LIMIT 10", [
    'start_date' => $start_date,
    'end_date' => $end_date
])->fetchAll();

// Popular Materi
$popular_materi = query("SELECT 
    m.materi_id,
    m.judul,
    k.nama_kategori,
    m.tingkat_kesulitan,
    COUNT(DISTINCT pm.user_id) as jumlah_siswa,
    COUNT(CASE WHEN pm.status = 'selesai' THEN 1 END) as jumlah_selesai,
    ROUND(AVG(CASE WHEN pm.status = 'selesai' THEN pn.nilai END), 1) as avg_nilai
    FROM materi m
    LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
    LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id
    LEFT JOIN penilaian_mentor pn ON pm.progress_id = pn.progress_id
    WHERE pm.tanggal_mulai BETWEEN :start_date AND :end_date OR pm.tanggal_mulai IS NULL
    GROUP BY m.materi_id
    ORDER BY jumlah_siswa DESC, jumlah_selesai DESC
    LIMIT 10", [
    'start_date' => $start_date,
    'end_date' => $end_date
])->fetchAll();

// Daily Activity
$daily_activity = query("SELECT 
    DATE(tanggal) as tanggal,
    COUNT(DISTINCT user_id) as siswa_aktif,
    SUM(durasi_menit) as total_durasi
    FROM aktivitas_belajar
    WHERE tanggal BETWEEN :start_date AND :end_date
    GROUP BY DATE(tanggal)
    ORDER BY tanggal DESC", [
    'start_date' => $start_date,
    'end_date' => $end_date
])->fetchAll();

// Mentor Performance
$mentor_performance = query("SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(DISTINCT ms.siswa_id) as jumlah_siswa,
    COUNT(DISTINCT pn.penilaian_id) as jumlah_penilaian,
    ROUND(AVG(pn.nilai), 1) as avg_nilai_diberikan
    FROM users u
    LEFT JOIN mentor_siswa ms ON u.user_id = ms.mentor_id
    LEFT JOIN penilaian_mentor pn ON u.user_id = pn.mentor_id 
        AND pn.tanggal_penilaian BETWEEN :start_date AND :end_date
    WHERE u.role = 'mentor'
    GROUP BY u.user_id
    ORDER BY jumlah_siswa DESC", [
    'start_date' => $start_date,
    'end_date' => $end_date
])->fetchAll();

// Prepare chart data
$chart_labels = [];
$chart_siswa = [];
$chart_durasi = [];
foreach ($daily_activity as $activity) {
    $chart_labels[] = date('d M', strtotime($activity['tanggal']));
    $chart_siswa[] = $activity['siswa_aktif'];
    $chart_durasi[] = round($activity['total_durasi'] / 60, 1); // convert to hours
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
                <h2 class="mb-1"><?php echo $page_title; ?></h2>
                <p class="text-muted">Overview performa sistem pembelajaran</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Cetak
                </button>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2"></i>Export CSV
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Pilih Jenis Export</h6></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=complete&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-file-csv me-2"></i>Laporan Lengkap
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=overall&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-chart-pie me-2"></i>Statistik Keseluruhan
                    </a></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=students&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-user-graduate me-2"></i>Data Siswa
                    </a></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=materi&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-book me-2"></i>Data Materi
                    </a></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=mentor&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Performa Mentor
                    </a></li>
                    <li><a class="dropdown-item" href="export_laporan.php?type=activity&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                        <i class="fas fa-calendar-day me-2"></i>Aktivitas Harian
                    </a></li>
                </ul>
            </div>
        </div>
        
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
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Siswa</h6>
                                <h3 class="mb-0"><?php echo $overall_stats['total_siswa']; ?></h3>
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
                                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Mentor</h6>
                                <h3 class="mb-0"><?php echo $overall_stats['total_mentor']; ?></h3>
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
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Materi</h6>
                                <h3 class="mb-0"><?php echo $overall_stats['total_materi']; ?></h3>
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
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Materi Selesai</h6>
                                <h3 class="mb-0"><?php echo $overall_stats['total_selesai']; ?></h3>
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
                        <h5 class="mb-0">Aktivitas Harian</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Status Progress</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Students -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top 10 Siswa Aktif</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th class="text-center">Materi</th>
                                        <th class="text-center">Selesai</th>
                                        <th class="text-center">Jam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_students)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($top_students as $student): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                <td class="text-center"><span class="badge bg-primary"><?php echo $student['total_materi']; ?></span></td>
                                                <td class="text-center"><span class="badge bg-success"><?php echo $student['selesai']; ?></span></td>
                                                <td class="text-center"><?php echo round($student['total_durasi'] / 60, 1); ?>h</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Popular Materi -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Materi Populer</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Judul</th>
                                        <th class="text-center">Siswa</th>
                                        <th class="text-center">Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($popular_materi)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($popular_materi as $materi): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars(substr($materi['judul'], 0, 30)); ?>
                                                    <?php if (strlen($materi['judul']) > 30) echo '...'; ?>
                                                </td>
                                                <td class="text-center"><span class="badge bg-info"><?php echo $materi['jumlah_siswa']; ?></span></td>
                                                <td class="text-center"><span class="badge bg-success"><?php echo $materi['jumlah_selesai']; ?></span></td>
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
        
        <!-- Mentor Performance -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Performa Mentor</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Mentor</th>
                                <th class="text-center">Jumlah Siswa</th>
                                <th class="text-center">Penilaian Diberikan</th>
                                <th class="text-center">Rata-rata Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mentor_performance)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data mentor</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mentor_performance as $mentor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mentor['full_name']); ?></td>
                                        <td class="text-center"><span class="badge bg-primary"><?php echo $mentor['jumlah_siswa']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-info"><?php echo $mentor['jumlah_penilaian']; ?></span></td>
                                        <td class="text-center">
                                            <?php if ($mentor['avg_nilai_diberikan']): ?>
                                                <span class="badge bg-success"><?php echo $mentor['avg_nilai_diberikan']; ?></span>
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
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Activity Chart
const activityCtx = document.getElementById('activityChart').getContext('2d');
new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_reverse($chart_labels)); ?>,
        datasets: [{
            label: 'Siswa Aktif',
            data: <?php echo json_encode(array_reverse($chart_siswa)); ?>,
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Jam Belajar',
            data: <?php echo json_encode(array_reverse($chart_durasi)); ?>,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Progress Chart
const progressCtx = document.getElementById('progressChart').getContext('2d');
const progressData = <?php echo json_encode($progress_stats); ?>;
const labels = progressData.map(item => {
    if (item.status === 'belum_mulai') return 'Belum Mulai';
    if (item.status === 'sedang_dipelajari') return 'Sedang';
    if (item.status === 'selesai') return 'Selesai';
    return item.status;
});
const data = progressData.map(item => item.jumlah);

new Chart(progressCtx, {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{
            data: data,
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

<?php include '../../includes/footer.php'; ?>