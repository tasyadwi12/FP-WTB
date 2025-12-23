<?php
/**
 * Settings Page - Admin
 * File: pages/admin/settings.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

// Set page info
$page_title = 'Pengaturan Sistem';
$current_page = 'settings';

// Process Category Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add/Edit Category
    if ($action === 'save_category') {
        $kategori_id = $_POST['kategori_id'] ?? 0;
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        
        if (empty($nama_kategori)) {
            setFlash(ERROR, 'Nama kategori wajib diisi!');
        } else {
            if ($kategori_id > 0) {
                // Update
                $sql = "UPDATE kategori_materi SET nama_kategori = :nama, deskripsi = :desc WHERE kategori_id = :id";
                $params = ['nama' => $nama_kategori, 'desc' => $deskripsi, 'id' => $kategori_id];
            } else {
                // Insert
                $sql = "INSERT INTO kategori_materi (nama_kategori, deskripsi) VALUES (:nama, :desc)";
                $params = ['nama' => $nama_kategori, 'desc' => $deskripsi];
            }
            
            if (execute($sql, $params)) {
                setFlash(SUCCESS, 'Kategori berhasil disimpan!');
            } else {
                setFlash(ERROR, 'Gagal menyimpan kategori!');
            }
        }
    }
    
    // Delete Category
    if ($action === 'delete_category') {
        $kategori_id = $_POST['kategori_id'] ?? 0;
        if ($kategori_id > 0) {
            try {
                execute("DELETE FROM kategori_materi WHERE kategori_id = :id", ['id' => $kategori_id]);
                setFlash(SUCCESS, 'Kategori berhasil dihapus!');
            } catch (Exception $e) {
                setFlash(ERROR, 'Gagal menghapus kategori! Pastikan tidak ada materi yang menggunakan kategori ini.');
            }
        }
    }
    
    // Change Admin Password
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlash(ERROR, 'Semua field password wajib diisi!');
        } elseif ($new_password !== $confirm_password) {
            setFlash(ERROR, 'Password baru dan konfirmasi tidak cocok!');
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            setFlash(ERROR, 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter!');
        } else {
            // Verify current password
            $user = query("SELECT password FROM users WHERE user_id = :id", ['id' => getUserId()])->fetch();
            if (password_verify($current_password, $user['password'])) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                if (execute("UPDATE users SET password = :pass WHERE user_id = :id", ['pass' => $hashed, 'id' => getUserId()])) {
                    setFlash(SUCCESS, 'Password berhasil diubah!');
                }
            } else {
                setFlash(ERROR, 'Password lama tidak sesuai!');
            }
        }
    }
    
    // Maintenance Mode Toggle
    if ($action === 'toggle_maintenance') {
        // This would need a settings table in production
        setFlash(SUCCESS, 'Mode maintenance diupdate!');
    }
    
    header('Location: settings.php');
    exit;
}

// Get all categories
$categories = query("SELECT k.*, COUNT(m.materi_id) as jumlah_materi 
                     FROM kategori_materi k 
                     LEFT JOIN materi m ON k.kategori_id = m.kategori_id 
                     GROUP BY k.kategori_id 
                     ORDER BY k.nama_kategori")->fetchAll();

// System Statistics for backup info
$backup_stats = query("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM materi) as total_materi,
    (SELECT COUNT(*) FROM progress_materi) as total_progress
")->fetch();

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="mb-4">
            <p class="text-muted">Kelola konfigurasi dan pengaturan aplikasi</p>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Settings Tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#categories" type="button">
                    <i class="fas fa-tags me-2"></i>Kategori Materi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security" type="button">
                    <i class="fas fa-lock me-2"></i>Keamanan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#system" type="button">
                    <i class="fas fa-cog me-2"></i>Sistem
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            
            <!-- Categories Tab -->
            <div class="tab-pane fade show active" id="categories">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kelola Kategori Materi</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetCategoryForm()">
                            <i class="fas fa-plus me-2"></i>Tambah Kategori
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th>Deskripsi</th>
                                        <th class="text-center">Jumlah Materi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada kategori</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($cat['nama_kategori']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($cat['deskripsi']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $cat['jumlah_materi']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteCategory(<?php echo $cat['kategori_id']; ?>)"
                                                                <?php echo $cat['jumlah_materi'] > 0 ? 'disabled title="Tidak bisa hapus kategori yang memiliki materi"' : ''; ?>>
                                                            <i class="fas fa-trash"></i>
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
            
            <!-- Security Tab -->
            <div class="tab-pane fade" id="security">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Ubah Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Pengaturan Keamanan</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Session Timeout</strong>
                                            <p class="text-muted small mb-0">Saat ini: <?php echo SESSION_LIFETIME / 3600; ?> jam</p>
                                        </div>
                                        <span class="badge bg-success">Aktif</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Login Attempts Limit</strong>
                                            <p class="text-muted small mb-0">Maksimal: <?php echo MAX_LOGIN_ATTEMPTS; ?> kali percobaan</p>
                                        </div>
                                        <span class="badge bg-success">Aktif</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Password Minimum Length</strong>
                                            <p class="text-muted small mb-0">Minimal: <?php echo PASSWORD_MIN_LENGTH; ?> karakter</p>
                                        </div>
                                        <span class="badge bg-success">Aktif</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Tab -->
            <div class="tab-pane fade" id="system">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Informasi Sistem</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Aplikasi</strong></td>
                                        <td><?php echo APP_NAME; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PHP Version</strong></td>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database</strong></td>
                                        <td><?php echo DB_NAME; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Base URL</strong></td>
                                        <td><?php echo BASE_URL; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Debug Mode</strong></td>
                                        <td>
                                            <?php if (DEBUG_MODE): ?>
                                                <span class="badge bg-warning">ON (Development)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">OFF (Production)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Timezone</strong></td>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Mode Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Aktifkan mode maintenance untuk melakukan pemeliharaan sistem. User tidak akan bisa login selama mode ini aktif.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle_maintenance">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                        <label class="form-check-label" for="maintenanceMode">
                                            <strong>Mode Maintenance</strong>
                                        </label>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-warning" disabled>
                                            <i class="fas fa-tools me-2"></i>Aktifkan Maintenance
                                        </button>
                                        <small class="d-block text-muted mt-2">Fitur ini memerlukan tabel settings tambahan</small>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Clear Cache</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Hapus cache untuk memperbarui data aplikasi.</p>
                                <button class="btn btn-info" onclick="alert('Cache cleared! (Implementasi memerlukan sistem caching)')">
                                    <i class="fas fa-broom me-2"></i>Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="categoryForm">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="kategori_id" id="kategori_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" id="nama_kategori" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Form (hidden) -->
<form id="deleteCategoryForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_category">
    <input type="hidden" name="kategori_id" id="delete_kategori_id">
</form>

<script>
function resetCategoryForm() {
    document.getElementById('categoryForm').reset();
    document.getElementById('kategori_id').value = '';
    document.getElementById('categoryModalTitle').textContent = 'Tambah Kategori';
}

function editCategory(category) {
    document.getElementById('kategori_id').value = category.kategori_id;
    document.getElementById('nama_kategori').value = category.nama_kategori;
    document.getElementById('deskripsi').value = category.deskripsi || '';
    document.getElementById('categoryModalTitle').textContent = 'Edit Kategori';
    
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function deleteCategory(id) {
    if (confirmDelete('Yakin hapus kategori ini?')) {
        document.getElementById('delete_kategori_id').value = id;
        document.getElementById('deleteCategoryForm').submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>