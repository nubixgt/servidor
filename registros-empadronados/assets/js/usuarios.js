/**
 * JavaScript para el módulo de Usuarios
 * Gestión de creación y edición de usuarios
 */

let modoEdicion = false;

document.addEventListener('DOMContentLoaded', function () {
    const nombresInput = document.getElementById('nombres');
    const apellidosInput = document.getElementById('apellidos');
    const usuarioInput = document.getElementById('usuario');
    const contrasenaInput = document.getElementById('contrasena');
    const dpiInput = document.getElementById('dpi');
    const telefonoInput = document.getElementById('telefono');
    const departamentoSelect = document.getElementById('departamento');
    const municipioSelect = document.getElementById('municipio');
    const togglePasswordBtn = document.getElementById('togglePassword');

    // Generar usuario y contraseña automáticamente
    function generarCredenciales() {
        // Solo generar si NO estamos en modo edición
        if (modoEdicion) return;

        const nombres = nombresInput.value.trim();
        const apellidos = apellidosInput.value.trim();

        if (nombres && apellidos) {
            // Generar usuario: primera letra del primer nombre + primer apellido
            const primerNombre = nombres.split(' ')[0];
            const primerApellido = apellidos.split(' ')[0];
            const usuario = (primerNombre.charAt(0) + primerApellido).toLowerCase();

            // Generar contraseña: usuario + número aleatorio de 2 dígitos
            const numeroAleatorio = Math.floor(Math.random() * 90) + 10;
            const contrasena = usuario + numeroAleatorio;

            usuarioInput.value = usuario;
            contrasenaInput.value = contrasena;

            // Hacer editables los campos
            usuarioInput.removeAttribute('readonly');
            contrasenaInput.removeAttribute('readonly');
        }
    }

    // Eventos para generar credenciales
    if (nombresInput && apellidosInput) {
        nombresInput.addEventListener('blur', generarCredenciales);
        apellidosInput.addEventListener('blur', generarCredenciales);
    }

    // Toggle para mostrar/ocultar contraseña
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const tipo = contrasenaInput.getAttribute('type');
            if (tipo === 'password') {
                contrasenaInput.setAttribute('type', 'text');
                this.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                contrasenaInput.setAttribute('type', 'password');
                this.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    }

    // Formateo automático de DPI: 0000 00000 0000
    if (dpiInput) {
        dpiInput.addEventListener('input', function (e) {
            let valor = e.target.value.replace(/\D/g, '');

            if (valor.length > 13) {
                valor = valor.substring(0, 13);
            }

            let formato = '';
            if (valor.length > 0) {
                formato = valor.substring(0, 4);
            }
            if (valor.length > 4) {
                formato += ' ' + valor.substring(4, 9);
            }
            if (valor.length > 9) {
                formato += ' ' + valor.substring(9, 13);
            }

            e.target.value = formato;
        });

        dpiInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Formateo automático de Teléfono: 0000-0000
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function (e) {
            let valor = e.target.value.replace(/\D/g, '');

            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }

            let formato = '';
            if (valor.length > 0) {
                formato = valor.substring(0, 4);
            }
            if (valor.length > 4) {
                formato += '-' + valor.substring(4, 8);
            }

            e.target.value = formato;
        });

        telefonoInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    }

    // Cargar municipios según departamento seleccionado
    if (departamentoSelect && municipioSelect) {
        departamentoSelect.addEventListener('change', function () {
            cargarMunicipios(this.value);
        });
    }

    // Limpiar formulario al cerrar modal
    const modal = document.getElementById('modalUsuario');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            limpiarFormulario();
        });
    }

    // Validación adicional antes de enviar
    const form = document.getElementById('formUsuario');
    if (form) {
        form.addEventListener('submit', function (e) {
            const dpi = dpiInput.value.replace(/\s/g, '');
            const telefono = telefonoInput.value.replace(/-/g, '');

            // Validar DPI (debe ser 13 dígitos si se ingresó)
            if (dpiInput.value && dpi.length !== 13) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'DPI inválido',
                    text: 'El DPI debe contener exactamente 13 dígitos'
                });
                return false;
            }

            // Validar teléfono (debe ser 8 dígitos si se ingresó)
            if (telefonoInput.value && telefono.length !== 8) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Teléfono inválido',
                    text: 'El teléfono debe contener exactamente 8 dígitos'
                });
                return false;
            }

            // En modo edición, la contraseña es opcional
            if (modoEdicion) {
                contrasenaInput.removeAttribute('required');
            }
        });
    }
});

/**
 * Función para cargar municipios según departamento
 */
