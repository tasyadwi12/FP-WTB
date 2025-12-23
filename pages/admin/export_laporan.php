<?php
/**
 * Export Laporan to CSV
 * File: pages/admin/export_laporan.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'overall';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_' . $type . '_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch($type) {
    case 'overall':
        exportOverallStats($output, $start_date, $end_date);
        break;
    case 'students':
        exportTopStudents($output, $start_date, $end_date);
        break;
    case 'materi':
        exportPopularMateri($output, $start_date, $end_date);
        break;
    case 'mentor':
        exportMentorPerformance($output, $start_date, $end_date);
        break;
    case 'activity':
        exportDailyActivity($output, $start_date, $end_date);
        break;
    case 'complete':
        exportCompleteReport($output, $start_date, $end_date);
        break;
    default:
        fputcsv($output, ['Error: Invalid export type']);
}

fclose($output);
exit;

function exportOverallStats($output, $start_date, $end_date) {
    $stats = query("SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
        (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentor,
        (SELECT COUNT(*) FROM materi) as total_materi,
        (SELECT COUNT(*) FROM progress_materi WHERE status = 'selesai') as total_selesai
    ")->fetch();
    
    fputcsv($output, ['STATISTIK KESELURUHAN']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['Metrik', 'Jumlah']);
    fputcsv($output, ['Total Siswa', $stats['total_siswa']]);
    fputcsv($output, ['Total Mentor', $stats['total_mentor']]);
    fputcsv($output, ['Total Materi', $stats['total_materi']]);
    fputcsv($output, ['Materi Selesai', $stats['total_selesai']]);
}

function exportTopStudents($output, $start_date, $end_date) {
    $students = query("SELECT 
        u.user_id,
        u.username,
        u.full_name,
        u.email,
        COUNT(DISTINCT pm.materi_id) as total_materi,
        COUNT(CASE WHEN pm.status = 'selesai' THEN 1 END) as selesai,
        COUNT(CASE WHEN pm.status = 'sedang_dipelajari' THEN 1 END) as sedang,
        SUM(ab.durasi_menit) as total_durasi,
        ROUND(SUM(ab.durasi_menit) / 60, 2) as total_jam
        FROM users u
        LEFT JOIN progress_materi pm ON u.user_id = pm.user_id
        LEFT JOIN aktivitas_belajar ab ON u.user_id = ab.user_id 
            AND ab.tanggal BETWEEN :start_date AND :end_date
        WHERE u.role = 'siswa'
        GROUP BY u.user_id
        ORDER BY total_materi DESC, selesai DESC", [
        'start_date' => $start_date,
        'end_date' => $end_date
    ])->fetchAll();
    
    fputcsv($output, ['LAPORAN SISWA AKTIF']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Username', 'Nama Lengkap', 'Email', 'Total Materi', 'Selesai', 'Sedang Dipelajari', 'Total Durasi (menit)', 'Total Jam']);
    
    $no = 1;
    foreach($students as $student) {
        fputcsv($output, [
            $no++,
            $student['username'],
            $student['full_name'],
            $student['email'],
            $student['total_materi'],
            $student['selesai'],
            $student['sedang'],
            $student['total_durasi'] ?? 0,
            $student['total_jam'] ?? 0
        ]);
    }
}

function exportPopularMateri($output, $start_date, $end_date) {
    $materi = query("SELECT 
        m.materi_id,
        m.judul,
        k.nama_kategori,
        m.tingkat_kesulitan,
        m.estimasi_waktu,
        COUNT(DISTINCT pm.user_id) as jumlah_siswa,
        COUNT(CASE WHEN pm.status = 'selesai' THEN 1 END) as jumlah_selesai,
        COUNT(CASE WHEN pm.status = 'sedang_dipelajari' THEN 1 END) as sedang_belajar,
        ROUND(AVG(CASE WHEN pm.status = 'selesai' THEN pn.nilai END), 1) as avg_nilai
        FROM materi m
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id
        LEFT JOIN penilaian_mentor pn ON pm.progress_id = pn.progress_id
        WHERE pm.tanggal_mulai BETWEEN :start_date AND :end_date OR pm.tanggal_mulai IS NULL
        GROUP BY m.materi_id
        ORDER BY jumlah_siswa DESC, jumlah_selesai DESC", [
        'start_date' => $start_date,
        'end_date' => $end_date
    ])->fetchAll();
    
    fputcsv($output, ['LAPORAN MATERI POPULER']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Judul Materi', 'Kategori', 'Tingkat Kesulitan', 'Estimasi Waktu (menit)', 'Jumlah Siswa', 'Selesai', 'Sedang Belajar', 'Rata-rata Nilai']);
    
    $no = 1;
    foreach($materi as $m) {
        fputcsv($output, [
            $no++,
            $m['judul'],
            $m['nama_kategori'],
            ucfirst($m['tingkat_kesulitan']),
            $m['estimasi_waktu'],
            $m['jumlah_siswa'],
            $m['jumlah_selesai'],
            $m['sedang_belajar'],
            $m['avg_nilai'] ?? '-'
        ]);
    }
}

function exportMentorPerformance($output, $start_date, $end_date) {
    $mentors = query("SELECT 
        u.user_id,
        u.username,
        u.full_name,
        u.email,
        u.phone,
        COUNT(DISTINCT ms.siswa_id) as jumlah_siswa,
        COUNT(DISTINCT pn.penilaian_id) as jumlah_penilaian,
        ROUND(AVG(pn.nilai), 1) as avg_nilai_diberikan,
        MIN(pn.tanggal_penilaian) as penilaian_pertama,
        MAX(pn.tanggal_penilaian) as penilaian_terakhir
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
    
    fputcsv($output, ['LAPORAN PERFORMA MENTOR']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Username', 'Nama Lengkap', 'Email', 'Telepon', 'Jumlah Siswa', 'Penilaian Diberikan', 'Rata-rata Nilai', 'Penilaian Pertama', 'Penilaian Terakhir']);
    
    $no = 1;
    foreach($mentors as $mentor) {
        fputcsv($output, [
            $no++,
            $mentor['username'],
            $mentor['full_name'],
            $mentor['email'],
            $mentor['phone'] ?? '-',
            $mentor['jumlah_siswa'],
            $mentor['jumlah_penilaian'],
            $mentor['avg_nilai_diberikan'] ?? '-',
            $mentor['penilaian_pertama'] ?? '-',
            $mentor['penilaian_terakhir'] ?? '-'
        ]);
    }
}

function exportDailyActivity($output, $start_date, $end_date) {
    $activities = query("SELECT 
        DATE(tanggal) as tanggal,
        COUNT(DISTINCT user_id) as siswa_aktif,
        SUM(durasi_menit) as total_durasi,
        ROUND(SUM(durasi_menit) / 60, 2) as total_jam,
        COUNT(*) as total_aktivitas
        FROM aktivitas_belajar
        WHERE tanggal BETWEEN :start_date AND :end_date
        GROUP BY DATE(tanggal)
        ORDER BY tanggal DESC", [
        'start_date' => $start_date,
        'end_date' => $end_date
    ])->fetchAll();
    
    fputcsv($output, ['LAPORAN AKTIVITAS HARIAN']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['Tanggal', 'Siswa Aktif', 'Total Aktivitas', 'Total Durasi (menit)', 'Total Jam']);
    
    foreach($activities as $activity) {
        fputcsv($output, [
            date('d-m-Y', strtotime($activity['tanggal'])),
            $activity['siswa_aktif'],
            $activity['total_aktivitas'],
            $activity['total_durasi'],
            $activity['total_jam']
        ]);
    }
}

function exportCompleteReport($output, $start_date, $end_date) {
    // Overall Stats
    fputcsv($output, ['LAPORAN LENGKAP SISTEM PEMBELAJARAN']);
    fputcsv($output, ['Periode', $start_date . ' s/d ' . $end_date]);
    fputcsv($output, ['Tanggal Export', date('d-m-Y H:i:s')]);
    fputcsv($output, []);
    
    // Stats
    $stats = query("SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
        (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentor,
        (SELECT COUNT(*) FROM materi) as total_materi,
        (SELECT COUNT(*) FROM progress_materi WHERE status = 'selesai') as total_selesai,
        (SELECT COUNT(*) FROM progress_materi WHERE status = 'sedang_dipelajari') as total_sedang
    ")->fetch();
    
    fputcsv($output, ['=== STATISTIK KESELURUHAN ===']);
    fputcsv($output, ['Total Siswa', $stats['total_siswa']]);
    fputcsv($output, ['Total Mentor', $stats['total_mentor']]);
    fputcsv($output, ['Total Materi', $stats['total_materi']]);
    fputcsv($output, ['Materi Selesai', $stats['total_selesai']]);
    fputcsv($output, ['Materi Sedang Dipelajari', $stats['total_sedang']]);
    fputcsv($output, []);
    
    // Progress Status
    $progress = query("SELECT 
        status,
        COUNT(*) as jumlah
        FROM progress_materi
        WHERE tanggal_mulai BETWEEN :start_date AND :end_date
        GROUP BY status", [
        'start_date' => $start_date,
        'end_date' => $end_date
    ])->fetchAll();
    
    fputcsv($output, ['=== STATUS PROGRESS (PERIODE) ===']);
    fputcsv($output, ['Status', 'Jumlah']);
    foreach($progress as $p) {
        $status_label = [
            'belum_mulai' => 'Belum Mulai',
            'sedang_dipelajari' => 'Sedang Dipelajari',
            'selesai' => 'Selesai'
        ];
        fputcsv($output, [$status_label[$p['status']] ?? $p['status'], $p['jumlah']]);
    }
    fputcsv($output, []);
    
    // Top Students
    fputcsv($output, ['=== TOP 10 SISWA AKTIF ===']);
    $students = query("SELECT 
        u.username,
        u.full_name,
        COUNT(DISTINCT pm.materi_id) as total_materi,
        COUNT(CASE WHEN pm.status = 'selesai' THEN 1 END) as selesai,
        ROUND(SUM(ab.durasi_menit) / 60, 2) as total_jam
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
    
    fputcsv($output, ['Nama', 'Total Materi', 'Selesai', 'Total Jam']);
    foreach($students as $s) {
        fputcsv($output, [$s['full_name'], $s['total_materi'], $s['selesai'], $s['total_jam'] ?? 0]);
    }
}
?>