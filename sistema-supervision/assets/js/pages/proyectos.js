/**
 * PROYECTOS - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.8
 */

let dataTable;
const BASE_URL = window.location.origin + '/sistema-supervision';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Proyectos cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initStatsAnimation();
    initDataTable();
    initFormValidation();
    initFieldFormatting();
});

/**
 * Inicializar formateo de campos
 */
function initFieldFormatting() {
    // Campos de moneda: Consejo, Muni, ODC
    const camposMoneda = ['consejo', 'muni', 'odc'];
    
    camposMoneda.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (!campo) return;
        
        // Permitir solo números y punto decimal mientras se escribe
        campo.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            e.target.value = value;
        });
        
        // Formatear con Q y comas al salir del campo
        campo.addEventListener('blur', function(e) {
            const value = e.target.value.trim();
            if (value === '' || value === '0') {
                e.target.value = '';
                calcularPresupuesto();
                return;
            }
            
            const numero = parseFloat(value);
            if (!isNaN(numero)) {
                e.target.value = formatearMoneda(numero);
            }
            
            calcularPresupuesto();
        });
        
        // Remover formato al enfocar para edición fácil
        campo.addEventListener('focus', function(e) {
            const value = e.target.value;
            if (value.startsWith('Q')) {
                e.target.value = value.replace(/[Q,]/g, '').trim();
            }
        });
    });
}

/**
 * Formatear número como moneda Q0,000.00
 */
