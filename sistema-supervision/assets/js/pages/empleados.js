/**
 * EMPLEADOS - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.7
 */

let dataTable;
const BASE_URL = window.location.origin + '/SistemaSupervision';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Empleados cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initStatsAnimation();
    initDataTable();
    initFormValidation();
    initFieldFormatting();
});

/**
 * Animar estadísticas
 */
function initStatsAnimation() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-target')) || 0;
        animateCounter(stat, 0, target, 1200);
    });
}

/**
 * Animar contador
 */
function animateCounter(element, start, end, duration) {
    if (end === 0) {
        element.textContent = '0';
        return;
    }
    
    const startTime = performance.now();
    const range = end - start;
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (range * easeOut));
        
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = end.toLocaleString();
        }
    }
    
    requestAnimationFrame(update);
}

/**
 * Inicializar formateo de campos
 */
function initFieldFormatting() {
    // Formateo de DPI (solo números, máximo 13)
    const dpiInput = document.getElementById('dpi');
    if (dpiInput) {
        dpiInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 13);
        });
    }
    
    // Formateo de teléfono (solo números, máximo 8)
    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 8);
        });
    }
    
    // Formateo de salario
    const salarioInput = document.getElementById('salario');
    if (salarioInput) {
        // Permitir solo números y punto decimal mientras escribe
        salarioInput.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9.]/g, '');
            // Permitir solo un punto decimal
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = value;
        });
        
        // Formatear al salir del campo
        salarioInput.addEventListener('blur', function(e) {
            let value = this.value.replace(/[^0-9.]/g, '');
            if (value !== '' && !isNaN(value)) {
                const number = parseFloat(value);
                this.value = 'Q' + number.toLocaleString('es-GT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
        
        // Remover formato al enfocar para permitir edición
        salarioInput.addEventListener('focus', function(e) {
            let value = this.value.replace(/[^0-9.]/g, '');
            this.value = value;
        });
    }
    
    // Formateo de horas extra (solo números enteros)
    const horasExtraInput = document.getElementById('horas_extra');
    if (horasExtraInput) {
        horasExtraInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
}

/**
 * Inicializar DataTables
 */
function initDataTable() {
    if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery no está cargado');
        return;
    }
    
    if (typeof jQuery.fn.DataTable === 'undefined') {
        console.error('❌ DataTables no está cargado');
        return;
    }
    
    try {
        dataTable = jQuery('#trabajadoresTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columnDefs: [
                { 
                    targets: 7,
                    orderable: false,
                    searchable: false
                }
            ],
            drawCallback: function() {
                console.log('✅ Tabla renderizada');
            }
        });
        
        console.log('✅ DataTables inicializado correctamente');
        
    } catch (error) {
        console.error('❌ Error al inicializar DataTables:', error);
    }
}

/**
 * Abrir modal para nuevo trabajador
 */
function abrirModalNuevo() {
    document.getElementById('modalTitle').textContent = 'Nuevo Trabajador';
    document.getElementById('formTrabajador').reset();
    document.getElementById('trabajador_id').value = '';
    document.getElementById('modalTrabajador').style.display = 'block';
    console.log('📝 Modal abierto para nuevo trabajador');
}

/**
 * Ver detalles del trabajador con SweetAlert2
 */
