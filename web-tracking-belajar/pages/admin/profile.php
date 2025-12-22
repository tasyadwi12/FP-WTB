<?php
/**
 * Profile Page - Admin
 * File: pages/admin/profile.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

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
            try {
                // Check email duplicate
                $check_result = query("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id", [
                    'email' => $email,
                    'user_id' => $user_id
                ]);
                
                $check = $check_result ? $check_result->fetch() : null;
                
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
            } catch (Exception $e) {
                setFlash(ERROR, 'Gagal update profil: ' . $e->getMessage());
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
            try {
                $current_result = query("SELECT avatar FROM users WHERE user_id = :id", ['id' => $user_id]);
                $current_avatar = null;
                if ($current_result) {
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
            } catch (Exception $e) {
                setFlash(ERROR, 'Gagal upload avatar: ' . $e->getMessage());
            }
        } else {
            setFlash(ERROR, 'Tidak ada file yang dipilih!');
        }
        header('Location: profile.php');
        exit;
    }
    
    if ($action === 'delete_avatar') {
        try {
            // Get current avatar
            $current_result = query("SELECT avatar FROM users WHERE user_id = :id", ['id' => $user_id]);
            if ($current_result) {
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
        } catch (Exception $e) {
            setFlash(ERROR, 'Gagal hapus avatar: ' . $e->getMessage());
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
            try {
                // Verify current password
                $user_result = query("SELECT password FROM users WHERE user_id = :id", ['id' => $user_id]);
                
                if ($user_result) {
                    $user_data = $user_result->fetch();
                    
                    if ($user_data && password_verify($current_password, $user_data['password'])) {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        if (execute("UPDATE users SET password = :pass WHERE user_id = :id", ['pass' => $hashed, 'id' => $user_id])) {
                            setFlash(SUCCESS, 'Password berhasil diubah!');
                        }
                    } else {
                        setFlash(ERROR, 'Password lama tidak sesuai!');
                    }
                }
            } catch (Exception $e) {
                setFlash(ERROR, 'Gagal ubah password: ' . $e->getMessage());
            }
        }
        header('Location: profile.php');
        exit;
    }
}

// Get User Data
try {
    $user_result = query("SELECT * FROM users WHERE user_id = :id", ['id' => $user_id]);
    $user = $user_result ? $user_result->fetch() : null;
} catch (Exception $e) {
    $user = null;
}

// Redirect if user not found
if (!$user) {
    header('Location: ../../auth/logout.php');
    exit;
}

// Get Admin Statistics
try {
    $stats_result = query("SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
        (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentor,
        (SELECT COUNT(*) FROM materi) as total_materi,
        (SELECT COUNT(*) FROM progress_materi) as total_progress
    ");
    
    $admin_stats = $stats_result ? $stats_result->fetch() : null;
} catch (Exception $e) {
    $admin_stats = null;
}

$stats = [
    'total_siswa' => $admin_stats['total_siswa'] ?? 0,
    'total_mentor' => $admin_stats['total_mentor'] ?? 0,
    'total_materi' => $admin_stats['total_materi'] ?? 0,
    'total_progress' => $admin_stats['total_progress'] ?? 0
];

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="mb-4">
            <p class="text-muted">Kelola informasi profil dan pengaturan akun administrator</p>
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
                            <?php 
                            $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                            if (isset($user['avatar']) && $user['avatar']) {
                                $avatar_path = ROOT_PATH . 'uploads/avatars/' . $user['avatar'];
                                if (file_exists($avatar_path)) {
                                    $avatar_url = BASE_URL . 'uploads/avatars/' . $user['avatar'];
                                }
                            }
                            ?>
                            
                            <?php if (isset($user['avatar']) && $user['avatar'] && file_exists(ROOT_PATH . 'uploads/avatars/' . $user['avatar'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     class="rounded-circle" 
                                     width="120" height="120"
                                     style="object-fit: cover;"
                                     alt="Avatar"
                                     id="avatarPreview">
                            <?php else: ?>
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px;"
                                     id="avatarPreview">
                                    <i class="fas fa-user-shield fa-4x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Upload Button Overlay -->
                            <div class="position-absolute bottom-0 end-0">
                                <button type="button" class="btn btn-danger btn-sm rounded-circle" 
                                        data-bs-toggle="modal" data-bs-target="#avatarModal"
                                        style="width: 35px; height: 35px;">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="badge bg-danger">Administrator</span>
                        
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
                
                <!-- System Stats -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik Sistem</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="bg-primary bg-opacity-10 rounded p-3">
                                    <h4 class="text-primary mb-1"><?php echo $stats['total_siswa']; ?></h4>
                                    <small class="text-muted">Total Siswa</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <h4 class="text-success mb-1"><?php echo $stats['total_mentor']; ?></h4>
                                    <small class="text-muted">Total Mentor</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-info bg-opacity-10 rounded p-3">
                                    <h4 class="text-info mb-1"><?php echo $stats['total_materi']; ?></h4>
                                    <small class="text-muted">Total Materi</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <h4 class="text-warning mb-1"><?php echo $stats['total_progress']; ?></h4>
                                    <small class="text-muted">Total Progress</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-8">
                
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
                    <?php if (isset($user['avatar']) && $user['avatar'] && file_exists(ROOT_PATH . 'uploads/avatars/' . $user['avatar'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                             class="rounded-circle mb-3" 
                             width="150" height="150"
                             style="object-fit: cover;"
                             alt="Current Avatar"
                             id="currentAvatar">
                    <?php else: ?>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 150px; height: 150px;"
                             id="currentAvatar">
                            <i class="fas fa-user-shield fa-5x"></i>
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
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-upload me-2"></i>Upload Foto
                        </button>
                        
                        <?php if (isset($user['avatar']) && $user['avatar']): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteAvatar()">
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