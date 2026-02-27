<?php
// web/modules/admin/area_tecnica/detalle_denuncia.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol(['admin', 'tecnico_2']);

// Validar ID
$id_denuncia = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_denuncia <= 0) {
    $_SESSION['error'] = 'ID de denuncia inválido';
    header("Location: index.php");
    exit;
}

// Función helper para rutas de archivos
function obtenerRutaArchivo($rutaBD)
{
    if (empty($rutaBD))
        return '';

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

    // Obtener información de la denuncia
    $sql = "SELECT * FROM denuncias WHERE id_denuncia = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_denuncia]);

    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = 'Denuncia no encontrada';
        header("Location: index.php");
        exit;
    }

    $denuncia = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener infracciones correctamente
    $sqlInfracciones = "SELECT tipo_infraccion, infraccion_otro 
                        FROM infracciones_denuncia 
                        WHERE id_denuncia = ?";
    $stmtInfracciones = $db->prepare($sqlInfracciones);
    $stmtInfracciones->execute([$id_denuncia]);
    $infracciones = $stmtInfracciones->fetchAll(PDO::FETCH_ASSOC);

    // Obtener evidencias
    $sqlEvidencias = "SELECT * FROM evidencias_denuncia WHERE id_denuncia = ?";
    $stmtEvidencias = $db->prepare($sqlEvidencias);
    $stmtEvidencias->execute([$id_denuncia]);
    $evidencias = $stmtEvidencias->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar la denuncia: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Determinar navbar y URL de retorno según el rol del usuario
require_once '../../../includes/detectar_rol_navbar.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Denuncia #<?php echo $denuncia['id_denuncia']; ?> - AppUBA</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/ver_denuncia_admin.css">

    <!-- Lightbox2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g"></script>
</head>

