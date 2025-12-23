<?php
/**
 * Target Functions
 * File: functions/target.php
 */

// ============================================
// TARGET CRUD FUNCTIONS
// ============================================

/**
 * Get All Targets by Siswa
 */
function getTargetsBySiswa($siswa_id, $status = null) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul,
                k.nama_kategori
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE t.siswa_id = :siswa_id";
    
    $params = ['siswa_id' => $siswa_id];
    
    if ($status) {
        $sql .= " AND t.status = :status";
        $params['status'] = $status;
    }
    
    $sql .= " ORDER BY t.target_date ASC, t.created_at DESC";
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Target by ID
 */
function getTargetById($target_id) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul,
                k.nama_kategori
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
            WHERE t.target_id = :target_id";
    
    $result = query($sql, ['target_id' => $target_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Create Target
 */
function createTarget($siswa_id, $data) {
    try {
        $sql = "INSERT INTO target_belajar 
                (siswa_id, materi_id, judul_target, deskripsi, target_date, status)
                VALUES 
                (:siswa_id, :materi_id, :judul_target, :deskripsi, :target_date, :status)";
        
        $params = [
            'siswa_id' => $siswa_id,
            'materi_id' => $data['materi_id'] ?? null,
            'judul_target' => $data['judul_target'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'target_date' => $data['target_date'],
            'status' => $data['status'] ?? 'pending'
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Target berhasil dibuat',
                'target_id' => getLastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat target'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update Target
 */
function updateTarget($target_id, $siswa_id, $data) {
    try {
        // Verify ownership
        $target = getTargetById($target_id);
        if (!$target || $target['siswa_id'] != $siswa_id) {
            return ['success' => false, 'message' => 'Target tidak ditemukan'];
        }
        
        $sql = "UPDATE target_belajar SET 
                materi_id = :materi_id,
                judul_target = :judul_target,
                deskripsi = :deskripsi,
                target_date = :target_date,
                status = :status,
                updated_at = NOW()
                WHERE target_id = :target_id AND siswa_id = :siswa_id";
        
        $params = [
            'target_id' => $target_id,
            'siswa_id' => $siswa_id,
            'materi_id' => $data['materi_id'] ?? null,
            'judul_target' => $data['judul_target'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'target_date' => $data['target_date'],
            'status' => $data['status']
        ];
        
        if (execute($sql, $params)) {
            return ['success' => true, 'message' => 'Target berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update target'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update Target Status
 */
function updateTargetStatus($target_id, $siswa_id, $status) {
    try {
        $sql = "UPDATE target_belajar SET 
                status = :status,
                completed_at = CASE WHEN :status = 'completed' THEN NOW() ELSE completed_at END,
                updated_at = NOW()
                WHERE target_id = :target_id AND siswa_id = :siswa_id";
        
        $params = [
            'target_id' => $target_id,
            'siswa_id' => $siswa_id,
            'status' => $status
        ];
        
        if (execute($sql, $params)) {
            return ['success' => true, 'message' => 'Status target berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update status'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete Target
 */
function deleteTarget($target_id, $siswa_id) {
    $sql = "DELETE FROM target_belajar WHERE target_id = :target_id AND siswa_id = :siswa_id";
    
    if (execute($sql, ['target_id' => $target_id, 'siswa_id' => $siswa_id])) {
        return ['success' => true, 'message' => 'Target berhasil dihapus'];
    }
    
    return ['success' => false, 'message' => 'Gagal menghapus target'];
}

/**
 * Complete Target
 */
function completeTarget($target_id, $siswa_id) {
    return updateTargetStatus($target_id, $siswa_id, 'completed');
}

/**
 * Cancel Target
 */
function cancelTarget($target_id, $siswa_id) {
    return updateTargetStatus($target_id, $siswa_id, 'cancelled');
}

/**
 * Start Target
 */
function startTarget($target_id, $siswa_id) {
    return updateTargetStatus($target_id, $siswa_id, 'in_progress');
}

// ============================================
// TARGET STATISTICS
// ============================================

/**
 * Get Target Statistics
 */
function getTargetStatistics($siswa_id) {
    $sql = "SELECT 
                COUNT(*) as total_targets,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status != 'completed' AND target_date < CURDATE() THEN 1 ELSE 0 END) as overdue
            FROM target_belajar
            WHERE siswa_id = :siswa_id";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Calculate Target Completion Rate
 */
function calculateTargetCompletionRate($siswa_id) {
    $stats = getTargetStatistics($siswa_id);
    
    if (!$stats || $stats['total_targets'] == 0) {
        return 0;
    }
    
    return round(($stats['completed'] / $stats['total_targets']) * 100, 1);
}

/**
 * Get Upcoming Targets
 */
function getUpcomingTargets($siswa_id, $days = 7) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul,
                DATEDIFF(t.target_date, CURDATE()) as days_left
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.siswa_id = :siswa_id
            AND t.status IN ('pending', 'in_progress')
            AND t.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY t.target_date ASC";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'days' => $days]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Overdue Targets
 */
function getOverdueTargets($siswa_id) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul,
                DATEDIFF(CURDATE(), t.target_date) as days_overdue
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.siswa_id = :siswa_id
            AND t.status NOT IN ('completed', 'cancelled')
            AND t.target_date < CURDATE()
            ORDER BY t.target_date ASC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Completed Targets
 */
function getCompletedTargets($siswa_id, $limit = 10) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul,
                DATEDIFF(t.completed_at, t.created_at) as days_to_complete
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.siswa_id = :siswa_id
            AND t.status = 'completed'
            ORDER BY t.completed_at DESC
            LIMIT :limit";
    
    $result = query($sql, ['siswa_id' => $siswa_id, 'limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Targets by Month
 */
function getTargetsByMonth($siswa_id, $year, $month) {
    $sql = "SELECT 
                t.*,
                m.judul as materi_judul
            FROM target_belajar t
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.siswa_id = :siswa_id
            AND YEAR(t.target_date) = :year
            AND MONTH(t.target_date) = :month
            ORDER BY t.target_date ASC";
    
    $result = query($sql, [
        'siswa_id' => $siswa_id,
        'year' => $year,
        'month' => $month
    ]);
    
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

// ============================================
// TARGET REMINDERS
// ============================================

/**
 * Get Targets Needing Reminder
 */
function getTargetsNeedingReminder($days_before = 3) {
    $sql = "SELECT 
                t.*,
                u.full_name,
                u.email,
                m.judul as materi_judul
            FROM target_belajar t
            JOIN users u ON t.siswa_id = u.user_id
            LEFT JOIN materi m ON t.materi_id = m.materi_id
            WHERE t.status IN ('pending', 'in_progress')
            AND t.target_date = DATE_ADD(CURDATE(), INTERVAL :days_before DAY)";
    
    $result = query($sql, ['days_before' => $days_before]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Create Reminder for Target
 */
function createTargetReminder($siswa_id, $target_id, $reminder_date, $message = null) {
    try {
        $sql = "INSERT INTO reminder (siswa_id, judul, pesan, tanggal_reminder, is_read)
                SELECT 
                    :siswa_id,
                    CONCAT('Reminder: ', judul_target) as judul,
                    :message as pesan,
                    :reminder_date,
                    0 as is_read
                FROM target_belajar
                WHERE target_id = :target_id";
        
        $params = [
            'siswa_id' => $siswa_id,
            'target_id' => $target_id,
            'reminder_date' => $reminder_date,
            'message' => $message ?? 'Jangan lupa untuk menyelesaikan target Anda!'
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Reminder berhasil dibuat',
                'reminder_id' => getLastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat reminder'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get Active Reminders
 */
function getActiveReminders($siswa_id) {
    $sql = "SELECT * FROM reminder 
            WHERE siswa_id = :siswa_id 
            AND is_read = 0
            AND tanggal_reminder <= CURDATE()
            ORDER BY tanggal_reminder ASC";
    
    $result = query($sql, ['siswa_id' => $siswa_id]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Mark Reminder as Read
 */
function markReminderAsRead($reminder_id, $siswa_id) {
    $sql = "UPDATE reminder SET is_read = 1 WHERE reminder_id = :reminder_id AND siswa_id = :siswa_id";
    return execute($sql, ['reminder_id' => $reminder_id, 'siswa_id' => $siswa_id]);
}

// ============================================
// TARGET CALENDAR
// ============================================

/**
 * Get Calendar Events
 */
function getCalendarEvents($siswa_id, $start_date, $end_date) {
    $sql = "SELECT 
                target_id as id,
                judul_target as title,
                target_date as date,
                status,
                CASE 
                    WHEN status = 'completed' THEN 'success'
                    WHEN status = 'cancelled' THEN 'danger'
                    WHEN status = 'in_progress' THEN 'primary'
                    WHEN target_date < CURDATE() THEN 'warning'
                    ELSE 'info'
                END as color
            FROM target_belajar
            WHERE siswa_id = :siswa_id
            AND target_date BETWEEN :start_date AND :end_date
            ORDER BY target_date ASC";
    
    $result = query($sql, [
        'siswa_id' => $siswa_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Target Achievement Rate by Period
 */
function getTargetAchievementRate($siswa_id, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM target_belajar
            WHERE siswa_id = :siswa_id
            AND target_date BETWEEN :start_date AND :end_date";
    
    $result = query($sql, [
        'siswa_id' => $siswa_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    $stats = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
    
    if (!$stats || $stats['total'] == 0) {
        return 0;
    }
    
    return round(($stats['completed'] / $stats['total']) * 100, 1);
}

/**
 * Check if Target is Achievable
 */
function isTargetAchievable($target_id) {
    $target = getTargetById($target_id);
    
    if (!$target) {
        return false;
    }
    
    // Check if target date is in the future
    if (strtotime($target['target_date']) < time()) {
        return false;
    }
    
    // Check if status is not cancelled
    if ($target['status'] === 'cancelled') {
        return false;
    }
    
    // Check if related materi progress is on track (if materi_id exists)
    if ($target['materi_id']) {
        $progress = getProgress($target['siswa_id'], $target['materi_id']);
        
        // If no progress or progress is low, might not be achievable
        if (!$progress || $progress['nilai_progress'] < 50) {
            return false;
        }
    }
    
    return true;
}
?>