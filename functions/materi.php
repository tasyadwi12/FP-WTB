<?php
/**
 * Admin Functions
 * File: functions/admin.php
 */

// ============================================
// ADMIN DASHBOARD FUNCTIONS
// ============================================

/**
 * Get Admin Dashboard Stats
 */
function getAdminDashboardStats() {
    try {
        $stats = [];
        
        // Total users by role
        $sql = "SELECT role, COUNT(*) as total FROM users WHERE is_active = 1 GROUP BY role";
        $result = query($sql);
        if ($result) {
            $stats['users_by_role'] = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['users_by_role'][$row['role']] = $row['total'];
            }
        }
        
        // Total materi
        $sql = "SELECT COUNT(*) as total FROM materi WHERE is_active = 1";
        $result = query($sql);
        $stats['total_materi'] = $result ? $result->fetch()['total'] : 0;
        
        // Total categories
        $sql = "SELECT COUNT(*) as total FROM kategori_materi WHERE is_active = 1";
        $result = query($sql);
        $stats['total_categories'] = $result ? $result->fetch()['total'] : 0;
        
        // Total progress entries
        $sql = "SELECT COUNT(*) as total FROM progress_materi";
        $result = query($sql);
        $stats['total_progress_entries'] = $result ? $result->fetch()['total'] : 0;
        
        // Total aktivitas today
        $sql = "SELECT COUNT(*) as total FROM aktivitas_belajar WHERE DATE(created_at) = CURDATE()";
        $result = query($sql);
        $stats['activities_today'] = $result ? $result->fetch()['total'] : 0;
        
        // Average progress
        $sql = "SELECT AVG(nilai_progress) as avg FROM progress_materi";
        $result = query($sql);
        $stats['avg_progress'] = $result ? round($result->fetch()['avg'], 1) : 0;
        
        return $stats;
        
    } catch (Exception $e) {
        return [];
    }
}

// ============================================
// USER MANAGEMENT FUNCTIONS
// ============================================

/**
 * Get All Users
 */
