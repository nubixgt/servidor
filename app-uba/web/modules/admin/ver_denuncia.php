<?php
// web/modules/admin/ver_denuncia.php
require_once '../../config/database.php';
require_once '../../includes/verificar_sesion.php';

// Verificar que sea administrador
verificarRol('admin');

// Obtener ID de la denuncia
$id_denuncia = $_GET['id'] ?? 0;

if ($id_denuncia <= 0) {
    header("Location: dashboard.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener información de la denuncia
$sql = "SELECT * FROM denuncias WHERE id_denuncia = :id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':id', $id_denuncia);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: dashboard.php");
    exit;
}

$denuncia = $stmt->fetch();

// Obtener infracciones
$sqlInfracciones = "SELECT tipo_infraccion, infraccion_otro 
                    FROM infracciones_denuncia 
                    WHERE id_denuncia = :id";
$stmtInfracciones = $db->prepare($sqlInfracciones);
$stmtInfracciones->bindParam(':id', $id_denuncia);
$stmtInfracciones->execute();
$infracciones = $stmtInfracciones->fetchAll();

// Obtener evidencias
$sqlEvidencias = "SELECT * FROM evidencias_denuncia 
                  WHERE id_denuncia = :id 
                  ORDER BY fecha_subida ASC";
$stmtEvidencias = $db->prepare($sqlEvidencias);
$stmtEvidencias->bindParam(':id', $id_denuncia);
$stmtEvidencias->execute();
$evidencias = $stmtEvidencias->fetchAll();

// Separar evidencias por tipo
$imagenes = [];
$archivos = [];

foreach ($evidencias as $evidencia) {
    if ($evidencia['tipo_archivo'] == 'imagen') {
        $imagenes[] = $evidencia;
    } else {
        $archivos[] = $evidencia;
    }
}

