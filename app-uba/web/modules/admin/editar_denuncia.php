<?php
// web/modules/admin/editar_denuncia.php
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

// Obtener infracciones actuales
$sqlInfracciones = "SELECT tipo_infraccion, infraccion_otro 
                    FROM infracciones_denuncia 
                    WHERE id_denuncia = :id";
$stmtInfracciones = $db->prepare($sqlInfracciones);
$stmtInfracciones->bindParam(':id', $id_denuncia);
$stmtInfracciones->execute();
$infracciones = $stmtInfracciones->fetchAll();

// Crear array de infracciones seleccionadas
$infraccionesSeleccionadas = [];
foreach ($infracciones as $infraccion) {
    $infraccionesSeleccionadas[] = $infraccion['tipo_infraccion'];
}

// Catálogo de infracciones
$catalogoInfracciones = [
    'Actos de Crueldad',
    'Abandono',
    'No garantizar condiciones de bienestar',
    'Maltrato físico',
    'Mutilaciones',
    'Envenenar o intoxicar a un animal',
    'Peleas de perros',
    'Técnicas de adiestramiento que causen sufrimiento',
    'Otros'
];

// Departamentos y municipios de Guatemala
$departamentos = [
    'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula', 'El Progreso',
    'Escuintla', 'Guatemala', 'Huehuetenango', 'Izabal', 'Jalapa', 'Jutiapa',
    'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu', 'Sacatepéquez',
    'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez', 'Totonicapán', 'Zacapa'
];

