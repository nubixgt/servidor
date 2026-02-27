<?php
// web/modules/admin/noticias/index.php
require_once '../../../config/database.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol('admin');

// Obtener todas las noticias
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT n.*, u.nombre_completo as creador
            FROM noticias n
            LEFT JOIN usuarios_web u ON n.creado_por = u.id_usuario
            ORDER BY n.fecha_publicacion DESC, n.fecha_creacion DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $noticias = $stmt->fetchAll();
    
    // ============================================
    // ESTADÍSTICAS PARA TARJETAS - OPCIÓN 3 MODIFICADA
    // ============================================
    
    // 1. Total de noticias
    $totalNoticias = count($noticias);
    
    // 2. Publicadas (activas en la app)
    $sqlPublicadas = "SELECT COUNT(*) as total FROM noticias WHERE estado = 'publicada'";
    $stmtPublicadas = $db->query($sqlPublicadas);
    $publicadas = $stmtPublicadas->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 3. Normales (prioridad normal)
    $sqlNormales = "SELECT COUNT(*) as total FROM noticias WHERE prioridad = 'normal'";
    $stmtNormales = $db->query($sqlNormales);
    $normales = $stmtNormales->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 4. Importantes (prioridad importante)
    $sqlImportantes = "SELECT COUNT(*) as total FROM noticias WHERE prioridad = 'importante'";
    $stmtImportantes = $db->query($sqlImportantes);
    $importantes = $stmtImportantes->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 5. Urgentes (prioridad urgente)
    $sqlUrgentes = "SELECT COUNT(*) as total FROM noticias WHERE prioridad = 'urgente'";
    $stmtUrgentes = $db->query($sqlUrgentes);
    $urgentes = $stmtUrgentes->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar las noticias: ' . $e->getMessage();
    $noticias = [];
    $totalNoticias = 0;
    $publicadas = 0;
    $normales = 0;
    $importantes = 0;
    $urgentes = 0;
}

// Función para obtener clase de badge según categoría
function getBadgeCategoria($categoria) {
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

// Función para obtener clase de badge según estado
function getBadgeEstado($estado) {
    $badges = [
        'publicada' => 'badge-publicada',
        'borrador' => 'badge-borrador',
        'archivada' => 'badge-archivada'
    ];
    return $badges[$estado] ?? 'badge-publicada';
}

// Función para obtener clase de badge según prioridad
function getBadgePrioridad($prioridad) {
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
    <title>Noticias - AppUBA</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../../css/noticias_admin.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- DataTables JS -->
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
            <h1><i class="fas fa-newspaper"></i> Gestión de Noticias</h1>
            <p>Administra las noticias que se mostrarán en la aplicación móvil AppUBA</p>
        </div>
        
        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalNoticias; ?></h3>
                    <p>Total de Noticias</p>
                </div>
            </div>
            
            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $publicadas; ?></h3>
                    <p>Publicadas</p>
                </div>
            </div>
            
            <div class="stat-card process">
                <div class="stat-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $normales; ?></h3>
                    <p>Normal</p>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $importantes; ?></h3>
                    <p>Importantes</p>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $urgentes; ?></h3>
                    <p>Urgentes</p>
                </div>
            </div>
        </div>
        
        <!-- Botón crear nueva noticia -->
        <div style="margin-bottom: 25px;">
            <a href="crear.php" class="btn-crear-noticia">
                <i class="fas fa-plus-circle"></i>
                Nueva Noticia
            </a>
        </div>
        
        <!-- Tabla de noticias -->
        <div class="table-section">
            <h3>
                <i class="fas fa-list"></i> Listado de Noticias
            </h3>
            <table id="tablaNoticias" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Fecha Publicación</th>
                        <th>Creado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noticias as $noticia): ?>
                        <tr>
                            <td><?php echo $noticia['id_noticia']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($noticia['titulo']); ?></strong>
                                <br>
                                <small style="color: #64748b;">
                                    <?php echo htmlspecialchars(substr($noticia['descripcion_corta'], 0, 80)); ?>...
                                </small>
                            </td>
                            <td>
                                <span class="badge-categoria <?php echo getBadgeCategoria($noticia['categoria']); ?>">
                                    <?php echo htmlspecialchars($noticia['categoria']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-estado <?php echo getBadgeEstado($noticia['estado']); ?>">
                                    <?php echo ucfirst($noticia['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-prioridad <?php echo getBadgePrioridad($noticia['prioridad']); ?>">
                                    <?php echo ucfirst($noticia['prioridad']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></td>
                            <td><?php echo htmlspecialchars($noticia['creador']); ?></td>
                            <td>
                                <button class="btn-action btn-view" 
                                        onclick="verNoticia(<?php echo $noticia['id_noticia']; ?>)" 
                                        title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" 
                                        onclick="window.location.href='editar.php?id=<?php echo $noticia['id_noticia']; ?>'" 
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" 
                                        onclick="eliminarNoticia(<?php echo $noticia['id_noticia']; ?>, '<?php echo htmlspecialchars($noticia['titulo']); ?>')" 
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- JS personalizado -->
    <script src="../../../js/noticias_admin.js"></script>
    
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaNoticias').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 10,
                responsive: false,
                scrollX: true,
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
                        title: 'Noticias_AppUBA'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn-dt',
                        title: 'Noticias - AppUBA',
                        orientation: 'landscape',
                        pageSize: 'LEGAL'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn-dt'
                    }
                ],
                order: [[5, 'desc']], // Ordenar por fecha de publicación descendente
                columnDefs: [
                    { orderable: false, targets: 7 } // Columna de acciones no ordenable
                ]
            });
        });
        
        // Función para ver noticia con SweetAlert
        function verNoticia(id) {
            Swal.fire({
                title: 'Ver Noticia',
                html: '<p>Redirigiendo a la vista detallada...</p>',
                icon: 'info',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `ver.php?id=${id}`;
            });
        }
        
        // Función para eliminar noticia con SweetAlert
        function eliminarNoticia(id, titulo) {
            Swal.fire({
                title: '¿Eliminar noticia?',
                html: `¿Estás seguro de eliminar <strong>"${titulo}"</strong>?<br><br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `eliminar.php?id=${id}`;
                }
            });
        }
    </script>
    
    <!-- Mensajes de éxito/error -->
    <?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?php echo $_SESSION['success']; ?>',
            confirmButtonColor: '#10b981',
            timer: 3000
        });
    </script>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonColor: '#ef4444'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</body>
</html>