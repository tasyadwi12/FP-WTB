<?php
/**
 * Footer Template - Enhanced & Modern
 * File: includes/footer.php
 */
?>

    <!-- Footer - Enhanced Design -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="footer-brand">
                        <div class="footer-logo-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="footer-brand-info">
                            <span class="footer-brand-name"><?php echo APP_NAME; ?></span>
                            <span class="footer-brand-tagline">Pantau progres belajar dengan mudah ✨</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-center">
                    <div class="footer-links">
                        <a href="#" class="footer-link">
                            <i class="fas fa-info-circle"></i>
                            Tentang Kami
                        </a>
                        <a href="#" class="footer-link">
                            <i class="fas fa-envelope"></i>
                            Kontak
                        </a>
                        <a href="#" class="footer-link">
                            <i class="fas fa-question-circle"></i>
                            FAQ
                        </a>
                        <a href="#" class="footer-link">
                            <i class="fas fa-shield-alt"></i>
                            Privasi
                        </a>
                    </div>
                </div>
                
                <div class="footer-right">
                    <p class="footer-copyright">
                        © <?php echo date('Y'); ?> <strong><?php echo APP_NAME; ?></strong>
                    </p>
                    <p class="footer-rights">All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
        /* Footer - Enhanced Modern Design */
        .main-footer {
            background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
            border-top: 1px solid #e5e7eb;
            margin-left: 280px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .main-footer.sidebar-collapsed {
            margin-left: 80px;
        }
        
        @media (max-width: 768px) {
            .main-footer {
                margin-left: 0;
            }
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 35px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
        }
        
        /* Footer Left - Brand */
        .footer-left {
            flex: 1;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .footer-logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            transition: transform 0.3s ease;
        }
        
        .footer-logo-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }
        
        .footer-brand-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .footer-brand-name {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.2;
        }
        
        .footer-brand-tagline {
            color: var(--gray-500);
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        /* Footer Center - Links */
        .footer-center {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        
        .footer-links {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .footer-link {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .footer-link i {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .footer-link:hover {
            color: var(--primary-color);
            background: var(--primary-light);
            transform: translateY(-1px);
        }
        
        /* Footer Right - Copyright */
        .footer-right {
            flex: 1;
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .footer-copyright {
            color: var(--dark-color);
            font-size: 0.9rem;
            margin: 0;
            font-weight: 500;
        }
        
        .footer-copyright strong {
            color: var(--primary-color);
        }
        
        .footer-rights {
            color: var(--gray-500);
            font-size: 0.8rem;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .footer-content {
                gap: 30px;
            }
            
            .footer-links {
                gap: 16px;
            }
        }
        
        @media (max-width: 768px) {
            .main-footer {
                margin-left: 0;
            }
            
            .footer-container {
                padding: 25px 20px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 24px;
            }
            
            .footer-left,
            .footer-center,
            .footer-right {
                flex: none;
                width: 100%;
            }
            
            .footer-brand {
                justify-content: center;
            }
            
            .footer-right {
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .footer-links {
                flex-direction: column;
                gap: 8px;
            }
            
            .footer-link {
                justify-content: center;
            }
        }
    </style>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JavaScript - Enhanced -->
    <script>
        // DOMContentLoaded Event
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Dashboard loaded successfully');
            
            // Flash Message Auto Hide with Animation
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 300);
                }, 5000);
            });
            
            // Load sidebar state from localStorage
            if (window.innerWidth > 768) {
                const sidebarState = localStorage.getItem('sidebarCollapsed');
                if (sidebarState === 'true') {
                    const sidebar = document.getElementById('sidebar');
                    const navbar = document.getElementById('navbar');
                    const mainContent = document.querySelector('.main-content');
                    const footer = document.querySelector('.main-footer');
                    
                    sidebar?.classList.add('collapsed');
                    navbar?.classList.add('sidebar-collapsed');
                    mainContent?.classList.add('sidebar-collapsed');
                    footer?.classList.add('sidebar-collapsed');
                }
            }
            
            // Sidebar Toggle with Smooth Animation
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const navbar = document.getElementById('navbar');
            const mainContent = document.querySelector('.main-content');
            const footer = document.querySelector('.main-footer');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (window.innerWidth > 768) {
                        // Desktop: Collapse/Expand
                        sidebar?.classList.toggle('collapsed');
                        navbar?.classList.toggle('sidebar-collapsed');
                        mainContent?.classList.toggle('sidebar-collapsed');
                        footer?.classList.toggle('sidebar-collapsed');
                        
                        // Save state
                        const isCollapsed = sidebar?.classList.contains('collapsed');
                        localStorage.setItem('sidebarCollapsed', isCollapsed);
                    } else {
                        // Mobile: Show/Hide
                        sidebar?.classList.toggle('active');
                        overlay?.classList.toggle('active');
                        document.body.style.overflow = sidebar?.classList.contains('active') ? 'hidden' : '';
                    }
                });
            }
            
            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar?.classList.remove('active');
                    overlay?.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    overlay?.classList.remove('active');
                    sidebar?.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    sidebar?.classList.remove('collapsed');
                }
            });
        });
        
        // Utility Functions
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        function formatDate(date) {
            const d = new Date(date);
            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(d);
        }
        
        // Modern Loading Overlay
        function showLoading() {
            const loadingHtml = `
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading...</p>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
        }
        
        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
            }
        }
        
        // Modern Toast Notification
        function showToast(message, type = 'success') {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            const toastHtml = `
                <div class="toast-notification toast-${type}" id="toast-${Date.now()}">
                    <div class="toast-icon">
                        <i class="fas ${icons[type] || icons.info}"></i>
                    </div>
                    <div class="toast-content">
                        <p class="toast-message">${message}</p>
                    </div>
                    <button class="toast-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            const container = document.getElementById('toastContainer') || createToastContainer();
            container.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = container.lastElementChild;
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
            return container;
        }
    </script>
    
    <style>
        /* Modern Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }
        
        .loading-spinner {
            text-align: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--gray-200);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-spinner p {
            color: var(--gray-600);
            font-weight: 500;
            margin: 0;
        }
        
        /* Modern Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 400px;
        }
        
        .toast-notification {
            background: white;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transform: translateX(450px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid;
        }
        
        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-message {
            margin: 0;
            font-weight: 500;
            font-size: 0.95rem;
            color: var(--dark-color);
        }
        
        .toast-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .toast-close:hover {
            background: var(--gray-100);
            color: var(--gray-600);
        }
        
        .toast-success {
            border-left-color: #10b981;
        }
        
        .toast-success .toast-icon {
            background: #d1fae5;
            color: #10b981;
        }
        
        .toast-error {
            border-left-color: #ef4444;
        }
        
        .toast-error .toast-icon {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .toast-warning {
            border-left-color: #f59e0b;
        }
        
        .toast-warning .toast-icon {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .toast-info {
            border-left-color: #3b82f6;
        }
        
        .toast-info .toast-icon {
            background: #dbeafe;
            color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .toast-container {
                left: 15px;
                right: 15px;
                bottom: 15px;
                max-width: none;
            }
        }
    </style>
    
    <!-- Additional Custom JS -->
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
    
</body>
</html>