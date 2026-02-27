// Inicializar DataTables cuando el documento esté listo
$(document).ready(function() {
    $('#contratosTable').DataTable({
        ajax: {
            url: '../../api/obtener_contratos.php',
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'nombre_completo' },
            { data: 'numero_contrato' },
            { data: 'servicios' },
            { data: 'fecha_contrato' },
            { data: 'fecha_inicio' },
            { data: 'fecha_fin' },
            { 
                data: 'monto_total',
                render: function(data) {
                    return 'Q' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="action-buttons">
                            <button class="action-btn btn-view" onclick="verContrato(${row.id})" title="Visualizar">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="action-btn btn-edit" onclick="editarContrato(${row.id})" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="action-btn btn-log" onclick="verBitacora(${row.id})" title="Bitácora">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </button>
                            <button class="action-btn btn-pdf" onclick="descargarPDF(${row.id})" title="Descargar PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[0, 'desc']],
        responsive: true,
        dom: '<"top"lf>rt<"bottom"ip><"clear">'
    });
});

// Función para visualizar contrato
function verContrato(id) {
    fetch(`../../api/ver_contrato.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModalContrato(data.contrato, data.archivos);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo cargar el contrato',
                    confirmButtonColor: '#667eea'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar el contrato',
                confirmButtonColor: '#667eea'
            });
        });
}

// Función para mostrar modal con detalles del contrato
function mostrarModalContrato(contrato, archivos) {
    const modalBody = document.getElementById('modalBody');
    
    // Formatear montos
    const montoTotal = 'Q' + parseFloat(contrato.monto_total).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    const montoPago = 'Q' + parseFloat(contrato.monto_pago).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Formatear DPI
    const dpiFormateado = contrato.dpi.replace(/(\d{4})(\d{5})(\d{4})/, '$1 $2 $3');
    
    // Nombres amigables para tipos de archivos
    const nombresArchivos = {
        'cv': 'Currículum Vitae (CV)',
        'titulo': 'Título Profesional',
        'colegiadoActivo': 'Colegiado Activo',
        'cuentaBanco': 'Cuenta de Banco',
        'dpiArchivo': 'DPI'
    };
    
    modalBody.innerHTML = `
        <!-- Información General -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-file-contract"></i> Información del Contrato</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Número de Contrato</label>
                    <p>${contrato.numero_contrato || 'N/A'}</p>
                </div>
                <div class="detail-item">
                    <label>Tipo de Servicio</label>
                    <p>${contrato.servicios || 'N/A'}</p>
                </div>
                <div class="detail-item">
                    <label>IVA</label>
                    <p>${contrato.iva || 'N/A'}</p>
                </div>
                <div class="detail-item">
                    <label>Fondos</label>
                    <p>${contrato.fondos || 'N/A'}</p>
                </div>
                <div class="detail-item">
                    <label>Armonización</label>
                    <p>${contrato.armonizacion || 'N/A'}</p>
                </div>
                <div class="detail-item">
                    <label>Fecha de Contrato</label>
                    <p>${contrato.fecha_contrato || 'N/A'}</p>
                </div>
            </div>
        </div>

        <!-- Datos del Contratista -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-user"></i> Datos del Contratista</h3>
            <div class="detail-grid">
                <div class="detail-item detail-full">
                    <label>Nombre Completo</label>
                    <p>${contrato.nombre_completo}</p>
                </div>
                <div class="detail-item">
                    <label>Edad</label>
                    <p>${contrato.edad} años</p>
                </div>
                <div class="detail-item">
                    <label>Estado Civil</label>
                    <p>${contrato.estado_civil}</p>
                </div>
                <div class="detail-item">
                    <label>Profesión</label>
                    <p>${contrato.profesion}</p>
                </div>
                <div class="detail-item">
                    <label>DPI</label>
                    <p>${dpiFormateado}</p>
                </div>
                <div class="detail-item detail-full">
                    <label>Domicilio</label>
                    <p>${contrato.domicilio}</p>
                </div>
            </div>
        </div>

        <!-- Fechas del Contrato -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-calendar-days"></i> Fechas</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Fecha de Inicio</label>
                    <p>${contrato.fecha_inicio}</p>
                </div>
                <div class="detail-item">
                    <label>Fecha de Finalización</label>
                    <p>${contrato.fecha_fin}</p>
                </div>
            </div>
        </div>

        <!-- Datos Financieros -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-money-bill-wave"></i> Datos Financieros</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Monto Total</label>
                    <p style="font-size: 1.2rem; font-weight: 600; color: #667eea;">${montoTotal}</p>
                </div>
                <div class="detail-item">
                    <label>Número de Pagos</label>
                    <p>${contrato.numero_pagos}</p>
                </div>
                <div class="detail-item">
                    <label>Monto por Pago</label>
                    <p style="font-size: 1.1rem; font-weight: 600; color: #10b981;">${montoPago}</p>
                </div>
            </div>
        </div>

        ${contrato.termino1 || contrato.termino2 || contrato.termino3 || contrato.termino4 || contrato.termino5 || 
          contrato.termino6 || contrato.termino7 || contrato.termino8 || contrato.termino9 || contrato.termino10 ? `
        <!-- Términos de Referencia -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-list-check"></i> Términos de Referencia</h3>
            <div class="detail-grid">
                ${generarTerminos(contrato)}
            </div>
        </div>
        ` : ''}

        ${archivos && archivos.length > 0 ? `
        <!-- Archivos Adjuntos -->
        <div class="detail-section">
            <h3><i class="fa-solid fa-paperclip"></i> Archivos Adjuntos</h3>
            <div class="archivos-grid">
                ${archivos.map(archivo => {
                    const extension = archivo.nombre_archivo.split('.').pop().toLowerCase();
                    const esPDF = extension === 'pdf';
                    const esImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);
                    
                    return `
                        <div class="archivo-item">
                            <div class="archivo-icon">
                                ${esPDF ? '<i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 2.5rem;"></i>' : 
                                  esImagen ? '<i class="fa-solid fa-file-image" style="color: #10b981; font-size: 2.5rem;"></i>' : 
                                  '<i class="fa-solid fa-file" style="color: #667eea; font-size: 2.5rem;"></i>'}
                            </div>
                            <div class="archivo-info">
                                <p class="archivo-nombre">${nombresArchivos[archivo.tipo_archivo] || archivo.tipo_archivo}</p>
                                <p class="archivo-file">${archivo.nombre_archivo}</p>
                            </div>
                            <a href="/Oirsa/${archivo.ruta_archivo.replaceAll('../', '')}" target="_blank" class="btn-ver-archivo" title="Ver archivo">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
        ` : ''}
    `;
    
    // Mostrar modal
    document.getElementById('modalVerContrato').style.display = 'block';
}

// Función auxiliar para generar términos
function generarTerminos(contrato) {
    let html = '';
    for (let i = 1; i <= 10; i++) {
        const termino = contrato[`termino${i}`];
        if (termino) {
            html += `
                <div class="detail-item detail-full">
                    <label>Término ${i}</label>
                    <p>${termino}</p>
                </div>
            `;
        }
    }
    return html;
}

// Función para cerrar modal
function cerrarModal() {
    document.getElementById('modalVerContrato').style.display = 'none';
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalVerContrato');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Función para editar contrato
function editarContrato(id) {
    window.location.href = `editar_contrato.php?id=${id}`;
}

// Función placeholder para bitácora
function verBitacora(id) {
    Swal.fire({
        icon: 'info',
        title: 'Funcionalidad en desarrollo',
        text: 'La bitácora del contrato estará disponible próximamente',
        confirmButtonColor: '#667eea'
    });
}

// Función para descargar PDF del contrato
function descargarPDF(id) {
    // Abrir PDF en nueva pestaña para descarga
    window.open(`../../api/generar_pdf.php?id=${id}`, '_blank');
}
