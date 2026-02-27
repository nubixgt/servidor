/**
 * assets/js/pages/supervisiones-tecnico.js
 * Supervisiones para Técnicos - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.4
 * ACTUALIZADO: IDs iguales al admin
 */

// Detectar la URL base del sistema
const BASE_URL = window.location.origin + '/SistemaSupervision';

let tablaSupervisiones;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Supervisiones (Técnico) cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initDataTable();
    animateStatValue();
});

/**
 * Inicializar DataTable
 */
function initDataTable() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
        console.error('❌ jQuery o DataTables no está cargado');
        return;
    }
    
    tablaSupervisiones = jQuery('#supervisionesTable').DataTable({
        ajax: {
            url: `${BASE_URL}/api/supervisiones.php`,
            dataSrc: 'data',
            error: function(xhr, error, thrown) {
                console.error('❌ Error al cargar supervisiones:', error);
                Swal.fire({
                    title: '¡Error!',
                    text: 'No se pudieron cargar las supervisiones',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        },
        columns: [
            {
                data: 'id',
                render: function(data) {
                    return `<span class="id-badge">#${data}</span>`;
                }
            },
            {
                data: 'fecha_supervision',
                render: function(data) {
                    if (!data) return '-';
                    const fecha = new Date(data);
                    return fecha.toLocaleDateString('es-GT', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    });
                }
            },
            { 
                data: 'proyecto_nombre',
                render: function(data) {
                    return `<strong>${data}</strong>`;
                }
            },
            { data: 'contratista_nombre' },
            {
                data: 'trabajador_nombre',
                render: function(data) {
                    if (!data) return '-';
                    const inicial = data.charAt(0).toUpperCase();
                    return `
                        <div class="worker-cell">
                            <div class="worker-avatar">${inicial}</div>
                            <span>${data}</span>
                        </div>
                    `;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="verSupervision(${row.id})" title="Ver detalles">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="btn-action btn-delete" onclick="eliminarSupervision(${row.id})" title="Eliminar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: '<"table-controls"<"table-length"l><"table-search"f>>rt<"table-pagination"ip>',
        drawCallback: function() {
            console.log('✅ Tabla de supervisiones actualizada');
        }
    });
    
    console.log('✅ DataTable inicializado');
}

/**
 * Animar el valor de estadísticas
 */
function animateStatValue() {
    const statValue = document.querySelector('.stat-value[data-target]');
    if (!statValue) return;
    
    const target = parseInt(statValue.getAttribute('data-target')) || 0;
    const duration = 2000;
    const increment = target / (duration / 16);
    let current = 0;
    
    function updateValue() {
        current += increment;
        if (current < target) {
            statValue.textContent = Math.floor(current);
            requestAnimationFrame(updateValue);
        } else {
            statValue.textContent = target;
        }
    }
    
    updateValue();
}

/**
 * Ver detalles de supervisión
 */
function verSupervision(id) {
    console.log('👁️ Viendo detalles de supervisión:', id);
    
    fetch(`${BASE_URL}/api/supervisiones.php`)
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.message);
            }
            
            const supervision = result.data.find(s => s.id == id);
            
            if (!supervision) {
                throw new Error('Supervisión no encontrada');
            }
            
            // Formatear fecha
            const fecha = new Date(supervision.fecha_supervision);
            const fechaFormateada = fecha.toLocaleDateString('es-GT', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            Swal.fire({
                title: `Supervisión #${supervision.id}`,
                html: `
                    <div class="details-grid">
                        <div class="detail-card">
                            <div class="detail-icon">🔵</div>
                            <div class="detail-content">
                                <span class="detail-label">ID</span>
                                <span class="detail-value">#${supervision.id}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <div class="detail-icon">🟡</div>
                            <div class="detail-content">
                                <span class="detail-label">Fecha</span>
                                <span class="detail-value">${fechaFormateada}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <div class="detail-icon">🟠</div>
                            <div class="detail-content">
                                <span class="detail-label">Proyecto</span>
                                <span class="detail-value">${supervision.proyecto_nombre}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <div class="detail-icon">🟢</div>
                            <div class="detail-content">
                                <span class="detail-label">Contratista</span>
                                <span class="detail-value">${supervision.contratista_nombre}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <div class="detail-icon">🟣</div>
                            <div class="detail-content">
                                <span class="detail-label">Trabajador</span>
                                <span class="detail-value">${supervision.trabajador_nombre}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <div class="detail-icon">🩷</div>
                            <div class="detail-content">
                                <span class="detail-label">Teléfono</span>
                                <span class="detail-value">${supervision.telefono || '-'}</span>
                            </div>
                        </div>
                        
                        <div class="detail-card" style="grid-column: 1 / -1;">
                            <div class="detail-icon">⚪</div>
                            <div class="detail-content">
                                <span class="detail-label">Observaciones</span>
                                <span class="detail-value">${supervision.observaciones || 'Sin observaciones'}</span>
                            </div>
                        </div>
                    </div>
                `,
                width: '900px',
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#8b5cf6',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(139, 92, 246, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            });
        })
        .catch(error => {
            console.error('❌ Error:', error);
            Swal.fire({
                title: '¡Error!',
                text: error.message || 'No se pudieron cargar los detalles',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        });
}

/**
 * Eliminar supervisión
 */
function eliminarSupervision(id) {
    console.log('🗑️ Eliminando supervisión:', id);
    
    Swal.fire({
        title: '¿Eliminar Supervisión?',
        text: '¿Estás seguro de que deseas eliminar esta supervisión? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#60a5fa',
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
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espera',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Eliminar
            fetch(`${BASE_URL}/api/supervisiones.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: 'Supervisión eliminada correctamente',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        timer: 2000
                    });
                    
                    // Recargar tabla
                    tablaSupervisiones.ajax.reload();
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                Swal.fire({
                    title: '¡Error!',
                    text: error.message || 'No se pudo eliminar la supervisión',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

/**
 * Aplicar filtros de fecha
 */
function aplicarFiltros() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    if (!fechaInicio || !fechaFin) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Debes seleccionar ambas fechas',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    if (new Date(fechaInicio) > new Date(fechaFin)) {
        Swal.fire({
            title: '¡Error!',
            text: 'La fecha de inicio no puede ser mayor a la fecha fin',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    // Filtrar tabla
    jQuery.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            const fechaRow = data[1]; // Columna de fecha
            const fecha = new Date(fechaRow.split('/').reverse().join('-'));
            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);
            
            return fecha >= inicio && fecha <= fin;
        }
    );
    
    tablaSupervisiones.draw();
    
    // Mostrar info
    const info = document.getElementById('filterInfo');
    const infoText = document.getElementById('filterInfoText');
    const btnClear = document.querySelector('.btn-clear-filter');
    const registrosFiltrados = tablaSupervisiones.rows({ search: 'applied' }).count();
    
    info.style.display = 'flex';
    infoText.textContent = `Mostrando ${registrosFiltrados} registros del ${fechaInicio} al ${fechaFin}`;
    btnClear.style.display = 'flex';
    
    console.log('✅ Filtros aplicados');
}

/**
 * Limpiar filtros
 */
function limpiarFiltros() {
    document.getElementById('fechaInicio').value = '';
    document.getElementById('fechaFin').value = '';
    
    jQuery.fn.dataTable.ext.search.pop();
    tablaSupervisiones.draw();
    
    document.getElementById('filterInfo').style.display = 'none';
    document.querySelector('.btn-clear-filter').style.display = 'none';
    
    console.log('✅ Filtros limpiados');
}

/**
 * Exportar a Excel
 */
function exportarExcel() {
    if (typeof XLSX === 'undefined') {
        console.error('❌ SheetJS (XLSX) no está cargado');
        Swal.fire({
            title: '¡Error!',
            text: 'No se pudo cargar la librería de exportación',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    // Obtener datos filtrados
    const datos = tablaSupervisiones.rows({ search: 'applied' }).data().toArray();
    
    if (datos.length === 0) {
        Swal.fire({
            title: '¡Atención!',
            text: 'No hay datos para exportar',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Preparar datos para Excel
    const datosExcel = datos.map(row => ({
        'ID': row.id,
        'Fecha': new Date(row.fecha_supervision).toLocaleDateString('es-GT'),
        'Proyecto': row.proyecto_nombre,
        'Contratista': row.contratista_nombre,
        'Trabajador': row.trabajador_nombre,
        'Teléfono': row.telefono || '-'
    }));
    
    // Crear workbook
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(datosExcel);
    
    XLSX.utils.book_append_sheet(wb, ws, 'Supervisiones');
    
    // Generar nombre de archivo
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const hoy = new Date().toLocaleDateString('es-GT').replace(/\//g, '-');
    
    let nombreArchivo = `Mis_Supervisiones_${hoy}`;
    
    if (fechaInicio && fechaFin) {
        nombreArchivo += `_desde_${fechaInicio}_hasta_${fechaFin}`;
    }
    
    nombreArchivo += '.xlsx';
    
    // Descargar
    XLSX.writeFile(wb, nombreArchivo);
    
    Swal.fire({
        title: '¡Éxito!',
        text: 'Archivo Excel generado correctamente',
        icon: 'success',
        confirmButtonColor: '#10b981',
        timer: 2000
    });
    
    console.log('✅ Excel exportado:', nombreArchivo);
}

document.head.appendChild(style);