function formatearMoneda(numero) {
    return 'Q' + numero.toLocaleString('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Calcular presupuesto automáticamente (Consejo + Muni)
 */
function calcularPresupuesto() {
    const consejoInput = document.getElementById('consejo');
    const muniInput = document.getElementById('muni');
    const presupuestoInput = document.getElementById('presupuesto');
    
    if (!consejoInput || !muniInput || !presupuestoInput) return;
    
    // Obtener valores limpios (sin Q, sin comas)
    const consejoValue = consejoInput.value.replace(/[Q,]/g, '').trim();
    const muniValue = muniInput.value.replace(/[Q,]/g, '').trim();
    
    const consejo = parseFloat(consejoValue) || 0;
    const muni = parseFloat(muniValue) || 0;
    
    const presupuesto = consejo + muni;
    
    if (presupuesto > 0) {
        presupuestoInput.value = formatearMoneda(presupuesto);
    } else {
        presupuestoInput.value = '';
    }
    
    console.log('💰 Presupuesto calculado:', {
        consejo: consejo,
        muni: muni,
        total: presupuesto
    });
}

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
        dataTable = jQuery('#proyectosTable').DataTable({
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
 * Abrir modal para nuevo proyecto
 */
function abrirModalNuevo() {
    document.getElementById('modalTitle').textContent = 'Nuevo Proyecto';
    document.getElementById('formProyecto').reset();
    document.getElementById('proyecto_id').value = '';
    document.getElementById('presupuesto').value = '';
    document.getElementById('modalProyecto').style.display = 'block';
    console.log('📝 Modal abierto para nuevo proyecto');
}

/**
 * Ver detalles del proyecto con SweetAlert2
 */
function verProyecto(id) {
    console.log('👁️ Ver proyecto ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el proyecto',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre') || 'N/A';
    const tipo = row.getAttribute('data-tipo') || 'N/A';
    const ubicacion = row.getAttribute('data-ubicacion') || 'N/A';
    const descripcion = row.getAttribute('data-descripcion') || 'N/A';
    const estado = row.getAttribute('data-estado') || 'activo';
    const fechaInicio = row.getAttribute('data-fecha-inicio') || '';
    const fechaFinEstimada = row.getAttribute('data-fecha-fin-estimada') || '';
    const fechaFinReal = row.getAttribute('data-fecha-fin-real') || '';
    const presupuesto = row.getAttribute('data-presupuesto') || '0';
    const consejo = row.getAttribute('data-consejo') || '0';
    const muni = row.getAttribute('data-muni') || '0';
    const odc = row.getAttribute('data-odc') || '0';
    const cliente = row.getAttribute('data-cliente') || 'N/A';
    const fechaCreacion = row.getAttribute('data-fecha') || 'N/A';
    
    // Formatear montos
    const formatearMonto = (monto) => {
        if (!monto || monto === '0' || monto === 'N/A') return 'No definido';
        return 'Q ' + parseFloat(monto).toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };
    
    const presupuestoFormateado = formatearMonto(presupuesto);
    const consejoFormateado = formatearMonto(consejo);
    const muniFormateado = formatearMonto(muni);
    const odcFormateado = formatearMonto(odc);
    
    // Formatear fechas
    const formatearFecha = (fecha) => {
        if (!fecha || fecha === 'N/A') return 'No definida';
        const date = new Date(fecha + 'T00:00:00');
        return date.toLocaleDateString('es-GT', { year: 'numeric', month: 'long', day: 'numeric' });
    };
    
    const estadoColor = {
        'activo': '#f59e0b',
        'completado': '#10b981',
        'pausado': '#6b7280',
        'cancelado': '#ef4444'
    };
    
    Swal.fire({
        title: 'Detalles del Proyecto',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="display: grid; gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ID</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">#${id}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #ea580c; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Nombre del Proyecto</div>
                        <div style="font-size: 16px; color: #9a3412; font-weight: 600;">${nombre}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #2563eb; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Tipo</div>
                        <div style="font-size: 16px; color: #1e40af; font-weight: 600;">${tipo}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 12px; border-left: 4px solid #6366f1;">
                        <div style="font-size: 12px; color: #4f46e5; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Ubicación</div>
                        <div style="font-size: 16px; color: #3730a3; font-weight: 600;">${ubicacion}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #7c3aed; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Cliente</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">${cliente}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-radius: 12px; border-left: 4px solid ${estadoColor[estado]};">
                        <div style="font-size: 12px; color: #ea580c; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Estado</div>
                        <div style="font-size: 16px; color: #9a3412; font-weight: 600; text-transform: capitalize;">${estado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; border-left: 4px solid #6b7280;">
                        <div style="font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Descripción</div>
                        <div style="font-size: 15px; color: #374151; line-height: 1.6;">${descripcion}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Consejo</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${consejoFormateado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Muni</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${muniFormateado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dcfce7 0%, #86efac 100%); border-radius: 12px; border-left: 4px solid #22c55e;">
                        <div style="font-size: 12px; color: #16a34a; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Presupuesto Total</div>
                        <div style="font-size: 18px; color: #166534; font-weight: 700;">${presupuestoFormateado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #eab308;">
                        <div style="font-size: 12px; color: #ca8a04; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ODC</div>
                        <div style="font-size: 16px; color: #854d0e; font-weight: 600;">${odcFormateado}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-radius: 12px; border-left: 4px solid #0284c7;">
                        <div style="font-size: 12px; color: #0369a1; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Inicio</div>
                        <div style="font-size: 16px; color: #075985; font-weight: 600;">${formatearFecha(fechaInicio)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; border-left: 4px solid #ec4899;">
                        <div style="font-size: 12px; color: #be185d; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha Fin Estimada</div>
                        <div style="font-size: 16px; color: #831843; font-weight: 600;">${formatearFecha(fechaFinEstimada)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 12px; border-left: 4px solid #22c55e;">
                        <div style="font-size: 12px; color: #16a34a; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha Fin Real</div>
                        <div style="font-size: 16px; color: #166534; font-weight: 600;">${formatearFecha(fechaFinReal)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #eab308;">
                        <div style="font-size: 12px; color: #ca8a04; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha de Registro</div>
                        <div style="font-size: 16px; color: #854d0e; font-weight: 600;">${fechaCreacion}</div>
                    </div>
                </div>
            </div>
        `,
        width: '700px',
        showCancelButton: true,
        confirmButtonText: 'Editar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(245, 158, 11, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass',
            cancelButton: 'swal-button-glass'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            editarProyecto(id);
        }
    });
}

/**
 * Editar proyecto
 */
function editarProyecto(id) {
    console.log('✏️ Editar proyecto ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró el proyecto',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const nombre = row.getAttribute('data-nombre') || '';
    const tipo = row.getAttribute('data-tipo') || '';
    const ubicacion = row.getAttribute('data-ubicacion') || '';
    const descripcion = row.getAttribute('data-descripcion') || '';
    const estado = row.getAttribute('data-estado') || 'activo';
    const fechaInicio = row.getAttribute('data-fecha-inicio') || '';
    const fechaFinEstimada = row.getAttribute('data-fecha-fin-estimada') || '';
    const fechaFinReal = row.getAttribute('data-fecha-fin-real') || '';
    const presupuesto = row.getAttribute('data-presupuesto') || '';
    const consejo = row.getAttribute('data-consejo') || '';
    const muni = row.getAttribute('data-muni') || '';
    const odc = row.getAttribute('data-odc') || '';
    const cliente = row.getAttribute('data-cliente') || '';
    
    console.log('📊 Datos cargados:', {
        id, nombre, tipo, ubicacion, descripcion, estado,
        fechaInicio, fechaFinEstimada, fechaFinReal, 
        presupuesto, consejo, muni, odc, cliente
    });
    
    document.getElementById('modalTitle').textContent = 'Editar Proyecto';
    document.getElementById('proyecto_id').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('tipo').value = tipo;
    document.getElementById('ubicacion').value = ubicacion;
    document.getElementById('descripcion').value = descripcion;
    document.getElementById('estado').value = estado;
    document.getElementById('fecha_inicio').value = fechaInicio;
    document.getElementById('fecha_fin_estimada').value = fechaFinEstimada;
    document.getElementById('fecha_fin_real').value = fechaFinReal;
    document.getElementById('cliente').value = cliente;
    
    // Formatear campos de moneda para edición
    if (consejo && consejo !== '0') {
        document.getElementById('consejo').value = formatearMoneda(parseFloat(consejo));
    }
    if (muni && muni !== '0') {
        document.getElementById('muni').value = formatearMoneda(parseFloat(muni));
    }
    if (odc && odc !== '0') {
        document.getElementById('odc').value = formatearMoneda(parseFloat(odc));
    }
    
    // Calcular presupuesto
    calcularPresupuesto();
    
    document.getElementById('modalProyecto').style.display = 'block';
    
    console.log('✅ Formulario cargado con datos del proyecto');
}

/**
 * Eliminar proyecto con SweetAlert2
 */
function eliminarProyecto(id) {
    console.log('🗑️ Eliminar proyecto ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const nombre = row ? row.getAttribute('data-nombre') : 'este proyecto';
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: `
            <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                Vas a eliminar el proyecto:<br>
                <strong style="color: #78350f; font-size: 18px;">${nombre}</strong>
            </p>
            <p style="font-size: 14px; color: #ef4444; font-weight: 600; margin-top: 16px;">
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
    
    const url = `${BASE_URL}/api/proyectos.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'El proyecto ha sido eliminado exitosamente',
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
                text: data.message || 'No se pudo eliminar el proyecto',
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
    document.getElementById('modalProyecto').style.display = 'none';
    document.getElementById('formProyecto').reset();
    console.log('❌ Modal cerrado');
}

/**
 * Cerrar modal al hacer clic fuera
 */
window.onclick = function(event) {
    const modal = document.getElementById('modalProyecto');
    if (event.target == modal) {
        cerrarModal();
    }
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formProyecto');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarProyecto();
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const nombre = document.getElementById('nombre').value.trim();
    const tipo = document.getElementById('tipo').value;
    
    if (!nombre) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El nombre del proyecto es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('nombre').focus();
        return false;
    }
    
    if (!tipo) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El tipo de proyecto es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('tipo').focus();
        return false;
    }
    
    // Validar fechas si están presentes
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFinEstimada = document.getElementById('fecha_fin_estimada').value;
    
    if (fechaInicio && fechaFinEstimada) {
        if (new Date(fechaFinEstimada) < new Date(fechaInicio)) {
            Swal.fire({
                title: '¡Atención!',
                text: 'La fecha fin estimada no puede ser anterior a la fecha de inicio',
                icon: 'warning',
                confirmButtonColor: '#f59e0b',
                background: 'rgba(255, 255, 255, 0.95)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            });
            document.getElementById('fecha_fin_estimada').focus();
            return false;
        }
    }
    
    return true;
}

/**
 * Guardar proyecto
 */
function guardarProyecto() {
    const form = document.getElementById('formProyecto');
    const formData = new FormData(form);
    const id = document.getElementById('proyecto_id').value;
    
    console.log('💾 Guardando proyecto...');
    console.log('ID:', id ? id : 'NUEVO');
    
    // Limpiar campos de moneda antes de enviar (quitar Q y comas)
    const consejo = document.getElementById('consejo').value.replace(/[Q,]/g, '').trim();
    const muni = document.getElementById('muni').value.replace(/[Q,]/g, '').trim();
    const odc = document.getElementById('odc').value.replace(/[Q,]/g, '').trim();
    const presupuesto = document.getElementById('presupuesto').value.replace(/[Q,]/g, '').trim();
    
    formData.set('consejo', consejo);
    formData.set('muni', muni);
    formData.set('odc', odc);
    formData.set('presupuesto', presupuesto);
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/proyectos.php`;
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
                text: 'Proyecto guardado correctamente',
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
                text: data.message || 'No se pudo guardar el proyecto',
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