function getAllUsers($role = null, $search = null, $page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    if ($role) {
        $sql .= " AND role = :role";
        $params['role'] = $role;
    }
    
    if ($search) {
        $sql .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $per_page;
    $params['offset'] = $offset;
    
    $result = query($sql, $params);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Count Users
 */
function countUsers($role = null, $search = null) {
    $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    $params = [];
    
    if ($role) {
        $sql .= " AND role = :role";
        $params['role'] = $role;
    }
    
    if ($search) {
        $sql .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $result = query($sql, $params);
    return $result ? $result->fetch()['total'] : 0;
}

/**
 * Create User (Admin)
 */
function createUser($data) {
    try {
        // Validate
        if (usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        if (emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email sudah terdaftar'];
        }
        
        $sql = "INSERT INTO users (username, email, password, full_name, role, phone, is_active)
                VALUES (:username, :email, :password, :full_name, :role, :phone, :is_active)";
        
        $params = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'User berhasil dibuat',
                'user_id' => getLastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat user'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update User (Admin)
 */
function updateUser($user_id, $data) {
    try {
        $sql = "UPDATE users SET 
                full_name = :full_name,
                email = :email,
                role = :role,
                phone = :phone,
                is_active = :is_active,
                updated_at = NOW()
                WHERE user_id = :user_id";
        
        $params = [
            'user_id' => $user_id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        if (execute($sql, $params)) {
            return ['success' => true, 'message' => 'User berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update user'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Reset User Password (Admin)
 */
function resetUserPassword($user_id, $new_password) {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
    
    if (execute($sql, ['user_id' => $user_id, 'password' => $hashed])) {
        return ['success' => true, 'message' => 'Password berhasil direset'];
    }
    
    return ['success' => false, 'message' => 'Gagal reset password'];
}

// ============================================
// KATEGORI MANAGEMENT
// ============================================

/**
 * Get All Categories
 */
function getAllCategories($include_inactive = false) {
    $sql = "SELECT * FROM kategori_materi";
    
    if (!$include_inactive) {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY nama_kategori ASC";
    
    $result = query($sql);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Get Category by ID
 */
function getCategoryById($kategori_id) {
    $sql = "SELECT * FROM kategori_materi WHERE kategori_id = :kategori_id";
    $result = query($sql, ['kategori_id' => $kategori_id]);
    return $result ? $result->fetch(PDO::FETCH_ASSOC) : null;
}

/**
 * Create Category
 */
function createCategory($data) {
    try {
        $sql = "INSERT INTO kategori_materi (nama_kategori, deskripsi, icon, is_active)
                VALUES (:nama_kategori, :deskripsi, :icon, :is_active)";
        
        $params = [
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'icon' => $data['icon'] ?? 'fa-folder',
            'is_active' => $data['is_active'] ?? 1
        ];
        
        if (execute($sql, $params)) {
            return [
                'success' => true,
                'message' => 'Kategori berhasil dibuat',
                'kategori_id' => getLastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat kategori'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update Category
 */
function updateCategory($kategori_id, $data) {
    try {
        $sql = "UPDATE kategori_materi SET 
                nama_kategori = :nama_kategori,
                deskripsi = :deskripsi,
                icon = :icon,
                is_active = :is_active,
                updated_at = NOW()
                WHERE kategori_id = :kategori_id";
        
        $params = [
            'kategori_id' => $kategori_id,
            'nama_kategori' => $data['nama_kategori'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'icon' => $data['icon'] ?? 'fa-folder',
            'is_active' => $data['is_active'] ?? 1
        ];
        
        if (execute($sql, $params)) {
            return ['success' => true, 'message' => 'Kategori berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update kategori'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete Category
 */
function deleteCategory($kategori_id) {
    // Check if category has materi
    $sql = "SELECT COUNT(*) as total FROM materi WHERE kategori_id = :kategori_id";
    $result = query($sql, ['kategori_id' => $kategori_id]);
    
    if ($result && $result->fetch()['total'] > 0) {
        return ['success' => false, 'message' => 'Kategori masih memiliki materi'];
    }
    
    $sql = "DELETE FROM kategori_materi WHERE kategori_id = :kategori_id";
    
    if (execute($sql, ['kategori_id' => $kategori_id])) {
        return ['success' => true, 'message' => 'Kategori berhasil dihapus'];
    }
    
    return ['success' => false, 'message' => 'Gagal menghapus kategori'];
}

// ============================================
// SYSTEM STATISTICS
// ============================================

/**
 * Get System Statistics
 */
function getSystemStatistics($start_date = null, $end_date = null) {
    $stats = [];
    
    // User growth
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as total 
            FROM users 
            GROUP BY DATE(created_at) 
            ORDER BY date DESC 
            LIMIT 30";
    $result = query($sql);
    $stats['user_growth'] = $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Activity trends
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as total 
            FROM aktivitas_belajar 
            GROUP BY DATE(created_at) 
            ORDER BY date DESC 
            LIMIT 30";
    $result = query($sql);
    $stats['activity_trends'] = $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Progress distribution
    $sql = "SELECT status, COUNT(*) as total 
            FROM progress_materi 
            GROUP BY status";
    $result = query($sql);
    $stats['progress_distribution'] = $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Top performing students
    $sql = "SELECT u.full_name, AVG(pm.nilai_progress) as avg_progress
            FROM users u
            JOIN progress_materi pm ON u.user_id = pm.siswa_id
            WHERE u.role = 'siswa'
            GROUP BY u.user_id
            ORDER BY avg_progress DESC
            LIMIT 10";
    $result = query($sql);
    $stats['top_students'] = $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    return $stats;
}

/**
 * Get Activity Logs
 */
function getActivityLogs($limit = 50) {
    $sql = "SELECT 
                a.*,
                u.full_name as user_name,
                m.judul as materi_judul
            FROM aktivitas_belajar a
            JOIN users u ON a.siswa_id = u.user_id
            LEFT JOIN materi m ON a.materi_id = m.materi_id
            ORDER BY a.created_at DESC
            LIMIT :limit";
    
    $result = query($sql, ['limit' => $limit]);
    return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Generate System Report
 */
function generateSystemReport($start_date, $end_date) {
    $report = [];
    
    $report['period'] = [
        'start' => $start_date,
        'end' => $end_date
    ];
    
    // New users
    $sql = "SELECT COUNT(*) as total FROM users 
            WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
    $result = query($sql, ['start_date' => $start_date, 'end_date' => $end_date]);
    $report['new_users'] = $result ? $result->fetch()['total'] : 0;
    
    // Total activities
    $sql = "SELECT COUNT(*) as total FROM aktivitas_belajar 
            WHERE DATE(tanggal_aktivitas) BETWEEN :start_date AND :end_date";
    $result = query($sql, ['start_date' => $start_date, 'end_date' => $end_date]);
    $report['total_activities'] = $result ? $result->fetch()['total'] : 0;
    
    // Completed materi
    $sql = "SELECT COUNT(*) as total FROM progress_materi 
            WHERE status = 'selesai' 
            AND DATE(tanggal_selesai) BETWEEN :start_date AND :end_date";
    $result = query($sql, ['start_date' => $start_date, 'end_date' => $end_date]);
    $report['completed_materi'] = $result ? $result->fetch()['total'] : 0;
    
    // Total study time
    $sql = "SELECT SUM(durasi_belajar) as total FROM aktivitas_belajar 
            WHERE DATE(tanggal_aktivitas) BETWEEN :start_date AND :end_date";
    $result = query($sql, ['start_date' => $start_date, 'end_date' => $end_date]);
    $report['total_study_time'] = $result ? $result->fetch()['total'] : 0;
    
    return $report;
}

/**
 * Export Data to CSV
 */
function exportToCSV($table, $filename) {
    $sql = "SELECT * FROM $table";
    $result = query($sql);
    
    if (!$result) {
        return false;
    }
    
    $output = fopen('php://output', 'w');
    
    // Headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Column headers
    $first_row = $result->fetch(PDO::FETCH_ASSOC);
    if ($first_row) {
        fputcsv($output, array_keys($first_row));
        fputcsv($output, $first_row);
        
        // Data rows
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}
?>