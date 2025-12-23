<?php
/**
 * Login Page - Modern Green Soft Theme
 * File: pages/auth/login.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

if (!defined('SUCCESS')) {
    define('SUCCESS', 'success');
    define('ERROR', 'error');
}

if (isLoggedIn()) {
    $role = getUserRole();
    header('Location: ' . BASE_URL . 'pages/' . $role . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_COOKIE['logout_success']) && $_COOKIE['logout_success'] === '1') {
    $logout_name = isset($_COOKIE['logout_name']) ? $_COOKIE['logout_name'] : 'User';
    $success = 'Logout berhasil! Sampai jumpa, ' . htmlspecialchars($logout_name) . '!';
    setcookie('logout_success', '', time() - 3600, '/');
    setcookie('logout_name', '', time() - 3600, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $db = getDB();
            if (!$db) {
                $error = 'Koneksi database gagal!';
            } else {
                $sql = "SELECT * FROM users WHERE (username = :username OR email = :email) AND is_active = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute(['username' => $username, 'email' => $username]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['avatar'] = $user['avatar'];
                        $_SESSION['is_logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        
                        session_regenerate_id(true);
                        
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + (86400 * 30), '/');
                        }
                        
                        $_SESSION['flash'] = [
                            'type' => 'success',
                            'message' => 'Login berhasil! Selamat datang, ' . $user['full_name']
                        ];
                        
                        header('Location: ' . BASE_URL . 'pages/' . $user['role'] . '/dashboard.php');
                        exit;
                    } else {
                        $error = 'Password salah!';
                    }
                } else {
                    $error = 'Username atau email tidak ditemukan!';
                }
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    if ($flash['type'] === 'success') {
        $success = $flash['message'];
    } else {
        $error = $flash['message'];
    }
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
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
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -200px;
            right: -150px;
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 15px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            padding: 35px 30px;
            animation: slideUp 0.5s ease;
            position: relative;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);
        }
        
        .login-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 400;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }
        
        .form-control {
            padding: 11px 16px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #9ca3af;
            padding: 0 12px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
        }
        
        .password-toggle {
            cursor: pointer;
            border-left: none !important;
            border-radius: 0 10px 10px 0 !important;
            transition: all 0.2s ease;
            padding: 0 12px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .btn-login {
            width: 100%;
            padding: 11px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.35);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .form-check-input {
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
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
            padding: 0 12px;
            position: relative;
            color: #9ca3af;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 14px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert-success {
            background: var(--primary-light);
            color: #065f46;
        }
        
        .link-primary {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .link-primary:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .back-home a:hover {
            text-decoration: underline;
        }
        
        .mb-3 {
            margin-bottom: 16px !important;
        }
        
        .mb-4 {
            margin-bottom: 20px !important;
        }
        
        @media (max-width: 768px) {
            .login-card {
                padding: 30px 25px;
            }
            .login-title {
                font-size: 1.4rem;
            }
            .login-icon {
                width: 60px;
                height: 60px;
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="login-title">Selamat Datang</h1>
                <p class="login-subtitle">Masuk untuk melanjutkan ke dashboard Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username atau Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="username" 
                               placeholder="Masukkan username atau email"
                               value="<?php echo htmlspecialchars($username ?? ''); ?>"
                               required
                               autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Masukkan password"
                               required>
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="remember" 
                               id="remember">
                        <label class="form-check-label" for="remember" style="font-weight: 500; color: #6b7280; font-size: 0.875rem;">
                            Ingat saya
                        </label>
                    </div>
                    <a href="lupa_password.php" class="link-primary" style="font-size: 0.875rem;">
                        Lupa password?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>
            
            <div class="divider">
                <span>Atau</span>
            </div>
            
            <div class="text-center">
                <p class="mb-0" style="color: #6b7280; font-size: 0.9rem;">
                    Belum punya akun? 
                    <a href="register.php" class="link-primary">
                        Daftar Sekarang
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
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
    
</body>
</html>