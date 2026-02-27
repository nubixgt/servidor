// js/ver_registros.js

$(document).ready(function() {
    // Configurar DataTable - TODAS LAS COLUMNAS VISIBLES CON SCROLL HORIZONTAL
    const tabla = $('#tablaRegistros').DataTable({
        ajax: {
            url: 'api/obtener_registros.php',
            dataSrc: '',
            error: function(xhr, error, thrown) {
                console.error('Error al cargar datos:', error);
                console.error('Respuesta:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar registros',
                    text: 'No se pudieron cargar los registros. Verifica la consola para más detalles.',
                    confirmButtonColor: '#38bdf8'
                });
            }
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'apellido' },
            { 
                data: 'telefono',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'telefono_americano',
                render: function(data) {
                    return data || '-';
                }
            },
            { data: 'como_se_entero' },
            { 
                data: 'correo',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'comentario',
                render: function(data) {
                    if (!data) return '-';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'fecha_registro',
                render: function(data) {
                    const fecha = new Date(data);
                    return fecha.toLocaleString('es-GT');
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    const telefono = row.telefono || 'Sin teléfono';
                    const telefonoUsa = row.telefono_americano || 'Sin teléfono USA';
                    return `
                        <button class="btn-action btn-bitacora" onclick="abrirBitacora(${row.id}, '${row.nombre}', '${row.apellido}', '${telefono}', '${telefonoUsa}')">
                            <i class="fa-solid fa-book"></i>
                        </button>
                        <button class="btn-action btn-edit" onclick="editarRegistro(${row.id})">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
            emptyTable: 'No hay registros disponibles',
            zeroRecords: 'No se encontraron registros que coincidan',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...'
        },
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
    });

    // Exportar a Excel
    $('#btnExportarExcel').on('click', function() {
        window.location.href = 'api/exportar_excel.php';
    });

    // Modal de Edición
    const modal = $('#modalEditar');
    const span = $('.close');

    span.on('click', function() {
        modal.hide();
    });

    $('#btnCancelar').on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });

    // Validación del teléfono guatemalteco en el modal - IGUAL QUE USA
    $('#edit_telefono').on('input', function(e) {
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
    });

    // Limpiar campo si solo queda +502
    $('#edit_telefono').on('blur', function() {
        if (this.value === '+502' || this.value === '+502 ' || this.value.trim() === '') {
            this.value = '';
        }
    });

    // Al hacer focus, si está vacío, agregar +502
    $('#edit_telefono').on('focus', function() {
        if (this.value === '') {
            this.value = '+502 ';
        }
    });

    // Prevenir backspace/delete en el prefijo del teléfono guatemalteco
    $('#edit_telefono').on('keydown', function(e) {
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

    // Validación del teléfono americano en el modal
    $('#edit_telefono_americano').on('input', function(e) {
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

    // Limpiar teléfono americano si solo queda +1
    $('#edit_telefono_americano').on('blur', function() {
        if (this.value === '+1' || this.value === '+1 ' || this.value.trim() === '') {
            this.value = '';
        }
    });

    // Al hacer focus en teléfono americano, si está vacío, agregar +1
    $('#edit_telefono_americano').on('focus', function() {
        if (this.value === '') {
            this.value = '+1 ';
        }
    });

    // Validar que el nombre solo contenga letras
    $('#edit_nombre').on('input', function() {
        this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
    });

    // Validar que el apellido solo contenga letras
    $('#edit_apellido').on('input', function() {
        this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
    });

    // Enviar formulario de edición
    $('#formEditar').on('submit', function(e) {
        e.preventDefault();
        
        // Primero confirmar con z-index alto
        Swal.fire({
            title: '¿Guardar cambios?',
            text: "¿Estás seguro de actualizar este registro?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#38bdf8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            customClass: {
                container: 'swal-high-zindex'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = {
                    id: $('#edit_id').val(),
                    nombre: $('#edit_nombre').val(),
                    apellido: $('#edit_apellido').val(),
                    telefono: $('#edit_telefono').val(),
                    telefono_americano: $('#edit_telefono_americano').val(),
                    como_se_entero: $('#edit_como_se_entero').val(),
                    correo: $('#edit_correo').val(),
                    comentario: $('#edit_comentario').val()
                };

                $.ajax({
                    url: 'api/actualizar_registro.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Actualizado!',
                                text: 'El registro se actualizó correctamente',
                                confirmButtonColor: '#38bdf8',
                                customClass: {
                                    container: 'swal-high-zindex'
                                }
                            }).then(() => {
                                modal.hide();
                                tabla.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#38bdf8',
                                customClass: {
                                    container: 'swal-high-zindex'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al actualizar el registro',
                            confirmButtonColor: '#38bdf8',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        });
                    }
                });
            }
        });
    });

    // Modal de Bitácora
    const modalBitacora = $('#modalBitacora');
    const spanBitacora = $('.close-bitacora');

    spanBitacora.on('click', function() {
        modalBitacora.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modalBitacora[0]) {
            modalBitacora.hide();
        }
    });

    // Formulario de agregar seguimiento
    $('#formAgregarSeguimiento').on('submit', function(e) {
        e.preventDefault();
        
        const registroId = $('#seguimiento_registro_id').val();
        const comentario = $('#nuevo_comentario').val().trim();

        if (!comentario) {
            Swal.fire({
                icon: 'warning',
                title: 'Comentario vacío',
                text: 'Por favor, ingresa un comentario',
                confirmButtonColor: '#38bdf8'
            });
            return;
        }

        $.ajax({
            url: 'api/agregar_seguimiento.php',
            method: 'POST',
            data: {
                registro_id: registroId,
                comentario: comentario
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#nuevo_comentario').val('');
                    cargarSeguimientos(registroId);
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Seguimiento agregado!',
                        text: 'El seguimiento se guardó correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    tabla.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#38bdf8'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al agregar el seguimiento',
                    confirmButtonColor: '#38bdf8'
                });
            }
        });
    });

    // BOTÓN CERRAR SESIÓN - ARREGLADO
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

// Función para editar registro - TOTALMENTE CORREGIDA
function editarRegistro(id) {
    $.ajax({
        url: 'api/obtener_registro.php?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#edit_id').val(data.registro.id);
                $('#edit_nombre').val(data.registro.nombre);
                $('#edit_apellido').val(data.registro.apellido);
                
                // Formatear teléfono guatemalteco - LÓGICA MEJORADA
                let telefonoGT = data.registro.telefono || '';
                if (telefonoGT && telefonoGT.trim() !== '') {
                    // Limpiar TODOS los caracteres no numéricos
                    let numerosSolos = telefonoGT.replace(/\D/g, '');
                    
                    console.log('Teléfono original:', telefonoGT);
                    console.log('Números extraídos:', numerosSolos);
                    
                    // Si empieza con 502, quitarlo
                    if (numerosSolos.startsWith('502') && numerosSolos.length > 3) {
                        numerosSolos = numerosSolos.substring(3);
                        console.log('Después de quitar 502:', numerosSolos);
                    }
                    
                    // Validar que tengamos números
                    if (numerosSolos.length > 0) {
                        // Limitar a 8 dígitos
                        if (numerosSolos.length > 8) {
                            numerosSolos = numerosSolos.substring(0, 8);
                        }
                        
                        // Formatear según la cantidad de dígitos
                        if (numerosSolos.length >= 4) {
                            telefonoGT = '+502 ' + numerosSolos.substring(0, 4);
                            if (numerosSolos.length > 4) {
                                telefonoGT += '-' + numerosSolos.substring(4);
                            }
                        } else {
                            telefonoGT = '+502 ' + numerosSolos;
                        }
                        
                        console.log('Teléfono formateado:', telefonoGT);
                    } else {
                        telefonoGT = '';
                    }
                } else {
                    telefonoGT = '';
                }
                
                $('#edit_telefono').val(telefonoGT);
                $('#edit_telefono_americano').val(data.registro.telefono_americano || '');
                $('#edit_como_se_entero').val(data.registro.como_se_entero);
                $('#edit_correo').val(data.registro.correo || '');
                $('#edit_comentario').val(data.registro.comentario || '');
                $('#modalEditar').show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el registro',
                    confirmButtonColor: '#38bdf8'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar el registro',
                confirmButtonColor: '#38bdf8'
            });
        }
    });
}

// Función para abrir modal de bitácora
function abrirBitacora(id, nombre, apellido, telefono, telefonoUsa) {
    $('#seguimiento_registro_id').val(id);
    $('#bitacora-nombre').text(nombre + ' ' + apellido);
    $('#bitacora-telefono').text(telefono);
    $('#bitacora-telefono-usa').text(telefonoUsa);
    $('#nuevo_comentario').val('');
    
    cargarSeguimientos(id);
    $('#modalBitacora').show();
}

// Función para cargar seguimientos
function cargarSeguimientos(registroId) {
    $('#lista-seguimientos').html('<p class="text-center text-muted">Cargando...</p>');
    
    $.ajax({
        url: 'api/obtener_seguimientos.php?registro_id=' + registroId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarSeguimientos(response.seguimientos);
            } else {
                $('#lista-seguimientos').html('<p class="text-center text-muted">Error al cargar seguimientos</p>');
            }
        },
        error: function() {
            $('#lista-seguimientos').html('<p class="text-center text-muted">Error al cargar seguimientos</p>');
        }
    });
}

// Función para mostrar seguimientos
function mostrarSeguimientos(seguimientos) {
    const listaSeguimientos = $('#lista-seguimientos');
    
    if (seguimientos.length === 0) {
        listaSeguimientos.html(`
            <div class="sin-seguimientos">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No hay seguimientos registrados aún</p>
                <small>Agrega el primer seguimiento usando el formulario de arriba</small>
            </div>
        `);
        return;
    }
    
    let html = '';
    seguimientos.forEach(function(seg) {
        const fecha = new Date(seg.fecha_creacion);
        const fechaFormateada = fecha.toLocaleString('es-GT', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="seguimiento-item">
                <div class="seguimiento-header">
                    <span class="seguimiento-usuario">${seg.usuario}</span>
                    <span class="seguimiento-fecha">${fechaFormateada}</span>
                </div>
                <div class="seguimiento-comentario">${seg.comentario}</div>
            </div>
        `;
    });
    
    listaSeguimientos.html(html);
}