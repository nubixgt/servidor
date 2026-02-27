<?php
// web/modules/admin/servicios/editar.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

$id_servicio = $_GET['id'] ?? 0;

if ($id_servicio <= 0) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del servicio
$sql = "SELECT * FROM servicios_autorizados WHERE id_servicio = :id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':id', $id_servicio);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: index.php");
    exit;
}

$servicio = $stmt->fetch();

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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servicio #<?php echo $id_servicio; ?> - AppUBA</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/servicios_admin.css">

    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Google Maps -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g&libraries=places"></script>
</head>

<body>
    <?php include '../../../includes/navbar_admin.php'; ?>

    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-edit"></i> Editar Servicio</h1>
            <p>Modificar información del servicio autorizado</p>
        </div>

        <form id="formEditar" method="POST" action="actualizar.php" enctype="multipart/form-data">
            <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo $servicio['imagen_url']; ?>">

            <!-- Información Básica -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nombre"><i class="fas fa-store"></i> Nombre de la Clínica/Veterinaria *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control"
                            value="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono *</label>
                        <input type="text" name="telefono" id="telefono" class="form-control"
                            value="<?php echo $servicio['telefono']; ?>" required maxlength="9"
                            pattern="[0-9]{4}-[0-9]{4}" title="Formato: 1234-5678 (8 dígitos)">
                    </div>

                    <div class="form-group">
                        <label for="estado"><i class="fas fa-toggle-on"></i> Estado *</label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="activo" <?php echo $servicio['estado'] == 'activo' ? 'selected' : ''; ?>>Activo
                            </option>
                            <option value="inactivo" <?php echo $servicio['estado'] == 'inactivo' ? 'selected' : ''; ?>>
                                Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="servicios_ofrecidos"><i class="fas fa-list"></i> Servicios Ofrecidos *</label>
                        <textarea name="servicios_ofrecidos" id="servicios_ofrecidos" class="form-control" rows="3"
                            required><?php echo htmlspecialchars($servicio['servicios_ofrecidos']); ?></textarea>
                        <small style="color: #6B7280; margin-top: 5px; display: block;">
                            Separar los servicios con comas
                        </small>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="form-section">
                <h2><i class="fas fa-map-marker-alt"></i> Ubicación</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="direccion"><i class="fas fa-map-pin"></i> Dirección Completa *</label>
                        <textarea name="direccion" id="direccion" class="form-control" rows="2"
                            required><?php echo htmlspecialchars($servicio['direccion']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-map"></i> Ajustar Ubicación en el Mapa</label>
                        <p style="color: #6B7280; font-size: 14px; margin-bottom: 10px;">
                            Busca la dirección o arrastra el marcador para ajustar la ubicación
                        </p>

                        <input type="text" id="searchBox" class="form-control"
                            placeholder="🔍 Buscar dirección en el mapa..." style="margin-bottom: 10px;">

                        <div class="mapa-form" id="mapa"></div>

                        <input type="hidden" name="latitud" id="latitud" value="<?php echo $servicio['latitud']; ?>"
                            required>
                        <input type="hidden" name="longitud" id="longitud" value="<?php echo $servicio['longitud']; ?>"
                            required>

                        <p class="coordenadas-info">
                            <i class="fas fa-map-pin"></i>
                            Coordenadas:
                            <span id="lat-display"><?php echo $servicio['latitud']; ?></span>,
                            <span id="lng-display"><?php echo $servicio['longitud']; ?></span>
                            <small style="display: block; margin-top: 5px; font-style: italic;">
                                (Arrastra el marcador para cambiar la ubicación)
                            </small>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Imagen -->
            <div class="form-section">
                <h2><i class="fas fa-image"></i> Imagen</h2>

                <?php if ($servicio['imagen_url']): ?>
                    <div style="margin-bottom: 20px;">
                        <p style="color: #6B7280; margin-bottom: 10px;">Imagen actual:</p>
                        <img src="<?php echo obtenerRutaArchivo($servicio['imagen_url']); ?>" alt="Imagen actual"
                            style="max-width: 300px; border-radius: 8px; border: 2px solid #E5E7EB;">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="imagen"><i class="fas fa-camera"></i> Cambiar Imagen (Opcional)</label>
                    <input type="file" name="imagen" id="imagen" class="form-control"
                        accept="image/jpeg,image/png,image/jpg">
                    <small style="color: #6B7280; margin-top: 5px; display: block;">
                        Formato: JPG, JPEG, PNG | Tamaño máximo: 2MB | Deja vacío si no deseas cambiar la imagen
                    </small>

                    <div id="preview" style="margin-top: 15px; display: none;">
                        <p style="color: #6B7280; margin-bottom: 10px;">Nueva imagen:</p>
                        <img id="preview-img" src="" alt="Vista previa"
                            style="max-width: 300px; border-radius: 8px; border: 2px solid #E5E7EB;">
                    </div>
                </div>
            </div>

            <!-- Botones -->
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

    <script src="../../../js/dashboard_admin.js"></script>
    <script src="../../../js/servicios_admin.js"></script>

    <script>
        let map;
        let marker;
        let geocoder;
        let searchBox;

        function initMap() {
            const currentLocation = {
                lat: <?php echo $servicio['latitud']; ?>,
                lng: <?php echo $servicio['longitud']; ?>
            };

            geocoder = new google.maps.Geocoder();

            map = new google.maps.Map(document.getElementById('mapa'), {
                zoom: 16,
                center: currentLocation
            });

            marker = new google.maps.Marker({
                position: currentLocation,
                map: map,
                draggable: true,
                title: 'Arrastra para ajustar la ubicación'
            });

            marker.addListener('dragend', function (event) {
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });

            map.addListener('click', function (event) {
                marker.setPosition(event.latLng);
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });

            const input = document.getElementById('searchBox');
            searchBox = new google.maps.places.SearchBox(input);

            map.addListener('bounds_changed', function () {
                searchBox.setBounds(map.getBounds());
            });

            searchBox.addListener('places_changed', function () {
                const places = searchBox.getPlaces();

                if (places.length == 0) return;

                const place = places[0];

                if (!place.geometry || !place.geometry.location) return;

                map.setCenter(place.geometry.location);
                map.setZoom(17);
                marker.setPosition(place.geometry.location);

                updateCoordinates(
                    place.geometry.location.lat(),
                    place.geometry.location.lng()
                );

                if (place.formatted_address) {
                    document.getElementById('direccion').value = place.formatted_address;
                }
            });
        }

        function updateCoordinates(lat, lng) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lng;
            document.getElementById('lat-display').textContent = lat.toFixed(6);
            document.getElementById('lng-display').textContent = lng.toFixed(6);
        }

        window.onload = initMap;

        // Preview de imagen
        document.getElementById('imagen').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Imagen muy grande',
                        text: 'La imagen no debe superar 2MB',
                        confirmButtonColor: '#DC2626'
                    });
                    e.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function confirmarCancelar() {
            Swal.fire({
                title: '¿Cancelar edición?',
                text: 'Los cambios no guardados se perderán',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Continuar editando'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        }

        document.getElementById('formEditar').addEventListener('submit', function (e) {
            e.preventDefault();

            const lat = parseFloat(document.getElementById('latitud').value);
            const lng = parseFloat(document.getElementById('longitud').value);

            if (!lat || !lng) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ubicación requerida',
                    text: 'Por favor verifica la ubicación en el mapa',
                    confirmButtonColor: '#059669'
                });
                return;
            }

            Swal.fire({
                title: '¿Guardar cambios?',
                text: 'Se actualizará la información del servicio',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Guardando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    this.submit();
                }
            });
        });
    </script>
</body>

</html>