<?php
// web/modules/admin/emitir_dictamen/procesar.php
require_once '../../../config/database.php';
require_once '../../../config/workflow.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol(['admin', 'tecnico_3']); // Permitir admin y tecnico_3

// Validar ID
$id_denuncia = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_denuncia <= 0) {
    $_SESSION['error'] = 'ID de denuncia inválido';
    header("Location: index.php");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener información básica de la denuncia
    $sql = "SELECT * FROM denuncias WHERE id_denuncia = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_denuncia]);

    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = 'Denuncia no encontrada';
        header("Location: index.php");
        exit;
    }

    $denuncia = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener historial de seguimiento
    $historial = WorkflowDenuncias::obtenerHistorial($id_denuncia, $db);

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar la denuncia: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Determinar navbar y URL de retorno según el rol del usuario
require_once '../../../includes/detectar_rol_navbar.php';

// Función helper para rutas de archivos
function obtenerRutaArchivo($rutaBD)
{
    if (empty($rutaBD))
        return '';
    $rutaLimpia = str_replace(['../', './'], '', $rutaBD);
    if (strpos($rutaLimpia, 'uploads/') === 0) {
        return "/app-uba/backend/" . $rutaLimpia;
    }
    if (strpos($rutaLimpia, 'backend/') === 0) {
        return "/app-uba/" . $rutaLimpia;
    }
    return "/app-uba/backend/" . $rutaLimpia;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Denuncia #<?php echo $denuncia['id_denuncia']; ?> - AppUBA</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/areas_tecnicas.css">

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include $navbarFile; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="welcome-section">
            <h1><i class="fas fa-file-signature"></i> Procesar Denuncia #<?php echo $denuncia['id_denuncia']; ?></h1>
            <p>Emitir Dictamen - Emisión de dictamen técnico</p>
        </div>

        <!-- Información de la Denuncia -->
        <div class="denuncia-info">
            <h3><i class="fas fa-info-circle"></i> Información de la Denuncia</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Denunciante</span>
                    <span class="info-value"><?php echo htmlspecialchars($denuncia['nombre_completo']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DPI</span>
                    <span class="info-value"><?php echo $denuncia['dpi']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ubicación</span>
                    <span
                        class="info-value"><?php echo $denuncia['departamento'] . ', ' . $denuncia['municipio']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Especie Animal</span>
                    <span class="info-value"><?php echo $denuncia['especie_animal']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Denuncia</span>
                    <span
                        class="info-value"><?php echo date('d/m/Y H:i', strtotime($denuncia['fecha_denuncia'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado Actual</span>
                    <span class="info-value">
                        <span class="badge-etapa badge-<?php echo $denuncia['estado_denuncia']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Historial de Seguimiento -->
        <?php if (count($historial) > 0): ?>
            <div class="historial-container">
                <h2><i class="fas fa-history"></i> Historial de Seguimiento</h2>
                <div class="historial-timeline">
                    <?php foreach ($historial as $item): ?>
                        <div class="historial-item <?php echo $item['accion']; ?>">
                            <div class="historial-header">
                                <span class="historial-etapa">
                                    <?php
                                    $etapas = [
                                        'area_legal' => 'Área Legal',
                                        'area_tecnica' => 'Área Técnica',
                                        'emitir_dictamen' => 'Emisión de Dictamen',
                                        'opinion_legal' => 'Opinión Legal',
                                        'resolucion_final' => 'Resolución Final'
                                    ];
                                    echo $etapas[$item['etapa']];
                                    ?> -
                                    <span style="color: <?php
                                    echo $item['accion'] == 'siguiente_paso' ? '#10b981' :
                                        ($item['accion'] == 'rechazado' ? '#ef4444' : '#3b82f6');
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $item['accion'])); ?>
                                    </span>
                                </span>
                                <span
                                    class="historial-fecha"><?php echo date('d/m/Y H:i', strtotime($item['fecha_procesamiento'])); ?></span>
                            </div>
                            <div class="historial-usuario">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['procesado_por_nombre']); ?>
                            </div>
                            <div class="historial-comentario">
                                <?php echo nl2br(htmlspecialchars($item['comentario'])); ?>
                            </div>
                            <?php if (!empty($item['archivos']) && count($item['archivos']) > 0): ?>
                                <div class="historial-archivos">
                                    <p style="margin: 10px 0 5px 0; font-weight: 600; color: #475569;">
                                        <i class="fas fa-paperclip"></i> Archivos adjuntos:
                                    </p>
                                    <div
                                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 10px;">
                                        <?php foreach ($item['archivos'] as $archivo): ?>
                                            <?php if ($archivo['tipo_archivo'] == 'imagen'): ?>
                                                <!-- Imagen con preview -->
                                                <a href="<?php echo obtenerRutaArchivo($archivo['ruta_archivo']); ?>" target="_blank"
                                                    style="display: block; border: 2px solid #e2e8f0; border-radius: 8px; overflow: hidden; transition: all 0.3s ease;">
                                                    <img src="<?php echo obtenerRutaArchivo($archivo['ruta_archivo']); ?>"
                                                        alt="<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>"
                                                        style="width: 100%; height: 120px; object-fit: cover;">
                                                    <div
                                                        style="padding: 8px; background: #f8fafc; font-size: 0.75rem; color: #64748b; text-align: center;">
                                                        <i class="fas fa-image"></i>
                                                        <?php echo htmlspecialchars(substr($archivo['nombre_archivo'], 0, 20)); ?>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <!-- Archivo para descargar -->
                                                <a href="<?php echo obtenerRutaArchivo($archivo['ruta_archivo']); ?>" target="_blank"
                                                    download
                                                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px; border: 2px solid #e2e8f0; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; background: #f8fafc;">
                                                    <?php
                                                    $extension = strtolower(pathinfo($archivo['nombre_archivo'], PATHINFO_EXTENSION));
                                                    $icono = 'fa-file';
                                                    $colorIcono = '#6B7280';

                                                    if (in_array($extension, ['pdf'])) {
                                                        $icono = 'fa-file-pdf';
                                                        $colorIcono = '#DC2626';
                                                    } elseif (in_array($extension, ['doc', 'docx'])) {
                                                        $icono = 'fa-file-word';
                                                        $colorIcono = '#2563EB';
                                                    } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                                        $icono = 'fa-file-excel';
                                                        $colorIcono = '#059669';
                                                    } elseif (in_array($extension, ['mp3', 'wav', 'ogg'])) {
                                                        $icono = 'fa-file-audio';
                                                        $colorIcono = '#7C3AED';
                                                    } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
                                                        $icono = 'fa-file-video';
                                                        $colorIcono = '#EA580C';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $icono; ?>"
                                                        style="color: <?php echo $colorIcono; ?>; font-size: 32px; margin-bottom: 8px;"></i>
                                                    <span
                                                        style="font-size: 0.75rem; color: #475569; text-align: center; word-break: break-word;">
                                                        <?php echo htmlspecialchars(substr($archivo['nombre_archivo'], 0, 20)); ?>
                                                    </span>
                                                    <span style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;">
                                                        <?php echo number_format($archivo['tamano_bytes'] / 1024, 1); ?> KB
                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario de Procesamiento -->
        <form id="formProcesar" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_denuncia" value="<?php echo $denuncia['id_denuncia']; ?>">

            <!-- Comentario -->
            <div class="form-section">
                <h2><i class="fas fa-comment-alt"></i> Comentario</h2>
                <div class="form-group">
                    <label for="comentario">
                        <i class="fas fa-pencil-alt"></i> Escriba su comentario sobre esta denuncia *
                    </label>
                    <textarea id="comentario" name="comentario" class="form-control"
                        placeholder="Escriba aquí su análisis, observaciones o decisión sobre esta denuncia..." required
                        minlength="20"></textarea>
                    <small style="color: #64748b; margin-top: 5px; display: block;">Mínimo 20 caracteres</small>
                </div>
            </div>

            <!-- Archivos Adjuntos -->
            <div class="form-section">
                <h2><i class="fas fa-paperclip"></i> Archivos Adjuntos (Opcional)</h2>
                <div class="form-group">
                    <label class="file-upload-area" for="archivos">
                        <input type="file" id="archivos" name="archivos[]" multiple
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.mp3,.mp4">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><strong>Haga clic o arrastre archivos aquí</strong></p>
                        <p>Máximo 10MB por archivo</p>
                        <p style="font-size: 0.85rem; color: #64748b;">Imágenes, PDFs, Word, Excel, Audio, Video</p>
                    </label>
                </div>

                <!-- Lista de archivos seleccionados -->
                <div id="filesList" class="files-list"></div>
            </div>

            <!-- Botones de Acción -->
            <div class="form-buttons">
                <a href="<?php echo $urlRetorno; ?>" class="btn btn-cancelar">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-siguiente" data-accion="siguiente_paso">
                    <i class="fas fa-arrow-right"></i> Siguiente Paso
                </button>
                <button type="submit" class="btn btn-rechazar" data-accion="rechazado">
                    <i class="fas fa-ban"></i> Rechazar
                </button>
            </div>
        </form>
    </div>

    <!-- JS personalizado -->
    <script src="../../../js/seguimiento_denuncias.js"></script>
</body>

</html>