<?php
// web/modules/admin/servicios/index.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

$database = new Database();
$db = $database->getConnection();

// Obtener todos los servicios
$sql = "SELECT s.*, u.nombre_completo as creador 
        FROM servicios_autorizados s 
        LEFT JOIN usuarios_web u ON s.creado_por = u.id_usuario 
        ORDER BY s.fecha_creacion DESC";
$stmt = $db->query($sql);
$servicios = $stmt->fetchAll();

// ============================================
// ESTADÍSTICAS PARA TARJETAS - OPCIÓN 2
// ============================================

// 1. Total de servicios
$totalServicios = count($servicios);

// 2. Servicios activos
$sqlActivos = "SELECT COUNT(*) as total FROM servicios_autorizados WHERE estado = 'activo'";
$stmtActivos = $db->query($sqlActivos);
$activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['total'];

// 3. Servicios inactivos
$sqlInactivos = "SELECT COUNT(*) as total FROM servicios_autorizados WHERE estado = 'inactivo'";
$stmtInactivos = $db->query($sqlInactivos);
$inactivos = $stmtInactivos->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Mejor calificados (4.5 o más estrellas)
$sqlMejorCalificados = "SELECT COUNT(*) as total FROM servicios_autorizados WHERE calificacion >= 4.5";
$stmtMejorCalificados = $db->query($sqlMejorCalificados);
$mejorCalificados = $stmtMejorCalificados->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios Autorizados - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/servicios_admin.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
</head>
<body>
    <?php include '../../../includes/navbar_admin.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header -->
        <div class="welcome-section">
            <h1><i class="fas fa-store"></i> Servicios Autorizados</h1>
            <p>Clínicas veterinarias registradas para la aplicación móvil</p>
        </div>
        
        <!-- Botón Nuevo Servicio -->
        <div style="margin-bottom: 25px;">
            <a href="crear.php" class="btn-crear-noticia">
                <i class="fas fa-plus-circle"></i>
                Nuevo Servicio
            </a>
        </div>
        
        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalServicios; ?></h3>
                    <p>Total de Servicios</p>
                </div>
            </div>
            
            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $activos; ?></h3>
                    <p>Activos</p>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $inactivos; ?></h3>
                    <p>Inactivos</p>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $mejorCalificados; ?></h3>
                    <p>Mejor Calificados (4.5+)</p>
                </div>
            </div>
        </div>
        
        <div class="table-section">
            <h3>
                <i class="fas fa-list"></i> Listado de Servicios
            </h3>
            <div class="table-responsive">
                <table id="tablaServicios" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Servicios</th>
                            <th>Calificación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $servicio): ?>
                        <tr>
                            <td>#<?php echo $servicio['id_servicio']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($servicio['direccion']); ?></td>
                            <td>
                                <i class="fas fa-phone"></i> <?php echo $servicio['telefono']; ?>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($servicio['servicios_ofrecidos'], 0, 50)); ?>...</small>
                            </td>
                            <td>
                                <span class="rating">
                                    <i class="fas fa-star"></i> <?php echo $servicio['calificacion']; ?>
                                    <small>(<?php echo $servicio['total_calificaciones']; ?>)</small>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $servicio['estado']; ?>">
                                    <?php echo ucfirst($servicio['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-action btn-view" onclick="verServicio(<?php echo $servicio['id_servicio']; ?>)" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="window.location.href='editar.php?id=<?php echo $servicio['id_servicio']; ?>'" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="eliminarServicio(<?php echo $servicio['id_servicio']; ?>)" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../../../js/dashboard_admin.js"></script>
    <script src="../../../js/servicios_admin.js"></script>
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaServicios').DataTable({
                responsive: false,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i> Copiar',
                        className: 'btn-dt'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn-dt',
                        title: 'Servicios_Autorizados_AppUBA'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn-dt',
                        title: 'Servicios Autorizados - AppUBA',
                        orientation: 'landscape',
                        pageSize: 'LEGAL'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn-dt'
                    }
                ],
                columnDefs: [
                    { orderable: false, targets: 7 }
                ]
            });
        });
        
        function verServicio(id) {
            Swal.fire({
                title: 'Ver Servicio',
                html: '<p>Redirigiendo a la vista detallada...</p>',
                icon: 'info',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `ver.php?id=${id}`;
            });
        }
        
        function eliminarServicio(id) {
            Swal.fire({
                title: '¿Eliminar servicio?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `eliminar.php?id=${id}`;
                }
            });
        }
    </script>
    
    <?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
            confirmButtonColor: '#1E3A8A',
            timer: 3000
        });
    </script>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>',
            confirmButtonColor: '#DC2626'
        });
    </script>
    <?php endif; ?>
</body>
</html>