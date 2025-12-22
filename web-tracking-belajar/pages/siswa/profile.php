<?php
/**
 * Profile Page - Siswa
 * File: pages/siswa/profile.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require siswa role
requireRole(ROLE_SISWA);

// Set page info
$page_title = 'Profil Saya';
$current_page = 'profile';

$user_id = getUserId();

// Process Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (!empty($full_name) && !empty($email)) {
            // Check email duplicate with error handling
            $check_result = query("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id", [
                'email' => $email,
                'user_id' => $user_id
            ]);
            
            $check = false;
            if ($check_result !== false) {
                $check = $check_result->fetch();
            }
            
            if ($check) {
                setFlash(ERROR, 'Email sudah digunakan oleh user lain!');
            } else {
                $sql = "UPDATE users SET 
                        full_name = :full_name,
                        email = :email,
                        phone = :phone
                        WHERE user_id = :user_id";
                
                if (execute($sql, [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'user_id' => $user_id
                ])) {
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    setFlash(SUCCESS, 'Profil berhasil diupdate!');
                }
            }
        }
        header('Location: profile.php');
        exit;
    }
    
    if ($action === 'upload_avatar') {
        // Check if file was uploaded
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            
            // Validate file size (max 2MB)
            if ($file['size'] > 2097152) {
                setFlash(ERROR, 'Ukuran file maksimal 2MB!');
                header('Location: profile.php');
                exit;
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                setFlash(ERROR, 'Format file harus JPG, PNG, atau GIF!');
                header('Location: profile.php');
                exit;
            }
            
            // Create uploads directory if not exists
            $upload_dir = ROOT_PATH . 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            // Get current avatar to delete later
            $current_result = query("SELECT avatar FROM users WHERE user_id = :id", ['id' => $user_id]);
            $current_avatar = null;
            if ($current_result !== false) {
                $current = $current_result->fetch();
                $current_avatar = $current['avatar'] ?? null;
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $sql = "UPDATE users SET avatar = :avatar WHERE user_id = :user_id";
                if (execute($sql, ['avatar' => $filename, 'user_id' => $user_id])) {
                    // Delete old avatar if exists
                    if ($current_avatar && file_exists($upload_dir . $current_avatar)) {
                        unlink($upload_dir . $current_avatar);
                    }
                    
                    $_SESSION['avatar'] = $filename;
                    setFlash(SUCCESS, 'Foto profil berhasil diupdate!');
                } else {
                    // Delete uploaded file if database update fails
                    unlink($filepath);
                    setFlash(ERROR, 'Gagal menyimpan foto profil!');
                }
            } else {
                setFlash(ERROR, 'Gagal mengupload file!');
            }
        } else {
            setFlash(ERROR, 'Tidak ada file yang dipilih!');
        }
        header('Location: profile.php');
        exit;
    }
    
    if ($action === 'delete_avatar') {
        // Get current avatar
        $current_result = query("SELECT avatar FROM users WHERE user_id = :id", ['id' => $user_id]);
        if ($current_result !== false) {
            $current = $current_result->fetch();
            $current_avatar = $current['avatar'] ?? null;
            
            if ($current_avatar) {
                $upload_dir = ROOT_PATH . 'uploads/avatars/';
                $filepath = $upload_dir . $current_avatar;
                
                // Delete file
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // Update database
                $sql = "UPDATE users SET avatar = NULL WHERE user_id = :user_id";
                if (execute($sql, ['user_id' => $user_id])) {
                    unset($_SESSION['avatar']);
                    setFlash(SUCCESS, 'Foto profil berhasil dihapus!');
                }
            }
        }
        header('Location: profile.php');
        exit;
    }
    
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
            // Verify current password with error handling
            $user_result = query("SELECT password FROM users WHERE user_id = :id", ['id' => $user_id]);
            
            if ($user_result !== false) {
                $user_data = $user_result->fetch();
                
                if ($user_data && password_verify($current_password, $user_data['password'])) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    if (execute("UPDATE users SET password = :pass WHERE user_id = :id", ['pass' => $hashed, 'id' => $user_id])) {
                        setFlash(SUCCESS, 'Password berhasil diubah!');
                    }
                } else {
                    setFlash(ERROR, 'Password lama tidak sesuai!');
                }
            } else {
                setFlash(ERROR, 'Terjadi kesalahan sistem!');
            }
        }
        header('Location: profile.php');
        exit;
    }
}

// Get User Data with error handling
$user_result = query("SELECT * FROM users WHERE user_id = :id", ['id' => $user_id]);
$user = null;

if ($user_result !== false) {
    $user = $user_result->fetch();
}

// Redirect if user not found
if (!$user) {
    header('Location: ../../auth/logout.php');
    exit;
}

// Get Learning Statistics with error handling
$stats_sql = "SELECT 
    COUNT(DISTINCT CASE WHEN pm.status = 'selesai' THEN pm.materi_id END) as materi_selesai,
    COUNT(DISTINCT CASE WHEN pm.status = 'sedang_dipelajari' THEN pm.materi_id END) as materi_progress,
    COALESCE(SUM(ab.durasi_menit), 0) as total_durasi,
    COUNT(DISTINCT ab.aktivitas_id) as total_aktivitas,
    COUNT(DISTINCT tb.target_id) as total_target,
    COUNT(DISTINCT CASE WHEN tb.status = 'completed' THEN tb.target_id END) as target_completed,
    COALESCE(AVG(pn.nilai), 0) as avg_nilai
    FROM users u
    LEFT JOIN progress_materi pm ON u.user_id = pm.user_id
    LEFT JOIN aktivitas_belajar ab ON u.user_id = ab.user_id
    LEFT JOIN target_belajar tb ON u.user_id = tb.user_id
    LEFT JOIN penilaian_mentor pn ON pm.progress_id = pn.progress_id
    WHERE u.user_id = :user_id";

$stats_result = query($stats_sql, ['user_id' => $user_id]);

$learning_stats = [
    'materi_selesai' => 0,
    'materi_progress' => 0,
    'total_durasi' => 0,
    'total_aktivitas' => 0,
    'total_target' => 0,
    'target_completed' => 0,
    'avg_nilai' => 0
];

if ($stats_result !== false) {
    $learning_stats = $stats_result->fetch();
}

// Get Mentor Info with error handling
$mentor_result = query("SELECT u.* FROM users u
    INNER JOIN mentor_siswa ms ON u.user_id = ms.mentor_id
    WHERE ms.siswa_id = :siswa_id", ['siswa_id' => $user_id]);

$mentor = null;
if ($mentor_result !== false) {
    $mentor = $mentor_result->fetch();
}

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="mb-4">
            <p class="text-muted">Kelola informasi profil dan pengaturan akun Anda</p>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3 position-relative d-inline-block">
                            <?php if (isset($user['avatar']) && $user['avatar']): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     class="rounded-circle" 
                                     width="120" height="120"
                                     style="object-fit: cover;"
                                     alt="Avatar"
                                     id="avatarPreview">
                            <?php else: ?>
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px;"
                                     id="avatarPreview">
                                    <i class="fas fa-user fa-4x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Upload Button Overlay -->
                            <div class="position-absolute bottom-0 end-0">
                                <button type="button" class="btn btn-primary btn-sm rounded-circle" 
                                        data-bs-toggle="modal" data-bs-target="#avatarModal"
                                        style="width: 35px; height: 35px;">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="badge bg-success">Siswa</span>
                        
                        <hr>
                        
                        <div class="text-start">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                            </small>
                            <?php if (isset($user['phone']) && $user['phone']): ?>
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                                </small>
                            <?php endif; ?>
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-calendar me-2"></i>Bergabung: <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                            </small>
                            <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                <small class="text-muted d-block">
                                    <i class="fas fa-clock me-2"></i>Login terakhir: <?php echo date('d M Y H:i', strtotime($user['last_login'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Mentor Info -->
                <?php if ($mentor): ?>
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Mentor Saya</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <?php if (isset($mentor['avatar']) && $mentor['avatar']): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($mentor['avatar']); ?>" 
                                         class="rounded-circle me-3" 
                                         width="50" height="50"
                                         style="object-fit: cover;"
                                         alt="Mentor Avatar">
                                <?php else: ?>
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-user-tie fa-lg"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($mentor['full_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($mentor['email']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-8">
                
                <!-- Learning Statistics -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik Pembelajaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <h2 class="text-success mb-1"><?php echo $learning_stats['materi_selesai'] ?? 0; ?></h2>
                                    <small class="text-muted">Materi Selesai</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <h2 class="text-warning mb-1"><?php echo $learning_stats['materi_progress'] ?? 0; ?></h2>
                                    <small class="text-muted">Sedang Dipelajari</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="bg-primary bg-opacity-10 rounded p-3">
                                    <h2 class="text-primary mb-1"><?php echo round(($learning_stats['total_durasi'] ?? 0) / 60, 1); ?>h</h2>
                                    <small class="text-muted">Total Jam Belajar</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-info bg-opacity-10 rounded p-3">
                                    <h2 class="text-info mb-1"><?php echo $learning_stats['total_aktivitas'] ?? 0; ?></h2>
                                    <small class="text-muted">Total Aktivitas</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-secondary bg-opacity-10 rounded p-3">
                                    <h2 class="text-secondary mb-1"><?php echo ($learning_stats['target_completed'] ?? 0); ?>/<?php echo ($learning_stats['total_target'] ?? 0); ?></h2>
                                    <small class="text-muted">Target Selesai</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <h2 class="text-success mb-1"><?php echo round($learning_stats['avg_nilai'] ?? 0, 1); ?></h2>
                                    <small class="text-muted">Rata-rata Nilai</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username tidak dapat diubah</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Ubah Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control" 
                                           required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                    <small class="text-muted">Minimal <?php echo PASSWORD_MIN_LENGTH; ?> karakter</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </button>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<!-- Avatar Upload Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Foto Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Preview -->
                <div class="text-center mb-3">
                    <?php if (isset($user['avatar']) && $user['avatar']): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                             class="rounded-circle mb-3" 
                             width="150" height="150"
                             style="object-fit: cover;"
                             alt="Current Avatar"
                             id="currentAvatar">
                    <?php else: ?>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 150px; height: 150px;"
                             id="currentAvatar">
                            <i class="fas fa-user fa-5x"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="action" value="upload_avatar">
                    
                    <div class="mb-3">
                        <label class="form-label">Pilih Foto</label>
                        <input type="file" name="avatar" class="form-control" 
                               accept="image/jpeg,image/jpg,image/png,image/gif" 
                               required
                               id="avatarInput">
                        <small class="text-muted">Format: JPG, PNG, GIF. Max 2MB</small>
                    </div>
                    
                    <!-- Preview New Image -->
                    <div id="newImagePreview" class="text-center mb-3" style="display: none;">
                        <p class="text-muted mb-2">Preview:</p>
                        <img id="previewImg" src="" class="rounded-circle" 
                             width="150" height="150" 
                             style="object-fit: cover;" 
                             alt="Preview">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Foto
                        </button>
                        
                        <?php if (isset($user['avatar']) && $user['avatar']): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteAvatar()">
                                <i class="fas fa-trash me-2"></i>Hapus Foto
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Delete Form (Hidden) -->
                <form method="POST" id="deleteAvatarForm" style="display: none;">
                    <input type="hidden" name="action" value="delete_avatar">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size
        if (file.size > 2097152) {
            alert('Ukuran file maksimal 2MB!');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Format file harus JPG, PNG, atau GIF!');
            this.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('previewImg').src = event.target.result;
            document.getElementById('newImagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

function deleteAvatar() {
    if (confirm('Yakin ingin menghapus foto profil?')) {
        document.getElementById('deleteAvatarForm').submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>