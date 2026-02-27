/**
 * NAVBAR ADMIN - JavaScript
 * Sistema de Supervisión v6.0.5
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar Admin loaded');
    
    initSidebar();
    initLogout();
    setActiveMenuItem();
});

/**
 * Inicializar sidebar (mobile toggle)
 */
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mobileToggle = document.getElementById('mobileToggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Cerrar sidebar en mobile al hacer click en un link
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
}

/**
 * Inicializar logout con SweetAlert2
 */
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: '¿Estás seguro que deseas cerrar tu sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Cerrar Sesión',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(59, 130, 246, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass',
                    cancelButton: 'swal-button-glass'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Cerrando Sesión...',
                        html: 'Hasta pronto 👋',
                        icon: 'success',
                        timer: 1500,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        background: 'rgba(255, 255, 255, 0.95)',
                        backdrop: 'rgba(59, 130, 246, 0.1)',
                        customClass: {
                            popup: 'swal-glassmorphism'
                        },
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        willClose: () => {
                            // Redirigir al logout
                            window.location.href = getBaseUrl() + '/logout.php';
                        }
                    });
                }
            });
        });
    }
}

/**
 * Marcar el item activo en el menú según la página actual
 */
function setActiveMenuItem() {
    const currentPage = getCurrentPage();
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const page = item.getAttribute('data-page');
        
        // Comparación exacta para evitar conflictos
        if (page && currentPage === page) {
            item.classList.add('active');
        }
    });
}

/**
 * Obtener la página actual desde la URL
 */
function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.substring(path.lastIndexOf('/') + 1);
    return page.replace('.php', '');
}

/**
 * Obtener la URL base del sitio
 */
function getBaseUrl() {
    const path = window.location.pathname;
    const segments = path.split('/');
    
    // Buscar el segmento "sistema-supervision" o similar
    const baseIndex = segments.findIndex(seg => seg.includes('Sistema') || seg === 'sistema-supervision');
    
    if (baseIndex !== -1) {
        return window.location.origin + segments.slice(0, baseIndex + 1).join('/');
    }
    
    // Fallback
    return window.location.origin;
}

/**
 * Agregar animación de ripple en los nav items
 */
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        const ripple = document.createElement('div');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        `;

        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

// Animación de ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);