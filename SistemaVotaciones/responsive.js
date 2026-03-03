/**
 * SISTEMA DE VOTACIONES - JavaScript Responsive
 * Funcionalidad para menú móvil y mejoras de UI
 * VERSIÓN CORREGIDA - Compatible con elementos HTML existentes
 */

(function() {
    'use strict';
    
    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        
        // ===== MENÚ MÓVIL =====
        initMobileMenu();
        
        // ===== TOOLTIPS =====
        initTooltips();
        
        // ===== ANIMACIONES AL SCROLL =====
        initScrollAnimations();
        
        // ===== TABLAS RESPONSIVE =====
        initResponsiveTables();
        
        // ===== CONFIRMACIONES =====
        initConfirmations();
        
    });
    
    /**
     * Inicializar menú móvil - CORREGIDO para usar elementos existentes
     */
    function initMobileMenu() {
        const menuBtn = document.getElementById('mobileMenuBtn') || document.querySelector('.mobile-menu-btn');
        const overlay = document.getElementById('sidebarOverlay') || document.querySelector('.sidebar-overlay');
        const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        
        if (!menuBtn || !overlay || !sidebar) {
            console.warn('Elementos del menú móvil no encontrados');
            return; // Si no existen los elementos, salir
        }
        
        // Event listeners
        menuBtn.addEventListener('click', toggleMobileMenu);
        overlay.addEventListener('click', closeMobileMenu);
        
        // Cerrar al hacer clic en un link del menú
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    closeMobileMenu();
                }
            });
        });
        
        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeMobileMenu();
            }
        });
    }
    
    /**
     * Toggle menú móvil
     */
    function toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay') || document.querySelector('.sidebar-overlay');
        const menuBtn = document.getElementById('mobileMenuBtn') || document.querySelector('.mobile-menu-btn');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Cambiar ícono
            if (menuBtn) {
                const icon = menuBtn.querySelector('i');
                if (icon) {
                    if (sidebar.classList.contains('show')) {
                        icon.className = 'bi bi-x';
                        document.body.style.overflow = 'hidden';
                    } else {
                        icon.className = 'bi bi-list';
                        document.body.style.overflow = '';
                    }
                }
            }
        }
    }
    
    /**
     * Cerrar menú móvil
     */
    function closeMobileMenu() {
        const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay') || document.querySelector('.sidebar-overlay');
        const menuBtn = document.getElementById('mobileMenuBtn') || document.querySelector('.mobile-menu-btn');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            
            if (menuBtn) {
                const icon = menuBtn.querySelector('i');
                if (icon) {
                    icon.className = 'bi bi-list';
                }
            }
            
            document.body.style.overflow = '';
        }
    }
    
    /**
     * Inicializar tooltips de Bootstrap
     */
    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"], [data-tooltip]')
            );
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
    
    /**
     * Animaciones al hacer scroll
     */
    function initScrollAnimations() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                        }
                    });
                },
                {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                }
            );
            
            // Observar cards y elementos animables
            const animatableElements = document.querySelectorAll('.card, .stat-card');
            animatableElements.forEach(el => {
                observer.observe(el);
            });
        }
    }
    
    /**
     * Hacer tablas más responsive - MEJORADO
     */
    function initResponsiveTables() {
        const tables = document.querySelectorAll('table.table');
        
        tables.forEach(table => {
            // Solo procesar si no está ya en un wrapper responsive
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
    
    /**
     * Confirmar acciones destructivas
     */
    function initConfirmations() {
        // Agregar confirmación a botones de eliminar
        const deleteButtons = document.querySelectorAll('[data-confirm]');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const message = this.getAttribute('data-confirm') || '¿Estás seguro?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    }
    
    /**
     * Detectar cambio de orientación
     */
    window.addEventListener('orientationchange', function() {
        // Cerrar menú móvil al cambiar orientación
        closeMobileMenu();
    });
    
    /**
     * Detectar cambio de tamaño de ventana
     */
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Cerrar menú si la ventana se agranda
            if (window.innerWidth >= 992) {
                closeMobileMenu();
            }
        }, 250);
    });
    
    /**
     * Añadir clase al hacer scroll (para efectos de header fijo, etc.)
     */
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
    
    /**
     * Mejorar experiencia de carga
     */
    window.addEventListener('load', function() {
        // Ocultar cualquier loader
        const loaders = document.querySelectorAll('.loading-overlay');
        loaders.forEach(loader => {
            loader.classList.add('d-none');
        });
        
        // Añadir clase de página cargada
        document.body.classList.add('page-loaded');
    });
    
    /**
     * Prevenir zoom accidental en iOS al hacer doble tap
     */
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
    
    // Exponer funciones globales si es necesario
    window.VotacionesApp = {
        toggleMobileMenu: toggleMobileMenu,
        closeMobileMenu: closeMobileMenu
    };
    
})();