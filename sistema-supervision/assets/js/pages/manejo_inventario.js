/**
 * MANEJO DE INVENTARIO - JAVASCRIPT
 * Sistema de Supervisión v6.0.6
 */

// Variables globales
let table;
let selectedFiles = [];
const MAX_FOTOS = 2;
const MIN_FOTOS = 1;

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    // ELIMINAR fila de "sin datos" si existe (para evitar warning de DataTables)
    const emptyRow = $('#tablaManejoInventario tbody tr td[colspan]').parent();
    if (emptyRow.length > 0) {
        emptyRow.remove(); // ✅ REMOVE en lugar de hide
    }
    
    initStatsAnimation();
    initDataTable();
    initSelect2();
    initEventListeners();
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
    // Destruir tabla si ya existe
    if ($.fn.DataTable.isDataTable('#tablaManejoInventario')) {
        $('#tablaManejoInventario').DataTable().destroy();
    }
    
    table = $('#tablaManejoInventario').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        destroy: true,
        columnDefs: [
            { 
                targets: -1, 
                orderable: false, 
                width: '120px',
                className: 'text-center'
            }
        ],
        drawCallback: function() {
            // Callback después de dibujar la tabla
            console.log('Tabla dibujada correctamente');
        }
    });
}

/**
 * Inicializar Select2
 */
function initSelect2() {
    $('.select2').select2({
        dropdownParent: $('#modalMovimiento'),
        width: '100%',
        placeholder: 'Seleccione una opción',
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });
}

/**
 * Inicializar Event Listeners
 */
function initEventListeners() {
    // Input de fotografías
    document.getElementById('fotografias').addEventListener('change', handleFileSelect);
    
    // Submit del formulario
    document.getElementById('formMovimiento').addEventListener('submit', handleSubmit);
}

/**
 * Abrir modal para nuevo movimiento
 */
function abrirModalNuevo() {
    document.getElementById('modalTitle').textContent = 'Nuevo Movimiento de Inventario';
    document.getElementById('formMovimiento').reset();
    document.getElementById('movimiento_id').value = '';
    
    // Limpiar select2
    $('.select2').val(null).trigger('change');
    
    // Limpiar fotos
    selectedFiles = [];
    document.getElementById('previewContainer').innerHTML = '';
    
    // Mostrar modal
    document.getElementById('modalMovimiento').classList.add('show');
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    document.getElementById('modalMovimiento').classList.remove('show');
    document.getElementById('formMovimiento').reset();
    selectedFiles = [];
    document.getElementById('previewContainer').innerHTML = '';
}

/**
 * Manejar selección de archivos
 */
function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    
    // Contar cuántas fotos existentes hay (las que tienen data-existente="true")
    const fotosExistentes = document.querySelectorAll('.preview-item[data-existente="true"]').length;
    
    // Contar cuántas fotos ya están seleccionadas (en selectedFiles)
    const fotosYaSeleccionadas = selectedFiles.length;
    
    // Total de fotos que habría si agregamos las nuevas
    const totalFotos = fotosExistentes + fotosYaSeleccionadas + files.length;
    
    // Validar cantidad máxima (existentes + ya seleccionadas + nuevas)
    if (totalFotos > MAX_FOTOS) {
        const espacioDisponible = MAX_FOTOS - fotosExistentes - fotosYaSeleccionadas;
        Swal.fire({
            icon: 'warning',
            title: 'Máximo de fotos excedido',
            html: `Ya tienes <strong>${fotosExistentes + fotosYaSeleccionadas} foto(s)</strong>.<br>Solo puedes agregar <strong>${espacioDisponible} más</strong> (máximo ${MAX_FOTOS} en total).`,
            confirmButtonColor: '#f97316'
        });
        e.target.value = '';
        return;
    }
    
    // Validar tipos de archivo
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    const invalidFiles = files.filter(file => !validTypes.includes(file.type));
    
    if (invalidFiles.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tipo de archivo no válido',
            html: 'Solo se permiten archivos: <strong>JPG, PNG, WEBP</strong>',
            confirmButtonColor: '#f97316'
        });
        e.target.value = '';
        return;
    }
    
    // Validar tamaño (5MB por archivo)
    const maxSize = 5 * 1024 * 1024; // 5MB
    const oversizedFiles = files.filter(file => file.size > maxSize);
    
    if (oversizedFiles.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Archivo muy grande',
            html: 'Cada foto debe ser menor a <strong>5MB</strong>',
            confirmButtonColor: '#f97316'
        });
        e.target.value = '';
        return;
    }
    
    // Guardar archivos seleccionados - AGREGAR a los existentes en lugar de reemplazar
    selectedFiles = [...selectedFiles, ...files];
    
    // Mostrar preview (sin limpiar las existentes)
    mostrarPreview(files);
}

