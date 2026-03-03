<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
    // Mobile Menu Functions
    function initMobileMenu() {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (!menuToggle || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
            menuToggle.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
            menuToggle.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuToggle.addEventListener('click', openSidebar);
        sidebarClose?.addEventListener('click', closeSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);

        // Close sidebar when clicking a nav link on mobile
        sidebar.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        });
    }

    // Toast Notification System
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container') || createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };

        toast.innerHTML = `
        <div class="toast-icon"><i class="fas fa-${icons[type] || icons.info}"></i></div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

        container.appendChild(toast);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    // Theme Toggle Functions
    function initThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('dark-icon');
        const lightIcon = document.getElementById('light-icon');

        if (!themeToggle) return;

        // Check saved theme or default to dark
        const savedTheme = localStorage.getItem('vider-theme') || 'dark';
        applyTheme(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('vider-theme', newTheme);
        });

        function applyTheme(theme) {
            if (theme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
                themeToggle.classList.add('light');
                darkIcon?.classList.remove('active');
                lightIcon?.classList.add('active');
            } else {
                document.documentElement.removeAttribute('data-theme');
                themeToggle.classList.remove('light');
                darkIcon?.classList.add('active');
                lightIcon?.classList.remove('active');
            }
        }
    }

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        initThemeToggle();
    });
</script>