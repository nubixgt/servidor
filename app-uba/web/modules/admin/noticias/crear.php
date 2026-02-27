<?php
// web/modules/admin/noticias/crear.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Noticia - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/noticias_admin.css">
    
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../../../includes/navbar_admin.php'; ?>
    
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1><i class="fas fa-plus-circle"></i> Nueva Noticia</h1>
            <p class="subtitle">Crear una nueva noticia para la aplicación móvil AppUBA</p>
        </div>
        
        <!-- Formulario -->
        <form id="formCrearNoticia" method="POST" action="guardar.php" enctype="multipart/form-data">
            
            <!-- Información Principal -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Información Principal</h2>
                
                <div class="form-grid">
                    <!-- Título -->
                    <div class="form-group full-width">
                        <label for="titulo">
                            <i class="fas fa-heading"></i> Título de la Noticia *
                        </label>
                        <input type="text" 
                               name="titulo" 
                               id="titulo" 
                               class="form-control" 
                               placeholder="Ej: Campaña de Esterilización Gratuita"
                               required
                               maxlength="200">
                    </div>
                    
                    <!-- Categoría -->
                    <div class="form-group">
                        <label for="categoria">
                            <i class="fas fa-tag"></i> Categoría *
                        </label>
                        <select name="categoria" id="categoria" class="form-control" required>
                            <option value="">Seleccionar categoría...</option>
                            <option value="Campaña">Campaña</option>
                            <option value="Rescate">Rescate</option>
                            <option value="Legislación">Legislación</option>
                            <option value="Alerta">Alerta</option>
                            <option value="Evento">Evento</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <!-- Fecha de Publicación -->
                    <div class="form-group">
                        <label for="fecha_publicacion">
                            <i class="fas fa-calendar"></i> Fecha de Publicación *
                        </label>
                        <input type="date" 
                               name="fecha_publicacion" 
                               id="fecha_publicacion" 
                               class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>
                    
                    <!-- Descripción Corta -->
                    <div class="form-group full-width">
                        <label for="descripcion_corta">
                            <i class="fas fa-align-left"></i> Descripción Corta (Preview para app móvil) *
                        </label>
                        <textarea name="descripcion_corta" 
                                  id="descripcion_corta" 
                                  class="form-control" 
                                  placeholder="Resumen breve que se mostrará en el listado de noticias..."
                                  required
                                  maxlength="500"></textarea>
                        <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                            Esta descripción se mostrará en la vista previa de la app móvil (máximo 500 caracteres)
                        </small>
                    </div>
                    
                    <!-- Contenido Completo -->
                    <div class="form-group full-width">
                        <label for="contenido_completo">
                            <i class="fas fa-file-alt"></i> Contenido Completo *
                        </label>
                        <textarea name="contenido_completo" 
                                  id="contenido_completo" 
                                  class="form-control tall" 
                                  placeholder="Contenido detallado de la noticia que se mostrará al abrirla..."
                                  required></textarea>
                        <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                            Escribe el contenido completo de la noticia con todos los detalles
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Configuración Adicional -->
            <div class="form-section">
                <h2><i class="fas fa-cog"></i> Configuración Adicional</h2>
                
                <div class="form-grid">
                    <!-- Estado -->
                    <div class="form-group">
                        <label for="estado">
                            <i class="fas fa-toggle-on"></i> Estado *
                        </label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="publicada" selected>Publicada (visible en app)</option>
                            <option value="borrador">Borrador (no visible)</option>
                            <option value="archivada">Archivada</option>
                        </select>
                    </div>
                    
                    <!-- Prioridad -->
                    <div class="form-group">
                        <label for="prioridad">
                            <i class="fas fa-exclamation-circle"></i> Prioridad *
                        </label>
                        <select name="prioridad" id="prioridad" class="form-control" required>
                            <option value="normal" selected>Normal</option>
                            <option value="importante">Importante</option>
                            <option value="urgente">Urgente</option>
                        </select>
                        <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                            Las noticias urgentes e importantes se destacarán en la app
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Imagen -->
            <div class="form-section">
                <h2><i class="fas fa-image"></i> Imagen de la Noticia (Opcional)</h2>
                
                <div class="form-group">
                    <label for="imagen">
                        <i class="fas fa-upload"></i> Seleccionar Imagen
                    </label>
                    <input type="file" 
                           name="imagen" 
                           id="imagen" 
                           class="form-control" 
                           accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                        Formatos permitidos: JPG, JPEG, PNG, WEBP | Tamaño máximo: 2MB
                    </small>
                </div>
                
                <!-- Preview de imagen -->
                <div id="imagenPreview" class="imagen-preview">
                    <img id="imagenPreviewImg" src="" alt="Preview">
                </div>
            </div>
            
            <!-- Botones -->
            <div class="form-buttons">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Crear Noticia
                </button>
            </div>
        </form>
    </div>
    
    <!-- JS personalizado -->
    <script src="../../../js/noticias_admin.js"></script>
</body>
</html>