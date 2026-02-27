<?php
// web/modules/admin/servicios/crear.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Servicio - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/servicios_admin.css">
    
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g&libraries=places"></script>
</head>
<body>
    <?php include '../../../includes/navbar_admin.php'; ?>
    
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-plus-circle"></i> Nuevo Servicio Autorizado</h1>
            <p class="subtitle">Registrar clínica o veterinaria para la aplicación móvil</p>
        </div>
        
        <form id="formCrear" method="POST" action="guardar.php" enctype="multipart/form-data">
            <!-- Información Básica -->
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nombre"><i class="fas fa-store"></i> Nombre de la Clínica/Veterinaria *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" 
                               placeholder="Ej: Clínica Veterinaria Mascota Feliz" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono *</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" 
                            placeholder="Ej: 2334-5678" required maxlength="9" 
                            pattern="[0-9]{4}-[0-9]{4}"
                            title="Formato: 1234-5678 (8 dígitos)">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado"><i class="fas fa-toggle-on"></i> Estado *</label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="servicios_ofrecidos"><i class="fas fa-list"></i> Servicios Ofrecidos *</label>
                        <textarea name="servicios_ofrecidos" id="servicios_ofrecidos" class="form-control" 
                                  placeholder="Ej: Consulta, Cirugía, Emergencias 24/7, Vacunación, Peluquería" 
                                  rows="3" required></textarea>
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
                        <textarea name="direccion" id="direccion" class="form-control" 
                                  placeholder="Ej: 5ta Avenida 12-53 Zona 10, Guatemala" 
                                  rows="2" required></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-map"></i> Seleccionar Ubicación en el Mapa</label>
                        <p style="color: #6B7280; font-size: 14px; margin-bottom: 10px;">
                            Busca la dirección o arrastra el marcador para ajustar la ubicación exacta
                        </p>
                        
                        <!-- Buscador de direcciones -->
                        <input type="text" id="searchBox" class="form-control" 
                               placeholder="🔍 Buscar dirección en el mapa..." 
                               style="margin-bottom: 10px;">
                        
                        <div class="mapa-form" id="mapa"></div>
                        
                        <input type="hidden" name="latitud" id="latitud" required>
                        <input type="hidden" name="longitud" id="longitud" required>
                        
                        <p class="coordenadas-info">
                            <i class="fas fa-map-pin"></i> 
                            Coordenadas: 
                            <span id="lat-display">--</span>, 
                            <span id="lng-display">--</span>
                            <small style="display: block; margin-top: 5px; font-style: italic;">
                                (Las coordenadas se capturan automáticamente al mover el marcador)
                            </small>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Imagen (Opcional) -->
            <div class="form-section">
                <h2><i class="fas fa-image"></i> Imagen (Opcional)</h2>
                <div class="form-group">
                    <label for="imagen"><i class="fas fa-camera"></i> Foto de la Clínica</label>
                    <input type="file" name="imagen" id="imagen" class="form-control" 
                           accept="image/jpeg,image/png,image/jpg">
                    <small style="color: #6B7280; margin-top: 5px; display: block;">
                        Formato: JPG, JPEG, PNG | Tamaño máximo: 2MB
                    </small>
                    
                    <div id="preview" style="margin-top: 15px; display: none;">
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
                    <i class="fas fa-save"></i> Guardar Servicio
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
        
        // Inicializar mapa
        function initMap() {
            // Ubicación por defecto: Ciudad de Guatemala
            const defaultLocation = { lat: 14.6349, lng: -90.5069 };
            
            geocoder = new google.maps.Geocoder();
            
            map = new google.maps.Map(document.getElementById('mapa'), {
                zoom: 13,
                center: defaultLocation
            });
            
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true,
                title: 'Arrastra para ajustar la ubicación'
            });
            
            // Actualizar coordenadas al arrastrar
            marker.addListener('dragend', function(event) {
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });
            
            // Click en el mapa para mover el marcador
            map.addListener('click', function(event) {
                marker.setPosition(event.latLng);
                updateCoordinates(event.latLng.lat(), event.latLng.lng());
            });
            
            // Configurar el buscador de direcciones
            const input = document.getElementById('searchBox');
            searchBox = new google.maps.places.SearchBox(input);
            
            // Bias hacia Guatemala
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });
            
            searchBox.addListener('places_changed', function() {
                const places = searchBox.getPlaces();
                
                if (places.length == 0) {
                    return;
                }
                
                const place = places[0];
                
                if (!place.geometry || !place.geometry.location) {
                    return;
                }
                
                // Mover el mapa y el marcador a la ubicación buscada
                map.setCenter(place.geometry.location);
                map.setZoom(17);
                marker.setPosition(place.geometry.location);
                
                updateCoordinates(
                    place.geometry.location.lat(),
                    place.geometry.location.lng()
                );
                
                // Opcional: actualizar el campo de dirección
                if (place.formatted_address) {
                    document.getElementById('direccion').value = place.formatted_address;
                }
            });
            
            // Establecer coordenadas iniciales
            updateCoordinates(defaultLocation.lat, defaultLocation.lng);
        }
        
        function updateCoordinates(lat, lng) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lng;
            document.getElementById('lat-display').textContent = lat.toFixed(6);
            document.getElementById('lng-display').textContent = lng.toFixed(6);
        }
        
        // Cargar mapa al iniciar
        window.onload = initMap;
        
        // Preview de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño (2MB)
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
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Confirmar cancelación
        function confirmarCancelar() {
            Swal.fire({
                title: '¿Cancelar registro?',
                text: 'Los datos ingresados se perderán',
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
        
        // Validar formulario antes de enviar
        document.getElementById('formCrear').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar coordenadas
            const lat = parseFloat(document.getElementById('latitud').value);
            const lng = parseFloat(document.getElementById('longitud').value);
            
            if (!lat || !lng) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ubicación requerida',
                    text: 'Por favor selecciona la ubicación en el mapa',
                    confirmButtonColor: '#059669'
                });
                return;
            }
            
            // Confirmar antes de guardar
            Swal.fire({
                title: '¿Guardar servicio?',
                text: 'Se registrará el nuevo servicio autorizado',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Guardando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Enviar formulario
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>