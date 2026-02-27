/**
 * assets/js/pages/inventario-tecnico.js
 * Inventario para Técnicos - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.4
 * Los técnicos solo ven SUS propios equipos
 */

const BASE_URL = window.location.origin + '/SistemaSupervision';
let dataTable;
let archivosSeleccionados = [];
let modoEdicion = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Inventario (Técnico) cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initStatsAnimation();
    initDataTable();
    initSelect2();
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
        dataTable = jQuery('#inventarioTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columnDefs: [
                { 
                    targets: -1,
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
 * Inicializar Select2
 */
function initSelect2() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        console.error('❌ Select2 no está cargado');
        return;
    }
    
    jQuery('.select2-search').select2({
        placeholder: "Buscar...",
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        },
        width: '100%',
        dropdownParent: jQuery('#modalInventario')
    });
    
    console.log('✅ Select2 inicializado correctamente');
}

/**
 * Abrir modal para nuevo equipo
 */
function abrirModalNuevo() {
    modoEdicion = false;
    document.getElementById('modalTitle').textContent = 'Nuevo Equipo';
    document.getElementById('formInventario').reset();
    document.getElementById('inventario_id').value = '';
    
    // Limpiar previsualización
    archivosSeleccionados = [];
    document.getElementById('preview-container').innerHTML = '';
    
    // Limpiar ubicación
    document.getElementById('ubicacion_texto').value = '';
    document.getElementById('ubicacion_latitud').value = '';
    document.getElementById('ubicacion_longitud').value = '';
    document.getElementById('location-status').style.display = 'none';
    document.getElementById('location-map').style.display = 'none';
    
    // Reinicializar Select2
    jQuery('.select2-search').val(null).trigger('change');
    
    document.getElementById('modalInventario').classList.add('show');
    console.log('📝 Modal abierto para nuevo equipo');
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    document.getElementById('modalInventario').classList.remove('show');
    archivosSeleccionados = [];
    console.log('❌ Modal cerrado');
}

/**
 * Cerrar modal al hacer clic fuera
 */
window.onclick = function(event) {
    const modal = document.getElementById('modalInventario');
    if (event.target === modal) {
        cerrarModal();
    }
}

/**
 * Capturar ubicación con GPS
 */
function capturarUbicacion() {
    const statusDiv = document.getElementById('location-status');
    const mapDiv = document.getElementById('location-map');
    const coordsDisplay = document.getElementById('coords-display');
    const ubicacionInput = document.getElementById('ubicacion_texto');
    
    if (!navigator.geolocation) {
        statusDiv.className = 'location-status error';
        statusDiv.innerHTML = '❌ Tu navegador no soporta geolocalización.<br>Por favor, escribe la ubicación manualmente.';
        statusDiv.style.display = 'block';
        ubicacionInput.focus();
        return;
    }
    
    statusDiv.className = 'location-status loading';
    statusDiv.textContent = '📡 Obteniendo ubicación GPS...';
    statusDiv.style.display = 'block';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            document.getElementById('ubicacion_latitud').value = lat;
            document.getElementById('ubicacion_longitud').value = lng;
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    const direccion = data.display_name || `${lat}, ${lng}`;
                    ubicacionInput.value = direccion;
                    
                    statusDiv.className = 'location-status success';
                    statusDiv.textContent = '✅ Ubicación GPS capturada exitosamente';
                    
                    coordsDisplay.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    mapDiv.style.display = 'block';
                })
                .catch(error => {
                    console.log('Error en geocoding:', error);
                    ubicacionInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    
                    statusDiv.className = 'location-status success';
                    statusDiv.textContent = '✅ Ubicación GPS capturada (solo coordenadas)';
                    
                    coordsDisplay.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    mapDiv.style.display = 'block';
                });
        },
        function(error) {
            let mensaje = '';
            let sugerencia = '';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    mensaje = '❌ Permiso de ubicación denegado';
                    sugerencia = '<br><strong>Solución:</strong> Tu navegador bloqueó el acceso a la ubicación.<br>' +
                                '👉 <strong>Puedes escribir la ubicación manualmente en el campo de arriba.</strong>';
                    break;
                case error.POSITION_UNAVAILABLE:
                    mensaje = '❌ Ubicación no disponible';
                    sugerencia = '<br>Por favor, escribe la ubicación manualmente.';
                    break;
                case error.TIMEOUT:
                    mensaje = '❌ Tiempo de espera agotado';
                    sugerencia = '<br>Intenta de nuevo o escribe la ubicación manualmente.';
                    break;
                default:
                    mensaje = '❌ Error desconocido al obtener ubicación';
                    sugerencia = '<br>Por favor, escribe la ubicación manualmente.';
            }
            
            statusDiv.className = 'location-status error';
            statusDiv.innerHTML = mensaje + sugerencia;
            statusDiv.style.display = 'block';
            
            ubicacionInput.focus();
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

