<?php
// web/modules/admin/dashboard.php
require_once '../../config/database.php';
require_once '../../config/workflow.php';
require_once '../../includes/verificar_sesion.php';

// Verificar que sea administrador
verificarRol('admin');

// Obtener estadísticas
$database = new Database();
$db = $database->getConnection();

// Total de denuncias
$sqlTotal = "SELECT COUNT(*) as total FROM denuncias";
$stmtTotal = $db->query($sqlTotal);
$totalDenuncias = $stmtTotal->fetch()['total'];

// Denuncias pendientes
$sqlPendientes = "SELECT COUNT(*) as total FROM denuncias WHERE estado_denuncia = 'pendiente'";
$stmtPendientes = $db->query($sqlPendientes);
$denunciasPendientes = $stmtPendientes->fetch()['total'];

// Denuncias en proceso
$sqlProceso = "SELECT COUNT(*) as total FROM denuncias WHERE estado_denuncia = 'en_proceso'";
$stmtProceso = $db->query($sqlProceso);
$denunciasProceso = $stmtProceso->fetch()['total'];

// Denuncias resueltas
$sqlResueltas = "SELECT COUNT(*) as total FROM denuncias WHERE estado_denuncia = 'resuelta'";
$stmtResueltas = $db->query($sqlResueltas);
$denunciasResueltas = $stmtResueltas->fetch()['total'];

// Denuncias rechazadas
$sqlRechazadas = "SELECT COUNT(*) as total FROM denuncias WHERE estado_denuncia = 'rechazada'";
$stmtRechazadas = $db->query($sqlRechazadas);
$denunciasRechazadas = $stmtRechazadas->fetch()['total'];

// TODAS las denuncias CON ETAPA ACTUAL para DataTables
$sqlTodasDenuncias = "SELECT d.id_denuncia, d.nombre_completo, d.departamento, d.municipio, 
                       d.especie_animal, d.estado_denuncia, d.fecha_denuncia,
                       COALESCE(
                           (SELECT s.etapa_actual 
                            FROM seguimiento_denuncias s 
                            WHERE s.id_denuncia = d.id_denuncia 
                            ORDER BY s.fecha_procesamiento DESC 
                            LIMIT 1),
                           'pendiente_revision'
                       ) as etapa_actual
                       FROM denuncias d
                       ORDER BY d.fecha_denuncia DESC";
