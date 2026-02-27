<?php
// web/modules/admin/noticias/ver.php
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
        return "/app-uba/backend/" . $rutaLimpia;
    }

    if (strpos($rutaLimpia, 'backend/') === 0) {
        return "/app-uba/" . $rutaLimpia;
    }

    return "/app-uba/backend/" . $rutaLimpia;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT n.*, u.nombre_completo as creador
            FROM noticias n
            LEFT JOIN usuarios_web u ON n.creado_por = u.id_usuario
            WHERE n.id_noticia = :id";

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

// Función para obtener clase de badge según categoría
function getBadgeCategoria($categoria)
{
    $badges = [
        'Campaña' => 'badge-campana',
        'Rescate' => 'badge-rescate',
        'Legislación' => 'badge-legislacion',
        'Alerta' => 'badge-alerta',
        'Evento' => 'badge-evento',
        'Otro' => 'badge-otro'
    ];
    return $badges[$categoria] ?? 'badge-otro';
}

function getBadgeEstado($estado)
{
    $badges = [
        'publicada' => 'badge-publicada',
        'borrador' => 'badge-borrador',
        'archivada' => 'badge-archivada'
    ];
    return $badges[$estado] ?? 'badge-publicada';
}

function getBadgePrioridad($prioridad)
{
    $badges = [
        'normal' => 'badge-normal',
        'importante' => 'badge-importante',
        'urgente' => 'badge-urgente'
    ];
    return $badges[$prioridad] ?? 'badge-normal';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($noticia['titulo']); ?> - AppUBA</title>

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

    <div class="dashboard-container">
        <div class="noticia-card">

            <!-- Imagen de la noticia -->
            <?php if (!empty($noticia['imagen_url'])): ?>
                <img src="<?php echo obtenerRutaArchivo($noticia['imagen_url']); ?>"
                    alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="noticia-imagen">
            <?php endif; ?>

            <!-- Meta información -->
            <div class="noticia-meta">
                <span class="badge-categoria <?php echo getBadgeCategoria($noticia['categoria']); ?>">
                    <?php echo htmlspecialchars($noticia['categoria']); ?>
                </span>
                <span class="badge-estado <?php echo getBadgeEstado($noticia['estado']); ?>">
                    <?php echo ucfirst($noticia['estado']); ?>
                </span>
                <span class="badge-prioridad <?php echo getBadgePrioridad($noticia['prioridad']); ?>">
                    <?php echo ucfirst($noticia['prioridad']); ?>
                </span>
            </div>

            <!-- Título -->
            <h1 class="noticia-titulo">
                <?php echo htmlspecialchars($noticia['titulo']); ?>
            </h1>

            <!-- Descripción corta -->
            <p class="noticia-descripcion">
                <?php echo nl2br(htmlspecialchars($noticia['descripcion_corta'])); ?>
            </p>

            <!-- Contenido completo -->
            <div class="noticia-contenido">
                <?php echo nl2br(htmlspecialchars($noticia['contenido_completo'])); ?>
            </div>

            <!-- Información adicional -->
            <div class="noticia-info-grid">
                <div class="info-item">
                    <span class="info-label">Fecha de Publicación</span>
                    <span class="info-value">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="info-label">Creado por</span>
                    <span class="info-value">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($noticia['creador']); ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="info-label">Fecha de Creación</span>
                    <span class="info-value">
                        <i class="fas fa-clock"></i>
                        <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_creacion'])); ?>
                    </span>
                </div>

                <?php if (!empty($noticia['fecha_modificacion'])): ?>
                    <div class="info-item">
                        <span class="info-label">Última Modificación</span>
                        <span class="info-value">
                            <i class="fas fa-edit"></i>
                            <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_modificacion'])); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botones de acción -->
            <div class="noticia-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver al Listado
                </a>
                <a href="editar.php?id=<?php echo $noticia['id_noticia']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    Editar Noticia
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <style>
        @media print {

            .navbar,
            .noticia-actions,
            .btn {
                display: none !important;
            }

            .noticia-detalle {
                padding: 20px;
            }

            .noticia-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</body>

</html>