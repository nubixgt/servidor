// web/js/servicios_admin.js

// Formatear teléfono automáticamente: 4528-9012
document.addEventListener('DOMContentLoaded', function() {
    const telefonoInputs = document.querySelectorAll('#telefono');
    
    telefonoInputs.forEach(function(telefonoInput) {
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function(e) {
                // Obtener solo números
                let value = e.target.value.replace(/\D/g, '');
                
                // Limitar a 8 dígitos
                if (value.length > 8) {
                    value = value.substring(0, 8);
                }
                
                // Formatear con guion después de 4 dígitos
                if (value.length > 4) {
                    value = value.substring(0, 4) + '-' + value.substring(4);
                }
                
                e.target.value = value;
            });
            
            // Prevenir pegado de texto no válido
            telefonoInput.addEventListener('paste', function(e) {
                e.preventDefault();
                
                // Obtener texto pegado
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                
                // Obtener solo números
                let numbers = pastedText.replace(/\D/g, '');
                
                // Limitar a 8 dígitos
                if (numbers.length > 8) {
                    numbers = numbers.substring(0, 8);
                }
                
                // Formatear con guion
                if (numbers.length > 4) {
                    numbers = numbers.substring(0, 4) + '-' + numbers.substring(4);
                }
                
                e.target.value = numbers;
            });
        }
    });
});