$stmtTodasDenuncias = $db->query($sqlTodasDenuncias);
$todasDenuncias = $stmtTodasDenuncias->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administrador</title>
    <link rel="stylesheet" href="../../css/dashboard_admin.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- jQuery (necesario para DataTables) -->
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

    <style>
        /* Estilos para badges de etapa */
        .badge-etapa {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-en-proceso {
            background-color: rgba(59, 130, 246, 0.15);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar_admin.php'; ?>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1><i class="fas fa-chart-line"></i> Panel de Administración</h1>
            <p>Bienvenido, <?php echo obtenerNombreUsuario(); ?></p>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalDenuncias; ?></h3>
                    <p>Total Denuncias</p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $denunciasPendientes; ?></h3>
                    <p>Pendientes</p>
                </div>
            </div>

            <div class="stat-card process">
                <div class="stat-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $denunciasProceso; ?></h3>
                    <p>En Proceso</p>
                </div>
            </div>

            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $denunciasResueltas; ?></h3>
                    <p>Resueltas</p>
                </div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $denunciasRechazadas; ?></h3>
                    <p>Rechazadas</p>
                </div>
            </div>
        </div>

        <!-- Sección de gráficos -->
        <div class="charts-section">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Estado de Denuncias</h3>
                <canvas id="estadoChart"></canvas>
            </div>
        </div>

        <!-- Tabla con DataTables -->
        <div class="table-section">
            <h3><i class="fas fa-list"></i> Listado de Denuncias</h3>
            <div class="table-responsive">
                <table id="tablaDenuncias" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Denunciante</th>
                            <th>Ubicación</th>
                            <th>Especie</th>
                            <th>Estado</th>
                            <th>Etapa Actual</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todasDenuncias as $denuncia): ?>
                            <tr>
                                <td>#<?php echo $denuncia['id_denuncia']; ?></td>
                                <td><?php echo htmlspecialchars($denuncia['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($denuncia['departamento'] . ', ' . $denuncia['municipio']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($denuncia['especie_animal']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $denuncia['estado_denuncia']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $denuncia['estado_denuncia'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-etapa badge-en-proceso">
                                        <?php echo WorkflowDenuncias::$nombresEtapas[$denuncia['etapa_actual']]; ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo strtotime($denuncia['fecha_denuncia']); ?>">
                                    <?php echo date('d/m/Y H:i', strtotime($denuncia['fecha_denuncia'])); ?>
                                </td>
                                <td>
                                    <button class="btn-action btn-view"
                                        onclick="window.location.href='ver_denuncia.php?id=<?php echo $denuncia['id_denuncia']; ?>'"
                                        title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-edit"
                                        onclick="window.location.href='editar_denuncia.php?id=<?php echo $denuncia['id_denuncia']; ?>'"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../../js/dashboard_admin.js"></script>
    <script>
        // Datos para el gráfico
        const ctx = document.getElementById('estadoChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'En Proceso', 'Resueltas', 'Rechazadas'],
                datasets: [{
                    data: [
                        <?php echo $denunciasPendientes; ?>,
                        <?php echo $denunciasProceso; ?>,
                        <?php echo $denunciasResueltas; ?>,
                        <?php echo $denunciasRechazadas; ?>
                    ],
                    backgroundColor: ['#FCD34D', '#8b5cf6', '#34D399', '#EF4444'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 13,
                                family: "'Inter', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed;
                                return label;
                            }
                        }
                    },
                    // Plugin para mostrar números en la gráfica
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 16,
                            family: "'Inter', sans-serif"
                        },
                        formatter: function (value, context) {
                            // Solo mostrar si el valor es mayor a 0
                            return value > 0 ? value : '';
                        }
                    }
                }
            },
            plugins: [{
                // Plugin personalizado para mostrar números
                id: 'customLabels',
                afterDatasetsDraw: function (chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach(function (dataset, i) {
                        const meta = chart.getDatasetMeta(i);
                        if (!meta.hidden) {
                            meta.data.forEach(function (element, index) {
                                // Dibujar el número
                                ctx.fillStyle = '#fff';
                                const fontSize = 18;
                                const fontStyle = 'bold';
                                const fontFamily = "'Inter', sans-serif";
                                ctx.font = fontStyle + ' ' + fontSize + 'px ' + fontFamily;

                                const dataString = dataset.data[index].toString();

                                // Solo mostrar si el valor es mayor a 0
                                if (dataset.data[index] > 0) {
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';

                                    const position = element.tooltipPosition();
                                    ctx.fillText(dataString, position.x, position.y);
                                }
                            });
                        }
                    });
                }
            }]
        });

        // Inicializar DataTable
        $(document).ready(function () {
            $('#tablaDenuncias').DataTable({
                responsive: false,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[6, 'desc']], // Ordenar por fecha descendente (ahora es columna 6)
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
                        title: 'Denuncias_AppUBA'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn-dt',
                        title: 'Denuncias AppUBA',
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
                    { orderable: false, targets: 7 } // Deshabilitar ordenamiento en columna de acciones (ahora es columna 7)
                ]
            });
        });

        // Funciones para botones de acción
        function verDetalle(id) {
            Swal.fire({
                title: 'Ver Detalle',
                text: `Mostrando detalle de la denuncia #${id}`,
                icon: 'info',
                confirmButtonColor: '#1E3A8A'
            });
            // Aquí irá la lógica para mostrar el detalle
        }

        function editarDenuncia(id) {
            Swal.fire({
                title: 'Editar Denuncia',
                text: `Editando denuncia #${id}`,
                icon: 'info',
                confirmButtonColor: '#1E3A8A'
            });
            // Aquí irá la lógica para editar
        }
    </script>
</body>

</html>