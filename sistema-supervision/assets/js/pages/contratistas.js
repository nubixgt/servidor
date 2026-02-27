/**
 * CONTRATISTAS - JavaScript con SweetAlert2
 * Sistema de Supervisión v5.0
 */

let dataTable;
const BASE_URL = window.location.origin + '/SistemaSupervision';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Contratistas cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initStatsAnimation();
    initDataTable();
    initFormValidation();
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
        dataTable = jQuery('#contratistasTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columnDefs: [
                { 
                    targets: 7,
                    orderable: false,
                    searchable: false
                },
                {
                    targets: 5,
                    type: 'num'
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
 * Abrir modal para nuevo contratista
 */
function abrirModalNuevo() {
    document.getElementById('modalTitle').textContent = 'Nuevo Contratista';
    document.getElementById('formContratista').reset();
    document.getElementById('contratista_id').value = '';
    document.getElementById('modalContratista').style.display = 'block';
    console.log('📝 Modal abierto para nuevo contratista');
}

/**
 * Ver detalles del contratista con SweetAlert2
 */
function verContratista(id) {
    console.log('👁️ Ver contratista ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el contratista',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre') || 'N/A';
    const nit = row.getAttribute('data-nit') || 'N/A';
    const direccion = row.getAttribute('data-direccion') || 'N/A';
    const telefono = row.getAttribute('data-telefono') || 'N/A';
    const email = row.getAttribute('data-email') || 'N/A';
    const contacto = row.getAttribute('data-contacto') || 'N/A';
    const estado = row.getAttribute('data-estado') || 'activo';
    const empleados = row.getAttribute('data-empleados') || '0';
    const fecha = row.getAttribute('data-fecha') || 'N/A';
    
    const estadoColor = {
        'activo': '#3b82f6',
        'inactivo': '#ef4444',
        'suspendido': '#f59e0b'
    };
    
    Swal.fire({
        title: 'Detalles del Contratista',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="display: grid; gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #60a5fa; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ID</div>
                        <div style="font-size: 16px; color: #1e3a8a; font-weight: 600;">#${id}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #2563eb; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Nombre de la Empresa</div>
                        <div style="font-size: 16px; color: #1e40af; font-weight: 600;">${nombre}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #7c3aed; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">NIT</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">${nit}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Contacto Principal</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">${contacto}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 12px; border-left: 4px solid #6366f1;">
                        <div style="font-size: 12px; color: #4f46e5; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Teléfono</div>
                        <div style="font-size: 16px; color: #3730a3; font-weight: 600;">${telefono}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; border-left: 4px solid #ec4899;">
                        <div style="font-size: 12px; color: #be185d; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Email</div>
                        <div style="font-size: 16px; color: #831843; font-weight: 600;">${email}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Total Empleados</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">👷 ${empleados} trabajadores</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-radius: 12px; border-left: 4px solid ${estadoColor[estado]};">
                        <div style="font-size: 12px; color: #ea580c; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Estado</div>
                        <div style="font-size: 16px; color: #9a3412; font-weight: 600; text-transform: capitalize;">${estado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; border-left: 4px solid #6b7280;">
                        <div style="font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Dirección</div>
                        <div style="font-size: 15px; color: #374151; line-height: 1.6;">${direccion}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-radius: 12px; border-left: 4px solid #0284c7;">
                        <div style="font-size: 12px; color: #0369a1; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Registro</div>
                        <div style="font-size: 16px; color: #075985; font-weight: 600;">${fecha}</div>
                    </div>
                </div>
            </div>
        `,
        width: '650px',
        showCancelButton: true,
        confirmButtonText: 'Editar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#3b82f6',
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
            editarContratista(id);
        }
    });
}

/**
 * Editar contratista
 */
function editarContratista(id) {
    console.log('✏️ Editar contratista ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el contratista',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre') || '';
    const nit = row.getAttribute('data-nit') || '';
    const direccion = row.getAttribute('data-direccion') || '';
    const telefono = row.getAttribute('data-telefono') || '';
    const email = row.getAttribute('data-email') || '';
    const contacto = row.getAttribute('data-contacto') || '';
    const estado = row.getAttribute('data-estado') || 'activo';
    
    console.log('📊 Datos cargados:', {
        id, nombre, nit, direccion, telefono, email, contacto, estado
    });
    
    document.getElementById('modalTitle').textContent = 'Editar Contratista';
    document.getElementById('contratista_id').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('nit').value = nit;
    document.getElementById('direccion').value = direccion;
    document.getElementById('telefono').value = telefono;
    document.getElementById('email').value = email;
    document.getElementById('contactoPrincipal').value = contacto;
    document.getElementById('estado').value = estado;
    
    document.getElementById('modalContratista').style.display = 'block';
    
    console.log('✅ Formulario cargado con datos del contratista');
}

/**
 * Eliminar contratista con SweetAlert2
 */
function eliminarContratista(id) {
    console.log('🗑️ Eliminar contratista ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const nombre = row ? row.getAttribute('data-nombre') : 'este contratista';
    const empleados = row ? row.getAttribute('data-empleados') : '0';
    
    let htmlContent = `
        <p style="font-size: 16px; color: #475569; margin: 16px 0;">
            Vas a eliminar al contratista:<br>
            <strong style="color: #1e3a8a; font-size: 18px;">${nombre}</strong>
        </p>
    `;
    
    if (parseInt(empleados) > 0) {
        htmlContent += `
            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 16px; border-radius: 12px; margin: 16px 0; border-left: 4px solid #f59e0b;">
                <p style="font-size: 14px; color: #78350f; font-weight: 600; margin: 0;">
                    ⚠️ ADVERTENCIA: Este contratista tiene <strong>${empleados} empleado(s)</strong> asignado(s).<br>
                    Al eliminarlo, los empleados quedarán sin contratista.
                </p>
            </div>
        `;
    }
    
    htmlContent += `
        <p style="font-size: 14px; color: #ef4444; font-weight: 600; margin-top: 16px;">
            ⚠️ Esta acción no se puede deshacer
        </p>
    `;
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: htmlContent,
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
    
    const url = `${BASE_URL}/api/contratistas.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'El contratista ha sido eliminado exitosamente',
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
                text: data.message || 'No se pudo eliminar el contratista',
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
    document.getElementById('modalContratista').style.display = 'none';
    document.getElementById('formContratista').reset();
    console.log('❌ Modal cerrado');
}

/**
 * Cerrar modal al hacer clic fuera
 */
window.onclick = function(event) {
    const modal = document.getElementById('modalContratista');
    if (event.target == modal) {
        cerrarModal();
    }
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formContratista');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarContratista();
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const nombre = document.getElementById('nombre').value.trim();
    const nit = document.getElementById('nit').value.trim();
    const contacto = document.getElementById('contactoPrincipal').value.trim();
    
    if (!nombre) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El nombre de la empresa es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('nombre').focus();
        return false;
    }
    
    if (!nit) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El NIT es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('nit').focus();
        return false;
    }
    
    if (!contacto) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El contacto principal es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('contactoPrincipal').focus();
        return false;
    }
    
    return true;
}

/**
 * Guardar contratista
 */
function guardarContratista() {
    const form = document.getElementById('formContratista');
    const formData = new FormData(form);
    const id = document.getElementById('contratista_id').value;
    
    console.log('💾 Guardando contratista...');
    console.log('ID:', id ? id : 'NUEVO');
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/contratistas.php`;
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
                text: 'Contratista guardado correctamente',
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
                text: data.message || 'No se pudo guardar el contratista',
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