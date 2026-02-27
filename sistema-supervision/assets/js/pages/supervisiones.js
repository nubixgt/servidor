/**
 * SUPERVISIONES - JavaScript con SweetAlert2 + Filtro de Fechas
 * Sistema de Supervisión v6.0.2
 */

let dataTable;
const BASE_URL = window.location.origin + '/SistemaSupervision';
let filtroActivo = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Supervisiones cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    // Verificar que SheetJS está cargado
    if (typeof XLSX === 'undefined') {
        console.error('❌ SheetJS (XLSX) no está cargado');
    } else {
        console.log('✅ SheetJS cargado correctamente');
    }
    
    initStatsAnimation();
    initDataTable();
    initDateInputs();
});

/**
 * Inicializar inputs de fecha con fecha máxima = hoy
 */
function initDateInputs() {
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    
    // Establecer fecha máxima como hoy
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.max = hoy;
    fechaFin.max = hoy;
    
    // Validar que fecha fin no sea menor que fecha inicio
    fechaInicio.addEventListener('change', function() {
        if (fechaFin.value && fechaFin.value < this.value) {
            fechaFin.value = this.value;
        }
        fechaFin.min = this.value;
    });
}

/**
 * Aplicar filtros de fecha
 */
function aplicarFiltros() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    // Validaciones
    if (!fechaInicio && !fechaFin) {
        Swal.fire({
            title: 'Campos vacíos',
            text: 'Por favor selecciona al menos una fecha',
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism'
            }
        });
        return;
    }
    
    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
        Swal.fire({
            title: 'Error en fechas',
            text: 'La fecha de inicio no puede ser mayor que la fecha fin',
            icon: 'error',
            confirmButtonColor: '#ef4444',
            background: 'rgba(255, 255, 255, 0.95)',
            customClass: {
                popup: 'swal-glassmorphism'
            }
        });
        return;
    }
    
    console.log('📅 Aplicando filtro:', { fechaInicio, fechaFin });
    
    // Aplicar filtro personalizado a DataTables
    jQuery.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            const row = document.querySelector(`#supervisionesTable tbody tr:nth-child(${dataIndex + 1})`);
            const fechaSupervision = row.getAttribute('data-fecha');
            
            if (!fechaSupervision) return false;
            
            const fechaSup = fechaSupervision.split(' ')[0]; // Formato: YYYY-MM-DD
            
            // Si solo hay fecha inicio
            if (fechaInicio && !fechaFin) {
                return fechaSup >= fechaInicio;
            }
            
            // Si solo hay fecha fin
            if (!fechaInicio && fechaFin) {
                return fechaSup <= fechaFin;
            }
            
            // Si hay ambas fechas
            return fechaSup >= fechaInicio && fechaSup <= fechaFin;
        }
    );
    
    // Redibujar tabla
    dataTable.draw();
    
    // Mostrar info del filtro
    filtroActivo = true;
    mostrarInfoFiltro(fechaInicio, fechaFin);
    
    // Mostrar botón de limpiar
    document.querySelector('.btn-clear-filter').style.display = 'flex';
    
    console.log('✅ Filtro aplicado correctamente');
}

/**
 * Limpiar filtros
 */
function limpiarFiltros() {
    console.log('🧹 Limpiando filtros...');
    
    // Limpiar inputs
    document.getElementById('fechaInicio').value = '';
    document.getElementById('fechaFin').value = '';
    
    // Remover filtro de DataTables
    jQuery.fn.dataTable.ext.search.pop();
    dataTable.draw();
    
    // Ocultar info y botón
    document.getElementById('filterInfo').style.display = 'none';
    document.querySelector('.btn-clear-filter').style.display = 'none';
    
    filtroActivo = false;
    
    Swal.fire({
        title: '¡Filtros limpiados!',
        text: 'Mostrando todas las supervisiones',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false,
        background: 'rgba(255, 255, 255, 0.95)',
        customClass: {
            popup: 'swal-glassmorphism'
        }
    });
    
    console.log('✅ Filtros limpiados');
}

/**
 * Mostrar información del filtro activo
 */