<body>
    <?php include $navbarFile; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="header-detalle">
            <h1><i class="fas fa-file-alt"></i> Denuncia #<?php echo $denuncia['id_denuncia']; ?></h1>
            <div>
                <span class="badge-estado badge-<?php echo $denuncia['estado_denuncia']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                </span>
            </div>
        </div>

        <!-- Información del Denunciante -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-user"></i> Información del Denunciante</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Tipo de Persona</span>
                    <span class="info-value"><?php echo $denuncia['tipo_persona']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nombre Completo</span>
                    <span
                        class="info-value destacado"><?php echo htmlspecialchars($denuncia['nombre_completo']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DPI</span>
                    <span class="info-value"><?php echo $denuncia['dpi']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Edad</span>
                    <span class="info-value"><?php echo $denuncia['edad']; ?> años</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Género</span>
                    <span class="info-value"><?php echo $denuncia['genero']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Celular</span>
                    <span class="info-value"><?php echo $denuncia['celular']; ?></span>
                </div>
            </div>

            <!-- Fotos DPI -->
            <h3><i class="fas fa-id-card"></i> Documentos DPI</h3>
            <div class="galeria-fotos">
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_frontal']); ?>" data-lightbox="dpi">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_frontal']); ?>" alt="DPI Frontal">
                        <div class="foto-label">DPI Frontal</div>
                    </a>
                </div>
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_trasera']); ?>" data-lightbox="dpi">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_trasera']); ?>" alt="DPI Trasera">
                        <div class="foto-label">DPI Trasera</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Ubicación de la Infracción -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-map-marker-alt"></i> Ubicación de la Infracción</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Dirección</span>
                    <span class="info-value"><?php echo htmlspecialchars($denuncia['direccion_infraccion']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Departamento</span>
                    <span class="info-value"><?php echo $denuncia['departamento']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Municipio</span>
                    <span class="info-value"><?php echo $denuncia['municipio']; ?></span>
                </div>
                <?php if (!empty($denuncia['color_casa'])): ?>
                    <div class="info-item">
                        <span class="info-label">Color de Casa</span>
                        <span class="info-value"><?php echo $denuncia['color_casa']; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($denuncia['color_puerta'])): ?>
                    <div class="info-item">
                        <span class="info-label">Color de Puerta</span>
                        <span class="info-value"><?php echo $denuncia['color_puerta']; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Foto Fachada -->
            <h3><i class="fas fa-home"></i> Foto de la Fachada</h3>
            <div class="galeria-fotos">
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_fachada']); ?>" data-lightbox="fachada">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_fachada']); ?>" alt="Fachada">
                        <div class="foto-label">Fachada del Lugar</div>
                    </a>
                </div>
            </div>

            <!-- Mapa -->
            <?php if (!empty($denuncia['latitud']) && !empty($denuncia['longitud'])): ?>
                <h3><i class="fas fa-map"></i> Ubicación en Mapa</h3>
                <div class="mapa-container">
                    <div id="mapa" style="width: 100%; height: 100%;"></div>
                </div>
                <script>
                    function initMap() {
                        const ubicacion = {
                            lat: <?php echo $denuncia['latitud']; ?>,
                            lng: <?php echo $denuncia['longitud']; ?>
                        };

                        const map = new google.maps.Map(document.getElementById('mapa'), {
                            zoom: 16,
                            center: ubicacion
                        });

                        new google.maps.Marker({
                            position: ubicacion,
                            map: map,
                            title: 'Ubicación de la denuncia'
                        });
                    }

                    window.addEventListener('load', initMap);
                </script>
            <?php endif; ?>
        </div>

        <!-- Información del Animal -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-paw"></i> Información del Animal</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Especie</span>
                    <span class="info-value destacado"><?php echo $denuncia['especie_animal']; ?></span>
                </div>
                <?php if (!empty($denuncia['especie_otro'])): ?>
                    <div class="info-item">
                        <span class="info-label">Otra Especie</span>
                        <span class="info-value"><?php echo $denuncia['especie_otro']; ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Cantidad</span>
                    <span class="info-value"><?php echo $denuncia['cantidad']; ?></span>
                </div>
                <?php if (!empty($denuncia['raza'])): ?>
                    <div class="info-item">
                        <span class="info-label">Raza</span>
                        <span class="info-value"><?php echo $denuncia['raza']; ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($denuncia['nombre_responsable'])): ?>
                    <div class="info-item">
                        <span class="info-label">Nombre del Responsable</span>
                        <span class="info-value"><?php echo htmlspecialchars($denuncia['nombre_responsable']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Infracciones -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-exclamation-triangle"></i> Tipos de Infracción</h2>
            <div class="lista-infracciones">
                <?php if (count($infracciones) > 0): ?>
                    <?php foreach ($infracciones as $infraccion): ?>
                        <span class="infraccion-badge">
                            <?php
                            echo htmlspecialchars($infraccion['tipo_infraccion']);
                            if (!empty($infraccion['infraccion_otro'])) {
                                echo ' - ' . htmlspecialchars($infraccion['infraccion_otro']);
                            }
                            ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b;">No hay infracciones registradas</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Descripción -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-align-left"></i> Descripción Detallada</h2>
            <p class="info-value"><?php echo nl2br(htmlspecialchars($denuncia['descripcion_detallada'])); ?></p>
        </div>

        <!-- Evidencias -->
        <?php if (count($evidencias) > 0): ?>
            <div class="seccion-detalle">
                <h2><i class="fas fa-paperclip"></i> Evidencias Adjuntas</h2>
                <div class="galeria-fotos">
                    <?php foreach ($evidencias as $evidencia): ?>
                        <?php if ($evidencia['tipo_evidencia'] == 'foto'): ?>
                            <!-- Evidencia tipo imagen -->
                            <div class="foto-item">
                                <a href="<?php echo obtenerRutaArchivo($evidencia['ruta_archivo']); ?>" data-lightbox="evidencias">
                                    <img src="<?php echo obtenerRutaArchivo($evidencia['ruta_archivo']); ?>" alt="Evidencia">
                                    <div class="foto-label">
                                        <i class="fas fa-image"></i> Imagen
                                    </div>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Evidencia tipo archivo -->
                            <div class="foto-item archivo-card">
                                <a href="<?php echo obtenerRutaArchivo($evidencia['ruta_archivo']); ?>" target="_blank" download>
                                    <div class="archivo-preview">
                                        <?php
                                        $extension = strtolower(pathinfo($evidencia['nombre_archivo'], PATHINFO_EXTENSION));
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
                                            style="font-size: 64px; color: <?php echo $colorIcono; ?>;"></i>
                                        <p style="margin-top: 10px; font-size: 12px; color: #6B7280; word-break: break-word;">
                                            <?php echo htmlspecialchars($evidencia['nombre_archivo']); ?>
                                        </p>
                                    </div>
                                    <div class="foto-label">
                                        <i class="fas fa-download"></i> Descargar
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="botones-accion">
            <a href="<?php echo $urlRetorno; ?>" class="btn-accion btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
            <a href="procesar.php?id=<?php echo $denuncia['id_denuncia']; ?>" class="btn-accion btn-editar">
                <i class="fas fa-clipboard-check"></i> Procesar Denuncia
            </a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Lightbox2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
</body>

</html>