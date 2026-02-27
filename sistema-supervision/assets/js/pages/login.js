/**
 * LOGIN PAGE - JavaScript con SweetAlert2
 * Sistema de Supervisión v5.0
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded with glassmorphism design + SweetAlert2');
    
    initLoginForm();
    initInputAnimations();
    initParallaxEffect();
});

/**
 * Mostrar alertas con SweetAlert2
 */
function showLoginAlert(type, message) {
    const alertConfig = {
        title: getAlertTitle(type),
        text: message,
        icon: type,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3b82f6',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(59, 130, 246, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass'
        }
    };

    Swal.fire(alertConfig);
}

/**
 * Obtener título según tipo de alerta
 */
function getAlertTitle(type) {
    const titles = {
        'error': '¡Error de Autenticación!',
        'warning': '¡Atención!',
        'success': '¡Bienvenido!',
        'info': 'Información'
    };
    return titles[type] || 'Notificación';
}

/**
 * Mostrar alerta de éxito en login
 */
function showSuccessLogin(usuario) {
    Swal.fire({
        title: '¡Bienvenido de vuelta!',
        text: `Hola ${usuario}, iniciando sesión...`,
        icon: 'success',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(59, 130, 246, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism'
        },
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Inicializar validación del formulario de login
 */
function initLoginForm() {
    const loginForm = document.getElementById('loginForm');
    const loginButton = loginForm.querySelector('.login-button');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario');
            const contrasena = document.getElementById('contrasena');
            
            let isValid = true;
            let errorMessage = '';
            
            // Validar usuario
            if (!usuario.value.trim()) {
                isValid = false;
                errorMessage = 'Por favor, ingrese su usuario.';
                usuario.style.borderColor = '#ef4444';
                shakeInput(usuario);
            } else {
                usuario.style.borderColor = '#bfdbfe';
            }
            
            // Validar contraseña
            if (!contrasena.value) {
                isValid = false;
                if (!errorMessage) {
                    errorMessage = 'Por favor, ingrese su contraseña.';
                }
                contrasena.style.borderColor = '#ef4444';
                shakeInput(contrasena);
            } else {
                contrasena.style.borderColor = '#bfdbfe';
            }
            
            // Si no es válido, prevenir envío y mostrar alerta
            if (!isValid) {
                e.preventDefault();
                
                Swal.fire({
                    title: '¡Campos Requeridos!',
                    text: errorMessage,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f59e0b',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(245, 158, 11, 0.1)',
                    customClass: {
                        popup: 'swal-glassmorphism',
                        confirmButton: 'swal-button-glass'
                    }
                });
                
                return;
            }

            // Estado de carga (solo visual, el PHP manejará la redirección)
            loginButton.classList.add('loading');
            loginButton.textContent = 'Verificando...';
        });
        
        // Limpiar errores al escribir
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '#bfdbfe';
            });
        });
    }
}

/**
 * Animaciones en inputs
 */
function initInputAnimations() {
    const inputs = document.querySelectorAll('.input-group input');
    
    inputs.forEach(input => {
        // Efecto de elevación en inputs al enfocar
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.style.transform = 'translateX(4px)';
            this.parentElement.parentElement.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });

        input.addEventListener('blur', function() {
            this.parentElement.parentElement.style.transform = 'translateX(0)';
        });

        // Animación de escritura
        input.addEventListener('input', function() {
            if(this.value.length > 0) {
                this.style.fontWeight = '500';
            } else {
                this.style.fontWeight = '400';
            }
        });
    });
}

/**
 * Efecto parallax suave en el mouse
 */
function initParallaxEffect() {
    document.addEventListener('mousemove', (e) => {
        const shapes = document.querySelectorAll('.floating-shape');
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;

        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 8;
            const xMove = (x - 0.5) * speed;
            const yMove = (y - 0.5) * speed;
            shape.style.transform = `translate(${xMove}px, ${yMove}px)`;
        });
    });
}

/**
 * Shake animation para input con error
 */
function shakeInput(input) {
    input.style.animation = 'shakeInput 0.5s';
    setTimeout(() => {
        input.style.animation = '';
    }, 500);
}

// Agregar animación de shake para inputs
const style = document.createElement('style');
style.textContent = `
    @keyframes shakeInput {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        50% { transform: translateX(5px); }
        75% { transform: translateX(-5px); }
    }
`;
document.head.appendChild(style);

/**
 * Auto-focus en el campo de usuario
 */
window.addEventListener('load', function() {
    const usuarioInput = document.getElementById('usuario');
    if (usuarioInput && !usuarioInput.value) {
        usuarioInput.focus();
    }
});