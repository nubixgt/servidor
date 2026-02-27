// Convertir número a palabras en español
function numeroALetras(numero) {
    const unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    const decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    const especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    const centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    if (numero === 0) return 'cero';
    if (numero === 100) return 'cien';

    let letras = '';

    // Millones
    if (numero >= 1000000) {
        const millones = Math.floor(numero / 1000000);
        letras += (millones === 1 ? 'un millón' : numeroALetras(millones) + ' millones');
        numero %= 1000000;
        if (numero > 0) letras += ' ';
    }

    // Miles
    if (numero >= 1000) {
        const miles = Math.floor(numero / 1000);
        if (miles === 1) {
            letras += 'mil';
        } else {
            letras += numeroALetras(miles) + ' mil';
        }
        numero %= 1000;
        if (numero > 0) letras += ' ';
    }

    // Centenas
    if (numero >= 100) {
        letras += centenas[Math.floor(numero / 100)];
        numero %= 100;
        if (numero > 0) letras += ' ';
    }

    // Decenas y unidades
    if (numero >= 20) {
        letras += decenas[Math.floor(numero / 10)];
        numero %= 10;
        if (numero > 0) letras += ' y ' + unidades[numero];
    } else if (numero >= 10) {
        letras += especiales[numero - 10];
    } else if (numero > 0) {
        letras += unidades[numero];
    }

    return letras.trim();
}

// Formatear monto con Q y comas
function formatearMonto(input) {
    // Si el valor ya está formateado (tiene Q), quitarlo
    let valor = input.value.replace(/Q/g, '').replace(/,/g, '').trim();
    
    // Si está vacío, limpiar todo
    if (valor === '') {
        input.value = '';
        document.getElementById('montoDisplay').textContent = '';
        return;
    }
    
    // Convertir a número decimal
    let numero = parseFloat(valor);
    
    // Si no es un número válido, salir
    if (isNaN(numero)) {
        return;
    }
    
    // Formatear con comas y decimales
    let formateado = 'Q' + numero.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    input.value = formateado;
    
    // Convertir a letras (solo la parte entera)
    let letras = numeroALetras(Math.floor(numero));
    document.getElementById('montoDisplay').textContent = `(${letras.charAt(0).toUpperCase() + letras.slice(1)} quetzales exactos)`;
}

// Formatear monto por pago
function formatearMontoPago(input) {
    // Si el valor ya está formateado (tiene Q), quitarlo
    let valor = input.value.replace(/Q/g, '').replace(/,/g, '').trim();
    
    // Si está vacío, limpiar
    if (valor === '') {
        input.value = '';
        document.getElementById('montoPagoDisplay').textContent = '';
        return;
    }
    
    // Convertir a número decimal
    let numero = parseFloat(valor);
    
    // Si no es un número válido, salir
    if (isNaN(numero)) {
        return;
    }
    
    // Formatear con comas y decimales
    let formateado = 'Q' + numero.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    input.value = formateado;
    
    // Convertir a letras (solo la parte entera)
    let letras = numeroALetras(Math.floor(numero));
    document.getElementById('montoPagoDisplay').textContent = `(${letras.charAt(0).toUpperCase() + letras.slice(1)} quetzales exactos)`;
}

// Mostrar número de pagos
function mostrarNumeroPagos(input) {
    let valor = input.value;
    
    if (valor === '' || valor === '0') {
        document.getElementById('pagosDisplay').textContent = '';
        return;
    }

    document.getElementById('pagosDisplay').textContent = `${valor} Pagos`;
}

// Generar número de contrato automáticamente
function generarNumeroContrato() {
    // Obtener el año actual
    const año = new Date().getFullYear();
    
    // Hacer petición AJAX para obtener el último número
    fetch('../../api/obtener_ultimo_contrato.php')
        .then(response => response.json())
        .then(data => {
            let numero = 1;
            if (data.ultimo_numero) {
                // Extraer el número del formato "001-2026-O-M"
                const partes = data.ultimo_numero.split('-');
                if (partes.length > 0 && partes[1] == año) {
                    numero = parseInt(partes[0]) + 1;
                }
            }
            
            // Formatear con ceros a la izquierda
            const numeroFormateado = String(numero).padStart(3, '0');
            document.getElementById('numeroContrato').value = `${numeroFormateado}-${año}-O-M`;
        })
        .catch(error => {
            console.error('Error al generar número de contrato:', error);
            // Si hay error, usar valor por defecto
            const año = new Date().getFullYear();
            document.getElementById('numeroContrato').value = `001-${año}-O-M`;
        });
}

