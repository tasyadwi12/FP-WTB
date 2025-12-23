<?php
/**
 * User Management Page - Admin (Fixed Avatar Display)
 * File: pages/admin/user-management.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

// Require admin role
requireRole(ROLE_ADMIN);

// Set page info
$page_title = 'Manajemen Pengguna';
$current_page = 'user-management';

// Process Actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;

// Delete User
if ($action === 'delete' && $user_id > 0) {
    try {
        $sql = "DELETE FROM users WHERE user_id = :user_id AND role != 'admin'";
        if (execute($sql, ['user_id' => $user_id])) {
            setFlash(SUCCESS, 'User berhasil dihapus!');
        } else {
            setFlash(ERROR, 'Gagal menghapus user!');
        }
    } catch (Exception $e) {
        setFlash(ERROR, 'Error: ' . $e->getMessage());
    }
    header('Location: user-management.php');
    exit;
}

// Toggle Active Status
if ($action === 'toggle' && $user_id > 0) {
    try {
        $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = :user_id";
        if (execute($sql, ['user_id' => $user_id])) {
            setFlash(SUCCESS, 'Status user berhasil diubah!');
        }
    } catch (Exception $e) {
        setFlash(ERROR, 'Error: ' . $e->getMessage());
    }
    header('Location: user-management.php');
    exit;
}

// Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'siswa');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($full_name)) {
        setFlash(ERROR, 'Username, email, dan nama lengkap wajib diisi!');
    } else {
        if ($user_id > 0) {
            // Update existing user
            $sql = "UPDATE users SET 
                    username = :username,
                    email = :email,
                    full_name = :full_name,
                    role = :role,
                    phone = :phone";
            
            $params = [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'role' => $role,
                'phone' => $phone,
                'user_id' => $user_id
            ];
            
            // Update password if provided
            if (!empty($password)) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE user_id = :user_id";
            
            if (execute($sql, $params)) {
                setFlash(SUCCESS, 'User berhasil diupdate!');
                header('Location: user-management.php');
                exit;
            }
        } else {
            // Create new user
            if (empty($password)) {
                setFlash(ERROR, 'Password wajib diisi untuk user baru!');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password, full_name, role, phone, is_active) 
                        VALUES (:username, :email, :password, :full_name, :role, :phone, 1)";
                
                $params = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashed_password,
                    'full_name' => $full_name,
                    'role' => $role,
                    'phone' => $phone
                ];
                
                if (execute($sql, $params)) {
                    setFlash(SUCCESS, 'User baru berhasil ditambahkan!');
                    header('Location: user-management.php');
                    exit;
                }
            }
        }
    }
}

// Get filter
$filter_role = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($filter_role)) {
    $sql .= " AND role = :role";
    $params['role'] = $filter_role;
}

if (!empty($search)) {
    $sql .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$users = query($sql, $params)->fetchAll();

// Get statistics
$stats = query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as siswa,
    SUM(CASE WHEN role = 'mentor' THEN 1 ELSE 0 END) as mentor,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM users")->fetch();

// Include header & sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-2"><?php echo $page_title; ?></h1>
                <p class="text-muted">Kelola semua pengguna sistem</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Tambah User
            </button>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?php echo $flash['type'] === SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total User</h6>
                                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Siswa</h6>
                                <h3 class="mb-0"><?php echo $stats['siswa']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 text-info rounded p-3">
                                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Mentor</h6>
                                <h3 class="mb-0"><?php echo $stats['mentor']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 text-warning rounded p-3">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Aktif</h6>
                                <h3 class="mb-0"><?php echo $stats['active']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter & Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Role</label>
                        <select name="role" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Role</option>
                            <option value="siswa" <?php echo $filter_role === 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                            <option value="mentor" <?php echo $filter_role === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                            <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cari User</label>
                        <input type="text" name="search" class="form-control" placeholder="Username, email, atau nama..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">Tidak ada user ditemukan</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                // FIXED: Proper avatar display logic
                                                $avatar_url = ASSETS_PATH . 'img/default-avatar.png';
                                                if (!empty($user['avatar']) && file_exists(ROOT_PATH . 'uploads/avatars/' . $user['avatar'])) {
                                                    $avatar_url = BASE_URL . 'uploads/avatars/' . $user['avatar'];
                                                }
                                                ?>
                                                <img src="<?php echo $avatar_url; ?>" 
                                                     class="rounded-circle me-2" 
                                                     width="32" height="32"
                                                     alt="Avatar"
                                                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($user['role'] === 'mentor'): ?>
                                                <span class="badge bg-info">Mentor</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Siswa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=toggle&id=<?php echo $user['user_id']; ?>" 
                                                   class="btn btn-outline-warning"
                                                   title="Toggle Status"
                                                   onclick="return confirm('Ubah status user ini?')">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <a href="?action=delete&id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       title="Hapus"
                                                       onclick="return confirm('Yakin hapus user ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
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
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="siswa">Siswa</option>
                            <option value="mentor">Mentor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" name="phone" id="phone" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span id="passwordRequired">*</span></label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
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

<style>
/* Avatar Styling */
.rounded-circle {
    object-fit: cover;
    border: 2px solid #f0f0f0;
}

/* Card Styling */
.card {
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

/* Table Styling */
.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Button Group Styling */
.btn-group-sm .btn {
    padding: 0.375rem 0.75rem;
}

/* Badge Styling */
.badge {
    padding: 0.5rem 0.75rem;
    font-weight: 500;
}

/* Modal Styling */
.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #e5e7eb;
    border-radius: 15px 15px 0 0;
}

.modal-footer {
    border-top: 1px solid #e5e7eb;
}
</style>

<script>
function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah User Baru';
    document.getElementById('password').required = true;
    document.getElementById('passwordRequired').style.display = 'inline';
}

function editUser(user) {
    document.getElementById('user_id').value = user.user_id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('role').value = user.role;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('modalTitle').textContent = 'Edit User';
    
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>