// Estados disponibles
$estados = ['pendiente', 'en_proceso', 'resuelta', 'rechazada'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Denuncia #<?php echo $id_denuncia; ?> - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../css/editar_denuncia_admin.css">
    
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g"></script>
</head>
<body>
    <?php include '../../includes/navbar_admin.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header -->
        <div class="welcome-section">
            <h1>
                <i class="fas fa-edit"></i> Editar Denuncia #<?php echo $id_denuncia; ?>
                <span class="badge badge-<?php echo $denuncia['estado_denuncia']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                </span>
            </h1>
            <p>Modifica la información de la denuncia registrada</p>
        </div>
        
        <form id="formEditar" method="POST" action="actualizar_denuncia.php">
            <input type="hidden" name="id_denuncia" value="<?php echo $id_denuncia; ?>">
            
            <!-- Cambiar Estado -->
            <div class="seccion-form">
                <h2><i class="fas fa-info-circle"></i> Estado de la Denuncia</h2>
                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select name="estado" id="estado" class="form-control" required>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado; ?>" 
                                <?php echo $denuncia['estado_denuncia'] == $estado ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $estado)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Información del Denunciante -->
            <div class="seccion-form">
                <h2><i class="fas fa-user"></i> Información del Denunciante</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['nombre_completo']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dpi">DPI *</label>
                        <input type="text" name="dpi" id="dpi" class="form-control" 
                               value="<?php echo $denuncia['dpi']; ?>" required maxlength="13">
                    </div>
                    
                    <div class="form-group">
                        <label for="edad">Edad *</label>
                        <input type="number" name="edad" id="edad" class="form-control" 
                               value="<?php echo $denuncia['edad']; ?>" required min="18" max="120">
                    </div>
                    
                    <div class="form-group">
                        <label for="genero">Género *</label>
                        <select name="genero" id="genero" class="form-control" required>
                            <option value="Masculino" <?php echo $denuncia['genero'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo $denuncia['genero'] == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="celular">Celular *</label>
                        <input type="text" name="celular" id="celular" class="form-control" 
                               value="<?php echo $denuncia['celular']; ?>" required maxlength="8">
                    </div>
                </div>
            </div>
            
            <!-- Ubicación -->
            <div class="seccion-form">
                <h2><i class="fas fa-map-marker-alt"></i> Ubicación del Incidente</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nombre_responsable">Nombre del Responsable</label>
                        <input type="text" name="nombre_responsable" id="nombre_responsable" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['nombre_responsable'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="direccion">Dirección *</label>
                        <textarea name="direccion" id="direccion" class="form-control" rows="3" required><?php echo htmlspecialchars($denuncia['direccion_infraccion']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="departamento">Departamento *</label>
                        <select name="departamento" id="departamento" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($departamentos as $depto): ?>
                                <option value="<?php echo $depto; ?>" 
                                    <?php echo $denuncia['departamento'] == $depto ? 'selected' : ''; ?>>
                                    <?php echo $depto; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="municipio">Municipio *</label>
                        <input type="text" name="municipio" id="municipio" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['municipio']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="color_casa">Color de Casa</label>
                        <input type="text" name="color_casa" id="color_casa" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['color_casa'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="color_puerta">Color de Puerta</label>
                        <input type="text" name="color_puerta" id="color_puerta" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['color_puerta'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Coordenadas y Mapa -->
                <?php if ($denuncia['latitud'] && $denuncia['longitud']): ?>
                <div class="form-group full-width">
                    <label>Ubicación en el Mapa</label>
                    <div class="mapa-editar" id="mapa"></div>
                    <input type="hidden" name="latitud" id="latitud" value="<?php echo $denuncia['latitud']; ?>">
                    <input type="hidden" name="longitud" id="longitud" value="<?php echo $denuncia['longitud']; ?>">
                    <p class="coordenadas-info">
                        <i class="fas fa-map-pin"></i> 
                        Lat: <span id="lat-display"><?php echo $denuncia['latitud']; ?></span>, 
                        Lng: <span id="lng-display"><?php echo $denuncia['longitud']; ?></span>
                        <small>(Arrastra el marcador para cambiar la ubicación)</small>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Detalles del Caso -->
            <div class="seccion-form">
                <h2><i class="fas fa-paw"></i> Detalles del Caso</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="especie">Especie Animal *</label>
                        <select name="especie" id="especie" class="form-control" required>
                            <option value="Caninos" <?php echo $denuncia['especie_animal'] == 'Caninos' ? 'selected' : ''; ?>>Caninos</option>
                            <option value="Felinos" <?php echo $denuncia['especie_animal'] == 'Felinos' ? 'selected' : ''; ?>>Felinos</option>
                            <option value="Equinos" <?php echo $denuncia['especie_animal'] == 'Equinos' ? 'selected' : ''; ?>>Equinos</option>
                            <option value="Otros" <?php echo $denuncia['especie_animal'] == 'Otros' ? 'selected' : ''; ?>>Otros</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="especie_otro">Especificar Especie (si es Otros)</label>
                        <input type="text" name="especie_otro" id="especie_otro" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['especie_otro'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cantidad">Cantidad *</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" 
                               value="<?php echo $denuncia['cantidad']; ?>" required min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="raza">Raza</label>
                        <input type="text" name="raza" id="raza" class="form-control" 
                               value="<?php echo htmlspecialchars($denuncia['raza'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción Detallada *</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="5" required><?php echo htmlspecialchars($denuncia['descripcion_detallada']); ?></textarea>
                    </div>
                </div>
                
                <!-- Infracciones -->
                <div class="form-group full-width">
                    <label>Tipos de Infracción *</label>
                    <div class="infracciones-grid">
                        <?php foreach ($catalogoInfracciones as $tipo): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="infracciones[]" value="<?php echo $tipo; ?>" 
                                    <?php echo in_array($tipo, $infraccionesSeleccionadas) ? 'checked' : ''; ?>>
                                <span><?php echo $tipo; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group full-width" id="otros-infraccion-grupo" style="display: none;">
                    <label for="infraccion_otro">Especifique la Infracción (Otros)</label>
                    <textarea name="infraccion_otro" id="infraccion_otro" class="form-control" rows="2"><?php 
                        foreach ($infracciones as $inf) {
                            if ($inf['tipo_infraccion'] == 'Otros' && $inf['infraccion_otro']) {
                                echo htmlspecialchars($inf['infraccion_otro']);
                            }
                        }
                    ?></textarea>
                </div>
            </div>
            
            <!-- Botones de Acción -->
            <div class="botones-form">
                <button type="button" class="btn-cancelar" onclick="confirmarCancelar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
    
    <script src="../../js/editar_denuncia_admin.js"></script>
    
    <?php if ($denuncia['latitud'] && $denuncia['longitud']): ?>
    <script>
        let map;
        let marker;
        
        function initMap() {
            const ubicacion = {
                lat: <?php echo $denuncia['latitud']; ?>,
                lng: <?php echo $denuncia['longitud']; ?>
            };
            
            map = new google.maps.Map(document.getElementById('mapa'), {
                zoom: 16,
                center: ubicacion
            });
            
            marker = new google.maps.Marker({
                position: ubicacion,
                map: map,
                draggable: true,
                title: 'Arrastra para cambiar ubicación'
            });
            
            // Actualizar coordenadas al arrastrar
            marker.addListener('dragend', function(event) {
                const newLat = event.latLng.lat();
                const newLng = event.latLng.lng();
                
                document.getElementById('latitud').value = newLat;
                document.getElementById('longitud').value = newLng;
                document.getElementById('lat-display').textContent = newLat.toFixed(6);
                document.getElementById('lng-display').textContent = newLng.toFixed(6);
            });
        }
        
        window.onload = initMap;
    </script>
    <?php endif; ?>
</body>
</html>