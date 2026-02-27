<?php
// web/modules/admin/servicios/ver.php
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
$sql = "SELECT s.*, u.nombre_completo as creador 
        FROM servicios_autorizados s 
        LEFT JOIN usuarios_web u ON s.creado_por = u.id_usuario 
        WHERE s.id_servicio = :id";
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
    <title>Ver Servicio #<?php echo $id_servicio; ?> - AppUBA</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/servicios_admin.css">

    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAv7ePQtbzerQS_OMNa7P3UtrZPMTxck7g"></script>

    <style>
        .detalle-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }

        .header-detalle {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-detalle h1 {
            color: #1F2937;
            font-size: 28px;
            margin: 0;
        }

        .header-detalle h1 i {
            color: #059669;
            margin-right: 10px;
        }

        .badge-estado {
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .badge-activo {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-inactivo {
            background: #FEE2E2;
            color: #991B1B;
        }

        .seccion-detalle {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .seccion-detalle h2 {
            color: #059669;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #E5E7EB;
        }

        .seccion-detalle h2 i {
            margin-right: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: #6B7280;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-value {
            color: #1F2937;
            font-size: 16px;
        }

        .info-value.destacado {
            font-weight: 600;
            color: #059669;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .rating-display i {
            color: #F59E0B;
        }

        .rating-display span {
            font-weight: 700;
            color: #F59E0B;
        }

        .rating-display small {
            color: #6B7280;
            font-size: 14px;
        }

        .mapa-detalle {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #E5E7EB;
            margin-top: 15px;
        }

        .imagen-servicio {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 15px;
        }

        .botones-accion {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-accion {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-volver {
            background: #6B7280;
            color: white;
        }

        .btn-volver:hover {
            background: #4B5563;
            transform: translateY(-2px);
        }

        .btn-editar {
            background: #059669;
            color: white;
        }

        .btn-editar:hover {
            background: #047857;
            transform: translateY(-2px);
        }

        .btn-imprimir {
            background: #3B82F6;
            color: white;
        }

        .btn-imprimir:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }

        .servicios-lista {
            background: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #059669;
            line-height: 1.8;
        }

        @media print {

            .navbar,
            .botones-accion {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php include '../../../includes/navbar_admin.php'; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="header-detalle">
            <h1><i class="fas fa-store"></i> <?php echo htmlspecialchars($servicio['nombre_servicio']); ?></h1>
            <span class="badge-estado badge-<?php echo $servicio['estado']; ?>">
                <?php echo ucfirst($servicio['estado']); ?>
            </span>
        </div>

        <!-- Información Básica -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-info-circle"></i> Información Básica</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">ID del Servicio</span>
                    <span class="info-value">#<?php echo $servicio['id_servicio']; ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Nombre del Servicio</span>
                    <span
                        class="info-value destacado"><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">
                        <i class="fas fa-phone" style="color: #059669;"></i>
                        <?php echo $servicio['telefono']; ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="info-label">Calificación</span>
                    <div class="rating-display">
                        <i class="fas fa-star"></i>
                        <span><?php echo $servicio['calificacion']; ?></span>
                        <small>(<?php echo $servicio['total_calificaciones']; ?> calificaciones)</small>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-label">Fecha de Registro</span>
                    <span
                        class="info-value"><?php echo date('d/m/Y H:i', strtotime($servicio['fecha_creacion'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Registrado Por</span>
                    <span class="info-value"><?php echo htmlspecialchars($servicio['creador']); ?></span>
                </div>
            </div>

            <div class="info-item" style="margin-top: 20px;">
                <span class="info-label">Servicios Ofrecidos</span>
                <div class="servicios-lista">
                    <?php echo nl2br(htmlspecialchars($servicio['servicios_ofrecidos'])); ?>
                </div>
            </div>
        </div>

        <!-- Ubicación -->
        <div class="seccion-detalle">
            <h2><i class="fas fa-map-marker-alt"></i> Ubicación</h2>
            <div class="info-item">
                <span class="info-label">Dirección</span>
                <span class="info-value"><?php echo htmlspecialchars($servicio['direccion']); ?></span>
            </div>

            <?php if ($servicio['latitud'] && $servicio['longitud']): ?>
                <div class="info-item" style="margin-top: 15px;">
                    <span class="info-label">Mapa de Ubicación</span>
                    <div class="mapa-detalle" id="mapa"></div>
                    <p style="margin-top: 10px; color: #6B7280; font-size: 14px;">
                        <i class="fas fa-map-pin" style="color: #DC2626;"></i>
                        Coordenadas: <?php echo $servicio['latitud']; ?>, <?php echo $servicio['longitud']; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Imagen -->
        <?php if ($servicio['imagen_url']): ?>
            <div class="seccion-detalle">
                <h2><i class="fas fa-image"></i> Imagen del Servicio</h2>
                <img src="<?php echo obtenerRutaArchivo($servicio['imagen_url']); ?>"
                    alt="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>" class="imagen-servicio">
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="botones-accion">
            <button class="btn-accion btn-volver" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </button>
            <button class="btn-accion btn-editar"
                onclick="window.location.href='editar.php?id=<?php echo $id_servicio; ?>'">
                <i class="fas fa-edit"></i> Editar Servicio
            </button>
            <button class="btn-accion btn-imprimir" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <script src="../../../js/dashboard_admin.js"></script>

    <?php if ($servicio['latitud'] && $servicio['longitud']): ?>
        <script>
            function initMap() {
                const ubicacion = {
                    lat: <?php echo $servicio['latitud']; ?>,
                    lng: <?php echo $servicio['longitud']; ?>
                };

                const map = new google.maps.Map(document.getElementById('mapa'), {
                    zoom: 16,
                    center: ubicacion
                });

                const marker = new google.maps.Marker({
                    position: ubicacion,
                    map: map,
                    title: '<?php echo addslashes($servicio['nombre_servicio']); ?>'
                });

                // Info Window con información del servicio
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                    <div style="padding: 10px;">
                        <h3 style="margin: 0 0 10px 0; color: #059669;">
                            <i class="fas fa-store"></i> ${marker.getTitle()}
                        </h3>
                        <p style="margin: 5px 0;">
                            <i class="fas fa-phone"></i> <?php echo $servicio['telefono']; ?>
                        </p>
                        <p style="margin: 5px 0;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo addslashes($servicio['direccion']); ?>
                        </p>
                    </div>
                `
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            }

            window.onload = initMap;
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '<?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>',
                confirmButtonColor: '#059669',
                timer: 3000
            });
        </script>
    <?php endif; ?>
</body>

</html>