// Mostrar/ocultar campo "Otro" en Armonización
function toggleArmonizacionOtro() {
    const select = document.getElementById('armonizacion');
    const container = document.getElementById('armonizacionOtroContainer');
    const input = document.getElementById('armonizacionOtro');
    
    if (select.value === 'Otro') {
        container.style.display = 'block';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

// Cargar número de contrato al cargar la página SOLO en formulario de creación
document.addEventListener('DOMContentLoaded', function() {
    // Solo generar número automáticamente si NO estamos en la página de edición
    // La página de edición tiene un formulario con id "editarContratoForm"
    const esEdicion = document.getElementById('editarContratoForm');
    
    if (!esEdicion) {
        // Estamos en el formulario de creación, generar número automáticamente
        generarNumeroContrato();
    }
});

// Formatear DPI
function formatearDPI(input) {
    let valor = input.value.replace(/\s/g, '');
    
    if (valor.length > 13) {
        valor = valor.substring(0, 13);
    }

    let formateado = '';
    if (valor.length > 0) {
        formateado = valor.substring(0, 4);
    }
    if (valor.length > 4) {
        formateado += ' ' + valor.substring(4, 9);
    }
    if (valor.length > 9) {
        formateado += ' ' + valor.substring(9, 13);
    }

    input.value = formateado;
}

// Validar formulario
function validarFormulario(event) {
    event.preventDefault();

    // Validar NUEVOS campos obligatorios
    const numeroContrato = document.getElementById('numeroContrato').value.trim();
    const servicios = document.getElementById('servicios').value;
    const iva = document.getElementById('iva').value;
    const fondos = document.getElementById('fondos').value;
    const armonizacion = document.getElementById('armonizacion').value;
    const fechaContrato = document.getElementById('fechaContrato').value;
    
    if (!numeroContrato) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, ingresa el número de contrato',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
    
    if (!servicios) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, selecciona el tipo de servicios',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
    
    if (!iva) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, selecciona la opción de IVA',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
    
    if (!fondos) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, selecciona los fondos',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
    
    if (!armonizacion) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, selecciona la armonización',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
    
    // Validar campo "Otro" si está seleccionado
    if (armonizacion === 'Otro') {
        const armonizacionOtro = document.getElementById('armonizacionOtro').value.trim();
        if (!armonizacionOtro) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor, especifica la armonización personalizada',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
    }
    
    if (!fechaContrato) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, ingresa la fecha de contrato',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    // Validar campos EXISTENTES obligatorios
    const nombreCompleto = document.getElementById('nombreCompleto').value.trim();
    const edad = document.getElementById('edad').value;
    const estadoCivil = document.getElementById('estadoCivil').value;
    const profesion = document.getElementById('profesion').value.trim();
    const domicilio = document.getElementById('domicilio').value.trim();
    const dpi = document.getElementById('dpi').value.replace(/\s/g, '');
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const montoTotal = document.getElementById('montoTotal').value;
    const numeroPagos = document.getElementById('numeroPagos').value;
    const montoPago = document.getElementById('montoPago').value;

    // Validar archivos obligatorios
    const cv = document.getElementById('cv').files.length;
    const titulo = document.getElementById('titulo').files.length;
    const cuentaBanco = document.getElementById('cuentaBanco').files.length;
    const dpiArchivo = document.getElementById('dpiArchivo').files.length;

    if (!nombreCompleto) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, ingresa el nombre completo',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!edad || edad < 18) {
        Swal.fire({
            icon: 'warning',
            title: 'Edad inválida',
            text: 'La edad debe ser mayor a 18 años',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!estadoCivil) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, selecciona el estado civil',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!profesion) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, ingresa la profesión',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!domicilio) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor, ingresa el domicilio',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (dpi.length !== 13) {
        Swal.fire({
            icon: 'warning',
            title: 'DPI inválido',
            text: 'El DPI debe tener 13 dígitos',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!fechaInicio || !fechaFin) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas requeridas',
            text: 'Por favor, ingresa las fechas de inicio y finalización',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (new Date(fechaFin) <= new Date(fechaInicio)) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas inválidas',
            text: 'La fecha de finalización debe ser posterior a la fecha de inicio',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!montoTotal || !numeroPagos || !montoPago) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos financieros incompletos',
            text: 'Por favor, completa todos los datos financieros',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!cv || !titulo || !cuentaBanco || !dpiArchivo) {
        Swal.fire({
            icon: 'warning',
            title: 'Archivos requeridos',
            text: 'Por favor, adjunta todos los archivos obligatorios (CV, Título, Cuenta de Banco, DPI)',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    // Si todo está correcto, enviar formulario
    enviarFormulario();
}

function enviarFormulario() {
    const formData = new FormData(document.getElementById('contratoForm'));

    fetch('../../api/procesar_formulario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Contrato registrado!',
                text: data.message,
                confirmButtonColor: '#667eea',
                confirmButtonText: 'Entendido'
            }).then(() => {
                document.getElementById('contratoForm').reset();
                document.getElementById('montoDisplay').textContent = '';
                document.getElementById('montoPagoDisplay').textContent = '';
                document.getElementById('pagosDisplay').textContent = '';
                
                // Limpiar vistas previas de archivos
                const previews = document.querySelectorAll('[id$="Preview"]');
                previews.forEach(preview => preview.remove());
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#667eea'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar el formulario',
            confirmButtonColor: '#667eea'
        });
        console.error('Error:', error);
    });
}

// Vista previa de archivos
function previsualizarArchivo(input) {
    const file = input.files[0];
    const previewId = input.id + 'Preview';
    
    // Eliminar vista previa anterior si existe
    const oldPreview = document.getElementById(previewId);
    if (oldPreview) {
        oldPreview.remove();
    }
    
    if (file) {
        const previewContainer = document.createElement('div');
        previewContainer.id = previewId;
        previewContainer.style.marginTop = '10px';
        previewContainer.style.padding = '15px';
        previewContainer.style.background = '#f8f9fa';
        previewContainer.style.borderRadius = '8px';
        previewContainer.style.border = '2px solid #667eea';
        previewContainer.style.textAlign = 'center';
        
        const fileType = file.type;
        
        if (fileType.startsWith('image/')) {
            // Vista previa de imagen
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '200px';
                img.style.borderRadius = '8px';
                img.style.display = 'block';
                img.style.margin = '0 auto';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else if (fileType === 'application/pdf') {
            // Vista previa de PDF simplificada
            const pdfInfo = document.createElement('div');
            pdfInfo.innerHTML = `
                <i class="fa-solid fa-file-pdf" style="font-size: 48px; color: #e74c3c;"></i>
                <p style="margin-top: 10px; color: #667eea; font-weight: 600;">${file.name}</p>
                <p style="font-size: 12px; color: #666; margin-bottom: 10px;">${(file.size / 1024).toFixed(2)} KB</p>
            `;
            previewContainer.appendChild(pdfInfo);
            
            // Crear URL del objeto para el PDF
            const pdfUrl = URL.createObjectURL(file);
            
            // Botón para abrir en nueva pestaña
            const openBtn = document.createElement('a');
            openBtn.href = pdfUrl;
            openBtn.target = '_blank';
            openBtn.innerHTML = '<i class="fa-solid fa-external-link-alt"></i> Ver PDF';
            openBtn.style.display = 'inline-block';
            openBtn.style.padding = '10px 20px';
            openBtn.style.background = '#667eea';
            openBtn.style.color = 'white';
            openBtn.style.textDecoration = 'none';
            openBtn.style.borderRadius = '8px';
            openBtn.style.fontSize = '14px';
            openBtn.style.fontWeight = '600';
            openBtn.style.transition = 'all 0.3s ease';
            openBtn.onmouseover = function() {
                this.style.background = '#5568d3';
                this.style.transform = 'translateY(-2px)';
            };
            openBtn.onmouseout = function() {
                this.style.background = '#667eea';
                this.style.transform = 'translateY(0)';
            };
            previewContainer.appendChild(openBtn);
        }
        
        // Agregar botón para eliminar
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Eliminar archivo';
        removeBtn.style.marginTop = '10px';
        removeBtn.style.padding = '8px 15px';
        removeBtn.style.background = '#e74c3c';
        removeBtn.style.color = 'white';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '5px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.fontSize = '14px';
        removeBtn.style.marginLeft = '10px';
        removeBtn.onclick = function() {
            input.value = '';
            previewContainer.remove();
        };
        previewContainer.appendChild(removeBtn);
        
        // Insertar después del input
        input.parentElement.appendChild(previewContainer);
    }
}
