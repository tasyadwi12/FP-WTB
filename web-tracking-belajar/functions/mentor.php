<?php
/**
 * Mentor Functions
 * File: functions/mentor.php
 */

// ============================================
// MENTOR DASHBOARD FUNCTIONS
// ============================================

/**
 * Get Mentor Dashboard Stats
 */
function getMentorDashboardStats($mentor_id) {
    try {
        $stats = [];
        
        // Total siswa bimbingan
        $sql = "SELECT COUNT(*) as total FROM mentor_siswa WHERE mentor_id = :mentor_id";
        $result = query($sql, ['mentor_id' => $mentor_id]);
        $stats['total_siswa'] = $result ? $result->fetch()['total'] : 0;
        
        // Siswa aktif (login dalam 7 hari terakhir)
        $sql = "SELECT COUNT(DISTINCT ms.siswa_id) as total 
                FROM mentor_siswa ms
                JOIN users u ON ms.siswa_id = u.user_id
                WHERE ms.mentor_id = :mentor_id 
                AND u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = query($sql, ['mentor_id' => $mentor_id]);
        $stats['siswa_aktif'] = $result ? $result->fetch()['total'] : 0;
        
        // Total penilaian yang diberikan
        $sql = "SELECT COUNT(*) as total FROM penilaian_mentor WHERE mentor_id = :mentor_id";
        $result = query($sql, ['mentor_id' => $mentor_id]);
        $stats['total_penilaian'] = $result ? $result->fetch()['total'] : 0;
        
        // Rata-rata progress siswa
        $sql = "SELECT AVG(nilai_progress) as avg_progress
                FROM progress_materi pm
                JOIN mentor_siswa ms ON pm.siswa_id = ms.siswa_id
                WHERE ms.mentor_id = :mentor_id";
        $result = query($sql, ['mentor_id' => $mentor_id]);
        $stats['avg_siswa_progress'] = $result ? round($result->fetch()['avg_progress'], 1) : 0;
        
        return $stats;
        
    } catch (Exception $e) {
        return [];
    }
}

// ============================================
// MENTOR-SISWA RELATIONSHIP
// ============================================

/**
 * Get Mentor's Students
 */