// Función helper para rutas de archivos
function obtenerRutaArchivo($rutaBD) {
    // Limpiar cualquier ../ o ./ del inicio
    $rutaLimpia = str_replace(['../', './'], '', $rutaBD);

    // Si empieza con 'uploads/', agregar el backend/
    if (strpos($rutaLimpia, 'uploads/') === 0) {
        return "/AppUBA/backend/" . $rutaLimpia;
    }

    // Si ya tiene backend/, solo agregar /AppUBA/
    if (strpos($rutaLimpia, 'backend/') === 0) {
        return "/AppUBA/" . $rutaLimpia;
    }

    // Por defecto
    return "/AppUBA/backend/" . $rutaLimpia;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Denuncia #<?php echo $id_denuncia; ?> - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../css/ver_denuncia_admin.css">
    
    <!-- Lightbox para imágenes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    
    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g"></script>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header -->
        <div class="welcome-section">
            <h1>
                <i class="fas fa-file-alt"></i> Denuncia #<?php echo $denuncia['id_denuncia']; ?>
                <span class="badge badge-<?php echo $denuncia['estado_denuncia']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                </span>
            </h1>
            <p>Detalles completos de la denuncia registrada</p>
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
                    <span class="info-value destacado"><?php echo htmlspecialchars($denuncia['nombre_completo']); ?></span>
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
            
            <!-- Fotos del DPI -->
            <h3><i class="fas fa-id-card"></i> Fotos del DPI</h3>
            <div class="galeria-fotos">
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_frontal']); ?>" data-lightbox="dpi" data-title="DPI - Frente">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_frontal']); ?>" alt="DPI Frontal">
                        <div class="foto-label">DPI Frontal</div>
                    </a>
                </div>
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_trasera']); ?>" data-lightbox="dpi" data-title="DPI - Reverso">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_dpi_trasera']); ?>" alt="DPI Trasero">
                        <div class="foto-label">DPI Trasero</div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Ubicación -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-map-marker-alt"></i> Ubicación del Incidente</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nombre del Responsable</span>
                    <span class="info-value"><?php echo $denuncia['nombre_responsable'] ?: 'No especificado'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dirección</span>
                    <span class="info-value"><?php echo htmlspecialchars($denuncia['direccion_infraccion']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Departamento</span>
                    <span class="info-value destacado"><?php echo $denuncia['departamento']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Municipio</span>
                    <span class="info-value destacado"><?php echo $denuncia['municipio']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Color de Casa</span>
                    <span class="info-value"><?php echo $denuncia['color_casa'] ?: 'No especificado'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Color de Puerta</span>
                    <span class="info-value"><?php echo $denuncia['color_puerta'] ?: 'No especificado'; ?></span>
                </div>
            </div>
            
            <!-- Foto de Fachada -->
            <h3><i class="fas fa-home"></i> Foto de la Fachada</h3>
            <div class="galeria-fotos">
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($denuncia['foto_fachada']); ?>" data-lightbox="fachada" data-title="Fachada del lugar">
                        <img src="<?php echo obtenerRutaArchivo($denuncia['foto_fachada']); ?>" alt="Fachada">
                        <div class="foto-label">Fachada</div>
                    </a>
                </div>
            </div>
            
            <!-- Mapa -->
            <?php if ($denuncia['latitud'] && $denuncia['longitud']): ?>
            <h3><i class="fas fa-map"></i> Ubicación en el Mapa</h3>
            <div class="mapa-container" id="mapa"></div>
            <p class="coordenadas-info">
                <i class="fas fa-map-pin"></i> 
                Coordenadas: <?php echo $denuncia['latitud']; ?>, <?php echo $denuncia['longitud']; ?>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Detalles del Caso -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-paw"></i> Detalles del Caso</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Especie Animal</span>
                    <span class="info-value destacado"><?php echo htmlspecialchars($denuncia['especie_animal']); ?></span>
                </div>
                <?php if ($denuncia['especie_otro']): ?>
                <div class="info-item">
                    <span class="info-label">Especificación de Especie</span>
                    <span class="info-value"><?php echo htmlspecialchars($denuncia['especie_otro']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Cantidad de Animales</span>
                    <span class="info-value destacado"><?php echo $denuncia['cantidad']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Raza</span>
                    <span class="info-value"><?php echo $denuncia['raza'] ?: 'No especificado'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Denuncia</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($denuncia['fecha_denuncia'])); ?></span>
                </div>
            </div>
            
            <!-- Descripción -->
            <div class="descripcion-detalle">
                <span class="info-label">Descripción Detallada</span>
                <p><?php echo nl2br(htmlspecialchars($denuncia['descripcion_detallada'])); ?></p>
            </div>
            
            <!-- Infracciones -->
            <h3><i class="fas fa-exclamation-triangle"></i> Tipos de Infracción</h3>
            <div class="lista-infracciones">
                <?php foreach ($infracciones as $infraccion): ?>
                    <span class="infraccion-badge">
                        <?php echo htmlspecialchars($infraccion['tipo_infraccion']); ?>
                        <?php if ($infraccion['infraccion_otro']): ?>
                            <br><small>(<?php echo htmlspecialchars($infraccion['infraccion_otro']); ?>)</small>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Evidencias -->
        <?php if (!empty($imagenes) || !empty($archivos)): ?>
        <div class="seccion-detalle">
            <h2><i class="fas fa-images"></i> Evidencias</h2>
            
            <!-- Fotos de Evidencia -->
            <?php if (!empty($imagenes)): ?>
            <h3><i class="fas fa-camera"></i> Fotografías (<?php echo count($imagenes); ?>)</h3>
            <div class="galeria-fotos">
                <?php foreach ($imagenes as $index => $imagen): ?>
                <div class="foto-item">
                    <a href="<?php echo obtenerRutaArchivo($imagen['ruta_archivo']); ?>" data-lightbox="evidencias" data-title="Evidencia <?php echo $index + 1; ?>">
                        <img src="<?php echo obtenerRutaArchivo($imagen['ruta_archivo']); ?>" alt="Evidencia <?php echo $index + 1; ?>">
                        <div class="foto-label">Evidencia <?php echo $index + 1; ?></div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Archivos Adjuntos -->
            <?php if (!empty($archivos)): ?>
            <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos (<?php echo count($archivos); ?>)</h3>
            <ul class="lista-archivos">
                <?php foreach ($archivos as $archivo): ?>
                <li class="archivo-item">
                    <div class="archivo-info">
                        <i class="archivo-icono <?php echo $archivo['tipo_archivo']; ?> fas fa-file-<?php 
                            echo $archivo['tipo_archivo'] == 'pdf' ? 'pdf' : 
                                ($archivo['tipo_archivo'] == 'doc' ? 'word' : 
                                ($archivo['tipo_archivo'] == 'audio' ? 'audio' : 
                                ($archivo['tipo_archivo'] == 'video' ? 'video' : 'alt'))); 
                        ?>"></i>
                        <div class="archivo-detalles">
                            <span class="archivo-nombre"><?php echo htmlspecialchars($archivo['nombre_archivo']); ?></span>
                            <span class="archivo-tamanio"><?php echo number_format($archivo['tamanio_kb'], 2); ?> KB</span>
                        </div>
                    </div>
                    <a href="<?php echo obtenerRutaArchivo($archivo['ruta_archivo']); ?>" download class="btn-descargar">
                        <i class="fas fa-download"></i> Descargar
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Botones de Acción -->
        <div class="botones-accion">
            <button class="btn-accion btn-volver" onclick="window.location.href='dashboard.php'">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </button>
            <button class="btn-accion btn-editar" onclick="window.location.href='editar_denuncia.php?id=<?php echo $id_denuncia; ?>'">
                <i class="fas fa-edit"></i> Editar Denuncia
            </button>
            <button class="btn-accion btn-imprimir" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>
    
    <script src="../../js/dashboard_admin.js"></script>
    
    <?php if ($denuncia['latitud'] && $denuncia['longitud']): ?>
    <script>
        // Inicializar mapa
        function initMap() {
            const ubicacion = {
                lat: <?php echo $denuncia['latitud']; ?>,
                lng: <?php echo $denuncia['longitud']; ?>
            };
            
            const map = new google.maps.Map(document.getElementById('mapa'), {
                zoom: 16,
                center: ubicacion
            });
            
            const marker = new google.maps.Marker({
                position: ubicacion,
                map: map,
                title: 'Ubicación de la denuncia'
            });
        }
        
        // Cargar mapa cuando la página esté lista
        window.onload = initMap;
    </script>
    <?php endif; ?>
</body>
</html>