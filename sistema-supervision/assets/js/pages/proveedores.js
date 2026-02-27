/**
 * PROVEEDORES - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.5
 */

let dataTable;
const BASE_URL = window.location.origin + '/SistemaSupervision';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Proveedores cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initStatsAnimation();
    initDataTable();
    initFormValidation();
    initPhoneValidation();
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
        dataTable = jQuery('#proveedoresTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columnDefs: [
                { 
                    targets: 5,
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
 * Validación de teléfono - SOLO 8 DÍGITOS
 */
function initPhoneValidation() {
    const telefonoInput = document.getElementById('telefono');
    
    if (!telefonoInput) return;
    
    // Solo permitir números
    telefonoInput.addEventListener('input', function(e) {
        // Remover cualquier caracter que no sea número
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Limitar a 8 dígitos
        if (this.value.length > 8) {
            this.value = this.value.slice(0, 8);
        }
    });
    
    // Validar al perder el foco
    telefonoInput.addEventListener('blur', function(e) {
        const telefono = this.value.trim();
        
        if (telefono && telefono.length !== 8) {
            Swal.fire({
                title: '¡Atención!',
                html: `
                    <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                        El teléfono debe tener exactamente <strong style="color: #8b5cf6;">8 dígitos</strong>
                    </p>
                    <p style="font-size: 14px; color: #8b5cf6; font-weight: 600;">
                        Ejemplo: 45289012
                    </p>
                `,
                icon: 'warning',
                confirmButtonColor: '#8b5cf6',
                background: 'rgba(255, 255, 255, 0.95)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            });
            this.focus();
        }
    });
    
    // Prevenir pegar texto no numérico
    telefonoInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericText = pastedText.replace(/[^0-9]/g, '').slice(0, 8);
        this.value = numericText;
    });
}

/**
 * Abrir modal para nuevo proveedor
 */
function abrirModalNuevo() {
    document.getElementById('modalTitle').textContent = 'Nuevo Proveedor';
    document.getElementById('formProveedor').reset();
    document.getElementById('proveedor_id').value = '';
    document.getElementById('modalProveedor').style.display = 'block';
    console.log('📝 Modal abierto para nuevo proveedor');
}

/**
 * Ver detalles del proveedor con SweetAlert2
 */
function verProveedor(id) {
    console.log('👁️ Ver proveedor ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el proveedor',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre');
    const nit = row.getAttribute('data-nit') || 'N/A';
    const telefono = row.getAttribute('data-telefono') || 'N/A';
    const observaciones = row.getAttribute('data-observaciones') || 'Sin observaciones';
    const estado = row.getAttribute('data-estado');
    const fecha = row.getAttribute('data-fecha');
    
    const estadoColor = {
        'activo': '#10b981',
        'inactivo': '#ef4444'
    };
    
    Swal.fire({
        title: 'Detalles del Proveedor',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="display: grid; gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #a78bfa; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ID</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">#${id}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #7c3aed; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Nombre</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">${nombre}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #2563eb; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">NIT</div>
                        <div style="font-size: 16px; color: #1e40af; font-weight: 600;">${nit}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Teléfono</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${telefono}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Observaciones</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">${observaciones}</div>
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
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#6b7280',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(139, 92, 246, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass',
            cancelButton: 'swal-button-glass'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            editarProveedor(id);
        }
    });
}

/**
 * Editar proveedor
 */
function editarProveedor(id) {
    console.log('✏️ Editar proveedor ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el proveedor',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre');
    const nit = row.getAttribute('data-nit');
    const telefono = row.getAttribute('data-telefono');
    const observaciones = row.getAttribute('data-observaciones');
    const estado = row.getAttribute('data-estado');
    
    console.log('📊 Datos cargados:', {
        id, nombre, nit, telefono, observaciones, estado
    });
    
    document.getElementById('modalTitle').textContent = 'Editar Proveedor';
    document.getElementById('proveedor_id').value = id;
    document.getElementById('nombre').value = nombre || '';
    document.getElementById('nit').value = nit || '';
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('observaciones').value = observaciones || '';
    document.getElementById('estado').value = estado || 'activo';
    
    document.getElementById('modalProveedor').style.display = 'block';
    
    console.log('✅ Formulario cargado con datos del proveedor');
}

/**
 * Eliminar proveedor con SweetAlert2
 */
function eliminarProveedor(id) {
    console.log('🗑️ Eliminar proveedor ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const nombre = row ? row.getAttribute('data-nombre') : 'este proveedor';
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: `
            <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                Vas a eliminar al proveedor:<br>
                <strong style="color: #5b21b6; font-size: 18px;">${nombre}</strong>
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
    
    const url = `${BASE_URL}/api/proveedores.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'El proveedor ha sido eliminado exitosamente',
                icon: 'success',
                confirmButtonColor: '#8b5cf6',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(139, 92, 246, 0.1)',
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
                text: data.message || 'No se pudo eliminar el proveedor',
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
    document.getElementById('modalProveedor').style.display = 'none';
    document.getElementById('formProveedor').reset();
    console.log('❌ Modal cerrado');
}

/**
 * Cerrar modal al hacer clic fuera
 */
window.onclick = function(event) {
    const modal = document.getElementById('modalProveedor');
    if (event.target == modal) {
        cerrarModal();
    }
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formProveedor');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarProveedor();
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const nombre = document.getElementById('nombre').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    
    if (!nombre) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El nombre es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#8b5cf6',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('nombre').focus();
        return false;
    }
    
    // Validar teléfono si se ingresó
    if (telefono && telefono.length !== 8) {
        Swal.fire({
            title: '¡Atención!',
            html: `
                <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                    El teléfono debe tener exactamente <strong style="color: #8b5cf6;">8 dígitos</strong>
                </p>
                <p style="font-size: 14px; color: #8b5cf6; font-weight: 600;">
                    Ejemplo: 45289012
                </p>
            `,
            icon: 'warning',
            confirmButtonColor: '#8b5cf6',
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
 * Guardar proveedor
 */
function guardarProveedor() {
    const form = document.getElementById('formProveedor');
    const formData = new FormData(form);
    const id = document.getElementById('proveedor_id').value;
    
    console.log('💾 Guardando proveedor...');
    console.log('ID:', id ? id : 'NUEVO');
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/proveedores.php`;
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
                text: 'Proveedor guardado correctamente',
                icon: 'success',
                confirmButtonColor: '#8b5cf6',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(139, 92, 246, 0.1)',
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
                text: data.message || 'No se pudo guardar el proveedor',
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