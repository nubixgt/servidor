// web/js/dashboard_admin.js

// Función para cerrar sesión
function cerrarSesion() {
    Swal.fire({
        title: '¿Cerrar sesión?',
        text: 'Se cerrará tu sesión actual',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/app-uba/logout.php';  // Ruta absoluta
        }
    });
}

// Resaltar el menú activo
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.navbar-menu a');
    
    menuLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
});

// Funciones para botones de acción
function verDetalle(id) {
    window.location.href = `ver_denuncia.php?id=${id}`;
}

function editarDenuncia(id) {
    window.location.href = `editar_denuncia.php?id=${id}`;
}

// Manejar dropdown en móvil
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 768) {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = this.closest('.dropdown');
                dropdown.classList.toggle('active');
            });
        });
    }
});