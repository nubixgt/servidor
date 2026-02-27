<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/database.php';

try {
    $conn = getConnection();

    // 1. Monto Total
    $stmt = $conn->query("SELECT COALESCE(SUM(monto_total), 0) as monto_total FROM contratos");
    $monto_total = $stmt->fetch(PDO::FETCH_ASSOC)['monto_total'];

    // 2. Total de Contratos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM contratos");
    $total_contratos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Contratos Activos (fecha_fin >= hoy)
    $stmt = $conn->query("SELECT COUNT(*) as activos FROM contratos WHERE fecha_fin >= CURDATE()");
    $contratos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

    // 4. Contratos del Mes Actual
    $stmt = $conn->query("SELECT COUNT(*) as mes_actual FROM contratos WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())");
    $contratos_mes = $stmt->fetch(PDO::FETCH_ASSOC)['mes_actual'];

    // 5. Servicios (con montos)
    $stmt = $conn->query("SELECT servicios, COUNT(*) as total, SUM(monto_total) as monto FROM contratos GROUP BY servicios");
    $servicios = [];
    $servicios_montos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $servicios[$row['servicios']] = $row['total'];
        $servicios_montos[$row['servicios']] = $row['monto'];
    }

    // 6. Fondos (con montos)
    $stmt = $conn->query("SELECT fondos, COUNT(*) as total, SUM(monto_total) as monto FROM contratos GROUP BY fondos");
    $fondos = [];
    $fondos_montos = [];
    $total_personas = 0;
    $total_monto_fondos = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fondos[$row['fondos']] = $row['total'];
        $fondos_montos[$row['fondos']] = $row['monto'];
        $total_personas += $row['total'];

    }

    // 7. Armonización (con montos)
    $stmt = $conn->query("SELECT armonizacion, COUNT(*) as total, SUM(monto_total) as monto FROM contratos GROUP BY armonizacion");
    $armonizacion = [];
    $armonizacion_montos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $armonizacion[$row['armonizacion']] = $row['total'];
        $armonizacion_montos[$row['armonizacion']] = $row['monto'];
    }

    // 8. IVA (con montos)
    $stmt = $conn->query("SELECT iva, COUNT(*) as total, SUM(monto_total) as monto FROM contratos GROUP BY iva");
    $iva_stats = [];
    $iva_montos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $iva_stats[$row['iva']] = $row['total'];
        $iva_montos[$row['iva']] = $row['monto'];
    }

    // 9. TODOS los Contratos para DataTables
    $stmt = $conn->query("SELECT numero_contrato, nombre_completo, servicios, monto_total, fecha_contrato, fecha_fin FROM contratos ORDER BY id DESC");
    $todos_contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $monto_total = 0;
    $total_contratos = 0;
    $contratos_activos = 0;
    $contratos_mes = 0;
    $servicios = [];
    $fondos = [];
    $armonizacion = [];
    $iva_stats = [];
    $todos_contratos = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Oirsa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/navbar.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>

    <!-- jQuery (requerido para DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="dashboard-container">
            <!-- Header Card -->
            <div class="header-card">
                <div class="header-title">
                    <h1>Dashboard Principal</h1>
                    <p>Bienvenido al sistema de gestión de contratos - OIRSA</p>
                </div>
                <div class="header-user">
                    <div class="user-info">
                        <div class="name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></div>
                        <div class="role">Administrador</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['usuario'], 0, 2)); ?>
                    </div>
                </div>
            </div>

            <!-- Fila 1: Estadísticas Principales (4 tarjetas grandes) -->
            <div class="main-stats">
                <div class="big-stat-card blue-gradient">
                    <div class="big-stat-icon">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>
                    <div class="big-stat-info">
                        <h2>Q<?php echo number_format($monto_total, 2); ?></h2>
                        <p>Monto Total</p>
                    </div>
                </div>

                <div class="big-stat-card blue-gradient">
                    <div class="big-stat-icon">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <div class="big-stat-info">
                        <h2><?php echo $total_contratos; ?></h2>
                        <p>Total Contratos</p>
                    </div>
                </div>

                <div class="big-stat-card green-gradient">
                    <div class="big-stat-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="big-stat-info">
                        <h2><?php echo $contratos_activos; ?></h2>
                        <p>Contratos Activos</p>
                    </div>
                </div>

                <div class="big-stat-card orange-gradient">
                    <div class="big-stat-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="big-stat-info">
                        <h2><?php echo $contratos_mes; ?></h2>
                        <p>Este Mes</p>
                    </div>
                </div>
            </div>

            <!-- Fila 2: Servicios y Fondos -->
            <div class="stats-row-2">
                <div class="detail-card-modern">
                    <div class="card-header">
                        <i class="fa-solid fa-briefcase"></i>
                        <h3>Tipo de Servicios</h3>
                    </div>
                    <div class="detail-list-modern">
                        <div class="detail-item-modern">
                            <div>
                                <span>Técnicos</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q
                                    <?php echo number_format($servicios_montos['Tecnicos'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge blue"><?php echo $servicios['Tecnicos'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Profesionales</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q
                                    <?php echo number_format($servicios_montos['Profesionales'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge blue"><?php echo $servicios['Profesionales'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern"
                            style="background: linear-gradient(135deg, #1A73E8 0%, #43A047 100%); margin-top: 10px;">
                            <span style="color: white; font-weight: 700;">Total Personas</span>
                            <div class="stat-badge" style="background: white; color: #1A73E8;">
                                <?php echo ($servicios['Tecnicos'] ?? 0) + ($servicios['Profesionales'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card-modern">
                    <div class="card-header">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <h3>Tipo de Fondos</h3>
                    </div>
                    <div class="detail-list-modern">
                        <div class="detail-item-modern">
                            <div>
                                <span>APN</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($fondos_montos['Apoyo a Programas Nacionales (APN)'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge green">
                                <?php echo $fondos['Apoyo a Programas Nacionales (APN)'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Opción 1</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($fondos_montos['Opcion 1'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge orange"><?php echo $fondos['Opcion 1'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Opción 2</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($fondos_montos['Opcion 2'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge pink"><?php echo $fondos['Opcion 2'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern"
                            style="background: linear-gradient(135deg, #1A73E8 0%, #43A047 100%); margin-top: 10px;">
                            <span style="color: white; font-weight: 700;">Total Personas</span>
                            <div class="stat-badge" style="background: white; color: #1A73E8;">
                                <?php echo $total_personas; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila 3: Armonización e IVA -->
            <div class="stats-row-2">
                <div class="detail-card-modern">
                    <div class="card-header">
                        <i class="fa-solid fa-scale-balanced"></i>
                        <h3>Cargo de Presupuesto</h3>
                    </div>
                    <div class="detail-list-modern">
                        <div class="detail-item-modern">
                            <div>
                                <span>Armonización de Normativas</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Armonizacion de Normativas'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge orange">
                                <?php echo $armonizacion['Armonizacion de Normativas'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Despacho Superior</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Despacho Superior'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge blue"><?php echo $armonizacion['Despacho Superior'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Dirección Sanidad Vegetal</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Direccion Sanidad Vegetal'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge green"><?php echo $armonizacion['Direccion Sanidad Vegetal'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Dirección Sanidad Animal</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Direccion Sanidad Animal'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge orange"><?php echo $armonizacion['Direccion Sanidad Animal'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Inocuidad de Alimentos</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Inocuidad de Alimentos'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge pink"><?php echo $armonizacion['Inocuidad de Alimentos'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Cuarentena Vegetal</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Cuarentena Vegetal'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge blue"><?php echo $armonizacion['Cuarentena Vegetal'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Trazabilidad</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Trazabilidad'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge green"><?php echo $armonizacion['Trazabilidad'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Otro</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q<?php echo number_format($armonizacion_montos['Otro'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge orange"><?php echo $armonizacion['Otro'] ?? 0; ?></div>
                        </div>
                        <!-- Total Personas -->
                        <div class="detail-item-modern"
                            style="background: linear-gradient(135deg, #1A73E8 0%, #43A047 100%); margin-top: 10px;">
                            <span style="color: white; font-weight: 700;">Total Personas</span>
                            <div class="stat-badge" style="background: white; color: #1A73E8;">
                                <?php
                                $total_armonizacion =
                                    ($armonizacion['Armonizacion de Normativas'] ?? 0) +
                                    ($armonizacion['Despacho Superior'] ?? 0) +
                                    ($armonizacion['Direccion Sanidad Vegetal'] ?? 0) +
                                    ($armonizacion['Direccion Sanidad Animal'] ?? 0) +
                                    ($armonizacion['Inocuidad de Alimentos'] ?? 0) +
                                    ($armonizacion['Cuarentena Vegetal'] ?? 0) +
                                    ($armonizacion['Trazabilidad'] ?? 0) +
                                    ($armonizacion['Otro'] ?? 0);
                                echo $total_armonizacion;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card-modern">
                    <div class="card-header">
                        <i class="fa-solid fa-percent"></i>
                        <h3>IVA</h3>
                    </div>
                    <div class="detail-list-modern">
                        <div class="detail-item-modern">
                            <div>
                                <span>Incluir</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q
                                    <?php echo number_format($iva_montos['Incluir'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge green"><?php echo $iva_stats['Incluir'] ?? 0; ?></div>
                        </div>
                        <div class="detail-item-modern">
                            <div>
                                <span>Sumarse</span>
                                <small style="display: block; color: #999; font-size: 12px;">
                                    Q
                                    <?php echo number_format($iva_montos['Sumarse'] ?? 0, 2); ?>
                                </small>
                            </div>
                            <div class="stat-badge orange"><?php echo $iva_stats['Sumarse'] ?? 0; ?></div>
                        </div>
                        <!-- Total Personas -->
                        <div class="detail-item-modern"
                            style="background: linear-gradient(135deg, #1A73E8 0%, #43A047 100%); margin-top: 10px;">
                            <span style="color: white; font-weight: 700;">Total Personas</span>
                            <div class="stat-badge" style="background: white; color: #1A73E8;">
                                <?php echo ($iva_stats['Incluir'] ?? 0) + ($iva_stats['Sumarse'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila 4: Tabla de Contratos con DataTables -->
            <div class="contracts-section">
                <div class="section-header">
                    <h2><i class="fa-solid fa-table"></i> Todos los Contratos</h2>
                </div>

                <?php if (count($todos_contratos) > 0): ?>
                    <div class="table-container">
                        <table id="contractsTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Nombre Completo</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Fecha Contrato</th>
                                    <th>Fecha Fin</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todos_contratos as $contrato):
                                    $fecha_fin = strtotime($contrato['fecha_fin']);
                                    $hoy = strtotime(date('Y-m-d'));
                                    $estado = $fecha_fin >= $hoy ? 'Activo' : 'Finalizado';
                                    $badge_class = $fecha_fin >= $hoy ? 'badge-active' : 'badge-finished';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($contrato['numero_contrato']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($contrato['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($contrato['servicios']); ?></td>
                                        <td>Q<?php echo number_format($contrato['monto_total'], 2); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($contrato['fecha_contrato'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($contrato['fecha_fin'])); ?></td>
                                        <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No hay contratos registrados aún</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#contractsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[0, 'desc']],
                responsive: true
            });
        });
    </script>
</body>

</html>