<?php
/**
 * Siswa Functions - FIXED & UPDATED
 * File: functions/siswa_functions.php
 * Functions for siswa dashboard and features
 */

// ============================================
// SISWA DASHBOARD FUNCTIONS
// ============================================

/**
 * Get Siswa Dashboard Statistics - FIXED
 */
function getSiswaDashboardStats($siswa_id) {
    try {
        // Total materi yang tersedia
        $total_materi_query = "SELECT COUNT(*) as total FROM materi WHERE is_active = 1";
        $total_materi_result = queryOne($total_materi_query);
        $total_materi = $total_materi_result['total'] ?? 0;
        
        // Materi yang sudah selesai (100%)
        $selesai_query = "
            SELECT COUNT(*) as total 
            FROM progress_materi 
            WHERE user_id = :user_id 
            AND persentase_selesai >= 100
        ";
        $selesai_result = queryOne($selesai_query, ['user_id' => $siswa_id]);
        $materi_selesai = $selesai_result['total'] ?? 0;
        
        // Materi sedang dipelajari (1-99%)
        $progress_query = "
            SELECT COUNT(*) as total 
            FROM progress_materi 
            WHERE user_id = :user_id 
            AND persentase_selesai > 0 
            AND persentase_selesai < 100
        ";
        $progress_result = queryOne($progress_query, ['user_id' => $siswa_id]);
        $materi_progress = $progress_result['total'] ?? 0;
        
        // Target aktif
        $target_query = "
            SELECT COUNT(*) as total 
            FROM target_belajar 
            WHERE user_id = :user_id 
            AND status = 'aktif'
        ";
        $target_result = queryOne($target_query, ['user_id' => $siswa_id]);
        $target_aktif = $target_result['total'] ?? 0;
        
        // Hitung persentase progress keseluruhan
        $progress_percentage = $total_materi > 0 ? round(($materi_selesai / $total_materi) * 100, 1) : 0;
        
        return [
            'total_materi' => $total_materi,
            'materi_selesai' => $materi_selesai,
            'materi_progress' => $materi_progress,
            'target_aktif' => $target_aktif,
            'progress_percentage' => $progress_percentage
        ];
        
    } catch (Exception $e) {
        error_log("Error in getSiswaDashboardStats: " . $e->getMessage());
        return [
            'total_materi' => 0,
            'materi_selesai' => 0,
            'materi_progress' => 0,
            'target_aktif' => 0,
            'progress_percentage' => 0
        ];
    }
}

/**
 * Get Siswa Recent Activities - FIXED
 */
