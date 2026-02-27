// js/formulario.js

document.addEventListener('DOMContentLoaded', function() {
    const registroForm = document.getElementById('registroForm');
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const telefonoInput = document.getElementById('telefono');
    const telefonoAmericanoInput = document.getElementById('telefono_americano');
    const comoSeEnteroSelect = document.getElementById('como_se_entero');
    const correoInput = document.getElementById('correo');
    const comentarioInput = document.getElementById('comentario');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const caracteresRestantes = document.getElementById('caracteresRestantes');
    const mensaje = document.getElementById('message');

    // Auto-ocultar mensaje después de 5 segundos
    if (mensaje) {
        setTimeout(function() {
            mensaje.style.transition = 'opacity 0.5s ease';
            mensaje.style.opacity = '0';
            setTimeout(function() {
                mensaje.remove();
            }, 500);
        }, 5000);
    }

    // Validación del teléfono guatemalteco en tiempo real CON +502 (CORREGIDO)
    telefonoInput.addEventListener('input', function(e) {
        let valor = e.target.value;
        
        // Remover todo excepto números y el signo +
        valor = valor.replace(/[^\d+]/g, '');
        
        // Si está vacío, limpiar
        if (valor.length === 0) {
            e.target.value = '';
            telefonoInput.classList.remove('error');
            return;
        }
        
        // Si no empieza con +, agregarlo
        if (!valor.startsWith('+')) {
            valor = '+' + valor;
        }
        
        // Si no tiene 502 después del +, agregarlo
        if (valor.length > 1 && !valor.startsWith('+502')) {
            valor = '+502' + valor.substring(1);
        } else if (valor.length === 1) {
            valor = '+502';
        }
        
        // Remover el prefijo para trabajar solo con números
        let numeros = valor.substring(4); // Quitar +502
        
        // Limitar a 8 dígitos
        if (numeros.length > 8) {
            numeros = numeros.slice(0, 8);
        }
        
        // Formatear: +502 XXXX-XXXX
        let formatted = '+502';
        if (numeros.length > 0) {
            formatted += ' ' + numeros.substring(0, 4);
        }
        if (numeros.length > 4) {
            formatted += '-' + numeros.substring(4, 8);
        }
        
        e.target.value = formatted;
        
        // Validar formato
        if (numeros.length === 8 && /^\+502 \d{4}-\d{4}$/.test(formatted)) {
            telefonoInput.classList.remove('error');
        } else if (numeros.length > 0) {
            telefonoInput.classList.add('error');
        } else {
            telefonoInput.classList.remove('error');
        }
    });

    // Limpiar campo si solo queda +502
    telefonoInput.addEventListener('blur', function() {
        if (this.value === '+502' || this.value === '+502 ' || this.value.trim() === '') {
            this.value = '';
            this.classList.remove('error');
        }
    });

    // Al hacer focus, si está vacío, agregar +502
    telefonoInput.addEventListener('focus', function() {
        if (this.value === '') {
            this.value = '+502 ';
        }
    });

    // Formateo automático del teléfono americano
    if (telefonoAmericanoInput) {
        telefonoAmericanoInput.addEventListener('input', function(e) {
            let valor = e.target.value;
            
            // Remover todo excepto números y el signo +
            valor = valor.replace(/[^\d+]/g, '');
            
            // Si está vacío, limpiar
            if (valor.length === 0) {
                e.target.value = '';
                return;
            }
            
            // Si no empieza con +, agregarlo
            if (!valor.startsWith('+')) {
                valor = '+' + valor;
            }
            
            // Si no tiene el 1 después del +, agregarlo
            if (valor.length > 1 && valor[1] !== '1') {
                valor = '+1' + valor.substring(1);
            } else if (valor.length === 1) {
                valor = '+1';
            }
            
            // Remover el prefijo para trabajar solo con números
            let numeros = valor.substring(2); // Quitar +1
            
            // Limitar a 10 dígitos
            if (numeros.length > 10) {
                numeros = numeros.slice(0, 10);
            }
            
            // Formatear: +1 XXX-XXX-XXXX
            let formatted = '+1';
            if (numeros.length > 0) {
                formatted += ' ' + numeros.substring(0, 3);
            }
            if (numeros.length > 3) {
                formatted += '-' + numeros.substring(3, 6);
            }
            if (numeros.length > 6) {
                formatted += '-' + numeros.substring(6, 10);
            }
            
            e.target.value = formatted;
        });

        // Limpiar campo si solo queda +1
        telefonoAmericanoInput.addEventListener('blur', function() {
            if (this.value === '+1' || this.value === '+1 ' || this.value.trim() === '') {
                this.value = '';
            }
        });

        // Al hacer focus, si está vacío, agregar +1
        telefonoAmericanoInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '+1 ';
            }
        });
    }

    // Prevenir pegar texto no válido en teléfono guatemalteco
    telefonoInput.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Limpiar el texto pegado
        pastedText = pastedText.replace(/[^\d]/g, '');
        
        if (pastedText.length >= 8) {
            pastedText = pastedText.slice(0, 8);
            telefonoInput.value = '+502 ' + pastedText.slice(0, 4) + '-' + pastedText.slice(4);
        } else if (pastedText.length > 4) {
            telefonoInput.value = '+502 ' + pastedText.slice(0, 4) + '-' + pastedText.slice(4);
        } else if (pastedText.length > 0) {
            telefonoInput.value = '+502 ' + pastedText;
        }
        
        // Disparar evento input para validación
        telefonoInput.dispatchEvent(new Event('input'));
    });

    // Prevenir teclas no numéricas en teléfono guatemalteco (excepto control) - MEJORADO
    telefonoInput.addEventListener('keydown', function(e) {
        const permitidas = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
        
        // Si es backspace y el valor actual es '+502 ' (solo el prefijo), prevenir
        if (e.key === 'Backspace') {
            const valor = this.value;
            const cursorPos = this.selectionStart;
            
            // Si el valor es exactamente '+502 ' y el cursor está al final o en posición 5
            if ((valor === '+502 ' && cursorPos === 5) || valor === '+502') {
                e.preventDefault();
                return;
            }
            
            // Si el cursor está en posición 0-4 (dentro del prefijo +502), prevenir
            if (cursorPos <= 4) {
                e.preventDefault();
                return;
            }
        }
        
        // Si es Delete y el cursor está dentro del prefijo, prevenir
        if (e.key === 'Delete') {
            const cursorPos = this.selectionStart;
            if (cursorPos < 4) {
                e.preventDefault();
                return;
            }
        }
        
        if (permitidas.includes(e.key)) {
            return;
        }
        
        // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }
        
        // Permitir números
        if (e.key >= '0' && e.key <= '9') {
            return;
        }
        
        e.preventDefault();
    });

    // Contador de caracteres para comentario
    if (comentarioInput) {
        comentarioInput.addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const restantes = maxLength - currentLength;
            
            caracteresRestantes.textContent = restantes + ' caracteres restantes';
            
            if (restantes < 50) {
                caracteresRestantes.style.color = '#f44336';
            } else {
                caracteresRestantes.style.color = '#999';
            }
        });
    }

    // Validación antes de enviar
    registroForm.addEventListener('submit', function(e) {
        let isValid = true;
        let primerError = null;

        // Limpiar estados de error previos
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Validar nombre
        if (nombreInput.value.trim() === '') {
            isValid = false;
            nombreInput.classList.add('error');
            if (!primerError) primerError = nombreInput;
        }

        // Validar apellido
        if (apellidoInput.value.trim() === '') {
            isValid = false;
            apellidoInput.classList.add('error');
            if (!primerError) primerError = apellidoInput;
        }

        // Validar teléfono guatemalteco (solo si se ingresó)
        const telefonoValor = telefonoInput.value.trim();
        const telefonoRegex = /^\+502 \d{4}-\d{4}$/;
        
        if (telefonoValor !== '' && !telefonoRegex.test(telefonoValor)) {
            isValid = false;
            telefonoInput.classList.add('error');
            if (!primerError) primerError = telefonoInput;
            mostrarError('El teléfono guatemalteco debe tener el formato +502 0000-0000');
        }

        // Validar cómo se enteró
        if (comoSeEnteroSelect.value === '') {
            isValid = false;
            comoSeEnteroSelect.classList.add('error');
            if (!primerError) primerError = comoSeEnteroSelect;
            if (isValid) mostrarError('Por favor, selecciona cómo te enteraste de nosotros');
        }

        if (!isValid) {
            e.preventDefault();
            if (primerError) {
                primerError.focus();
            }
        }
    });

    // Limpiar formulario
    btnLimpiar.addEventListener('click', function() {
        setTimeout(function() {
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            if (caracteresRestantes) {
                caracteresRestantes.textContent = '500 caracteres restantes';
                caracteresRestantes.style.color = '#999';
            }
            nombreInput.focus();
        }, 10);
    });

    // Función para mostrar error
    function mostrarError(texto) {
        // Remover mensaje anterior si existe
        const mensajeAnterior = document.getElementById('message');
        if (mensajeAnterior) {
            mensajeAnterior.remove();
        }

        // Crear nuevo mensaje
        const nuevoMensaje = document.createElement('div');
        nuevoMensaje.className = 'message error';
        nuevoMensaje.id = 'message';
        nuevoMensaje.textContent = texto;

        const formHeader = document.querySelector('.form-header');
        formHeader.parentNode.insertBefore(nuevoMensaje, formHeader.nextSibling);
    }

    // Enfocar el primer campo al cargar
    nombreInput.focus();

    // Limpiar errores al escribir
    nombreInput.addEventListener('input', function() {
        this.classList.remove('error');
    });

    apellidoInput.addEventListener('input', function() {
        this.classList.remove('error');
    });

    comoSeEnteroSelect.addEventListener('change', function() {
        this.classList.remove('error');
    });

    // Validar que el nombre solo contenga letras y espacios
    nombreInput.addEventListener('input', function() {
        // Permitir letras, espacios, acentos y ñ
        this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
    });

    // Validar que el apellido solo contenga letras y espacios
    apellidoInput.addEventListener('input', function() {
        // Permitir letras, espacios, acentos y ñ
        this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
    });

    // Botón Ver Registros
    const btnVerRegistros = document.getElementById('btnVerRegistros');
    if (btnVerRegistros) {
        btnVerRegistros.addEventListener('click', function() {
            window.location.href = 'ver_registros.php';
        });
    }

    // Confirmación de cerrar sesión
    const btnCerrarSesion = document.getElementById('btnCerrarSesion');
    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "¿Estás seguro de que deseas salir?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#38bdf8',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });
    }
});