/**
 * Previsualizar imágenes
 */
function previsualizarImagenes(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('preview-container');
    
    // Validar número de archivos (máximo 3)
    if (archivosSeleccionados.length + files.length > 3) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Máximo 3 archivos permitidos',
            icon: 'warning',
            confirmButtonColor: '#dc2626'
        });
        event.target.value = '';
        return;
    }
    
    // Validar tamaño de cada archivo (máximo 5MB)
    const maxSize = 5 * 1024 * 1024;
    for (let file of files) {
        if (file.size > maxSize) {
            Swal.fire({
                title: '¡Atención!',
                text: `El archivo ${file.name} excede los 5MB`,
                icon: 'warning',
                confirmButtonColor: '#dc2626'
            });
            event.target.value = '';
            return;
        }
    }
    
    // Agregar archivos a la lista
    Array.from(files).forEach((file) => {
        archivosSeleccionados.push(file);
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.dataset.index = archivosSeleccionados.length - 1;
            
            if (file.type === 'application/pdf') {
                previewItem.innerHTML = `
                    <div class="pdf-preview">📄</div>
                    <button type="button" class="remove-image" onclick="removerImagen(${archivosSeleccionados.length - 1})">
                        ×
                    </button>
                `;
            } else {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-image" onclick="removerImagen(${archivosSeleccionados.length - 1})">
                        ×
                    </button>
                `;
            }
            
            previewContainer.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Remover imagen de la previsualización
 */
function removerImagen(index) {
    archivosSeleccionados.splice(index, 1);
    
    const previewContainer = document.getElementById('preview-container');
    previewContainer.innerHTML = '';
    
    archivosSeleccionados.forEach((file, newIndex) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.dataset.index = newIndex;
            
            if (file.type === 'application/pdf') {
                previewItem.innerHTML = `
                    <div class="pdf-preview">📄</div>
                    <button type="button" class="remove-image" onclick="removerImagen(${newIndex})">
                        ×
                    </button>
                `;
            } else {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-image" onclick="removerImagen(${newIndex})">
                        ×
                    </button>
                `;
            }
            
            previewContainer.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    });
    
    document.getElementById('fotografias').value = '';
}

/**
 * Eliminar foto existente
 */
function eliminarFotoExistente(fotoId, button) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta fotografía será eliminada permanentemente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/api/inventario.php?eliminar_foto=${fotoId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const previewItem = button.closest('.preview-item');
                    previewItem.remove();
                    
                    Swal.fire({
                        title: '¡Eliminada!',
                        text: 'Fotografía eliminada correctamente',
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        timer: 2000
                    });
                } else {
                    Swal.fire({
                        title: '¡Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#dc2626'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: '¡Error!',
                    text: 'Error al eliminar la fotografía',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
            });
        }
    });
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formInventario');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarEquipo();
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const tipoEquipo = document.getElementById('tipo_equipo').value.trim();
    
    if (!tipoEquipo) {
        Swal.fire({
            title: '¡Atención!',
            text: 'El tipo de equipo es obligatorio',
            icon: 'warning',
            confirmButtonColor: '#dc2626'
        });
        document.getElementById('tipo_equipo').focus();
        return false;
    }
    
    // Validar que haya al menos 1 foto (solo en modo creación)
    if (!modoEdicion && archivosSeleccionados.length < 1) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Debes subir al menos 1 fotografía',
            icon: 'warning',
            confirmButtonColor: '#dc2626'
        });
        return false;
    }
    
    return true;
}

/**
 * Guardar equipo
 */