/**
 * Mostrar preview de imágenes
 */
function mostrarPreview(files) {
    const container = document.getElementById('previewContainer');
    
    // Calcular el índice inicial basado en cuántas fotos ya hay en selectedFiles
    const startIndex = selectedFiles.length - files.length;
    
    // Agregar nuevos previews SIN eliminar los anteriores
    files.forEach((file, index) => {
        const reader = new FileReader();
        const globalIndex = startIndex + index; // Índice global en selectedFiles
        
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${globalIndex + 1}">
                <button type="button" class="remove-image" onclick="removeImage(${globalIndex})" title="Eliminar">
                    ×
                </button>
            `;
            container.appendChild(div);
        };
        
        reader.readAsDataURL(file);
    });
}

/**
 * Eliminar imagen del preview
 */
function removeImage(index) {
    // Eliminar archivo del array
    selectedFiles.splice(index, 1);
    
    // Actualizar input file
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('fotografias').files = dt.files;
    
    // Limpiar TODOS los previews nuevos (no los existentes)
    const container = document.getElementById('previewContainer');
    const previewsNuevos = container.querySelectorAll('.preview-item:not([data-existente="true"])');
    previewsNuevos.forEach(preview => preview.remove());
    
    // Reconstruir todos los previews con índices correctos
    selectedFiles.forEach((file, idx) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${idx + 1}">
                <button type="button" class="remove-image" onclick="removeImage(${idx})" title="Eliminar">
                    ×
                </button>
            `;
            container.appendChild(div);
        };
        
        reader.readAsDataURL(file);
    });
}

/**
 * Manejar submit del formulario
 */
async function handleSubmit(e) {
    e.preventDefault();
    
    // Validar campos obligatorios
    const producto = document.getElementById('producto').value.trim();
    const tipoGestion = document.getElementById('tipo_gestion').value.trim();
    const proyectoId = document.getElementById('proyecto_id').value;
    const trabajadorId = document.getElementById('trabajador_id').value;
    const fechaEntrega = document.getElementById('fecha_entrega').value;
    
    if (!producto) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debes seleccionar un producto',
            confirmButtonColor: '#f97316'
        });
        document.getElementById('producto').focus();
        return;
    }
    
    if (!tipoGestion) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debes seleccionar el tipo de gestión',
            confirmButtonColor: '#f97316'
        });
        document.getElementById('tipo_gestion').focus();
        return;
    }
    
    if (!proyectoId) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debes seleccionar un proyecto',
            confirmButtonColor: '#f97316'
        });
        return;
    }
    
    if (!trabajadorId) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debes seleccionar un responsable',
            confirmButtonColor: '#f97316'
        });
        return;
    }
    
    if (!fechaEntrega) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Debes seleccionar la fecha de entrega',
            confirmButtonColor: '#f97316'
        });
        document.getElementById('fecha_entrega').focus();
        return;
    }
    
    // ⭐ VALIDACIÓN CRÍTICA: Fotografías
    const movimientoId = document.getElementById('movimiento_id').value;
    
    if (!movimientoId) { // Solo validar en nuevo (no en editar)
        const totalFotos = selectedFiles.length;
        
        if (totalFotos < MIN_FOTOS) {
            Swal.fire({
                icon: 'error',
                title: 'Fotografías requeridas',
                html: `Debes subir <strong>al menos ${MIN_FOTOS} fotografía</strong>.<br>Actualmente: ${totalFotos}`,
                confirmButtonColor: '#f97316'
            });
            document.getElementById('fotografias').focus();
            return;
        }
        
        if (totalFotos > MAX_FOTOS) {
            Swal.fire({
                icon: 'error',
                title: 'Demasiadas fotografías',
                html: `Máximo <strong>${MAX_FOTOS} fotografías</strong> permitidas.<br>Actualmente: ${totalFotos}`,
                confirmButtonColor: '#f97316'
            });
            document.getElementById('fotografias').focus();
            return;
        }
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando...',
        html: 'Por favor espera mientras se procesa el movimiento',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar FormData
    const formData = new FormData();
    formData.append('producto', producto);
    formData.append('tipo_gestion', tipoGestion);
    formData.append('proyecto_id', proyectoId);
    formData.append('trabajador_id', trabajadorId);
    formData.append('fecha_entrega', fechaEntrega);
    
    const observaciones = document.getElementById('observaciones').value.trim();
    if (observaciones) {
        formData.append('observaciones', observaciones);
    }
    
    // Agregar fotografías
    selectedFiles.forEach((file, index) => {
        formData.append('fotografias[]', file);
    });
    
    try {
        let url, method;
        
        if (movimientoId) {
            // Editar - Usar POST con _method=PUT para soportar archivos
            url = '../../api/manejo_inventario.php';
            method = 'POST';
            formData.append('id', movimientoId);
            formData.append('_method', 'PUT');
        } else {
            // Crear
            url = '../../api/manejo_inventario.php';
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: method,
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                html: movimientoId 
                    ? 'Movimiento actualizado correctamente' 
                    : `Movimiento registrado con ID: <strong>#${String(data.id).padStart(4, '0')}</strong>`,
                confirmButtonColor: '#f97316'
            }).then(() => {
                cerrarModal();
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al guardar');
        }
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error al guardar el movimiento',
            confirmButtonColor: '#f97316'
        });
    }
}