function getMentorStudents($mentor_id) {
    $sql = "SELECT 
                u.user_id,
                u.username,
                u.full_name,
                u.email,
                u.avatar,
                u.last_login,
                ms.assigned_at,
                COUNT(DISTINCT pm.materi_id) as total_materi_progress,
                AVG(pm.nilai_progress) as avg_progress
            FROM mentor_siswa ms
            JOIN users u ON ms.siswa_id = u.user_id
            LEFT JOIN progress_materi pm ON u.user_id = pm.siswa_id
            WHERE ms.mentor_id = :mentor_id AND u.is_active = 1
            GROUP BY u.user_id
            ORDER BY u.full_name ASC";
    
    $result = query($sql, ['mentor_id' => $mentor_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Student Detail for Mentor
 */
function getStudentDetailForMentor($siswa_id, $mentor_id) {
    // Verify mentor-siswa relationship
    if (!verifyMentorSiswa($mentor_id, $siswa_id)) {
        return null;
    }
    
    $sql = "SELECT 
                u.*,
                ms.assigned_at,
                COUNT(DISTINCT pm.materi_id) as total_materi,
                SUM(CASE WHEN pm.status = 'selesai' THEN 1 ELSE 0 END) as materi_selesai,
                AVG(pm.nilai_progress) as avg_progress,
                SUM(ab.durasi_belajar) as total_durasi_belajar
            FROM users u
            JOIN mentor_siswa ms ON u.user_id = ms.siswa_id
            LEFT JOIN progress_materi pm ON u.user_id = pm.siswa_id
            LEFT JOIN aktivitas_belajar ab ON u.user_id = ab.siswa_id
            WHERE u.user_id = :siswa_id AND ms.mentor_id = :mentor_id
            GROUP BY u.user_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'mentor_id' => $mentor_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Verify Mentor-Siswa Relationship
 */
function verifyMentorSiswa($mentor_id, $siswa_id) {
    $sql = "SELECT * FROM mentor_siswa WHERE mentor_id = :mentor_id AND siswa_id = :siswa_id";
    $result = query($sql, ['mentor_id' => $mentor_id, 'siswa_id' => $siswa_id]);
    return $result && $result->rowCount() > 0;
}

/**
 * Assign Student to Mentor
 */
function assignStudentToMentor($mentor_id, $siswa_id) {
    try {
        // Check if already assigned
        if (verifyMentorSiswa($mentor_id, $siswa_id)) {
            return [
                'success' => false,
                'message' => 'Siswa sudah ditugaskan ke mentor ini'
            ];
        }
        
        $sql = "INSERT INTO mentor_siswa (mentor_id, siswa_id) VALUES (:mentor_id, :siswa_id)";
        
        if (execute($sql, ['mentor_id' => $mentor_id, 'siswa_id' => $siswa_id])) {
            return [
                'success' => true,
                'message' => 'Siswa berhasil ditugaskan'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal menugaskan siswa'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Remove Student from Mentor
 */
function removeStudentFromMentor($mentor_id, $siswa_id) {
    $sql = "DELETE FROM mentor_siswa WHERE mentor_id = :mentor_id AND siswa_id = :siswa_id";
    
    if (execute($sql, ['mentor_id' => $mentor_id, 'siswa_id' => $siswa_id])) {
        return [
            'success' => true,
            'message' => 'Siswa berhasil dihapus dari bimbingan'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal menghapus siswa'
    ];
}

// ============================================
// PENILAIAN / ASSESSMENT FUNCTIONS
// ============================================

/**
 * Get Student Assessments
 */
function getStudentAssessments($siswa_id, $mentor_id = null) {
    $sql = "SELECT p.*, m.full_name as mentor_name, mat.judul as materi_judul
            FROM penilaian_mentor p
            JOIN users m ON p.mentor_id = m.user_id
            LEFT JOIN materi mat ON p.materi_id = mat.materi_id
            WHERE p.siswa_id = :siswa_id";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($mentor_id) {
        $sql .= " AND p.mentor_id = :mentor_id";
        $params['mentor_id'] = $mentor_id;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Create Assessment
 */
function createAssessment($mentor_id, $data) {
    try {
        // Verify mentor-siswa relationship
        if (!verifyMentorSiswa($mentor_id, $data['siswa_id'])) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menilai siswa ini'
            ];
        }
        
        $sql = "INSERT INTO penilaian_mentor 
                (mentor_id, siswa_id, materi_id, nilai, feedback, tanggal_penilaian)
                VALUES 
                (:mentor_id, :siswa_id, :materi_id, :nilai, :feedback, :tanggal_penilaian)";
        
        $params = [
            'mentor_id' => $mentor_id,
            'siswa_id' => $data['siswa_id'],
            'materi_id' => $data['materi_id'] ?? null,
            'nilai' => $data['nilai'],
            'feedback' => $data['feedback'] ?? null,
            'tanggal_penilaian' => $data['tanggal_penilaian'] ?? date('Y-m-d')
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Penilaian berhasil ditambahkan',
                'penilaian_id' => getLastInsertId()
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal menambahkan penilaian'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Update Assessment
 */
function updateAssessment($penilaian_id, $mentor_id, $data) {
    try {
        // Verify ownership
        $sql = "SELECT * FROM penilaian_mentor WHERE penilaian_id = :penilaian_id AND mentor_id = :mentor_id";
        $result = query($sql, ['penilaian_id' => $penilaian_id, 'mentor_id' => $mentor_id]);
        
        if (!$result || $result->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Penilaian tidak ditemukan'
            ];
        }
        
        $sql = "UPDATE penilaian_mentor 
                SET nilai = :nilai,
                    feedback = :feedback,
                    updated_at = NOW()
                WHERE penilaian_id = :penilaian_id AND mentor_id = :mentor_id";
        
        $params = [
            'penilaian_id' => $penilaian_id,
            'mentor_id' => $mentor_id,
            'nilai' => $data['nilai'],
            'feedback' => $data['feedback'] ?? null
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Penilaian berhasil diupdate'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Gagal update penilaian'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete Assessment
 */
function deleteAssessment($penilaian_id, $mentor_id) {
    $sql = "DELETE FROM penilaian_mentor WHERE penilaian_id = :penilaian_id AND mentor_id = :mentor_id";
    
    if (execute($sql, ['penilaian_id' => $penilaian_id, 'mentor_id' => $mentor_id])) {
        return [
            'success' => true,
            'message' => 'Penilaian berhasil dihapus'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal menghapus penilaian'
    ];
}

// ============================================
// STUDENT PROGRESS MONITORING
// ============================================

/**
 * Get Student Progress by Mentor
 */
function getStudentProgress($siswa_id, $mentor_id) {
    // Verify relationship
    if (!verifyMentorSiswa($mentor_id, $siswa_id)) {
        return [];
    }
    
    $sql = "SELECT 
                pm.*,
                m.judul as materi_judul,
                m.deskripsi as materi_deskripsi,
                k.nama_kategori
            FROM progress_materi pm
            JOIN materi m ON pm.materi_id = m.materi_id
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE pm.siswa_id = :siswa_id
            ORDER BY pm.updated_at DESC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Student Learning Activities
 */
function getStudentActivities($siswa_id, $mentor_id, $limit = 20) {
    // Verify relationship
    if (!verifyMentorSiswa($mentor_id, $siswa_id)) {
        return [];
    }
    
    $sql = "SELECT 
                a.*,
                m.judul as materi_judul
            FROM aktivitas_belajar a
            LEFT JOIN materi m ON a.materi_id = m.materi_id
            WHERE a.siswa_id = :siswa_id
            ORDER BY a.tanggal_aktivitas DESC, a.created_at DESC
            LIMIT :limit";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Student Statistics
 */
function getStudentStatistics($siswa_id, $mentor_id) {
    // Verify relationship
    if (!verifyMentorSiswa($mentor_id, $siswa_id)) {
        return null;
    }
    
    $sql = "SELECT * FROM statistik_belajar WHERE siswa_id = :siswa_id";
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

// ============================================
// REPORTS FUNCTIONS
// ============================================

/**
 * Generate Mentor Report
 */
function generateMentorReport($mentor_id, $start_date = null, $end_date = null) {
    $start_date = $start_date ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $end_date ?? date('Y-m-d');
    
    $report = [];
    
    // Students summary
    $report['total_students'] = count(getMentorStudents($mentor_id));
    
    // Assessments given
    $sql = "SELECT COUNT(*) as total FROM penilaian_mentor 
            WHERE mentor_id = :mentor_id 
            AND tanggal_penilaian BETWEEN :start_date AND :end_date";
    $result = query($sql, [
        'mentor_id' => $mentor_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $report['assessments_given'] = $result ? $result->fetch()['total'] : 0;
    
    // Average student progress
    $sql = "SELECT AVG(pm.nilai_progress) as avg_progress
            FROM progress_materi pm
            JOIN mentor_siswa ms ON pm.siswa_id = ms.siswa_id
            WHERE ms.mentor_id = :mentor_id";
    $result = query($sql, ['mentor_id' => $mentor_id]);
    $report['avg_student_progress'] = $result ? round($result->fetch()['avg_progress'], 1) : 0;
    
    return $report;
}
?>