function guardarEquipo() {
    const form = document.getElementById('formInventario');
    const formData = new FormData();
    
    const inventarioId = document.getElementById('inventario_id').value;
    formData.append('id', inventarioId);
    formData.append('tipo_equipo', document.getElementById('tipo_equipo').value);
    formData.append('ubicacion_texto', document.getElementById('ubicacion_texto').value);
    formData.append('ubicacion_latitud', document.getElementById('ubicacion_latitud').value);
    formData.append('ubicacion_longitud', document.getElementById('ubicacion_longitud').value);
    formData.append('proyecto_id', document.getElementById('proyecto_id').value || '');
    formData.append('contratista_id', document.getElementById('contratista_id').value || '');
    formData.append('observaciones', document.getElementById('observaciones').value);
    formData.append('estado', document.getElementById('estado').value);
    
    archivosSeleccionados.forEach((file) => {
        formData.append('fotografias[]', file);
    });
    
    if (modoEdicion) {
        formData.append('_method', 'PUT');
    }
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/inventario.php`;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
        
        if (data.success) {
            cerrarModal();
            Swal.fire({
                title: '¡Éxito!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '¡Error!',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
        
        Swal.fire({
            title: '¡Error!',
            text: 'Error al comunicarse con el servidor',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    });
}

/**
 * Ver detalles del equipo con SweetAlert2
 */
function verEquipo(id) {
    console.log('👁️ Ver equipo ID:', id);
    
    Swal.fire({
        title: 'Cargando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`${BASE_URL}/api/inventario.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const equipo = data.data;
                const fotos = data.fotografias || [];
                
                let fotosHTML = '';
                if (fotos.length > 0) {
                    fotosHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-top: 16px;">';
                    fotos.forEach(foto => {
                        if (foto.tipo_archivo === 'application/pdf') {
                            fotosHTML += `
                                <a href="${BASE_URL}/${foto.ruta_archivo}" target="_blank" style="display: flex; align-items: center; justify-content: center; height: 150px; background: #f3f4f6; border-radius: 10px; font-size: 3rem; text-decoration: none; border: 2px solid #e5e7eb;">
                                    📄
                                </a>
                            `;
                        } else {
                            fotosHTML += `
                                <img src="${BASE_URL}/${foto.ruta_archivo}" alt="Foto" style="width: 100%; height: 150px; object-fit: cover; border-radius: 10px; border: 2px solid #e5e7eb;">
                            `;
                        }
                    });
                    fotosHTML += '</div>';
                } else {
                    fotosHTML = '<p style="color: #9ca3af; font-style: italic;">Sin fotografías</p>';
                }
                
                const estadoTexto = {
                    'activo': 'Activo',
                    'en_mantenimiento': 'En Mantenimiento',
                    'fuera_servicio': 'Fuera de Servicio',
                    'dado_baja': 'Dado de Baja'
                };
                
                const estadoColor = {
                    'activo': '#10b981',
                    'en_mantenimiento': '#f59e0b',
                    'fuera_servicio': '#dc2626',
                    'dado_baja': '#6b7280'
                };
                
                Swal.fire({
                    title: 'Detalles del Equipo',
                    html: `
                        <div style="text-align: left; padding: 20px;">
                            <div style="display: grid; gap: 16px;">
                                <div style="padding: 12px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 10px; border-left: 4px solid #dc2626;">
                                    <div style="font-size: 11px; color: #991b1b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Tipo de Equipo</div>
                                    <div style="font-size: 16px; color: #7f1d1d; font-weight: 600;">${equipo.tipo_equipo}</div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 10px; border-left: 4px solid #6366f1;">
                                    <div style="font-size: 11px; color: #3730a3; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Ubicación</div>
                                    <div style="font-size: 14px; color: #312e81; font-weight: 500;">${equipo.ubicacion_texto || 'Sin ubicación'}
                                        ${equipo.ubicacion_latitud && equipo.ubicacion_longitud ? 
                                            `<br><a href="https://www.google.com/maps?q=${equipo.ubicacion_latitud},${equipo.ubicacion_longitud}" target="_blank" style="color: #4f46e5; text-decoration: underline;">📍 Ver en Google Maps</a>` 
                                            : ''}
                                    </div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 10px; border-left: 4px solid #f59e0b;">
                                    <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Proyecto Asignado</div>
                                    <div style="font-size: 14px; color: #78350f; font-weight: 500;">${equipo.proyecto_nombre || 'Sin asignar'}</div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; border-left: 4px solid #3b82f6;">
                                    <div style="font-size: 11px; color: #1e40af; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Contratista</div>
                                    <div style="font-size: 14px; color: #1e3a8a; font-weight: 500;">${equipo.contratista_nombre || 'Sin asignar'}</div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); border-radius: 10px; border-left: 4px solid ${estadoColor[equipo.estado]};">
                                    <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Estado</div>
                                    <div style="font-size: 14px; color: #78350f; font-weight: 600;">${estadoTexto[equipo.estado]}</div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 10px; border-left: 4px solid #6b7280;">
                                    <div style="font-size: 11px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Observaciones</div>
                                    <div style="font-size: 14px; color: #374151; line-height: 1.5;">${equipo.observaciones || 'Sin observaciones'}</div>
                                </div>
                                
                                <div style="padding: 12px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 10px; border-left: 4px solid #10b981;">
                                    <div style="font-size: 11px; color: #065f46; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Fotografías</div>
                                    ${fotosHTML}
                                </div>
                            </div>
                        </div>
                    `,
                    width: '700px',
                    showCancelButton: true,
                    confirmButtonText: 'Editar',
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    customClass: {
                        popup: 'swal-glassmorphism'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        editarEquipo(id);
                    }
                });
            } else {
                Swal.fire({
                    title: '¡Error!',
                    text: 'Error al cargar los detalles',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: '¡Error!',
                text: 'Error al cargar los detalles',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        });
}

