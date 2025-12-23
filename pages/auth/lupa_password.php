<?php
/**
 * Lupa Password - Versi Sederhana
 * File: pages/auth/lupa_password.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

if (isLoggedIn()) {
    $role = getUserRole();
    header('Location: ' . BASE_URL . 'pages/' . $role . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password dan konfirmasi tidak cocok!';
    } else {
        try {
            $db = getDB();
            
            // Cek apakah email terdaftar
            $sql = "SELECT user_id FROM users WHERE email = :email AND is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    'password' => $hashed_password,
                    'user_id' => $user['user_id']
                ]);
                
                if ($result) {
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'message' => 'Password berhasil direset! Silakan login dengan password baru.'
                    ];
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Gagal mereset password!';
                }
            } else {
                $error = 'Email tidak terdaftar atau akun tidak aktif!';
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
    <title>Lupa Password - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .forgot-card {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .forgot-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981, #34d399);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.8rem;
        }
        
        .forgot-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .forgot-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-control {
            padding: 10px 14px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e5e7eb;
            border-right: none;
            color: #9ca3af;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: #10b981;
        }
        
        .password-toggle {
            cursor: pointer;
            border-left: none !important;
        }
        
        .password-toggle:hover {
            color: #10b981;
        }
        
        .btn-submit {
            width: 100%;
            padding: 11px;
            border-radius: 8px;
            font-weight: 600;
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            color: white;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px;
            font-size: 0.9rem;
        }
        
        .link-primary {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }
        
        .link-primary:hover {
            color: #059669;
            text-decoration: underline;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    
    <div class="forgot-card">
        <div class="forgot-icon">
            <i class="fas fa-key"></i>
        </div>
        
        <h1 class="forgot-title">Lupa Password</h1>
        <p class="forgot-subtitle">Masukkan email dan password baru Anda</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Terdaftar</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" 
                           class="form-control" 
                           name="email" 
                           placeholder="contoh@email.com"
                           required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" 
                           class="form-control" 
                           name="new_password" 
                           id="new_password"
                           placeholder="Minimal 6 karakter"
                           required
                           minlength="6">
                    <span class="input-group-text password-toggle" onclick="togglePassword('new_password', 'icon1')">
                        <i class="fas fa-eye" id="icon1"></i>
                    </span>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" 
                           class="form-control" 
                           name="confirm_password" 
                           id="confirm_password"
                           placeholder="Ulangi password baru"
                           required
                           minlength="6">
                    <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password', 'icon2')">
                        <i class="fas fa-eye" id="icon2"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-submit">
                <i class="fas fa-save me-2"></i>Reset Password
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <p class="mb-0" style="color: #6b7280; font-size: 0.9rem;">
                Sudah ingat password? 
                <a href="login.php" class="link-primary">Login di sini</a>
            </p>
        </div>
        
        <div class="back-link">
            <a href="../../index.php">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>
    
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
    </script>
    
</body>
</html>