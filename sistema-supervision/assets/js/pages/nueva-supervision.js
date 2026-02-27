/**
 * NUEVA SUPERVISIÓN - JavaScript con SweetAlert2
 * Sistema de Supervisión v6.0.2
 */

// Detectar la URL base del sistema
const BASE_URL = window.location.origin + '/SistemaSupervision';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Módulo de Nueva Supervisión cargado');
    console.log('📍 Base URL:', BASE_URL);
    
    initSelect2();
    initFormValidation();
    initPreviewUpdates();
    initTelefonoFormatting();
});

/**
 * Inicializar Select2 en todos los selectores
 */
function initSelect2() {
    if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery no está cargado');
        return;
    }
    
    if (typeof jQuery.fn.select2 === 'undefined') {
        console.error('❌ Select2 no está cargado');
        return;
    }
    
    try {
        jQuery('.select2-search').select2({
            placeholder: 'Seleccione una opción...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                },
                searching: function() {
                    return "Buscando...";
                }
            },
            width: '100%'
        });
        
        console.log('✅ Select2 inicializado correctamente');
        
    } catch (error) {
        console.error('❌ Error al inicializar Select2:', error);
    }
}

/**
 * Formatear teléfono automáticamente
 */
function initTelefonoFormatting() {
    const telefonoInput = document.getElementById('telefono');
    
    if (!telefonoInput) return;
    
    telefonoInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Solo números
        
        if (value.length > 8) {
            value = value.substring(0, 8);
        }
        
        if (value.length >= 4) {
            value = value.substring(0, 4) + '-' + value.substring(4);
        }
        
        e.target.value = value;
    });
    
    console.log('✅ Formateo de teléfono inicializado');
}

/**
 * Inicializar actualización de vista previa
 */
function initPreviewUpdates() {
    const proyectoSelect = document.getElementById('proyecto_id');
    const contratistaSelect = document.getElementById('contratista_id');
    const trabajadorSelect = document.getElementById('trabajador_id');
    const telefonoInput = document.getElementById('telefono');
    const previewCard = document.getElementById('preview-card');
    
    const updatePreview = () => {
        const proyectoText = proyectoSelect.options[proyectoSelect.selectedIndex]?.text || '-';
        const contratistaText = contratistaSelect.options[contratistaSelect.selectedIndex]?.text || '-';
        const trabajadorText = trabajadorSelect.options[trabajadorSelect.selectedIndex]?.text || '-';
        const telefonoValue = telefonoInput.value || '-';
        
        document.getElementById('preview-proyecto').textContent = 
            proyectoSelect.value ? proyectoText : '-';
        document.getElementById('preview-contratista').textContent = 
            contratistaSelect.value ? contratistaText : '-';
        document.getElementById('preview-trabajador').textContent = 
            trabajadorSelect.value ? trabajadorText : '-';
        document.getElementById('preview-telefono').textContent = telefonoValue;
        
        // Mostrar preview si hay algún campo lleno
        if (proyectoSelect.value || contratistaSelect.value || trabajadorSelect.value || telefonoInput.value) {
            previewCard.style.display = 'block';
        } else {
            previewCard.style.display = 'none';
        }
    };
    
    jQuery('#proyecto_id').on('change', updatePreview);
    jQuery('#contratista_id').on('change', updatePreview);
    jQuery('#trabajador_id').on('change', updatePreview);
    telefonoInput.addEventListener('input', updatePreview);
    
    console.log('✅ Vista previa inicializada');
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    const form = document.getElementById('formSupervision');
    
    if (!form) {
        console.error('❌ Formulario no encontrado');
        return;
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return false;
        }
        
        guardarSupervision();
    });
    
    console.log('✅ Validación de formulario inicializada');
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const proyectoId = document.getElementById('proyecto_id').value;
    const contratistaId = document.getElementById('contratista_id').value;
    const trabajadorId = document.getElementById('trabajador_id').value;
    const telefono = document.getElementById('telefono').value.trim();
    
    if (!proyectoId) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Por favor seleccione un proyecto',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(59, 130, 246, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        jQuery('#proyecto_id').select2('open');
        return false;
    }
    
    if (!contratistaId) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Por favor seleccione un contratista',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(59, 130, 246, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        jQuery('#contratista_id').select2('open');
        return false;
    }
    
    if (!trabajadorId) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Por favor seleccione un trabajador',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(59, 130, 246, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        jQuery('#trabajador_id').select2('open');
        return false;
    }
    
    if (!telefono) {
        Swal.fire({
            title: '¡Atención!',
            text: 'Por favor ingrese un número de teléfono',
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3b82f6',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(59, 130, 246, 0.1)',
            customClass: {
                popup: 'swal-glassmorphism',
                confirmButton: 'swal-button-glass'
            }
        });
        document.getElementById('telefono').focus();
        return false;
    }
    
    // Validar formato de teléfono (mínimo 8 dígitos)
    const telefonoSinGuion = telefono.replace(/-/g, '');
    if (telefonoSinGuion.length < 8) {
        Swal.fire({
            title: '¡Teléfono inválido!',
            text: 'El teléfono debe tener al menos 8 dígitos',
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(239, 68, 68, 0.1)',
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
 * Guardar supervisión
 */
function guardarSupervision() {
    const form = document.getElementById('formSupervision');
    const formData = new FormData(form);
    
    console.log('💾 Guardando supervisión...');
    console.log('📊 Datos del formulario:');
    for (let pair of formData.entries()) {
        console.log(`  ${pair[0]}: ${pair[1]}`);
    }
    
    const btnGuardar = form.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg><span>Guardando...</span>';
    
    const url = `${BASE_URL}/api/supervisiones.php`;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Respuesta del servidor:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📦 Datos recibidos:', data);
        
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
        
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                html: `
                    <p style="font-size: 16px; color: #475569; margin-bottom: 12px;">
                        Supervisión guardada correctamente
                    </p>
                    <p style="font-size: 14px; color: #3b82f6; font-weight: 600;">
                        📋 ID: #${data.id}
                    </p>
                `,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#10b981',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(16, 185, 129, 0.1)',
                customClass: {
                    popup: 'swal-glassmorphism',
                    confirmButton: 'swal-button-glass'
                }
            }).then(() => {
                window.location.href = `${BASE_URL}/modules/admin/supervisiones.php`;
            });
        } else {
            Swal.fire({
                title: '¡Error!',
                text: data.message || 'No se pudo guardar la supervisión',
                icon: 'error',
                confirmButtonText: 'Entendido',
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
            confirmButtonText: 'Entendido',
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
    
    .swal-glassmorphism {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    
    .swal-button-glass {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
`;
document.head.appendChild(style);