<?php
/**
 * Logout Page
 * File: pages/auth/logout.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$full_name = getUserFullName();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();
session_write_close();

setcookie('logout_success', '1', time() + 10, '/');
setcookie('logout_name', $full_name, time() + 10, '/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - <?php echo APP_NAME; ?></title>
    
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
            top: -150px;
            right: -150px;
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .logout-container {
            max-width: 450px;
            width: 100%;
            padding: 15px;
            position: relative;
            z-index: 1;
        }
        
        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            padding: 40px 30px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 2.5rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);
            animation: checkmark 0.5s ease 0.3s both;
        }
        
        @keyframes checkmark {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        .logout-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        
        .logout-message {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .btn-custom {
            padding: 11px 28px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 5px;
            font-size: 0.95rem;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), #34d399);
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-outline-custom {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .redirect-info {
            margin-top: 20px;
            color: #9ca3af;
            font-size: 0.85rem;
        }
        
        .countdown {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .logout-card {
                padding: 35px 25px;
            }
            .logout-title {
                font-size: 1.5rem;
            }
            .logout-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
            .btn-custom {
                padding: 10px 24px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="logout-title">Logout Berhasil!</h1>
            
            <p class="logout-message">
                Terima kasih telah menggunakan sistem kami.<br>
                Sampai jumpa lagi, <strong><?php echo htmlspecialchars($full_name); ?></strong>!
            </p>
            
            <div class="mt-3">
                <a href="login.php" class="btn btn-primary-custom btn-custom">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Kembali
                </a>
                <a href="../../index.php" class="btn btn-outline-custom btn-custom">
                    <i class="fas fa-home me-2"></i>Ke Beranda
                </a>
            </div>
            
            <div class="redirect-info">
                <i class="fas fa-info-circle me-1"></i>
                Anda akan dialihkan ke halaman login dalam <span class="countdown" id="countdown">5</span> detik...
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
    
</body>
</html>