function verTrabajador(id) {
    console.log('👁️ Ver trabajador ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el trabajador',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre');
    const contratista = row.getAttribute('data-contratista-nombre');
    const puesto = row.getAttribute('data-puesto') || 'N/A';
    const telefono = row.getAttribute('data-telefono') || 'N/A';
    const dpi = row.getAttribute('data-dpi') || 'N/A';
    const fechaNacimiento = row.getAttribute('data-fecha-nacimiento') || 'N/A';
    const fechaContratacion = row.getAttribute('data-fecha-contratacion') || 'N/A';
    const salario = row.getAttribute('data-salario');
    const horasExtra = row.getAttribute('data-horas-extra') || '0';
    const modalidad = row.getAttribute('data-modalidad') || 'N/A';
    const estado = row.getAttribute('data-estado');
    const fecha = row.getAttribute('data-fecha');
    
    // Formatear salario
    let salarioFormateado = 'N/A';
    if (salario && salario !== '' && !isNaN(salario)) {
        const number = parseFloat(salario);
        salarioFormateado = 'Q' + number.toLocaleString('es-GT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Formatear fechas
    const formatearFecha = (fecha) => {
        if (!fecha || fecha === 'N/A' || fecha === '') return 'N/A';
        const partes = fecha.split('-');
        if (partes.length === 3) {
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return fecha;
    };
    
    const estadoColor = {
        'activo': '#10b981',
        'inactivo': '#ef4444',
        'suspendido': '#f59e0b'
    };
    
    Swal.fire({
        title: 'Detalles del Trabajador',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="display: grid; gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #60a5fa; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ID</div>
                        <div style="font-size: 16px; color: #1e3a8a; font-weight: 600;">#${id}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Nombre</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${nombre}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #7c3aed; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Contratista</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">${contratista}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Puesto</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">${puesto}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #2563eb; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">DPI</div>
                        <div style="font-size: 16px; color: #1e40af; font-weight: 600;">${dpi}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; border-left: 4px solid #ec4899;">
                        <div style="font-size: 12px; color: #be185d; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Teléfono</div>
                        <div style="font-size: 16px; color: #831843; font-weight: 600;">${telefono}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; border-left: 4px solid #6b7280;">
                        <div style="font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Nacimiento</div>
                        <div style="font-size: 16px; color: #374151; font-weight: 600;">${formatearFecha(fechaNacimiento)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 12px; border-left: 4px solid #6366f1;">
                        <div style="font-size: 12px; color: #4f46e5; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Contratación</div>
                        <div style="font-size: 16px; color: #3730a3; font-weight: 600;">${formatearFecha(fechaContratacion)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Salario</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${salarioFormateado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #2563eb; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Horas Extra</div>
                        <div style="font-size: 16px; color: #1e40af; font-weight: 600;">${horasExtra} horas extras</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Modalidad</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">${modalidad}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-radius: 12px; border-left: 4px solid ${estadoColor[estado]};">
                        <div style="font-size: 12px; color: #ea580c; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Estado</div>
                        <div style="font-size: 16px; color: #9a3412; font-weight: 600; text-transform: capitalize;">${estado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 12px; border-left: 4px solid #6366f1;">
                        <div style="font-size: 12px; color: #4f46e5; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Registro</div>
                        <div style="font-size: 16px; color: #3730a3; font-weight: 600;">${fecha}</div>
                    </div>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Editar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(16, 185, 129, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass',
            cancelButton: 'swal-button-glass'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            editarTrabajador(id);
        }
    });
}

/**
 * Editar trabajador
 */
function editarTrabajador(id) {
    console.log('✏️ Editar trabajador ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el trabajador',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre');
    const contratistaId = row.getAttribute('data-contratista-id');
    const puesto = row.getAttribute('data-puesto');
    const dpi = row.getAttribute('data-dpi');
    const telefono = row.getAttribute('data-telefono');
    const fechaNacimiento = row.getAttribute('data-fecha-nacimiento');
    const fechaContratacion = row.getAttribute('data-fecha-contratacion');
    const salario = row.getAttribute('data-salario');
    const horasExtra = row.getAttribute('data-horas-extra');
    const modalidad = row.getAttribute('data-modalidad');
    const estado = row.getAttribute('data-estado');
    
    console.log('📊 Datos cargados:', {
        id, nombre, contratistaId, puesto, dpi, telefono, 
        fechaNacimiento, fechaContratacion, salario, horasExtra, modalidad, estado
    });
    
    document.getElementById('modalTitle').textContent = 'Editar Trabajador';
    document.getElementById('trabajador_id').value = id;
    document.getElementById('nombre').value = nombre || '';
    document.getElementById('contratista_id').value = contratistaId || '';
    document.getElementById('puesto').value = puesto || '';
    document.getElementById('dpi').value = dpi || '';
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('fecha_nacimiento').value = fechaNacimiento || '';
    document.getElementById('fecha_contratacion').value = fechaContratacion || '';
    document.getElementById('horas_extra').value = horasExtra || '0';
    document.getElementById('modalidad').value = modalidad || '';
    document.getElementById('estado').value = estado || 'activo';
    
    // Formatear salario para edición
    if (salario && salario !== '' && !isNaN(salario)) {
        const number = parseFloat(salario);
        document.getElementById('salario').value = 'Q' + number.toLocaleString('es-GT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    } else {
        document.getElementById('salario').value = '';
    }
    
    document.getElementById('modalTrabajador').style.display = 'block';
    
    console.log('✅ Formulario cargado con datos del trabajador');
}

/**
 * Eliminar trabajador con SweetAlert2
 */
function eliminarTrabajador(id) {
    console.log('🗑️ Eliminar trabajador ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const nombre = row ? row.getAttribute('data-nombre') : 'este trabajador';
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: `
            <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                Vas a eliminar al trabajador:<br>
                <strong style="color: #1e3a8a; font-size: 18px;">${nombre}</strong>
            </p>
            <p style="font-size: 14px; color: #ef4444; font-weight: 600;">
                ⚠️ Esta acción no se puede deshacer
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(239, 68, 68, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass',
            cancelButton: 'swal-button-glass'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarEliminacion(id);
        }
    });
}

/**
 * Ejecutar eliminación
 */
function ejecutarEliminacion(id) {
    Swal.fire({
        title: 'Eliminando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const url = `${BASE_URL}/api/trabajadores.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'El trabajador ha sido eliminado exitosamente',
                icon: 'success',
                confirmButtonColor: '#10b981',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(16, 185, 129, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '¡Error!',
                text: data.message || 'No se pudo eliminar el trabajador',
                icon: 'error',
                confirmButtonColor: '#ef4444',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(239, 68, 68, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            });
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        Swal.fire({
            title: '¡Error!',
            text: 'Error al comunicarse con el servidor',
            icon: 'error',
            confirmButtonColor: '#ef4444',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(239, 68, 68, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
    });
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    document.getElementById('modalTrabajador').style.display = 'none';
    document.getElementById('formTrabajador').reset();
    console.log('❌ Modal cerrado');
}

/**
 * Cerrar modal al hacer clic fuera
 */
window.onclick = function(event) {
    const modal = document.getElementById('modalTrabajador');
    if (event.target == modal) {
        cerrarModal();
    }
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formTrabajador');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarTrabajador();
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const nombre = document.getElementById('nombre').value.trim();
    const contratista = document.getElementById('contratista_id').value;
    const puesto = document.getElementById('puesto').value.trim();
    const dpi = document.getElementById('dpi').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    
    if (!nombre) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El nombre es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('nombre').focus();
        return false;
    }
    
    if (!contratista) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Debe seleccionar un contratista',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('contratista_id').focus();
        return false;
    }
    
    if (!puesto) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El puesto es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('puesto').focus();
        return false;
    }
    
    if (!dpi) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El DPI es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('dpi').focus();
        return false;
    }
    
    if (dpi.length !== 13) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El DPI debe tener exactamente 13 dígitos',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('dpi').focus();
        return false;
    }
    
    if (!telefono) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El teléfono es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('telefono').focus();
        return false;
    }
    
    if (telefono.length !== 8) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El teléfono debe tener exactamente 8 dígitos',
            icon: 'warning',
            confirmButtonColor: '#10b981',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('telefono').focus();
        return false;
    }
    
    return true;
}

/**
 * Guardar trabajador
 */
function guardarTrabajador() {
    const form = document.getElementById('formTrabajador');
    const formData = new FormData(form);
    const id = document.getElementById('trabajador_id').value;
    
    // Limpiar salario antes de enviar (quitar formato)
    const salarioInput = document.getElementById('salario');
    if (salarioInput.value) {
        const salarioLimpio = salarioInput.value.replace(/[^0-9.]/g, '');
        formData.set('salario', salarioLimpio);
    }
    
    console.log('💾 Guardando trabajador...');
    console.log('ID:', id ? id : 'NUEVO');
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/trabajadores.php`;
    let method = id ? 'PUT' : 'POST';
    let body;
    
    if (method === 'PUT') {
        const params = new URLSearchParams(formData);
        body = params.toString();
    } else {
        body = formData;
    }
    
    fetch(url, {
        method: method,
        body: body,
        headers: method === 'PUT' ? {
            'Content-Type': 'application/x-www-form-urlencoded'
        } : undefined
    })
    .then(response => response.json())
    .then(data => {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
        
        if (data.success) {
            cerrarModal();
            Swal.fire({
                title: '¡Éxito!',
                text: 'Trabajador guardado correctamente',
                icon: 'success',
                confirmButtonColor: '#10b981',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(16, 185, 129, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '¡Error!',
                text: data.message || 'No se pudo guardar el trabajador',
                icon: 'error',
                confirmButtonColor: '#ef4444',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(239, 68, 68, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            });
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
        
        Swal.fire({
            title: '¡Error!',
            text: 'Error al comunicarse con el servidor',
            icon: 'error',
            confirmButtonColor: '#ef4444',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(239, 68, 68, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
    });
}

// Animación de spin para el loader
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);