function mostrarInfoFiltro(fechaInicio, fechaFin) {
    const filterInfo = document.getElementById('filterInfo');
    const filterText = document.getElementById('filterInfoText');
    
    let texto = 'Filtrando supervisiones ';
    
    if (fechaInicio && fechaFin) {
        const inicio = new Date(fechaInicio).toLocaleDateString('es-GT', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        const fin = new Date(fechaFin).toLocaleDateString('es-GT', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        texto += `desde el ${inicio} hasta el ${fin}`;
    } else if (fechaInicio) {
        const inicio = new Date(fechaInicio).toLocaleDateString('es-GT', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        texto += `desde el ${inicio} en adelante`;
    } else if (fechaFin) {
        const fin = new Date(fechaFin).toLocaleDateString('es-GT', { 
            year: 'numeric', month: 'long', day: 'numeric' 
        });
        texto += `hasta el ${fin}`;
    }
    
    // Agregar contador de resultados
    const resultados = dataTable.rows({ search: 'applied' }).count();
    texto += ` • ${resultados} resultado${resultados !== 1 ? 's' : ''} encontrado${resultados !== 1 ? 's' : ''}`;
    
    filterText.textContent = texto;
    filterInfo.style.display = 'flex';
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
 * Inicializar DataTables (SIN columna Estado)
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
        dataTable = jQuery('#supervisionesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[1, 'desc']], // Ordenar por fecha
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columnDefs: [
                { 
                    targets: 5, // Columna de Acciones
                    orderable: false,
                    searchable: false
                }
            ],
            drawCallback: function() {
                console.log('✅ Tabla renderizada');
                
                // Actualizar info del filtro si está activo
                if (filtroActivo) {
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;
                    mostrarInfoFiltro(fechaInicio, fechaFin);
                }
            }
        });
        
        console.log('✅ DataTables inicializado correctamente');
        
    } catch (error) {
        console.error('❌ Error al inicializar DataTables:', error);
    }
}

/**
 * Exportar a Excel (con filtros aplicados)
 */
function exportarExcel() {
    console.log('📊 Exportando a Excel...');
    
    // Verificar que SheetJS está disponible
    if (typeof XLSX === 'undefined') {
        Swal.fire({
            title: '¡Error!',
            text: 'La librería de Excel no está cargada',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    // Obtener solo las filas VISIBLES (filtradas)
    const rows = dataTable.rows({ search: 'applied' }).nodes();
    
    if (rows.length === 0) {
        Swal.fire({
            title: 'Sin datos',
            text: 'No hay supervisiones para exportar',
            icon: 'info',
            confirmButtonColor: '#8b5cf6'
        });
        return;
    }
    
    // Crear array de datos para Excel
    const data = [];
    
    // Encabezados (SIN Estado)
    data.push(['ID', 'Fecha', 'Proyecto', 'Contratista', 'Trabajador']);
    
    // Datos (solo filas filtradas)
    jQuery(rows).each(function() {
        const row = this;
        const id = row.getAttribute('data-id');
        const fecha = row.getAttribute('data-fecha');
        const proyecto = row.getAttribute('data-proyecto');
        const contratista = row.getAttribute('data-contratista');
        const trabajador = row.getAttribute('data-trabajador');
        
        // Formatear fecha para Excel
        const fechaFormateada = fecha ? new Date(fecha).toLocaleDateString('es-GT', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }) : 'N/A';
        
        data.push([
            `#${id}`,
            fechaFormateada,
            proyecto,
            contratista,
            trabajador
        ]);
    });
    
    // Crear libro de Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(data);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 8 },  // ID
        { wch: 20 }, // Fecha
        { wch: 35 }, // Proyecto
        { wch: 30 }, // Contratista
        { wch: 25 }  // Trabajador
    ];
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Supervisiones');
    
    // Nombre del archivo con fecha y filtro
    const fecha = new Date().toLocaleDateString('es-GT').replace(/\//g, '-');
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    let nombreArchivo = `Supervisiones_${fecha}`;
    
    if (filtroActivo) {
        if (fechaInicio && fechaFin) {
            nombreArchivo += `_${fechaInicio}_a_${fechaFin}`;
        } else if (fechaInicio) {
            nombreArchivo += `_desde_${fechaInicio}`;
        } else if (fechaFin) {
            nombreArchivo += `_hasta_${fechaFin}`;
        }
    }
    
    nombreArchivo += '.xlsx';
    
    // Descargar archivo
    XLSX.writeFile(wb, nombreArchivo);
    
    // Mensaje de éxito
    const mensaje = filtroActivo 
        ? `Se ha exportado el archivo con ${rows.length} supervisión${rows.length !== 1 ? 'es' : ''} filtrada${rows.length !== 1 ? 's' : ''}`
        : `Se ha exportado el archivo con todas las supervisiones`;
    
    Swal.fire({
        title: '¡Exportado!',
        html: `
            <p style="font-size: 16px; color: #475569; margin-bottom: 12px;">
                ${mensaje}
            </p>
            <p style="font-size: 14px; color: #10b981; font-weight: 600;">
                📄 ${nombreArchivo}
            </p>
        `,
        icon: 'success',
        confirmButtonColor: '#10b981',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(16, 185, 129, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass'
        }
    });
    
    console.log('✅ Excel exportado:', nombreArchivo);
}

/**
 * Ver detalles de supervisión con SweetAlert2 (SIN ESTADO + CON TELÉFONO)
 */
function verSupervision(id) {
    console.log('👁️ Ver supervisión ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    
    if (!row) {
        Swal.fire({
            title: '¡Error!',
            text: 'No se encontró la supervisión',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    const fecha = row.getAttribute('data-fecha') || 'N/A';
    const proyecto = row.getAttribute('data-proyecto') || 'N/A';
    const contratista = row.getAttribute('data-contratista') || 'N/A';
    const trabajador = row.getAttribute('data-trabajador') || 'N/A';
    const telefono = row.getAttribute('data-telefono') || 'N/A';
    const observaciones = row.getAttribute('data-observaciones') || 'Sin observaciones';
    
    const formatearFecha = (fechaStr) => {
        if (!fechaStr || fechaStr === 'N/A') return 'No definida';
        const date = new Date(fechaStr);
        return date.toLocaleDateString('es-GT', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    Swal.fire({
        title: 'Detalles de la Supervisión',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="display: grid; gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: #60a5fa; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">ID</div>
                        <div style="font-size: 16px; color: #1e3a8a; font-weight: 600;">#${id}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: #d97706; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Fecha</div>
                        <div style="font-size: 16px; color: #78350f; font-weight: 600;">${formatearFecha(fecha)}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-radius: 12px; border-left: 4px solid #f97316;">
                        <div style="font-size: 12px; color: #ea580c; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Proyecto</div>
                        <div style="font-size: 16px; color: #9a3412; font-weight: 600;">${proyecto}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: #059669; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Contratista</div>
                        <div style="font-size: 16px; color: #065f46; font-weight: 600;">${contratista}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 12px; color: #7c3aed; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Trabajador</div>
                        <div style="font-size: 16px; color: #5b21b6; font-weight: 600;">${trabajador}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; border-left: 4px solid #ec4899;">
                        <div style="font-size: 12px; color: #be185d; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">📞 Teléfono</div>
                        <div style="font-size: 16px; color: #831843; font-weight: 600;">${telefono}</div>
                    </div>
                    
                    <div style="padding: 16px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; border-left: 4px solid #6b7280;">
                        <div style="font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Observaciones</div>
                        <div style="font-size: 15px; color: #374151; line-height: 1.6;">${observaciones}</div>
                    </div>
                </div>
            </div>
        `,
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#8b5cf6',
        background: 'rgba(255, 255, 255, 0.95)',
        backdrop: 'rgba(139, 92, 246, 0.1)',
        customClass: {
            popup: 'swal-glassmorphism',
            confirmButton: 'swal-button-glass'
        }
    });
}

/**
 * Eliminar supervisión con SweetAlert2
 */
function eliminarSupervision(id) {
    console.log('🗑️ Eliminar supervisión ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const proyecto = row ? row.getAttribute('data-proyecto') : 'esta supervisión';
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: `
            <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                Vas a eliminar la supervisión del proyecto:<br>
                <strong style="color: #1e3a8a; font-size: 18px;">${proyecto}</strong>
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
    
    const url = `${BASE_URL}/api/supervisiones.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: 'La supervisión ha sido eliminada exitosamente',
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
                text: data.message || 'No se pudo eliminar la supervisión',
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