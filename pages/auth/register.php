<?php
/**
 * Register Page - Modern Green Soft Theme
 * File: pages/auth/register.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

if (isLoggedIn()) {
    $role = getUserRole();
    header('Location: ' . BASE_URL . 'pages/' . $role . '/dashboard.php');
    exit;
}

if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 6);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'siswa');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi tidak cocok!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } else {
        try {
            $db = getDB();
            
            $check_username = $db->prepare("SELECT user_id FROM users WHERE username = :username");
            $check_username->execute(['username' => $username]);
            
            if ($check_username->rowCount() > 0) {
                $error = 'Username sudah digunakan!';
            } else {
                $check_email = $db->prepare("SELECT user_id FROM users WHERE email = :email");
                $check_email->execute(['email' => $email]);
                
                if ($check_email->rowCount() > 0) {
                    $error = 'Email sudah terdaftar!';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO users (username, email, password, full_name, role, phone, is_active) 
                            VALUES (:username, :email, :password, :full_name, :role, :phone, 1)";
                    
                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute([
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashed_password,
                        'full_name' => $full_name,
                        'role' => $role,
                        'phone' => $phone
                    ]);
                    
                    if ($result) {
                        $_SESSION['flash'] = [
                            'type' => 'success',
                            'message' => 'Registrasi berhasil! Silakan login dengan akun Anda.'
                        ];
                        header('Location: login.php');
                        exit;
                    } else {
                        $error = 'Terjadi kesalahan saat mendaftar.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #34d399 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            bottom: -180px;
            left: -120px;
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .register-container {
            max-width: 480px;
            width: 100%;
            padding: 15px;
            position: relative;
            z-index: 1;
        }
        
        .register-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 28px 24px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .register-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.22);
        }
        
        .register-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        
        .register-subtitle {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 400;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: 0.8rem;
        }
        
        .form-control, .form-select {
            padding: 9px 14px;
            border-radius: 9px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 9px 0 0 9px;
            color: #9ca3af;
            padding: 0 11px;
            font-size: 0.875rem;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 9px 9px 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
        }
        
        .password-toggle {
            cursor: pointer;
            border-left: none !important;
            border-radius: 0 9px 9px 0 !important;
            transition: all 0.2s ease;
            padding: 0 11px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .btn-register {
            width: 100%;
            padding: 10px;
            border-radius: 9px;
            font-weight: 700;
            font-size: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 16px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider span {
            background: white;
            padding: 0 10px;
            position: relative;
            color: #9ca3af;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 9px;
            border: none;
            padding: 10px 12px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .strength-weak { color: #ef4444; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: var(--primary-color); }
        
        .link-primary {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .link-primary:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 16px;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .back-home a:hover {
            text-decoration: underline;
        }
        
        .mb-3 {
            margin-bottom: 12px !important;
        }
        
        .mb-4 {
            margin-bottom: 16px !important;
        }
        
        small.text-muted {
            font-size: 0.75rem;
        }
        
        .form-check-input {
            cursor: pointer;
        }
        
        .form-check-label {
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .row {
            margin-left: -6px;
            margin-right: -6px;
        }
        
        .row > * {
            padding-left: 6px;
            padding-right: 6px;
        }
        
        @media (max-width: 768px) {
            .register-card {
                padding: 24px 20px;
            }
            .register-title {
                font-size: 1.3rem;
            }
            .register-icon {
                width: 55px;
                height: 55px;
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Buat Akun Baru</h1>
                <p class="register-subtitle">Daftar untuk mulai tracking progres belajar Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   name="username" 
                                   placeholder="Username"
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                   required
                                   minlength="4"
                                   autofocus>
                        </div>
                        <small class="text-muted">Min. 4 karakter</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" 
                                   class="form-control" 
                                   name="email" 
                                   placeholder="email@example.com"
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="full_name" 
                               placeholder="Nama lengkap Anda"
                               value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="siswa" selected>Siswa / Mahasiswa</option>
                            <option value="mentor">Mentor / Pengajar</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. Telepon</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="tel" 
                                   class="form-control" 
                                   name="phone" 
                                   placeholder="08xxxxxxxxxx"
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Minimal 6 karakter"
                               required
                               minlength="6"
                               onkeyup="checkPasswordStrength()">
                        <span class="input-group-text password-toggle" 
                              onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </span>
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               name="confirm_password" 
                               id="confirm_password"
                               placeholder="Ulangi password"
                               required
                               minlength="6">
                        <span class="input-group-text password-toggle" 
                              onclick="togglePassword('confirm_password', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="agree" required>
                    <label class="form-check-label" for="agree" style="color: #6b7280; font-weight: 500;">
                        Saya setuju dengan <a href="#" class="link-primary">Syarat & Ketentuan</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                </button>
            </form>
            
            <div class="divider">
                <span>Atau</span>
            </div>
            
            <div class="text-center">
                <p class="mb-0" style="color: #6b7280; font-size: 0.85rem;">
                    Sudah punya akun? 
                    <a href="login.php" class="link-primary">
                        Masuk di sini
                    </a>
                </p>
            </div>
        </div>
        
        <div class="back-home">
            <a href="../../index.php">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/\d/.test(password)) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.innerHTML = '<i class="fas fa-circle strength-weak"></i> Password lemah';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthDiv.innerHTML = '<i class="fas fa-circle strength-medium"></i> Password sedang';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.innerHTML = '<i class="fas fa-circle strength-strong"></i> Password kuat';
                strengthDiv.className = 'password-strength strength-strong';
            }
        }
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
        });
    </script>
    
</body>
</html>