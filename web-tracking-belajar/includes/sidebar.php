<?php
/**
 * Sidebar Navigation - Modern Green Soft Theme
 * File: includes/sidebar.php
 */

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
$current_role = getUserRole();

// Helper function untuk active class
if (!function_exists('isActive')) {
    function isActive($page) {
        global $current_page;
        return ($current_page === $page) ? 'active' : '';
    }
}

// Get user avatar
$user_avatar = $_SESSION['avatar'] ?? null;
$avatar_url = ASSETS_PATH . 'img/default-avatar.png';

if ($user_avatar && file_exists(ROOT_PATH . 'uploads/avatars/' . $user_avatar)) {
    $avatar_url = BASE_URL . 'uploads/avatars/' . $user_avatar;
}

// Menu items berdasarkan role
$menu_items = [
    'siswa' => [
        ['icon' => 'fas fa-home', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fas fa-book', 'label' => 'Materi Belajar', 'url' => 'materi-list.php'],
        ['icon' => 'fas fa-chart-line', 'label' => 'Progress Saya', 'url' => 'progress.php'],
        ['icon' => 'fas fa-bullseye', 'label' => 'Target Belajar', 'url' => 'target.php'],
        ['icon' => 'fas fa-history', 'label' => 'Riwayat', 'url' => 'history.php'],
        ['icon' => 'fas fa-user', 'label' => 'Profil Saya', 'url' => 'profile.php'],
    ],
    'mentor' => [
        ['icon' => 'fas fa-home', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fas fa-users', 'label' => 'Daftar Siswa', 'url' => 'siswa-list.php'],
        ['icon' => 'fas fa-chart-bar', 'label' => 'Monitor Progress', 'url' => 'monitor-progress.php'],
        ['icon' => 'fas fa-chalkboard-teacher', 'label' => 'Bimbingan', 'url' => 'bimbingan.php'],
        ['icon' => 'fas fa-file-alt', 'label' => 'Laporan', 'url' => 'laporan.php'],
        ['icon' => 'fas fa-user', 'label' => 'Profil Saya', 'url' => 'profile.php'],
    ],
    'admin' => [
        ['icon' => 'fas fa-home', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fas fa-users-cog', 'label' => 'Manajemen User', 'url' => 'user-management.php'],
        ['icon' => 'fas fa-book-open', 'label' => 'Manajemen Materi', 'url' => 'materi-management.php'],
        ['icon' => 'fas fa-chart-bar', 'label' => 'Laporan', 'url' => 'laporan.php'],
        ['icon' => 'fas fa-cog', 'label' => 'Pengaturan', 'url' => 'settings.php'],
        ['icon' => 'fas fa-user', 'label' => 'Profil Saya', 'url' => 'profile.php'],
    ]
];

$current_menu = $menu_items[$current_role] ?? $menu_items['siswa'];
?>

<!-- Sidebar - Modern Green Soft Design -->
<aside class="modern-sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div class="modern-sidebar-header">
        <div class="modern-logo-wrapper">
            <div class="modern-logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="modern-logo-text">
                <h5 class="modern-logo-title">Tracking</h5>
                <span class="modern-logo-subtitle">Belajar</span>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Content -->
    <div class="modern-sidebar-content">
        <!-- User Info Card -->
        <div class="modern-user-card">
            <div class="modern-user-avatar">
                <img src="<?php echo $avatar_url; ?>" 
                     alt="Avatar"
                     onerror="this.src='<?php echo ASSETS_PATH; ?>img/default-avatar.png'">
                <div class="modern-user-status"></div>
            </div>
            <div class="modern-user-info">
                <h6 class="modern-user-name"><?php echo htmlspecialchars(getUserFullName()); ?></h6>
                <span class="modern-user-role"><?php echo ucfirst(getUserRole()); ?></span>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="modern-sidebar-nav">
            <ul class="modern-nav-list">
                <?php foreach ($current_menu as $item): ?>
                    <li class="modern-nav-item">
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="modern-nav-link <?php echo isActive($item['url']); ?>">
                            <span class="modern-nav-icon">
                                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </span>
                            <span class="modern-nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                            <?php if (isActive($item['url']) === 'active'): ?>
                                <span class="modern-nav-indicator"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="modern-sidebar-footer">
            <a href="<?php echo BASE_URL; ?>pages/auth/logout.php" class="modern-logout-btn">
                <span class="modern-logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </span>
                <span class="modern-logout-text">Keluar</span>
            </a>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="modern-sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* Sidebar - Modern Green Soft Design */
.modern-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    background: white;
    z-index: 1050;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

/* Scrollbar Styling */
.modern-sidebar-content {
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
    padding: 0 20px 20px;
}

.modern-sidebar-content::-webkit-scrollbar {
    width: 6px;
}

.modern-sidebar-content::-webkit-scrollbar-track {
    background: transparent;
}

.modern-sidebar-content::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.modern-sidebar-content::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Sidebar Header */
.modern-sidebar-header {
    padding: 24px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: white;
}

.modern-logo-wrapper {
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.3s ease;
}

.modern-logo-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.modern-logo-text {
    transition: opacity 0.3s ease;
    line-height: 1.3;
}

.modern-logo-title {
    color: #0f172a;
    font-weight: 700;
    font-size: 1.25rem;
    margin: 0;
    letter-spacing: -0.02em;
}

.modern-logo-subtitle {
    color: #10b981;
    font-size: 0.85rem;
    font-weight: 500;
}

/* User Card */
.modern-user-card {
    background: linear-gradient(135deg, #d1fae5, #f9fafb);
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.3s ease;
}

.modern-user-card:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    transform: translateY(-2px);
}

.modern-user-avatar {
    position: relative;
    flex-shrink: 0;
}

.modern-user-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.modern-user-status {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #d1fae5;
}

.modern-user-info {
    flex: 1;
    min-width: 0;
    transition: opacity 0.3s ease;
}

.modern-user-name {
    color: #0f172a;
    font-weight: 600;
    font-size: 0.95rem;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.modern-user-role {
    color: #10b981;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Navigation */
.modern-sidebar-nav {
    margin-top: 10px;
}

.modern-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.modern-nav-item {
    position: relative;
}

.modern-nav-link {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: #6b7280;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    font-weight: 500;
    font-size: 0.95rem;
}

.modern-nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: #d1fae5;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 0;
}

.modern-nav-link:hover {
    color: #10b981;
}

.modern-nav-link:hover::before {
    opacity: 1;
}

.modern-nav-link.active {
    color: #10b981;
    background: #d1fae5;
    font-weight: 600;
}

.modern-nav-link.active::before {
    opacity: 1;
}

.modern-nav-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 14px;
    font-size: 1.1rem;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.modern-nav-label {
    flex: 1;
    transition: opacity 0.3s ease;
    position: relative;
    z-index: 1;
    white-space: nowrap;
}

.modern-nav-indicator {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    z-index: 1;
}

/* Sidebar Footer */
.modern-sidebar-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    margin-top: auto;
    background: white;
}

.modern-logout-btn {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: #6b7280;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.2s ease;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.modern-logout-btn:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fecaca;
    transform: translateY(-2px);
}

.modern-logout-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 14px;
    font-size: 1.1rem;
}

.modern-logout-text {
    transition: opacity 0.3s ease;
}

/* Collapsed State */
.modern-sidebar.collapsed {
    width: 80px;
}

.modern-sidebar.collapsed .modern-logo-text,
.modern-sidebar.collapsed .modern-nav-label,
.modern-sidebar.collapsed .modern-user-info,
.modern-sidebar.collapsed .modern-logout-text {
    opacity: 0;
    visibility: hidden;
    width: 0;
}

.modern-sidebar.collapsed .modern-logo-wrapper {
    justify-content: center;
}

.modern-sidebar.collapsed .modern-user-card {
    padding: 12px;
    justify-content: center;
}

.modern-sidebar.collapsed .modern-nav-link {
    justify-content: center;
    padding: 14px 0;
}

.modern-sidebar.collapsed .modern-nav-icon {
    margin-right: 0;
}

.modern-sidebar.collapsed .modern-logout-btn {
    justify-content: center;
}

.modern-sidebar.collapsed .modern-logout-icon {
    margin-right: 0;
}

.modern-sidebar.collapsed .modern-nav-indicator {
    display: none;
}

/* Sidebar Overlay */
.modern-sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1040;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modern-sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .modern-sidebar {
        transform: translateX(-100%);
        box-shadow: none;
    }
    
    .modern-sidebar.active {
        transform: translateX(0);
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.1);
    }
    
    .modern-sidebar.collapsed {
        width: 280px;
    }
    
    .modern-sidebar.collapsed .modern-logo-text,
    .modern-sidebar.collapsed .modern-nav-label,
    .modern-sidebar.collapsed .modern-user-info,
    .modern-sidebar.collapsed .modern-logout-text {
        opacity: 1;
        visibility: visible;
        width: auto;
    }
    
    .modern-sidebar.collapsed .modern-user-card {
        justify-content: flex-start;
        padding: 18px;
    }
    
    .modern-sidebar.collapsed .modern-nav-link {
        justify-content: flex-start;
        padding: 14px 16px;
    }
    
    .modern-sidebar.collapsed .modern-nav-icon {
        margin-right: 14px;
    }
    
    .modern-sidebar.collapsed .modern-logout-btn {
        justify-content: flex-start;
    }
    
    .modern-sidebar.collapsed .modern-logout-icon {
        margin-right: 14px;
    }
}

/* Animation */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.modern-nav-item {
    animation: slideInLeft 0.3s ease;
    animation-fill-mode: both;
}

.modern-nav-item:nth-child(1) { animation-delay: 0.05s; }
.modern-nav-item:nth-child(2) { animation-delay: 0.1s; }
.modern-nav-item:nth-child(3) { animation-delay: 0.15s; }
.modern-nav-item:nth-child(4) { animation-delay: 0.2s; }
.modern-nav-item:nth-child(5) { animation-delay: 0.25s; }
.modern-nav-item:nth-child(6) { animation-delay: 0.3s; }
</style>