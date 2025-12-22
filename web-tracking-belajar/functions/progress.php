<?php
/**
 * Progress Functions
 * File: functions/progress.php
 */

// ============================================
// PROGRESS CRUD FUNCTIONS
// ============================================

/**
 * Get Progress by Siswa
 */
function getProgressBySiswa($siswa_id, $status = null) {
    $sql = "SELECT 
                pm.*,
                m.judul as materi_judul,
                m.deskripsi as materi_deskripsi,
                k.nama_kategori
            FROM progress_materi pm
            JOIN materi m ON pm.materi_id = m.materi_id
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE pm.siswa_id = :siswa_id";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($status) {
        $sql .= " AND pm.status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY pm.updated_at DESC";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Progress by Materi
 */
function getProgressByMateri($materi_id) {
    $sql = "SELECT 
                pm.*,
                u.full_name as siswa_name,
                u.email as siswa_email
            FROM progress_materi pm
            JOIN users u ON pm.siswa_id = u.user_id
            WHERE pm.materi_id = :materi_id
            ORDER BY pm.nilai_progress DESC, pm.updated_at DESC";
    
    $result = query($sql, ['materi_id' => $materi_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Specific Progress
 */
function getProgress($siswa_id, $materi_id) {
    $sql = "SELECT 
                pm.*,
                m.judul as materi_judul,
                m.deskripsi as materi_deskripsi
            FROM progress_materi pm
            JOIN materi m ON pm.materi_id = m.materi_id
            WHERE pm.siswa_id = :siswa_id AND pm.materi_id = :materi_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'materi_id' => $materi_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Create or Update Progress
 */
function upsertProgress($siswa_id, $materi_id, $data) {
    try {
        // Check if progress exists
        $existing = getProgress($siswa_id, $materi_id);
        
        if ($existing) {
            // Update existing progress
            return updateProgress($existing['progress_id'], $data);
        } else {
            // Create new progress
            return createProgress($siswa_id, $materi_id, $data);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Create Progress
 */
function createProgress($siswa_id, $materi_id, $data) {
    try {
        $sql = "INSERT INTO progress_materi 
                (siswa_id, materi_id, status, nilai_progress, catatan_siswa, tanggal_mulai)
                VALUES 
                (:siswa_id, :materi_id, :status, :nilai_progress, :catatan_siswa, :tanggal_mulai)";
        
        $params = [
            'siswa_id' => $siswa_id,
            'materi_id' => $materi_id,
            'status' => $data['status'] ?? 'belum_mulai',
            'nilai_progress' => $data['nilai_progress'] ?? 0,
            'catatan_siswa' => $data['catatan_siswa'] ?? null,
            'tanggal_mulai' => $data['tanggal_mulai'] ?? date('Y-m-d')
        ];
        
        if (execute($sql, $params)) {
            // Update statistik
            updateStatistikBelajar($siswa_id);
            
            return [
                'success' => true,
                'message' => 'Progress berhasil dibuat',
                'progress_id' => getLastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat progress'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update Progress
 */
function updateProgress($progress_id, $data) {
    try {
        $sql = "UPDATE progress_materi SET 
                status = :status,
                nilai_progress = :nilai_progress,
                catatan_siswa = :catatan_siswa,
                updated_at = NOW()";
        
        $params = [
            'progress_id' => $progress_id,
            'status' => $data['status'],
            'nilai_progress' => $data['nilai_progress'],
            'catatan_siswa' => $data['catatan_siswa'] ?? null
        ];
        
        // If status is selesai, set tanggal_selesai
        if ($data['status'] === 'selesai') {
            $sql .= ", tanggal_selesai = NOW()";
        }
        
        $sql .= " WHERE progress_id = :progress_id";
        
        if (execute($sql, $params)) {
            // Get siswa_id for updating statistik
            $progress = getProgressById($progress_id);
            if ($progress) {
                updateStatistikBelajar($progress['siswa_id']);
            }
            
            return ['success' => true, 'message' => 'Progress berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update progress'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get Progress by ID
 */
function getProgressById($progress_id) {
    $sql = "SELECT * FROM progress_materi WHERE progress_id = :progress_id";
    $result = query($sql, ['progress_id' => $progress_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Update Progress Status
 */
function updateProgressStatus($siswa_id, $materi_id, $status) {
    $progress = getProgress($siswa_id, $materi_id);
    
    if ($progress) {
        return updateProgress($progress['progress_id'], [
            'status' => $status,
            'nilai_progress' => $progress['nilai_progress']
        ]);
    }
    
    return ['success' => false, 'message' => 'Progress tidak ditemukan'];
}

/**
 * Update Progress Value
 */
function updateProgressValue($siswa_id, $materi_id, $nilai_progress) {
    try {
        // Auto determine status based on progress value
        $status = 'belum_mulai';
        if ($nilai_progress > 0 && $nilai_progress < 100) {
            $status = 'sedang_dipelajari';
        } elseif ($nilai_progress >= 100) {
            $status = 'selesai';
        }
        
        $progress = getProgress($siswa_id, $materi_id);
        
        if ($progress) {
            return updateProgress($progress['progress_id'], [
                'status' => $status,
                'nilai_progress' => $nilai_progress
            ]);
        } else {
            return createProgress($siswa_id, $materi_id, [
                'status' => $status,
                'nilai_progress' => $nilai_progress
            ]);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete Progress
 */
function deleteProgress($progress_id) {
    $sql = "DELETE FROM progress_materi WHERE progress_id = :progress_id";
    
    if (execute($sql, ['progress_id' => $progress_id])) {
        return ['success' => true, 'message' => 'Progress berhasil dihapus'];
    }
    
    return ['success' => false, 'message' => 'Gagal menghapus progress'];
}

// ============================================
// PROGRESS STATISTICS
// ============================================

/**
 * Calculate Overall Progress
 */
function calculateOverallProgress($siswa_id) {
    $sql = "SELECT 
                COUNT(*) as total_materi,
                AVG(nilai_progress) as avg_progress,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'sedang_dipelajari' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as not_started
            FROM progress_materi
            WHERE siswa_id = :siswa_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Get Progress by Category
 */
function getProgressByCategory($siswa_id) {
    $sql = "SELECT 
                k.nama_kategori,
                COUNT(*) as total_materi,
                AVG(pm.nilai_progress) as avg_progress,
                SUM(CASE WHEN pm.status = 'selesai' THEN 1 ELSE 0 END) as completed
            FROM progress_materi pm
            JOIN materi m ON pm.materi_id = m.materi_id
            JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE pm.siswa_id = :siswa_id
            GROUP BY k.kategori_id, k.nama_kategori
            ORDER BY avg_progress DESC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Progress Chart Data
 */
function getProgressChartData($siswa_id, $period = 'month') {
    $date_format = ($period === 'week') ? '%Y-%m-%d' : '%Y-%m';
    
    $sql = "SELECT 
                DATE_FORMAT(updated_at, '$date_format') as period,
                AVG(nilai_progress) as avg_progress,
                COUNT(*) as total_updates
            FROM progress_materi
            WHERE siswa_id = :siswa_id
            GROUP BY period
            ORDER BY period ASC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Recent Progress Updates
 */
function getRecentProgressUpdates($siswa_id, $limit = 10) {
    $sql = "SELECT 
                pm.*,
                m.judul as materi_judul
            FROM progress_materi pm
            JOIN materi m ON pm.materi_id = m.materi_id
            WHERE pm.siswa_id = :siswa_id
            ORDER BY pm.updated_at DESC
            LIMIT :limit";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

// ============================================
// STATISTIK BELAJAR
// ============================================

/**
 * Update Statistik Belajar
 */
function updateStatistikBelajar($siswa_id) {
    try {
        $sql = "INSERT INTO statistik_belajar 
                (siswa_id, total_materi, materi_selesai, materi_sedang, total_durasi, avg_progress, last_updated)
                SELECT 
                    :siswa_id,
                    COUNT(*) as total_materi,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as materi_selesai,
                    SUM(CASE WHEN status = 'sedang_dipelajari' THEN 1 ELSE 0 END) as materi_sedang,
                    COALESCE((SELECT SUM(durasi_belajar) FROM aktivitas_belajar WHERE siswa_id = :siswa_id), 0) as total_durasi,
                    AVG(nilai_progress) as avg_progress,
                    NOW() as last_updated
                FROM progress_materi
                WHERE siswa_id = :siswa_id
                ON DUPLICATE KEY UPDATE
                    total_materi = VALUES(total_materi),
                    materi_selesai = VALUES(materi_selesai),
                    materi_sedang = VALUES(materi_sedang),
                    total_durasi = VALUES(total_durasi),
                    avg_progress = VALUES(avg_progress),
                    last_updated = NOW()";
        
        return execute($sql, ['siswa_id' => $siswa_id]);
        
    } catch (Exception $e) {
        error_log('Error updating statistik: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get Statistik Belajar
 */
function getStatistikBelajar($siswa_id) {
    $sql = "SELECT * FROM statistik_belajar WHERE siswa_id = :siswa_id";
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Get All Statistik (for leaderboard)
 */
function getAllStatistik($order_by = 'avg_progress', $limit = 10) {
    $sql = "SELECT 
                s.*,
                u.full_name as siswa_name,
                u.avatar
            FROM statistik_belajar s
            JOIN users u ON s.siswa_id = u.user_id
            WHERE u.is_active = 1
            ORDER BY s.$order_by DESC
            LIMIT :limit";
    
    $result = query($sql, ['limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Calculate Completion Rate
 */
function calculateCompletionRate($siswa_id) {
    $stats = calculateOverallProgress($siswa_id);
    
    if (!$stats || $stats['total_materi'] == 0) {
        return 0;
    }
    
    return round(($stats['completed'] / $stats['total_materi']) * 100, 1);
}

/**
 * Get Progress Comparison
 */
function getProgressComparison($siswa_id) {
    $siswa_progress = calculateOverallProgress($siswa_id);
    
    $sql = "SELECT AVG(nilai_progress) as class_average FROM progress_materi";
    $result = query($sql);
    $class_average = $result ? $result->fetch()['class_average'] : 0;
    
    return [
        'siswa_progress' => $siswa_progress ? $siswa_progress['avg_progress'] : 0,
        'class_average' => round($class_average, 1),
        'difference' => round(($siswa_progress['avg_progress'] ?? 0) - $class_average, 1)
    ];
}
?>