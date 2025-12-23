<?php
/**
 * Landing Page / Index - Enhanced with YouTube Integration
 * File: index.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once 'config/config.php';

if (isLoggedIn()) {
    $role = getUserRole();
    header('Location: ' . BASE_URL . 'pages/' . $role . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Belajar dengan Video YouTube</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary-color: #34d399;
            --accent-color: #6ee7b7;
            --youtube-red: #ff0000;
            --dark-color: #0f172a;
            --light-color: #f9fafb;
            --gray-600: #4b5563;
            --gray-700: #374151;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            overflow-x: hidden;
            color: var(--gray-700);
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

        .nav-link {
            color: var(--gray-700) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .btn-nav {
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nav-login {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-nav-login:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-nav-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            margin-left: 10px;
        }

        .btn-nav-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .hero-title .highlight {
            background: linear-gradient(to right, #fff, #a7f3d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.8;
            font-weight: 400;
        }

        .hero-badges {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .badge-hero {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-hero {
            padding: 18px 45px;
            font-size: 1.1rem;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            border: 2px solid transparent;
        }
        
        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn-hero-primary:hover {
            background: rgba(255,255,255,0.95);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }
        
        .btn-hero-outline {
            background: transparent;
            color: white;
            border-color: white;
        }
        
        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-4px);
        }

        /* Video Preview Card */
        .video-preview-card {
            background: white;
            border-radius: 20px;
            padding: 15px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .video-preview {
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .video-preview:hover {
            transform: scale(1.02);
        }

        .video-preview img {
            width: 100%;
            display: block;
        }

        .video-play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .video-preview:hover .video-play-overlay {
            background: rgba(0, 0, 0, 0.5);
        }

        .video-play-btn {
            width: 80px;
            height: 80px;
            background: var(--youtube-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .video-preview:hover .video-play-btn {
            transform: scale(1.15);
            box-shadow: 0 10px 30px rgba(255, 0, 0, 0.4);
        }

        .video-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .video-stat-item {
            text-align: center;
        }

        .video-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .video-stat-label {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        /* YouTube Integration Section */
        .youtube-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .youtube-section::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -300px;
            left: -200px;
        }

        .youtube-feature-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .youtube-feature-box:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-8px);
        }

        .youtube-icon {
            width: 70px;
            height: 70px;
            background: var(--youtube-red);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: var(--light-color);
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 70px;
        }
        
        .section-title {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark-color);
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }
        
        .section-subtitle {
            font-size: 1.25rem;
            color: var(--gray-600);
            font-weight: 400;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 50px rgba(16, 185, 129, 0.15);
            border-color: var(--primary-light);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 2.2rem;
            color: white;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-description {
            color: var(--gray-600);
            line-height: 1.7;
            font-size: 1rem;
        }

        /* How It Works Section */
        .how-it-works-section {
            padding: 100px 0;
            background: white;
        }

        .step-card {
            position: relative;
            text-align: center;
            padding: 30px;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 900;
            color: white;
            margin: 0 auto 25px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .step-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
        }

        .step-description {
            color: var(--gray-600);
            line-height: 1.7;
        }

        .step-arrow {
            position: absolute;
            top: 50%;
            right: -20px;
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.3;
            transform: translateY(-50%);
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 80px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stats-section::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -250px;
            right: -100px;
        }
        
        .stat-item {
            text-align: center;
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .stat-label {
            font-size: 1.2rem;
            opacity: 0.95;
            font-weight: 500;
        }

        /* Testimonials Section */
        .testimonials-section {
            padding: 100px 0;
            background: #f9fafb;
        }

        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .testimonial-info h4 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .testimonial-info p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .testimonial-text {
            color: var(--gray-700);
            line-height: 1.8;
            font-style: italic;
        }

        .testimonial-rating {
            color: #fbbf24;
            margin-top: 15px;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -200px;
            right: -100px;
        }
        
        .cta-title {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }
        
        .cta-subtitle {
            font-size: 1.25rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        
        .btn-cta {
            padding: 20px 55px;
            font-size: 1.2rem;
            border-radius: 14px;
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: 800;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .btn-cta:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            color: var(--primary-color);
        }
        
        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .footer-description {
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-icon:hover {
            background: var(--primary-color);
            transform: translateY(-5px);
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }

        .footer-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 50px;
            padding-top: 30px;
            text-align: center;
        }

        .footer-bottom p {
            margin: 0;
            opacity: 0.7;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-subtitle {
                font-size: 1.1rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 2.5rem;
            }
            .cta-title {
                font-size: 2rem;
            }
            .step-arrow {
                display: none;
            }
            .btn-hero {
                padding: 14px 30px;
                font-size: 1rem;
            }
        }

        /* Scroll to Top Button */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
        }

        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i>
                <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">Cara Kerja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimoni</a>
                    </li>
                    <li class="nav-item">
                        <a href="pages/auth/login.php" class="btn btn-nav btn-nav-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="pages/auth/register.php" class="btn btn-nav btn-nav-register">
                            <i class="fas fa-rocket me-2"></i>Daftar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <div class="hero-badges">
                        <span class="badge-hero">
                            <i class="fab fa-youtube"></i>
                            Integrasi YouTube
                        </span>
                        <span class="badge-hero">
                            <i class="fas fa-chart-line"></i>
                            Real-time Tracking
                        </span>
                    </div>
                    <h1 class="hero-title">
                        Belajar dengan <span class="highlight">Video YouTube</span>, Pantau Progress dengan Mudah
                    </h1>
                    <p class="hero-subtitle">
                        Platform tracking pembelajaran modern yang terintegrasi dengan YouTube. 
                        Tonton video tutorial, catat progress, dan raih target belajar Anda secara efektif.
                    </p>
                    <div class="mt-4">
                        <a href="pages/auth/register.php" class="btn-hero btn-hero-primary me-3 mb-3">
                            <i class="fas fa-rocket me-2"></i>Mulai Gratis
                        </a>
                        <a href="#youtube-features" class="btn-hero btn-hero-outline mb-3">
                            <i class="fab fa-youtube me-2"></i>Lihat Fitur
                        </a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="video-preview-card">
                        <div class="video-preview" onclick="alert('Video preview - Fitur akan tersedia setelah login!')">
                            <img src="https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg" alt="Video Preview">
                            <div class="video-play-overlay">
                                <div class="video-play-btn">
                                    <i class="fab fa-youtube"></i>
                                </div>
                            </div>
                        </div>
                        <div class="video-stats">
                            <div class="video-stat-item">
                                <div class="video-stat-number">500+</div>
                                <div class="video-stat-label">Video Materi</div>
                            </div>
                            <div class="video-stat-item">
                                <div class="video-stat-number">10K+</div>
                                <div class="video-stat-label">Viewer</div>
                            </div>
                            <div class="video-stat-item">
                                <div class="video-stat-number">4.9/5</div>
                                <div class="video-stat-label">Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- YouTube Integration Section -->
    <section class="youtube-section" id="youtube-features">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title text-white">
                    <i class="fab fa-youtube me-3"></i>
                    Integrasi YouTube yang Powerful
                </h2>
                <p class="section-subtitle text-white opacity-75">
                    Belajar langsung dari video YouTube dengan fitur tracking yang canggih
                </p>
            </div>

            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-play"></i>
                        </div>
                        <h4 class="mb-3">Tonton & Belajar</h4>
                        <p class="mb-0">
                            Akses ribuan video tutorial dari YouTube langsung di platform. 
                            Playlist otomatis, bookmark favorit, dan speed control.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="mb-3">Track Waktu Belajar</h4>
                        <p class="mb-0">
                            Sistem otomatis mencatat durasi menonton setiap video. 
                            Statistik lengkap per hari, minggu, dan bulan.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 class="mb-3">Progress Otomatis</h4>
                        <p class="mb-0">
                            Tandai video sebagai selesai secara otomatis. 
                            Visual progress bar untuk setiap materi pembelajaran.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <h4 class="mb-3">Notes & Timestamps</h4>
                        <p class="mb-0">
                            Catat poin penting dengan timestamp. Kembali ke momen 
                            spesifik kapan saja untuk review.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <h4 class="mb-3">Custom Playlist</h4>
                        <p class="mb-0">
                            Buat playlist pribadi dari video YouTube favorit. 
                            Organize berdasarkan topik dan prioritas.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="youtube-feature-box">
                        <div class="youtube-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4 class="mb-3">Analytics Dashboard</h4>
                        <p class="mb-0">
                            Lihat statistik lengkap aktivitas belajar. Grafik interaktif 
                            dan insight untuk optimasi belajar.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Fitur Lengkap untuk Pembelajaran Efektif</h2>
                <p class="section-subtitle">Semua yang Anda butuhkan dalam satu platform</p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="feature-title">Manajemen Materi</h3>
                        <p class="feature-description">
                            Organize materi belajar dengan mudah. Kategori, tag, dan pencarian 
                            cepat untuk akses materi kapan saja.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="feature-title">Pencatatan Aktivitas</h3>
                        <p class="feature-description">
                            Catat setiap sesi belajar dengan detail. Riwayat lengkap dengan 
                            timestamp untuk evaluasi progress.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Visualisasi Progress</h3>
                        <p class="feature-description">
                            Dashboard interaktif dengan grafik real-time. Pantau kemajuan 
                            dalam format yang mudah dipahami.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="feature-title">Target & Goals</h3>
                        <p class="feature-description">
                            Set target harian, mingguan, dan bulanan. Reminder otomatis 
                            untuk menjaga konsistensi belajar.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Bimbingan Mentor</h3>
                        <p class="feature-description">
                            Kolaborasi dengan mentor profesional. Monitor progress dan 
                            dapatkan feedback konstruktif.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Responsive Design</h3>
                        <p class="feature-description">
                            Akses dari desktop, tablet, atau smartphone. Interface yang 
                            optimal di semua perangkat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section" id="how-it-works">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Cara Kerja Platform</h2>
                <p class="section-subtitle">Mulai tracking progress dalam 3 langkah mudah</p>
            </div>

            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Daftar & Setup Profile</h3>
                        <p class="step-description">
                            Buat akun gratis dalam hitungan menit. Lengkapi profil dan 
                            tentukan goal pembelajaran Anda.
                        </p>
                        <div class="step-arrow d-none d-md-block">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Pilih Materi & Tonton</h3>
                        <p class="step-description">
                            Browse katalog materi atau tambah video YouTube sendiri. 
                            Mulai belajar dengan player terintegrasi.
                        </p>
                        <div class="step-arrow d-none d-md-block">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Track & Achieve</h3>
                        <p class="step-description">
                            Sistem otomatis track progress Anda. Lihat statistik, 
                            achievement, dan terus tingkatkan skill.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3" data-aos="zoom-in" data-aos-delay="100">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-users"></i>
                            <span>5,000+</span>
                        </div>
                        <div class="stat-label">Pengguna Aktif</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="zoom-in" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fab fa-youtube"></i>
                            <span>1,500+</span>
                        </div>
                        <div class="stat-label">Video Materi</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="zoom-in" data-aos-delay="300">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-clock"></i>
                            <span>50K+</span>
                        </div>
                        <div class="stat-label">Jam Belajar</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="zoom-in" data-aos-delay="400">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-trophy"></i>
                            <span>98%</span>
                        </div>
                        <div class="stat-label">Tingkat Kepuasan</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Apa Kata Mereka?</h2>
                <p class="section-subtitle">Testimoni dari pengguna yang sudah merasakan manfaatnya</p>
            </div>

            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="testimonial-header">
                            <div class="testimonial-avatar">BD</div>
                            <div class="testimonial-info">
                                <h4>Budi Santoso</h4>
                                <p>Mahasiswa Informatika</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                            "Platform ini sangat membantu saya untuk track progress belajar coding. 
                            Integrasi YouTube-nya top! Bisa langsung praktek sambil nonton tutorial."
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="testimonial-header">
                            <div class="testimonial-avatar">ST</div>
                            <div class="testimonial-info">
                                <h4>Siti Nurhaliza</h4>
                                <p>UI/UX Designer</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                            "Fitur tracking waktu dan progress bar-nya sangat memotivasi! 
                            Sekarang saya lebih konsisten belajar design. Dashboard-nya juga keren!"
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-card">
                        <div class="testimonial-header">
                            <div class="testimonial-avatar">AR</div>
                            <div class="testimonial-info">
                                <h4>Ahmad Rizki</h4>
                                <p>Data Analyst</p>
                            </div>
                        </div>
                        <p class="testimonial-text">
                            "Sempurna untuk self-learning! Bisa custom playlist YouTube, 
                            catat notes, dan lihat statistik belajar. Recommended banget!"
                        </p>
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <div data-aos="zoom-in">
                <h2 class="cta-title">Mulai Tracking Progres Belajar Anda Sekarang!</h2>
                <p class="cta-subtitle">
                    Bergabunglah dengan ribuan pengguna yang sudah merasakan kemudahan 
                    belajar dengan platform kami. 100% GRATIS!
                </p>
                <a href="pages/auth/register.php" class="btn-cta">
                    <i class="fas fa-rocket me-2"></i>Daftar Gratis Sekarang
                </a>
                <div class="mt-4">
                    <small class="d-block opacity-75">
                        <i class="fas fa-check-circle me-2"></i>Tanpa Kartu Kredit
                        <span class="mx-3">•</span>
                        <i class="fas fa-check-circle me-2"></i>Setup Instant
                        <span class="mx-3">•</span>
                        <i class="fas fa-check-circle me-2"></i>Support 24/7
                    </small>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">
                        <i class="fas fa-graduation-cap me-2"></i>
                        <?php echo APP_NAME; ?>
                    </div>
                    <p class="footer-description">
                        Platform tracking pembelajaran modern dengan integrasi YouTube. 
                        Belajar lebih efektif, pantau progress dengan mudah.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Platform</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Fitur</a></li>
                        <li><a href="#how-it-works">Cara Kerja</a></li>
                        <li><a href="#testimonials">Testimoni</a></li>
                        <li><a href="#">Pricing</a></li>
                    </ul>
                </div>

                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Tutorial</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>

                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Kontak</a></li>
                        <li><a href="#">Karir</a></li>
                        <li><a href="#">Partner</a></li>
                    </ul>
                </div>

                <div class="col-md-2 col-6 mb-4">
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">License</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>
                    &copy; <?php echo date('Y'); ?> <strong><?php echo APP_NAME; ?></strong>. 
                    Dibuat dengan <i class="fas fa-heart" style="color: #ef4444;"></i> untuk pembelajaran yang lebih baik.
                </p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }

            // Scroll to top button
            const scrollTop = document.getElementById('scrollTop');
            if (window.scrollY > 300) {
                scrollTop.classList.add('show');
            } else {
                scrollTop.classList.remove('show');
            }
        });

        // Scroll to top functionality
        document.getElementById('scrollTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const targetPosition = target.offsetTop - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
    
</body>
</html>