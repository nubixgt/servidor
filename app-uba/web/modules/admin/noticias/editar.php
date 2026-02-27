<?php
// web/modules/admin/noticias/editar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

// Validar ID
$id_noticia = $_GET['id'] ?? 0;

if ($id_noticia <= 0) {
    $_SESSION['error'] = 'ID de noticia inválido';
    header("Location: index.php");
    exit;
}

// Función helper para rutas de archivos
function obtenerRutaArchivo($rutaBD)
{
    if (empty($rutaBD))
        return null;

    $rutaLimpia = str_replace(['../', './'], '', $rutaBD);

    if (strpos($rutaLimpia, 'uploads/') === 0) {
        return "/AppUBA/backend/" . $rutaLimpia;
    }

    if (strpos($rutaLimpia, 'backend/') === 0) {
        return "/AppUBA/" . $rutaLimpia;
    }

    return "/AppUBA/backend/" . $rutaLimpia;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT * FROM noticias WHERE id_noticia = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id_noticia);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = 'Noticia no encontrada';
        header("Location: index.php");
        exit;
    }

    $noticia = $stmt->fetch();

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar la noticia: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Noticia - AppUBA</title>

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
            <h1><i class="fas fa-edit"></i> Editar Noticia</h1>
            <p>Modifica la información de la noticia</p>
        </div>

        <!-- Formulario -->
        <form id="formEditarNoticia" method="POST" action="actualizar.php" enctype="multipart/form-data">
            <input type="hidden" name="id_noticia" value="<?php echo $noticia['id_noticia']; ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo $noticia['imagen_url']; ?>">

            <!-- Información Principal -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Información Principal</h2>

                <div class="form-grid">
                    <!-- Título -->
                    <div class="form-group full-width">
                        <label for="titulo">
                            <i class="fas fa-heading"></i> Título de la Noticia *
                        </label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                            value="<?php echo htmlspecialchars($noticia['titulo']); ?>" required maxlength="200">
                    </div>

                    <!-- Categoría -->
                    <div class="form-group">
                        <label for="categoria">
                            <i class="fas fa-tag"></i> Categoría *
                        </label>
                        <select name="categoria" id="categoria" class="form-control" required>
                            <option value="">Seleccionar categoría...</option>
                            <option value="Campaña" <?php echo $noticia['categoria'] == 'Campaña' ? 'selected' : ''; ?>>
                                Campaña</option>
                            <option value="Rescate" <?php echo $noticia['categoria'] == 'Rescate' ? 'selected' : ''; ?>>
                                Rescate</option>
                            <option value="Legislación" <?php echo $noticia['categoria'] == 'Legislación' ? 'selected' : ''; ?>>Legislación</option>
                            <option value="Alerta" <?php echo $noticia['categoria'] == 'Alerta' ? 'selected' : ''; ?>>
                                Alerta</option>
                            <option value="Evento" <?php echo $noticia['categoria'] == 'Evento' ? 'selected' : ''; ?>>
                                Evento</option>
                            <option value="Otro" <?php echo $noticia['categoria'] == 'Otro' ? 'selected' : ''; ?>>Otro
                            </option>
                        </select>
                    </div>

                    <!-- Fecha de Publicación -->
                    <div class="form-group">
                        <label for="fecha_publicacion">
                            <i class="fas fa-calendar"></i> Fecha de Publicación *
                        </label>
                        <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control"
                            value="<?php echo $noticia['fecha_publicacion']; ?>" required>
                    </div>

                    <!-- Descripción Corta -->
                    <div class="form-group full-width">
                        <label for="descripcion_corta">
                            <i class="fas fa-align-left"></i> Descripción Corta (Preview para app móvil) *
                        </label>
                        <textarea name="descripcion_corta" id="descripcion_corta" class="form-control" required
                            maxlength="500"><?php echo htmlspecialchars($noticia['descripcion_corta']); ?></textarea>
                        <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                            Esta descripción se mostrará en la vista previa de la app móvil (máximo 500 caracteres)
                        </small>
                    </div>

                    <!-- Contenido Completo -->
                    <div class="form-group full-width">
                        <label for="contenido_completo">
                            <i class="fas fa-file-alt"></i> Contenido Completo *
                        </label>
                        <textarea name="contenido_completo" id="contenido_completo" class="form-control tall"
                            required><?php echo htmlspecialchars($noticia['contenido_completo']); ?></textarea>
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
                            <option value="publicada" <?php echo $noticia['estado'] == 'publicada' ? 'selected' : ''; ?>>
                                Publicada (visible en app)</option>
                            <option value="borrador" <?php echo $noticia['estado'] == 'borrador' ? 'selected' : ''; ?>>
                                Borrador (no visible)</option>
                            <option value="archivada" <?php echo $noticia['estado'] == 'archivada' ? 'selected' : ''; ?>>
                                Archivada</option>
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div class="form-group">
                        <label for="prioridad">
                            <i class="fas fa-exclamation-circle"></i> Prioridad *
                        </label>
                        <select name="prioridad" id="prioridad" class="form-control" required>
                            <option value="normal" <?php echo $noticia['prioridad'] == 'normal' ? 'selected' : ''; ?>>
                                Normal</option>
                            <option value="importante" <?php echo $noticia['prioridad'] == 'importante' ? 'selected' : ''; ?>>Importante</option>
                            <option value="urgente" <?php echo $noticia['prioridad'] == 'urgente' ? 'selected' : ''; ?>>
                                Urgente</option>
                        </select>
                        <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                            Las noticias urgentes e importantes se destacarán en la app
                        </small>
                    </div>
                </div>
            </div>

            <!-- Imagen -->
            <div class="form-section">
                <h2><i class="fas fa-image"></i> Imagen de la Noticia</h2>

                <?php if (!empty($noticia['imagen_url'])): ?>
                    <div style="margin-bottom: 20px;">
                        <p style="color: var(--color-texto-claro); margin-bottom: 10px;">Imagen actual:</p>
                        <img src="<?php echo obtenerRutaArchivo($noticia['imagen_url']); ?>" alt="Imagen actual"
                            style="max-width: 300px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="imagen">
                        <i class="fas fa-upload"></i> Cambiar Imagen (Opcional)
                    </label>
                    <input type="file" name="imagen" id="imagen" class="form-control"
                        accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small style="color: var(--color-texto-claro); margin-top: 5px; display: block;">
                        Formatos permitidos: JPG, JPEG, PNG, WEBP | Tamaño máximo: 2MB
                        <br>
                        Si no seleccionas una nueva imagen, se mantendrá la actual
                    </small>
                </div>

                <!-- Preview de nueva imagen -->
                <div id="imagenPreview" class="imagen-preview">
                    <p style="color: var(--color-texto-claro); margin-bottom: 10px;">Nueva imagen:</p>
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
                    Actualizar Noticia
                </button>
            </div>
        </form>
    </div>

    <!-- JS personalizado -->
    <script src="../../../js/noticias_admin.js"></script>
</body>

</html>