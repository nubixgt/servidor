// web/js/editar_denuncia_admin.js

// Mostrar/ocultar campo de "Otros" en infracciones
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="infracciones[]"]');
    const otrosGrupo = document.getElementById('otros-infraccion-grupo');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const otrosChecked = Array.from(checkboxes).some(cb => 
                cb.value === 'Otros' && cb.checked
            );
            
            if (otrosChecked) {
                otrosGrupo.style.display = 'block';
            } else {
                otrosGrupo.style.display = 'none';
                document.getElementById('infraccion_otro').value = '';
            }
        });
    });
    
    // Verificar al cargar la página
    const otrosChecked = Array.from(checkboxes).some(cb => 
        cb.value === 'Otros' && cb.checked
    );
    if (otrosChecked) {
        otrosGrupo.style.display = 'block';
    }
});

// Validar formulario antes de enviar
document.getElementById('formEditar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar que al menos una infracción esté seleccionada
    const infraccionesChecked = document.querySelectorAll('input[name="infracciones[]"]:checked');
    
    if (infraccionesChecked.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos un tipo de infracción',
            confirmButtonColor: '#1E3A8A'
        });
        return;
    }
    
    // Validar campo "Otros" si está marcado
    const otrosChecked = Array.from(infraccionesChecked).some(cb => cb.value === 'Otros');
    const otroTexto = document.getElementById('infraccion_otro').value.trim();
    
    if (otrosChecked && !otroTexto) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe especificar el tipo de infracción en el campo "Otros"',
            confirmButtonColor: '#1E3A8A'
        });
        document.getElementById('infraccion_otro').focus();
        return;
    }
    
    // Confirmar antes de guardar
    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizará la información de la denuncia',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1E3A8A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Guardando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar formulario
            this.submit();
        }
    });
});

// Función para confirmar cancelación
function confirmarCancelar() {
    Swal.fire({
        title: '¿Cancelar edición?',
        text: 'Los cambios no guardados se perderán',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Continuar editando'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'dashboard.php';
        }
    });
}

// Validación en tiempo real para DPI
document.getElementById('dpi').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, ''); // Solo números
    
    if (value.length > 13) {
        value = value.substring(0, 13);
    }
    
    e.target.value = value;
});

// Validación en tiempo real para celular
document.getElementById('celular').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, ''); // Solo números
    
    if (value.length > 8) {
        value = value.substring(0, 8);
    }
    
    e.target.value = value;
});

// Validación de edad
document.getElementById('edad').addEventListener('input', function(e) {
    const edad = parseInt(e.target.value);
    
    if (edad < 18) {
        e.target.setCustomValidity('La edad debe ser mayor a 18 años');
    } else if (edad > 120) {
        e.target.setCustomValidity('La edad debe ser menor a 120 años');
    } else {
        e.target.setCustomValidity('');
    }
});

// Validación de cantidad
document.getElementById('cantidad').addEventListener('input', function(e) {
    const cantidad = parseInt(e.target.value);
    
    if (cantidad < 1) {
        e.target.setCustomValidity('La cantidad debe ser al menos 1');
    } else {
        e.target.setCustomValidity('');
    }
});

// Mostrar/ocultar campo especie_otro
document.getElementById('especie').addEventListener('change', function() {
    const especieOtro = document.getElementById('especie_otro');
    
    if (this.value === 'Otros') {
        especieOtro.parentElement.style.display = 'block';
        especieOtro.required = true;
    } else {
        especieOtro.parentElement.style.display = 'none';
        especieOtro.required = false;
        especieOtro.value = '';
    }
});

// Verificar al cargar
if (document.getElementById('especie').value === 'Otros') {
    document.getElementById('especie_otro').parentElement.style.display = 'block';
}