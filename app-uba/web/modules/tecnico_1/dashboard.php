<?php
// web/modules/tecnico_1/dashboard.php
require_once '../../config/database.php';
require_once '../../config/workflow.php';
require_once '../../includes/verificar_sesion.php';

verificarRol('tecnico_1');

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener denuncias para área legal
    $etapas = WorkflowDenuncias::$etapasPorRol['tecnico_1'];
    $denuncias = WorkflowDenuncias::obtenerDenunciasPorEtapa($etapas, $db);

    // ============================================
    // ESTADÍSTICAS PARA TARJETAS
    // ============================================

    // 1. Total pendientes en mi área
    $totalPendientes = count($denuncias);

    // 2. Urgentes (más de 5 días sin procesar)
    $fechaLimite = date('Y-m-d H:i:s', strtotime('-5 days'));
    $sqlUrgentes = "SELECT COUNT(*) as total 
                    FROM denuncias d
                    WHERE d.estado_denuncia IN ('pendiente', 'en_proceso')
                    AND d.fecha_denuncia <= :fecha_limite
                    AND COALESCE(
                        (SELECT s.etapa_actual 
                         FROM seguimiento_denuncias s 
                         WHERE s.id_denuncia = d.id_denuncia 
                         ORDER BY s.fecha_procesamiento DESC 
                         LIMIT 1),
                        'pendiente_revision'
                    ) IN ('" . implode("','", $etapas) . "')";
    $stmtUrgentes = $db->prepare($sqlUrgentes);
    $stmtUrgentes->bindParam(':fecha_limite', $fechaLimite);
    $stmtUrgentes->execute();
    $urgentes = $stmtUrgentes->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Procesadas hoy por el usuario actual
    $hoy = date('Y-m-d');
    $sqlProcesadasHoy = "SELECT COUNT(*) as total 
                         FROM seguimiento_denuncias 
                         WHERE procesado_por = :usuario_id 
                         AND DATE(fecha_procesamiento) = :hoy
                         AND etapa IN ('area_legal')";
    $stmtProcesadasHoy = $db->prepare($sqlProcesadasHoy);
    $stmtProcesadasHoy->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmtProcesadasHoy->bindParam(':hoy', $hoy);
    $stmtProcesadasHoy->execute();
    $procesadasHoy = $stmtProcesadasHoy->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Sin revisar (sin seguimiento)
    $sqlSinRevisar = "SELECT COUNT(*) as total 
                      FROM denuncias d
                      WHERE d.estado_denuncia IN ('pendiente', 'en_proceso')
                      AND NOT EXISTS (
                          SELECT 1 FROM seguimiento_denuncias s 
                          WHERE s.id_denuncia = d.id_denuncia
                      )
                      AND COALESCE(
                          (SELECT s2.etapa_actual 
                           FROM seguimiento_denuncias s2 
                           WHERE s2.id_denuncia = d.id_denuncia 
                           ORDER BY s2.fecha_procesamiento DESC 
                           LIMIT 1),
                          'pendiente_revision'
                      ) IN ('" . implode("','", $etapas) . "')";
    $stmtSinRevisar = $db->query($sqlSinRevisar);
    $sinRevisar = $stmtSinRevisar->fetch(PDO::FETCH_ASSOC)['total'];

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar las denuncias: ' . $e->getMessage();
    $denuncias = [];
    $totalPendientes = 0;
    $urgentes = 0;
    $procesadasHoy = 0;
    $sinRevisar = 0;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Legal - Técnico 1 - AppUBA</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../css/dashboard_admin.css">
    <link rel="stylesheet" href="../../css/areas_tecnicas.css">
    <link rel="stylesheet" href="../../css/dashboard_tecnicos.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
    <?php include '../../includes/navbar_tecnico1.php'; ?>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1><i class="fas fa-balance-scale"></i> Mi Área: Área Legal</h1>
            <p>Revisión legal de denuncias de maltrato animal</p>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php echo $totalPendientes; ?>
                    </h3>
                    <p>Pendientes en mi área</p>
                </div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php echo $urgentes; ?>
                    </h3>
                    <p>Urgentes (+5 días)</p>
                </div>
            </div>

            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php echo $procesadasHoy; ?>
                    </h3>
                    <p>Procesadas hoy</p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php echo $sinRevisar; ?>
                    </h3>
                    <p>Sin revisar</p>
                </div>
            </div>
        </div>

        <!-- Tabla de denuncias -->
        <div class="table-section">
            <h3>
                <i class="fas fa-list"></i> Mis Denuncias Asignadas
            </h3>
            <table id="tablaDenuncias" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Denunciante</th>
                        <th>DPI</th>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Especie Animal</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Etapa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($denuncias as $denuncia): ?>
                        <tr>
                            <td>
                                <?php echo $denuncia['id_denuncia']; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($denuncia['nombre_completo']); ?>
                                </strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($denuncia['dpi']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($denuncia['departamento']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($denuncia['municipio']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($denuncia['especie_animal']); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($denuncia['fecha_denuncia'])); ?>
                            </td>
                            <td>
                                <?php
                                $estadoClass = '';
                                switch ($denuncia['estado_denuncia']) {
                                    case 'pendiente':
                                        $estadoClass = 'badge-pendiente';
                                        break;
                                    case 'en_proceso':
                                        $estadoClass = 'badge-en-proceso';
                                        break;
                                    case 'resuelta':
                                        $estadoClass = 'badge-resuelta';
                                        break;
                                    case 'rechazada':
                                        $estadoClass = 'badge-rechazada';
                                        break;
                                }
                                ?>
                                <span class="badge-etapa <?php echo $estadoClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-etapa badge-en-proceso">
                                    <?php echo WorkflowDenuncias::$nombresEtapas[$denuncia['etapa_actual']]; ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="verDetalleDenuncia(<?php echo $denuncia['id_denuncia']; ?>)"
                                    class="btn-action btn-ver" title="Ver detalle">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                <a href="/app-uba/web/modules/admin/area_legal/procesar.php?id=<?php echo $denuncia['id_denuncia']; ?>"
                                    class="btn-action btn-procesar" title="Procesar denuncia">
                                    <i class="fas fa-clipboard-check"></i> Procesar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JS personalizado -->
    <script src="../../js/dashboard_tecnico1.js"></script>

    <script>
        // Función para ver detalle con SweetAlert
        function verDetalleDenuncia(id) {
            Swal.fire({
                title: 'Ver Denuncia',
                html: '<p>Redirigiendo a la vista detallada...</p>',
                icon: 'info',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = '/app-uba/web/modules/admin/area_legal/detalle_denuncia.php?id=' + id;
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