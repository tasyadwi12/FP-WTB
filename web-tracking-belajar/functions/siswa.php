<?php
/**
 * Siswa Functions
 * File: functions/siswa.php
 */

// ============================================
// SISWA PROFILE FUNCTIONS
// ============================================

/**
 * Get Siswa Dashboard Stats
 */
function getSiswaDashboardStats($siswa_id) {
    try {
        $stats = [];
        
        // Total materi
        $sql = "SELECT COUNT(*) as total FROM materi WHERE is_active = 1";
        $result = query($sql);
        $stats['total_materi'] = $result ? $result->fetch()['total'] : 0;
        
        // Materi selesai
        $sql = "SELECT COUNT(*) as total FROM progress_materi 
                WHERE siswa_id = :siswa_id AND status = 'selesai'";
        $result = query($sql, ['siswa_id' => $siswa_id]);
        $stats['materi_selesai'] = $result ? $result->fetch()['total'] : 0;
        
        // Materi sedang dipelajari
        $sql = "SELECT COUNT(*) as total FROM progress_materi 
                WHERE siswa_id = :siswa_id AND status = 'sedang_dipelajari'";
        $result = query($sql, ['siswa_id' => $siswa_id]);
        $stats['materi_progress'] = $result ? $result->fetch()['total'] : 0;
        
        // Target aktif
        $sql = "SELECT COUNT(*) as total FROM target_belajar 
                WHERE siswa_id = :siswa_id AND status IN ('pending', 'in_progress')";
        $result = query($sql, ['siswa_id' => $siswa_id]);
        $stats['target_aktif'] = $result ? $result->fetch()['total'] : 0;
        
        // Progress percentage
        if ($stats['total_materi'] > 0) {
            $stats['progress_percentage'] = round(($stats['materi_selesai'] / $stats['total_materi']) * 100, 1);
        } else {
            $stats['progress_percentage'] = 0;
        }
        
        return $stats;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get Siswa Recent Activities
 */
function getSiswaRecentActivities($siswa_id, $limit = 10) {
    $sql = "SELECT a.*, m.judul as materi_judul 
            FROM aktivitas_belajar a
            LEFT JOIN materi m ON a.materi_id = m.materi_id
            WHERE a.siswa_id = :siswa_id
            ORDER BY a.tanggal_aktivitas DESC, a.created_at DESC
            LIMIT :limit";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Siswa Progress Summary
 */
function getSiswaProgressSummary($siswa_id) {
    $sql = "SELECT 
                COUNT(*) as total_materi,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'sedang_dipelajari' THEN 1 ELSE 0 END) as sedang,
                SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum,
                AVG(nilai_progress) as avg_progress
            FROM progress_materi
            WHERE siswa_id = :siswa_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

// ============================================
// SISWA MATERI FUNCTIONS
// ============================================

/**
 * Get Available Materi for Siswa
 */
function getAvailableMateri($siswa_id, $kategori_id = null) {
    $sql = "SELECT m.*, k.nama_kategori,
            COALESCE(p.status, 'belum_mulai') as status,
            p.nilai_progress
            FROM materi m
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            LEFT JOIN progress_materi p ON m.materi_id = p.materi_id AND p.siswa_id = :siswa_id
            WHERE m.is_active = 1";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($kategori_id) {
        $sql .= " AND m.kategori_id = :kategori_id";
        $params['kategori_id'] = $kategori_id;
    }
    
    $sql .= " ORDER BY k.nama_kategori, m.judul";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Siswa Materi Detail
 */
function getSiswaMateriDetail($materi_id, $siswa_id) {
    $sql = "SELECT m.*, k.nama_kategori,
            COALESCE(p.status, 'belum_mulai') as progress_status,
            p.nilai_progress,
            p.catatan_siswa,
            p.tanggal_mulai,
            p.tanggal_selesai
            FROM materi m
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            LEFT JOIN progress_materi p ON m.materi_id = p.materi_id AND p.siswa_id = :siswa_id
            WHERE m.materi_id = :materi_id AND m.is_active = 1";
    
    $result = query($sql, ['materi_id' => $materi_id, 'siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

// ============================================
// SISWA TARGET FUNCTIONS
// ============================================

/**
 * Get Siswa Targets
 */
function getSiswaTargets($siswa_id, $status = null) {
    $sql = "SELECT t.*, m.judul as materi_judul
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.siswa_id = :siswa_id";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($status) {
        $sql .= " AND t.status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY t.target_date ASC";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Create Siswa Target
 */
function createSiswaTarget($siswa_id, $data) {
    try {
        $sql = "INSERT INTO target_belajar 
                (siswa_id, materi_id, judul_target, deskripsi, target_date, status)
                VALUES 
                (:siswa_id, :materi_id, :judul_target, :deskripsi, :target_date, 'pending')";
        
        $params = [
            'siswa_id' => $siswa_id,
            'materi_id' => $data['materi_id'] ?? null,
            'judul_target' => $data['judul_target'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'target_date' => $data['target_date']
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Target berhasil dibuat',
                'target_id' => getLastInsertId()
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal membuat target'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Update Target Status
 */
function updateTargetStatus($target_id, $siswa_id, $status) {
    $sql = "UPDATE target_belajar 
            SET status = :status, 
                completed_at = CASE WHEN :status = 'completed' THEN NOW() ELSE NULL END,
                updated_at = NOW()
            WHERE target_id = :target_id AND siswa_id = :siswa_id";
    
    $params = [
        'target_id' => $target_id,
        'siswa_id' => $siswa_id,
        'status' => $status
    ];
    
    return execute($sql, $params);
}

// ============================================
// SISWA LEARNING HISTORY
// ============================================

/**
 * Get Learning History
 */
function getLearningHistory($siswa_id, $start_date = null, $end_date = null) {
    $sql = "SELECT a.*, m.judul as materi_judul, k.nama_kategori
            FROM aktivitas_belajar a
            LEFT JOIN materi m ON a.materi_id = m.materi_id
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE a.siswa_id = :siswa_id";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($start_date && $end_date) {
        $sql .= " AND a.tanggal_aktivitas BETWEEN :start_date AND :end_date";
        $params['start_date'] = $start_date;
        $params['end_date'] = $end_date;
    }
    
    $sql .= " ORDER BY a.tanggal_aktivitas DESC, a.created_at DESC";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Add Learning Activity
 */
function addLearningActivity($siswa_id, $data) {
    try {
        $sql = "INSERT INTO aktivitas_belajar 
                (siswa_id, materi_id, tanggal_aktivitas, durasi_belajar, catatan)
                VALUES 
                (:siswa_id, :materi_id, :tanggal_aktivitas, :durasi_belajar, :catatan)";
        
        $params = [
            'siswa_id' => $siswa_id,
            'materi_id' => $data['materi_id'],
            'tanggal_aktivitas' => $data['tanggal_aktivitas'] ?? date('Y-m-d'),
            'durasi_belajar' => $data['durasi_belajar'] ?? 0,
            'catatan' => $data['catatan'] ?? null
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Aktivitas belajar berhasil ditambahkan'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal menambahkan aktivitas'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

// ============================================
// SISWA STATISTICS
// ============================================

/**
 * Get Weekly Study Stats
 */
function getWeeklyStudyStats($siswa_id) {
    $sql = "SELECT 
                DATE(tanggal_aktivitas) as date,
                SUM(durasi_belajar) as total_durasi,
                COUNT(*) as total_aktivitas
            FROM aktivitas_belajar
            WHERE siswa_id = :siswa_id 
            AND tanggal_aktivitas >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(tanggal_aktivitas)
            ORDER BY date ASC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Monthly Progress
 */
function getMonthlyProgress($siswa_id, $year = null, $month = null) {
    $year = $year ?? date('Y');
    $month = $month ?? date('m');
    
    $sql = "SELECT 
                DAY(tanggal_aktivitas) as day,
                COUNT(*) as activities,
                SUM(durasi_belajar) as total_duration
            FROM aktivitas_belajar
            WHERE siswa_id = :siswa_id 
            AND YEAR(tanggal_aktivitas) = :year 
            AND MONTH(tanggal_aktivitas) = :month
            GROUP BY DAY(tanggal_aktivitas)
            ORDER BY day ASC";
    
    $result = query($sql, [
        'siswa_id' => $siswa_id,
        'year' => $year,
        'month' => $month
    ]);
    
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Study Time by Category
 */
function getStudyTimeByCategory($siswa_id) {
    $sql = "SELECT 
                k.nama_kategori,
                SUM(a.durasi_belajar) as total_durasi,
                COUNT(DISTINCT a.materi_id) as total_materi
            FROM aktivitas_belajar a
            JOIN materi m ON a.materi_id = m.materi_id
            JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE a.siswa_id = :siswa_id
            GROUP BY k.kategori_id, k.nama_kategori
            ORDER BY total_durasi DESC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Siswa Rank
 */
function getSiswaRank($siswa_id) {
    $sql = "SELECT 
                user_id,
                full_name,
                total_durasi,
                @rank := @rank + 1 as ranking
            FROM (
                SELECT 
                    u.user_id,
                    u.full_name,
                    COALESCE(SUM(a.durasi_belajar), 0) as total_durasi
                FROM users u
                LEFT JOIN aktivitas_belajar a ON u.user_id = a.siswa_id
                WHERE u.role = 'siswa' AND u.is_active = 1
                GROUP BY u.user_id, u.full_name
                ORDER BY total_durasi DESC
            ) as ranked,
            (SELECT @rank := 0) r
            HAVING user_id = :siswa_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}
?>