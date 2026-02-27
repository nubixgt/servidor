// web/js/login.js

// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Validación del formulario
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value;
    
    if (!usuario || !password) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor complete todos los campos',
            confirmButtonColor: '#DC2626'
        });
        return false;
    }
    
    // Validar que el usuario no tenga espacios
    if (usuario.includes(' ')) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Usuario inválido',
            text: 'El usuario no puede contener espacios',
            confirmButtonColor: '#DC2626'
        });
        return false;
    }
});

// Prevenir espacios en el campo de usuario
document.getElementById('usuario').addEventListener('keypress', function(e) {
    if (e.key === ' ') {
        e.preventDefault();
    }
});

// Convertir a minúsculas automáticamente
document.getElementById('usuario').addEventListener('input', function(e) {
    this.value = this.value.toLowerCase();
});