function getSiswaRecentActivities($siswa_id, $limit = 5) {
    try {
        $sql = "SELECT 
                    ab.aktivitas_id,
                    ab.aktivitas,
                    ab.durasi_menit as durasi_belajar,
                    ab.catatan,
                    ab.tanggal,
                    ab.created_at,
                    m.judul as materi_judul,
                    k.nama_kategori
                FROM aktivitas_belajar ab
                LEFT JOIN materi m ON ab.materi_id = m.materi_id
                LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
                WHERE ab.user_id = :user_id
                ORDER BY ab.created_at DESC
                LIMIT :limit";
        
        $stmt = query($sql, [
            'user_id' => $siswa_id,
            'limit' => $limit
        ]);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getSiswaRecentActivities: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Siswa Progress Summary - FIXED
 */
function getSiswaProgressSummary($siswa_id) {
    try {
        $sql = "SELECT 
                    CASE 
                        WHEN persentase_selesai >= 100 THEN 'selesai'
                        WHEN persentase_selesai > 0 THEN 'sedang_dipelajari'
                        ELSE 'belum_mulai'
                    END as status,
                    COUNT(*) as jumlah
                FROM progress_materi
                WHERE user_id = :user_id
                GROUP BY status";
        
        $stmt = query($sql, ['user_id' => $siswa_id]);
        
        $result = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['status']] = $row['jumlah'];
            }
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error in getSiswaProgressSummary: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Upcoming Targets - FIXED
 */
function getUpcomingTargets($siswa_id, $days = 7) {
    try {
        $sql = "SELECT 
                    tb.target_id,
                    tb.judul_target,
                    tb.deskripsi,
                    tb.target_date,
                    tb.status,
                    m.judul as materi_judul,
                    DATEDIFF(tb.target_date, CURDATE()) as days_left
                FROM target_belajar tb
                LEFT JOIN materi m ON tb.materi_id = m.materi_id
                WHERE tb.user_id = :user_id
                AND tb.status = 'aktif'
                AND tb.target_date >= CURDATE()
                AND tb.target_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY tb.target_date ASC
                LIMIT 5";
        
        $stmt = query($sql, [
            'user_id' => $siswa_id,
            'days' => $days
        ]);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getUpcomingTargets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Overdue Targets - FIXED
 */
function getOverdueTargets($siswa_id) {
    try {
        $sql = "SELECT 
                    tb.target_id,
                    tb.judul_target,
                    tb.target_date,
                    m.judul as materi_judul,
                    DATEDIFF(CURDATE(), tb.target_date) as days_overdue
                FROM target_belajar tb
                LEFT JOIN materi m ON tb.materi_id = m.materi_id
                WHERE tb.user_id = :user_id
                AND tb.status = 'aktif'
                AND tb.target_date < CURDATE()
                ORDER BY tb.target_date ASC";
        
        $stmt = query($sql, ['user_id' => $siswa_id]);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getOverdueTargets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Recommended Materi - FIXED
 */
function getRecommendedMateri($siswa_id, $limit = 3) {
    try {
        $sql = "SELECT 
                    m.materi_id,
                    m.judul,
                    m.deskripsi,
                    k.nama_kategori,
                    COALESCE(pm.persentase_selesai, 0) as progress
                FROM materi m
                LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
                LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id AND pm.user_id = :user_id
                WHERE m.is_active = 1
                AND (pm.persentase_selesai IS NULL OR pm.persentase_selesai < 100)
                ORDER BY 
                    CASE 
                        WHEN pm.persentase_selesai > 0 THEN 0
                        ELSE 1
                    END,
                    pm.last_accessed DESC,
                    m.created_at DESC
                LIMIT :limit";
        
        $stmt = query($sql, [
            'user_id' => $siswa_id,
            'limit' => $limit
        ]);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getRecommendedMateri: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Total Study Time Today - FIXED
 */
function getTotalStudyTimeToday($siswa_id) {
    try {
        $sql = "SELECT COALESCE(SUM(durasi_menit), 0) as total_durasi
                FROM aktivitas_belajar
                WHERE user_id = :user_id
                AND DATE(tanggal) = CURDATE()";
        
        $result = queryOne($sql, ['user_id' => $siswa_id]);
        return $result['total_durasi'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error in getTotalStudyTimeToday: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calculate Study Streak - NEW & DYNAMIC
 */
function calculateStudyStreak($siswa_id) {
    try {
        $consecutive_streak = 0;
        $check_date = date('Y-m-d');
        
        // Check last 30 days for consecutive streak
        for ($i = 0; $i < 30; $i++) {
            $activity_check = queryOne("
                SELECT COUNT(*) as has_activity
                FROM aktivitas_belajar
                WHERE user_id = :user_id
                AND DATE(tanggal) = :check_date
            ", [
                'user_id' => $siswa_id,
                'check_date' => $check_date
            ]);
            
            if ($activity_check['has_activity'] > 0) {
                $consecutive_streak++;
                $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $consecutive_streak;
        
    } catch (Exception $e) {
        error_log("Error in calculateStudyStreak: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get Study Streak with Message - NEW
 */
function getStudyStreakWithMessage($siswa_id) {
    $streak = calculateStudyStreak($siswa_id);
    
    // Get streak message based on days
    if ($streak >= 30) {
        $message = "Luar biasa! Kamu legend! 🏆";
        $emoji = "🔥";
    } elseif ($streak >= 14) {
        $message = "Hebat! Pertahankan semangat!";
        $emoji = "🌟";
    } elseif ($streak >= 7) {
        $message = "Bagus! Terus konsisten ya!";
        $emoji = "⭐";
    } elseif ($streak >= 3) {
        $message = "Pertahankan momentum belajarmu!";
        $emoji = "💪";
    } elseif ($streak > 0) {
        $message = "Awal yang baik! Terus semangat!";
        $emoji = "👍";
    } else {
        $message = "Yuk mulai belajar hari ini!";
        $emoji = "📚";
    }
    
    return [
        'streak' => $streak,
        'message' => $message,
        'emoji' => $emoji
    ];
}

/**
 * Check if Studied Today - NEW
 */
function hasStudiedToday($siswa_id) {
    try {
        $result = queryOne("
            SELECT COUNT(*) as count
            FROM aktivitas_belajar
            WHERE user_id = :user_id
            AND DATE(tanggal) = CURDATE()
        ", ['user_id' => $siswa_id]);
        
        return ($result['count'] ?? 0) > 0;
        
    } catch (Exception $e) {
        error_log("Error in hasStudiedToday: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Materi by Category - FIXED
 */
function getMateriByCategory($kategori_id = null, $siswa_id = null) {
    try {
        $sql = "SELECT 
                    m.*,
                    k.nama_kategori,
                    pm.persentase_selesai as progress_percentage
                FROM materi m
                LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id";
        
        if ($siswa_id) {
            $sql .= " LEFT JOIN progress_materi pm ON m.materi_id = pm.materi_id AND pm.user_id = :user_id";
        }
        
        $sql .= " WHERE m.is_active = 1";
        
        if ($kategori_id) {
            $sql .= " AND m.kategori_id = :kategori_id";
        }
        
        $sql .= " ORDER BY m.created_at DESC";
        
        $params = [];
        if ($siswa_id) {
            $params['user_id'] = $siswa_id;
        }
        if ($kategori_id) {
            $params['kategori_id'] = $kategori_id;
        }
        
        $stmt = query($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getMateriByCategory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get All Categories with Count - FIXED
 */
function getAllCategories() {
    try {
        $sql = "SELECT 
                    k.*, 
                    COUNT(m.materi_id) as total_materi
                FROM kategori_materi k
                LEFT JOIN materi m ON k.kategori_id = m.kategori_id AND m.is_active = 1
                GROUP BY k.kategori_id
                ORDER BY k.nama_kategori ASC";
        
        $stmt = query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
    } catch (Exception $e) {
        error_log("Error in getAllCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Materi Progress - NEW
 */
function getMateriProgress($siswa_id, $materi_id) {
    try {
        $sql = "SELECT * FROM progress_materi 
                WHERE user_id = :user_id 
                AND materi_id = :materi_id";
        
        return queryOne($sql, [
            'user_id' => $siswa_id,
            'materi_id' => $materi_id
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getMateriProgress: " . $e->getMessage());
        return null;
    }
}

/**
 * Update Materi Progress - NEW
 */
function updateMateriProgress($siswa_id, $materi_id, $percentage) {
    try {
        // Check if progress exists
        $existing = getMateriProgress($siswa_id, $materi_id);
        
        if ($existing) {
            // Update existing progress
            $sql = "UPDATE progress_materi 
                    SET persentase_selesai = :percentage,
                        last_accessed = NOW()
                    WHERE user_id = :user_id 
                    AND materi_id = :materi_id";
        } else {
            // Insert new progress
            $sql = "INSERT INTO progress_materi 
                    (user_id, materi_id, persentase_selesai, last_accessed) 
                    VALUES (:user_id, :materi_id, :percentage, NOW())";
        }
        
        return execute($sql, [
            'user_id' => $siswa_id,
            'materi_id' => $materi_id,
            'percentage' => $percentage
        ]);
        
    } catch (Exception $e) {
        error_log("Error in updateMateriProgress: " . $e->getMessage());
        return false;
    }
}

/**
 * Log Activity - NEW
 */
function logActivity($siswa_id, $materi_id, $aktivitas, $durasi_menit, $catatan = null) {
    try {
        $sql = "INSERT INTO aktivitas_belajar 
                (user_id, materi_id, aktivitas, durasi_menit, catatan, tanggal, created_at) 
                VALUES (:user_id, :materi_id, :aktivitas, :durasi_menit, :catatan, CURDATE(), NOW())";
        
        return execute($sql, [
            'user_id' => $siswa_id,
            'materi_id' => $materi_id,
            'aktivitas' => $aktivitas,
            'durasi_menit' => $durasi_menit,
            'catatan' => $catatan
        ]);
        
    } catch (Exception $e) {
        error_log("Error in logActivity: " . $e->getMessage());
        return false;
    }
}

/**
 * Render Quick Actions
 */
function renderQuickActions($actions) {
    echo '<div class="quick-actions-grid">';
    foreach ($actions as $action) {
        $color = $action['color'] ?? 'primary';
        
        echo '<a href="' . e($action['url']) . '" class="quick-action-card ' . $color . '">';
        echo '<div class="quick-action-icon">';
        echo '<i class="' . e($action['icon']) . '"></i>';
        echo '</div>';
        echo '<div class="quick-action-content">';
        echo '<h6>' . e($action['title']) . '</h6>';
        echo '<p>' . e($action['description']) . '</p>';
        echo '</div>';
        echo '<div class="quick-action-arrow">';
        echo '<i class="fas fa-arrow-right"></i>';
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';
}
?>