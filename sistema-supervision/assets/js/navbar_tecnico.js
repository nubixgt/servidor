/**
 * assets/js/navbar_tecnico.js
 * Navbar Técnico - Sidebar Navigation + Logout
 * Sistema de Supervisión v6.0.4
 * ACTUALIZADO: Agregado manejo de logout con SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Navbar Técnico cargado');
    
    initSidebar();
    setActiveMenuItem();
    initLogout(); // ⭐ NUEVO: Inicializar logout
});

/**
 * Inicializar sidebar
 */
function initSidebar() {
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!mobileToggle || !sidebar || !overlay) return;
    
    // Toggle sidebar en móvil
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Bloquear scroll del body cuando sidebar está abierto
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
    
    // Cerrar sidebar al hacer clic en overlay
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Cerrar sidebar al hacer clic en un link (solo en móvil)
    const navLinks = sidebar.querySelectorAll('.nav-item');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Ajustar al cambiar tamaño de ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    console.log('✅ Sidebar inicializado');
}

/**
 * Marcar el menú activo según la página actual
 */
function setActiveMenuItem() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href.split('/').pop())) {
            item.classList.add('active');
        }
    });
}

/**
 * ⭐ NUEVO: Inicializar botón de logout
 */
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (!logoutBtn) {
        console.warn('⚠️ Botón de logout no encontrado');
        return;
    }
    
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('🔴 Logout clicked');
        
        // Verificar si SweetAlert2 está disponible
        if (typeof Swal === 'undefined') {
            console.error('❌ SweetAlert2 no está cargado');
            // Fallback: confirmación nativa del navegador
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = '/SistemaSupervision/logout.php?logout=success';
            }
            return;
        }
        
        // Mostrar confirmación con SweetAlert2
        Swal.fire({
            title: '¿Cerrar Sesión?',
            text: '¿Estás seguro de que deseas salir del sistema?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#60a5fa',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(30, 58, 138, 0.4)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass',
                cancelButton: 'swal-button-glass'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Cerrando sesión...',
                    text: 'Por favor espera',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Redirigir después de 1 segundo
                setTimeout(() => {
                    window.location.href = '/SistemaSupervision/logout.php?logout=success';
                }, 1000);
            }
        });
    });
    
    console.log('✅ Logout inicializado correctamente');
}

// ⭐ NUEVO: Estilos para SweetAlert2
const style = document.createElement('style');
style.textContent = `
    .swal-glassmorphism {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .swal-button-glass {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .swal-button-glass:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
`;
document.head.appendChild(style);