function cargarMunicipios(departamento) {
    const municipioSelect = document.getElementById('municipio');

    if (departamento) {
        municipioSelect.disabled = true;
        municipioSelect.innerHTML = '<option value="">Cargando...</option>';

        fetch(`../ajax/obtener_datos.php?accion=lista_municipios&departamento=${encodeURIComponent(departamento)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    municipioSelect.innerHTML = '<option value="">Seleccione...</option>';
                    data.data.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio;
                        option.textContent = municipio;
                        municipioSelect.appendChild(option);
                    });
                    municipioSelect.disabled = false;
                } else {
                    municipioSelect.innerHTML = '<option value="">No hay municipios disponibles</option>';
                }
            })
            .catch(error => {
                console.error('Error al cargar municipios:', error);
                municipioSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los municipios'
                });
            });
    } else {
        municipioSelect.disabled = true;
        municipioSelect.innerHTML = '<option value="">Primero seleccione departamento</option>';
    }
}

/**
 * Función para abrir modal en modo EDITAR
 */
function editarUsuario(usuario) {
    modoEdicion = true;

    // Cambiar título del modal
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Editar Usuario';

    // Cambiar acción del formulario
    document.getElementById('accionForm').value = 'editar_usuario';

    // Cambiar texto del botón
    document.getElementById('btnSubmitTexto').textContent = 'Actualizar Usuario';

    // Cargar datos del usuario
    document.getElementById('usuarioId').value = usuario.id;

    // Separar nombre completo en nombres y apellidos
    const nombreCompleto = usuario.NombreCompleto.trim();
    const partes = nombreCompleto.split(' ');

    // Asumir que las primeras 2 palabras son nombres y el resto apellidos
    // O si solo hay 2 palabras, 1 nombre y 1 apellido
    let nombres = '';
    let apellidos = '';

    if (partes.length >= 3) {
        nombres = partes.slice(0, 2).join(' '); // Primeras 2 palabras
        apellidos = partes.slice(2).join(' '); // Resto
    } else if (partes.length === 2) {
        nombres = partes[0];
        apellidos = partes[1];
    } else {
        nombres = nombreCompleto;
    }

    document.getElementById('nombres').value = nombres;
    document.getElementById('apellidos').value = apellidos;
    document.getElementById('usuario').value = usuario.Usuario;
    document.getElementById('rol').value = usuario.Rol;
    document.getElementById('dpi').value = usuario.DPI || '';
    document.getElementById('telefono').value = usuario.Telefono || '';
    document.getElementById('departamento').value = usuario.Departamento || '';

    // Habilitar edición de usuario y contraseña
    document.getElementById('usuario').removeAttribute('readonly');
    document.getElementById('contrasena').removeAttribute('readonly');

    // Contraseña opcional en edición
    document.getElementById('contrasena').removeAttribute('required');
    document.getElementById('contrasena').value = '';
    document.getElementById('labelContrasena').innerHTML = 'Contraseña <small class="text-muted">(dejar en blanco para no cambiar)</small>';
    document.getElementById('helpContrasena').textContent = 'Solo complete si desea cambiar la contraseña';

    // Cargar municipios si hay departamento
    if (usuario.Departamento) {
        cargarMunicipios(usuario.Departamento);

        // Esperar un momento y seleccionar el municipio
        setTimeout(() => {
            document.getElementById('municipio').value = usuario.Municipio || '';
        }, 500);
    }

    // Abrir formulario (overlay Premium o fallback a Bootstrap si existiera)
    try {
    if (typeof abrirModalPremium === 'function') {
        abrirModalPremium();              // usa tu overlay actual #modalUsuarioPremium
    } else {
        const m = document.getElementById('modalUsuario');
        if (m && window.bootstrap?.Modal) {
        new bootstrap.Modal(m).show();  // fallback si algún día agregas un modal BS
        }
    }
    } catch (err) {
    console.error('No se pudo abrir el modal de edición:', err);
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo abrir el formulario' });
    }

}

/**
 * Función para limpiar el formulario (modo crear)
 */
function limpiarFormulario() {
    modoEdicion = false;

    // Resetear formulario
    document.getElementById('formUsuario').reset();

    // Cambiar título del modal
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario';

    // Cambiar acción del formulario
    document.getElementById('accionForm').value = 'crear_usuario';

    // Cambiar texto del botón
    document.getElementById('btnSubmitTexto').textContent = 'Crear Usuario';

    // Limpiar ID oculto
    document.getElementById('usuarioId').value = '';

    // Bloquear campos de usuario y contraseña hasta que se generen
    document.getElementById('usuario').setAttribute('readonly', true);
    document.getElementById('contrasena').setAttribute('readonly', true);

    // Restaurar contraseña como requerida
    document.getElementById('contrasena').setAttribute('required', true);
    document.getElementById('labelContrasena').textContent = 'Contraseña *';
    document.getElementById('helpContrasena').textContent = 'Se genera automáticamente, puede editarla';

    // Resetear select de municipios
    const municipioSelect = document.getElementById('municipio');
    municipioSelect.disabled = true;
    municipioSelect.innerHTML = '<option value="">Primero seleccione departamento</option>';

    // Resetear toggle de contraseña
    const toggleBtn = document.getElementById('togglePassword');
    if (toggleBtn) {
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
        document.getElementById('contrasena').setAttribute('type', 'password');
    }
}

/**
 * Función global para abrir modal en modo crear
 */
window.abrirModalCrear = function () {
    limpiarFormulario();
}

// Exponer función de editar globalmente
window.editarUsuario = editarUsuario;