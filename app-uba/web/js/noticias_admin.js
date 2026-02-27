// web/js/noticias_admin.js

// ============================================
// VALIDACIONES Y LÓGICA DEL MÓDULO DE NOTICIAS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // PREVIEW DE IMAGEN AL SELECCIONAR ARCHIVO
    // ==========================================
    const imagenInput = document.getElementById('imagen');
    const imagenPreview = document.getElementById('imagenPreview');
    const imagenPreviewImg = document.getElementById('imagenPreviewImg');
    
    if (imagenInput) {
        imagenInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validar tipo de archivo
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tipo de archivo no válido',
                        text: 'Solo se permiten imágenes JPG, JPEG, PNG o WEBP',
                        confirmButtonColor: '#ef4444'
                    });
                    imagenInput.value = '';
                    imagenPreview.classList.remove('show');
                    return;
                }
                
                // Validar tamaño (máximo 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB en bytes
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Archivo muy grande',
                        text: 'La imagen no debe superar los 2MB',
                        confirmButtonColor: '#ef4444'
                    });
                    imagenInput.value = '';
                    imagenPreview.classList.remove('show');
                    return;
                }
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagenPreviewImg.src = e.target.result;
                    imagenPreview.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                imagenPreview.classList.remove('show');
            }
        });
    }
    
    // ==========================================
    // VALIDACIÓN DEL FORMULARIO DE CREAR/EDITAR
    // ==========================================
    const formCrearNoticia = document.getElementById('formCrearNoticia');
    const formEditarNoticia = document.getElementById('formEditarNoticia');
    
    // Validación para crear
    if (formCrearNoticia) {
        formCrearNoticia.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const titulo = document.getElementById('titulo').value.trim();
            const categoria = document.getElementById('categoria').value;
            const descripcionCorta = document.getElementById('descripcion_corta').value.trim();
            const contenidoCompleto = document.getElementById('contenido_completo').value.trim();
            const fechaPublicacion = document.getElementById('fecha_publicacion').value;
            
            // Validaciones
            if (titulo === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El título de la noticia es obligatorio',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (titulo.length < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Título muy corto',
                    text: 'El título debe tener al menos 10 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (categoria === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Debes seleccionar una categoría',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (descripcionCorta === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'La descripción corta es obligatoria',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (descripcionCorta.length < 20) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Descripción muy corta',
                    text: 'La descripción corta debe tener al menos 20 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (contenidoCompleto === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El contenido completo es obligatorio',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (contenidoCompleto.length < 50) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contenido muy corto',
                    text: 'El contenido completo debe tener al menos 50 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (fechaPublicacion === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'La fecha de publicación es obligatoria',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            // Confirmación antes de enviar
            Swal.fire({
                title: '¿Crear esta noticia?',
                text: "La noticia se publicará en la aplicación móvil",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formCrearNoticia.submit();
                }
            });
        });
    }
    
    // Validación para editar
    if (formEditarNoticia) {
        formEditarNoticia.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const titulo = document.getElementById('titulo').value.trim();
            const categoria = document.getElementById('categoria').value;
            const descripcionCorta = document.getElementById('descripcion_corta').value.trim();
            const contenidoCompleto = document.getElementById('contenido_completo').value.trim();
            const fechaPublicacion = document.getElementById('fecha_publicacion').value;
            
            // Validaciones
            if (titulo === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El título de la noticia es obligatorio',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (titulo.length < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Título muy corto',
                    text: 'El título debe tener al menos 10 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (categoria === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Debes seleccionar una categoría',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (descripcionCorta === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'La descripción corta es obligatoria',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (descripcionCorta.length < 20) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Descripción muy corta',
                    text: 'La descripción corta debe tener al menos 20 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (contenidoCompleto === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El contenido completo es obligatorio',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (contenidoCompleto.length < 50) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contenido muy corto',
                    text: 'El contenido completo debe tener al menos 50 caracteres',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            if (fechaPublicacion === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'La fecha de publicación es obligatoria',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }
            
            // Confirmación antes de enviar
            Swal.fire({
                title: '¿Actualizar esta noticia?',
                text: "Los cambios se reflejarán en la aplicación móvil",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formEditarNoticia.submit();
                }
            });
        });
    }
    
    // ==========================================
    // CONFIRMACIÓN AL CANCELAR EDICIÓN/CREACIÓN
    // ==========================================
    const botonesCancelar = document.querySelectorAll('a[href*="index.php"], a[href*="ver.php"]');
    
    botonesCancelar.forEach(boton => {
        // Solo aplicar en formularios de crear/editar
        if (formCrearNoticia || formEditarNoticia) {
            if (boton.classList.contains('btn-secondary') || boton.textContent.includes('Cancelar')) {
                boton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = this.getAttribute('href');
                    
                    Swal.fire({
                        title: '¿Cancelar?',
                        text: "Los cambios no guardados se perderán",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, cancelar',
                        cancelButtonText: 'Continuar editando'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url;
                        }
                    });
                });
            }
        }
    });
    
    // ==========================================
    // CONTADOR DE CARACTERES PARA TEXTAREAS
    // ==========================================
    const descripcionCorta = document.getElementById('descripcion_corta');
    const contenidoCompleto = document.getElementById('contenido_completo');
    
    if (descripcionCorta) {
        const contadorDescripcion = document.createElement('small');
        contadorDescripcion.style.cssText = 'color: #64748b; margin-top: 5px; display: block;';
        descripcionCorta.parentNode.appendChild(contadorDescripcion);
        
        function actualizarContadorDescripcion() {
            const length = descripcionCorta.value.length;
            contadorDescripcion.textContent = `${length} caracteres`;
            if (length < 20) {
                contadorDescripcion.style.color = '#ef4444';
            } else {
                contadorDescripcion.style.color = '#10b981';
            }
        }
        
        descripcionCorta.addEventListener('input', actualizarContadorDescripcion);
        actualizarContadorDescripcion();
    }
    
    if (contenidoCompleto) {
        const contadorContenido = document.createElement('small');
        contadorContenido.style.cssText = 'color: #64748b; margin-top: 5px; display: block;';
        contenidoCompleto.parentNode.appendChild(contadorContenido);
        
        function actualizarContadorContenido() {
            const length = contenidoCompleto.value.length;
            contadorContenido.textContent = `${length} caracteres`;
            if (length < 50) {
                contadorContenido.style.color = '#ef4444';
            } else {
                contadorContenido.style.color = '#10b981';
            }
        }
        
        contenidoCompleto.addEventListener('input', actualizarContadorContenido);
        actualizarContadorContenido();
    }
    
    // ==========================================
    // CONFIRMACIÓN DE ELIMINACIÓN
    // ==========================================
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            const titulo = this.getAttribute('data-titulo') || 'esta noticia';
            
            Swal.fire({
                title: '¿Eliminar noticia?',
                html: `¿Estás seguro de eliminar <strong>"${titulo}"</strong>?<br><br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
});