/**
 * Editar equipo
 */
function editarEquipo(id) {
    modoEdicion = true;
    document.getElementById('modalTitle').textContent = 'Editar Equipo';
    
    fetch(`${BASE_URL}/api/inventario.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const equipo = data.data;
                const fotos = data.fotografias || [];
                
                document.getElementById('inventario_id').value = equipo.id;
                document.getElementById('tipo_equipo').value = equipo.tipo_equipo;
                document.getElementById('ubicacion_texto').value = equipo.ubicacion_texto || '';
                document.getElementById('ubicacion_latitud').value = equipo.ubicacion_latitud || '';
                document.getElementById('ubicacion_longitud').value = equipo.ubicacion_longitud || '';
                document.getElementById('observaciones').value = equipo.observaciones || '';
                document.getElementById('estado').value = equipo.estado;
                
                jQuery('#proyecto_id').val(equipo.proyecto_id || '').trigger('change');
                jQuery('#contratista_id').val(equipo.contratista_id || '').trigger('change');
                
                if (equipo.ubicacion_latitud && equipo.ubicacion_longitud) {
                    document.getElementById('location-status').className = 'location-status success';
                    document.getElementById('location-status').textContent = '✅ Ubicación guardada';
                    document.getElementById('location-status').style.display = 'block';
                    
                    document.getElementById('coords-display').textContent = 
                        `${equipo.ubicacion_latitud}, ${equipo.ubicacion_longitud}`;
                    document.getElementById('location-map').style.display = 'block';
                }
                
                const previewContainer = document.getElementById('preview-container');
                previewContainer.innerHTML = '';
                archivosSeleccionados = [];
                
                if (fotos.length > 0) {
                    fotos.forEach(foto => {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.dataset.fotoId = foto.id;
                        previewItem.dataset.existente = 'true';
                        
                        if (foto.tipo_archivo === 'application/pdf') {
                            previewItem.innerHTML = `
                                <a href="${BASE_URL}/${foto.ruta_archivo}" target="_blank" class="pdf-preview-link">
                                    <div class="pdf-preview">📄</div>
                                </a>
                                <button type="button" class="remove-image" onclick="eliminarFotoExistente(${foto.id}, this)">
                                    ×
                                </button>
                            `;
                        } else {
                            previewItem.innerHTML = `
                                <img src="${BASE_URL}/${foto.ruta_archivo}" alt="Foto del equipo">
                                <button type="button" class="remove-image" onclick="eliminarFotoExistente(${foto.id}, this)">
                                    ×
                                </button>
                            `;
                        }
                        
                        previewContainer.appendChild(previewItem);
                    });
                }
                
                document.getElementById('modalInventario').classList.add('show');
            } else {
                Swal.fire({
                    title: '¡Error!',
                    text: 'Error al cargar el equipo',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: '¡Error!',
                text: 'Error al cargar el equipo',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        });
}

/**
 * Eliminar equipo con SweetAlert2
 */
function eliminarEquipo(id) {
    console.log('🗑️ Eliminar equipo ID:', id);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const tipo = row ? row.getAttribute('data-tipo') : 'este equipo';
    
    Swal.fire({
        title: '¿Estás seguro?',
        html: `
            <p style="font-size: 16px; color: #475569; margin: 16px 0;">
                Vas a eliminar el equipo:<br>
                <strong style="color: #991b1b; font-size: 18px;">${tipo}</strong>
            </p>
            <p style="font-size: 14px; color: #ef4444; font-weight: 600; margin-top: 16px;">
                ⚠️ Esta acción también eliminará todas las fotografías asociadas
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
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
    
    const url = `${BASE_URL}/api/inventario.php?id=${id}`;
    
    fetch(url, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Eliminado!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '¡Error!',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: '¡Error!',
            text: 'Error al comunicarse con el servidor',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    });
}

document.head.appendChild(style);