/**
 * Ver detalles del movimiento
 */
async function verMovimiento(id) {
    try {
        const response = await fetch(`../../api/manejo_inventario.php?id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        const mov = data.data;
        
        // Formatear fecha
        const fechaEntrega = new Date(mov.fecha_entrega);
        const opcionesFecha = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            timeZone: 'America/Guatemala'
        };
        const fechaFormateada = fechaEntrega.toLocaleDateString('es-GT', opcionesFecha);
        
        // Crear HTML de galería de fotos
        let galeriaHTML = '';
        if (mov.fotografias && mov.fotografias.length > 0) {
            galeriaHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 12px;">';
            mov.fotografias.forEach((foto, index) => {
                galeriaHTML += `
                    <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <img src="../../${foto.ruta_archivo}" 
                             alt="Foto ${index + 1}" 
                             style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                             onclick="window.open('../../${foto.ruta_archivo}', '_blank')">
                    </div>
                `;
            });
            galeriaHTML += '</div>';
        } else {
            galeriaHTML = '<p style="color: #9ca3af; font-style: italic;">Sin fotografías</p>';
        }
        
        // Badge de tipo
        const tipoBadge = mov.tipo_gestion === 'Salida de Bodega' 
            ? '<span style="background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%); color: #991b1b; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px;">Salida de Bodega</span>'
            : '<span style="background: linear-gradient(135deg, #d1fae5 0%, #6ee7b7 100%); color: #065f46; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px;">Ingreso de Bodega</span>';
        
        Swal.fire({
            title: `<span style="color: #ea580c;">Movimiento #${String(mov.id).padStart(4, '0')}</span>`,
            html: `
                <div style="text-align: left; padding: 10px;">
                    <!-- Producto -->
                    <div style="background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); padding: 20px; border-radius: 12px; margin-bottom: 16px;">
                        <div style="font-size: 13px; font-weight: 700; color: #9a3412; text-transform: uppercase; margin-bottom: 8px;">📦 Producto</div>
                        <div style="font-size: 18px; font-weight: 700; color: #7c2d12;">${mov.producto}</div>
                    </div>
                    
                    <!-- Tipo de Gestión -->
                    <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; margin-bottom: 16px;">
                        <div style="font-size: 13px; font-weight: 700; color: #374151; text-transform: uppercase; margin-bottom: 8px;">🔄 Tipo de Gestión</div>
                        <div style="font-size: 16px; font-weight: 600; color: #1f2937;">${tipoBadge}</div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <!-- Proyecto -->
                        <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 20px; border-radius: 12px;">
                            <div style="font-size: 13px; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 8px;">🏗️ Proyecto</div>
                            <div style="font-size: 16px; font-weight: 600; color: #1e3a8a;">${mov.proyecto_nombre || 'Sin asignar'}</div>
                        </div>
                        
                        <!-- Responsable -->
                        <div style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); padding: 20px; border-radius: 12px;">
                            <div style="font-size: 13px; font-weight: 700; color: #4338ca; text-transform: uppercase; margin-bottom: 8px;">👤 Responsable</div>
                            <div style="font-size: 16px; font-weight: 600; color: #3730a3;">${mov.trabajador_nombre || 'Sin asignar'}</div>
                        </div>
                    </div>
                    
                    <!-- Fecha de Entrega -->
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin-bottom: 16px;">
                        <div style="font-size: 13px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 8px;">📅 Fecha de Entrega</div>
                        <div style="font-size: 16px; font-weight: 600; color: #78350f;">${fechaFormateada}</div>
                    </div>
                    
                    <!-- Observaciones -->
                    ${mov.observaciones ? `
                    <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 20px; border-radius: 12px; margin-bottom: 16px;">
                        <div style="font-size: 13px; font-weight: 700; color: #374151; text-transform: uppercase; margin-bottom: 8px;">📝 Observaciones</div>
                        <div style="font-size: 15px; color: #1f2937; line-height: 1.6;">${mov.observaciones}</div>
                    </div>
                    ` : ''}
                    
                    <!-- Fotografías -->
                    <div style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); padding: 20px; border-radius: 12px;">
                        <div style="font-size: 13px; font-weight: 700; color: #4338ca; text-transform: uppercase; margin-bottom: 8px;">📸 Fotografías (${mov.fotografias ? mov.fotografias.length : 0})</div>
                        ${galeriaHTML}
                    </div>
                </div>
            `,
            width: '700px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#f97316',
            customClass: {
                popup: 'swal-wide'
            }
        });
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo cargar la información',
            confirmButtonColor: '#f97316'
        });
    }
}

