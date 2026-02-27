/**
 * Session Manager - Sistema de Supervisión v6.0.4
 * Gestión de sesión con advertencia de inactividad
 */

(function() {
    'use strict';
    
    // ========== CONFIGURACIÓN ==========
    const CONFIG = {
        WARNING_TIME: 1500000,    // ✅ 25 minutos (1500000 ms) - mostrar advertencia
        LOGOUT_TIME: 300000,      // ✅ 5 minutos (300000 ms) - tiempo para decidir
        CHECK_INTERVAL: 10000     // ✅ Verificar cada 10 segundos (más eficiente)
    };
    
    // ========== VARIABLES GLOBALES ==========
    let lastActivity = Date.now();
    let warningShown = false;
    let warningTimer = null;
    let logoutTimer = null;
    let checkInterval = null;
    let countdownInterval = null;
    let remainingTime = 0;
    
    // ========== DETECTAR ACTIVIDAD DEL USUARIO ==========
    const activityEvents = [
        'mousedown', 
        'mousemove', 
        'keypress', 
        'scroll', 
        'touchstart',
        'click'
    ];
    
    function resetActivity() {
        lastActivity = Date.now();
        
        // Si ya se mostró la advertencia, ocultarla
        if (warningShown) {
            Swal.close();
            warningShown = false;
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);
            clearInterval(countdownInterval);
        }
    }
    
    // Registrar eventos de actividad
    activityEvents.forEach(event => {
        document.addEventListener(event, resetActivity, true);
    });
    
    // ========== MOSTRAR ADVERTENCIA DE INACTIVIDAD ==========
    function showInactivityWarning() {
        if (warningShown) return;
        
        warningShown = true;
        remainingTime = CONFIG.LOGOUT_TIME / 1000; // Convertir a segundos
        
        // Mostrar SweetAlert
        Swal.fire({
            title: '⚠️ ¿Sigues ahí?',
            html: `
                <div style="text-align: center;">
                    <p style="font-size: 16px; margin-bottom: 20px;">
                        Hemos detectado inactividad. Tu sesión se cerrará automáticamente en:
                    </p>
                    <div style="font-size: 48px; font-weight: 700; color: #f59e0b; margin: 20px 0;">
                        <span id="countdown">${remainingTime}</span>
                    </div>
                    <p style="font-size: 14px; color: #64748b;">
                        ¿Deseas continuar con tu sesión?
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '✅ Sí, seguir activo',
            cancelButtonText: '🚪 Cerrar sesión',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#dc2626',
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: 'rgba(255, 255, 255, 0.98)',
            backdrop: 'rgba(245, 158, 11, 0.4)',
            customClass: {
                popup: 'swal-glassmorphism swal-session-warning',
                confirmButton: 'swal-button-glass',
                cancelButton: 'swal-button-glass'
            },
            didOpen: () => {
                // Iniciar countdown
                startCountdown();
            },
            didClose: () => {
                // Limpiar countdown al cerrar
                clearInterval(countdownInterval);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Usuario eligió continuar
                keepSessionActive();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Usuario eligió cerrar sesión
                logout();
            }
        });
        
        // Timer de logout automático (1 minuto)
        logoutTimer = setTimeout(() => {
            autoLogout();
        }, CONFIG.LOGOUT_TIME);
    }
    
    // ========== COUNTDOWN EN EL MODAL ==========
    function startCountdown() {
        const countdownElement = document.getElementById('countdown');
        
        countdownInterval = setInterval(() => {
            remainingTime--;
            
            if (countdownElement) {
                countdownElement.textContent = remainingTime;
                
                // Cambiar color según tiempo restante
                if (remainingTime <= 10) {
                    countdownElement.style.color = '#dc2626'; // Rojo
                    countdownElement.style.animation = 'pulse 1s infinite';
                } else if (remainingTime <= 30) {
                    countdownElement.style.color = '#f59e0b'; // Naranja
                }
            }
            
            if (remainingTime <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }
    
    // ========== MANTENER SESIÓN ACTIVA ==========
    function keepSessionActive() {
        warningShown = false;
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        clearInterval(countdownInterval);
        resetActivity();
        
        // Mostrar confirmación breve
        Swal.fire({
            title: '✅ Sesión Activa',
            text: 'Tu sesión continúa activa',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(16, 185, 129, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism'
            }
        });
    }
    
    // ========== CERRAR SESIÓN MANUALMENTE ==========
    function logout() {
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
        
        // Redirigir al logout
        setTimeout(() => {
            window.location.href = '/SistemaSupervision/logout.php?logout=manual';
        }, 1000);
    }
    
    // ========== LOGOUT AUTOMÁTICO ==========
    function autoLogout() {
        Swal.close();
        
        Swal.fire({
            title: '⏰ Sesión Cerrada',
            text: 'Tu sesión se ha cerrado automáticamente por inactividad',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f59e0b',
            allowOutsideClick: false,
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(245, 158, 11, 0.2)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        }).then(() => {
            window.location.href = '/SistemaSupervision/logout.php?logout=timeout';
        });
    }
    
    // ========== VERIFICAR INACTIVIDAD ==========
    function checkInactivity() {
        const now = Date.now();
        const timeSinceLastActivity = now - lastActivity;
        
        // Si han pasado WARNING_TIME segundos sin actividad
        if (timeSinceLastActivity >= CONFIG.WARNING_TIME && !warningShown) {
            showInactivityWarning();
        }
    }
    
    // ========== INICIALIZAR ==========
    function init() {
        console.log('🔒 Session Manager iniciado');
        console.log(`⏱️ Advertencia: ${CONFIG.WARNING_TIME / 1000}s | Logout: ${CONFIG.LOGOUT_TIME / 1000}s`);
        
        // Verificar inactividad cada segundo
        checkInterval = setInterval(checkInactivity, CONFIG.CHECK_INTERVAL);
        
        // Registrar actividad inicial
        resetActivity();
    }
    
    // ========== INICIAR AL CARGAR LA PÁGINA ==========
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();