/**
 * Editar movimiento
 */
async function editarMovimiento(id) {
    try {
        const response = await fetch(`../../api/manejo_inventario.php?id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        const mov = data.data;
        
        // Llenar formulario
        document.getElementById('modalTitle').textContent = 'Editar Movimiento';
        document.getElementById('movimiento_id').value = mov.id;
        document.getElementById('producto').value = mov.producto;
        document.getElementById('tipo_gestion').value = mov.tipo_gestion;
        document.getElementById('fecha_entrega').value = mov.fecha_entrega;
        document.getElementById('observaciones').value = mov.observaciones || '';
        
        // Seleccionar en Select2
        $('#proyecto_id').val(mov.proyecto_id).trigger('change');
        $('#trabajador_id').val(mov.trabajador_id).trigger('change');
        
        // Limpiar fotos nuevas
        selectedFiles = [];
        document.getElementById('fotografias').value = '';
        
        // Mostrar fotos existentes
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = '';
        
        if (mov.fotografias && mov.fotografias.length > 0) {
            mov.fotografias.forEach((foto, index) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.setAttribute('data-existente', 'true'); // ⭐ CRÍTICO: Marcar como existente
                div.innerHTML = `
                    <img src="../../${foto.ruta_archivo}" alt="Foto ${index + 1}">
                    <button type="button" class="remove-image" onclick="eliminarFotoExistente(${mov.id}, ${foto.id})" title="Eliminar foto">
                        ×
                    </button>
                    <span class="foto-existente-badge">Foto existente</span>
                `;
                previewContainer.appendChild(div);
            });
        }
        
        // Mostrar modal
        document.getElementById('modalMovimiento').classList.add('show');
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo cargar el movimiento',
            confirmButtonColor: '#f97316'
        });
    }
}

/**
 * Eliminar foto existente (de un movimiento ya guardado)
 */
async function eliminarFotoExistente(manejoId, fotoId) {
    Swal.fire({
        title: '¿Eliminar esta foto?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch('../../api/manejo_inventario.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_foto&foto_id=${fotoId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminada!',
                        text: 'La foto ha sido eliminada',
                        confirmButtonColor: '#f97316',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Recargar el formulario de edición para actualizar las fotos
                    editarMovimiento(manejoId);
                } else {
                    throw new Error(data.message);
                }
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo eliminar la foto',
                    confirmButtonColor: '#f97316'
                });
            }
        }
    });
}

/**
 * Eliminar movimiento
 */
function eliminarMovimiento(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        html: 'Esta acción eliminará el movimiento y <strong>todas sus fotografías</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch('../../api/manejo_inventario.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El movimiento ha sido eliminado correctamente',
                        confirmButtonColor: '#f97316'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo eliminar el movimiento',
                    confirmButtonColor: '#f97316'
                });
            }
        }
    });
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalMovimiento');
    if (event.target === modal) {
        cerrarModal();
    }
};