<?php
require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

// Obtener usuario actual
$usuarioActual = getUsuarioActual();
$esAdministrador = esAdmin();

// Parámetros de búsqueda
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir query para vales
$sql = "SELECT * FROM vales WHERE 1=1";
$params = [];

if (!empty($buscar)) {
    $sql .= " AND (numero_vale LIKE ? OR nombre_solicitante LIKE ? OR descripcion LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $params[] = $buscar_param;
}

if (!empty($fecha_desde)) {
    $sql .= " AND fecha_solicitud >= ?";
    $params[] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $sql .= " AND fecha_solicitud <= ?";
    $params[] = $fecha_hasta;
}

$sql .= " ORDER BY fecha_creacion DESC";

$db = getDB();
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vales = $stmt->fetchAll();

// Estadísticas con los mismos filtros
$stats_sql = "SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total FROM vales WHERE 1=1";
$stats_params = [];

if (!empty($buscar)) {
    $stats_sql .= " AND (numero_vale LIKE ? OR nombre_solicitante LIKE ? OR descripcion LIKE ?)";
    $buscar_param = "%$buscar%";
    $stats_params[] = $buscar_param;
    $stats_params[] = $buscar_param;
    $stats_params[] = $buscar_param;
}

if (!empty($fecha_desde)) {
    $stats_sql .= " AND fecha_solicitud >= ?";
    $stats_params[] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $stats_sql .= " AND fecha_solicitud <= ?";
    $stats_params[] = $fecha_hasta;
}

$stmt_stats = $db->prepare($stats_sql);
$stmt_stats->execute($stats_params);
$stats = $stmt_stats->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Vales - VISAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --maga-azul-oscuro: #1e3a5f;
            --maga-azul-medio: #2a4a6f;
            --maga-cyan: #0abde3;
            --maga-cyan-claro: #48d1ff;
            --maga-cyan-oscuro: #0097c7;
            --color-primario: #1e3a5f;
            --color-secundario: #2c5282;
            --color-acento: #0abde3;
            --color-exito: #10b981;
            --color-advertencia: #f39c12;
            --color-peligro: #e74c3c;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-principal: #f7fafc;
            --bg-blanco: #ffffff;
            --sombra-suave: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --sombra-media: 0 4px 6px rgba(0,0,0,0.1);
            --sombra-grande: 0 10px 40px rgba(0,0,0,0.15);
            --transicion: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 50%, var(--maga-cyan-oscuro) 100%);
            min-height: 100vh;
            padding: 20px;
            animation: gradientShift 15s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* BARRA DE USUARIO */
        .user-bar {
            background: var(--bg-blanco);
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: var(--sombra-grande);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.3);
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--color-texto);
            font-size: 15px;
        }
        
        .role-badge {
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-badge.admin {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            color: var(--maga-azul-oscuro);
        }
        
        .btn-action:hover {
            background: var(--maga-azul-oscuro);
            color: white;
            border-color: var(--maga-azul-oscuro);
            transform: translateY(-1px);
        }
        
        .header {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: var(--sombra-grande);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primario) 0%, var(--color-acento) 100%);
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 16px;
            color: var(--color-texto-claro);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-weight: 500;
            box-shadow: var(--sombra-suave);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
            color: #047857;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.08) 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
            color: #1d4ed8;
            border: 2px solid #3b82f6;
        }
        
        .alert-icon {
            font-size: 20px;
            margin-right: 12px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-blanco);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--sombra-grande);
            border-left: 4px solid;
            transition: var(--transicion);
        }

        .stat-card:hover {
            transform: translateY(-8px);
        }
        
        .stat-card.total { border-left-color: var(--color-acento); }
        .stat-card.monto { border-left-color: #9b59b6; }
        
        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-texto-claro);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-sublabel {
            font-size: 12px;
            color: var(--color-texto-claro);
            font-style: italic;
            margin-top: 8px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--color-primario);
        }

        .filter-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 10px;
            font-weight: 700;
        }
        
        .filters-section {
            background: var(--bg-blanco);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--sombra-grande);
            margin-bottom: 30px;
        }
        
        .filters-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .filters-title::before {
            content: '🔍';
            margin-right: 12px;
            font-size: 24px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 8px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: var(--transicion);
            background: #fafafa;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--color-acento);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: var(--sombra-suave);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
        }

        .btn-nuevo-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .btn-nuevo {
            padding: 12px 24px;
            background: #f8fafc;
            color: var(--maga-azul-oscuro);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-nuevo:hover {
            background: var(--maga-azul-oscuro);
            color: white;
            border-color: var(--maga-azul-oscuro);
        }

        /* ========================================
           ESTADOS DE VALES
           ======================================== */
        .estado-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .estado-pendiente {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .estado-liquidado {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        /* ========================================
           BOTONES DE ACCIONES - REDISEÑADOS
           ======================================== */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-accion {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transicion);
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-accion .icono {
            font-size: 14px;
        }

        /* Botón VER - Azul Cyan */
        .btn-ver {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
        }

        .btn-ver:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.5);
        }

        /* Botón BITÁCORA - Púrpura */
        .btn-bitacora {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        }

        .btn-bitacora:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.5);
        }

        /* Botón EDITAR - Naranja/Ámbar */
        .btn-editar {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-editar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.5);
        }

        /* Botón ELIMINAR - Rojo Oscuro/Granate (diferente al estado pendiente) */
        .btn-eliminar {
            background: linear-gradient(135deg, #991b1b 0%, #7f1d1d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(153, 27, 27, 0.3);
        }

        .btn-eliminar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(153, 27, 27, 0.5);
        }
        
        .table-container {
            background: var(--bg-blanco);
            border-radius: 16px;
            box-shadow: var(--sombra-grande);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            color: white;
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        td {
            padding: 16px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        tbody tr {
            transition: var(--transicion);
        }

        tbody tr:hover {
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.05) 0%, rgba(72, 209, 255, 0.02) 100%);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 40px;
            color: var(--color-texto-claro);
        }

        .no-results h3 {
            font-size: 24px;
            color: var(--color-primario);
            margin-bottom: 12px;
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .user-bar { flex-direction: column; text-align: center; }
            .header { padding: 20px 25px; }
            .header h1 { font-size: 24px; }
            .stats-container { grid-template-columns: 1fr; }
            .filters-grid { grid-template-columns: 1fr; }
            .btn-nuevo-container { justify-content: center; }
            .table-container { overflow-x: auto; }
            table { min-width: 900px; }
            
            /* Fix para campos de fecha en móvil */
            .filters-section {
                padding: 20px 15px;
            }
            
            .filter-group {
                width: 100%;
                overflow: hidden;
            }
            
            .filter-group input,
            .filter-group select {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                font-size: 16px; /* Evita zoom en iOS */
                padding: 12px 10px;
            }
            
            .filter-group input[type="date"] {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .filter-actions .btn {
                width: 100%;
                justify-content: center;
            }

            /* Botones de acciones en móvil */
            .actions {
                gap: 6px;
            }

            .btn-accion {
                padding: 6px 10px;
                font-size: 11px;
            }

            .btn-accion .texto {
                display: none;
            }

            .btn-accion .icono {
                font-size: 16px;
            }
        }
    </style>

    <script>
        function confirmarEliminacion(numeroVale) {
            return confirm('¿Está seguro que desea eliminar el vale ' + numeroVale + '?\n\nEsta acción no se puede deshacer.');
        }
    </script>
</head>
<body>
    <div class="container">
        
        <!-- BARRA DE USUARIO -->
        <div class="user-bar">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioActual['nombre_completo']); ?></span>
                    <span class="role-badge <?php echo $esAdministrador ? 'admin' : ''; ?>">
                        <?php echo $esAdministrador ? '🔑 Administrador' : '👤 Usuario'; ?>
                    </span>
                </div>
            </div>
            <div class="user-actions">
                <?php if ($esAdministrador): ?>
                    <a href="index.php" class="btn-action">Crear Vale</a>
                    <a href="usuarios.php" class="btn-action">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-action">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="header">
            <h1>📋 Sistema de Vales de Caja Chica</h1>
            <p>VISAR - Viceministerio de Sanidad Agropecuaria y Regulaciones</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <strong><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></strong>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <strong><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (!$esAdministrador): ?>
            <div class="alert alert-info">
                <span class="alert-icon">ℹ️</span>
                <strong>Modo vista: Solo puedes ver los vales y su bitácora. Para crear o editar vales, contacta a un administrador.</strong>
            </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-label">
                    Total de Vales
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                        <span class="filter-badge">FILTRADO</span>
                    <?php endif; ?>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                    <div class="stat-sublabel">
                        <?php 
                        if (!empty($fecha_desde) && !empty($fecha_hasta)) {
                            echo "Del " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta));
                        } elseif (!empty($fecha_desde)) {
                            echo "Desde " . date('d/m/Y', strtotime($fecha_desde));
                        } elseif (!empty($fecha_hasta)) {
                            echo "Hasta " . date('d/m/Y', strtotime($fecha_hasta));
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card monto">
                <div class="stat-label">
                    Monto Total
                    <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                        <span class="filter-badge">FILTRADO</span>
                    <?php endif; ?>
                </div>
                <div class="stat-value">Q. <?php echo number_format($stats['monto_total'], 2); ?></div>
                <?php if (!empty($fecha_desde) || !empty($fecha_hasta)): ?>
                    <div class="stat-sublabel">
                        <?php 
                        if (!empty($fecha_desde) && !empty($fecha_hasta)) {
                            echo "Del " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta));
                        } elseif (!empty($fecha_desde)) {
                            echo "Desde " . date('d/m/Y', strtotime($fecha_desde));
                        } elseif (!empty($fecha_hasta)) {
                            echo "Hasta " . date('d/m/Y', strtotime($fecha_hasta));
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="filters-section">
            <div class="filters-title">Filtros de Búsqueda</div>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Buscar</label>
                        <input type="text" name="buscar" placeholder="No. vale, nombre o descripción" 
                               value="<?php echo htmlspecialchars($buscar); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Fecha Desde</label>
                        <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="listar_vales.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <?php if ($esAdministrador): ?>
        <div class="btn-nuevo-container">
            <a href="index.php" class="btn-nuevo">+ Nuevo Vale</a>
        </div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if (count($vales) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No. Vale</th>
                            <th>Fecha</th>
                            <th>Departamento</th>
                            <th>Solicitante</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vales as $vale): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($vale['numero_vale']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($vale['fecha_solicitud'])); ?></td>
                                <td><?php echo htmlspecialchars($vale['departamento']); ?></td>
                                <td><?php echo htmlspecialchars($vale['nombre_solicitante']); ?></td>
                                <td><?php echo htmlspecialchars($vale['categoria']); ?></td>
                                <td><strong>Q. <?php echo number_format($vale['monto'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $estado = isset($vale['estado']) ? $vale['estado'] : 'PENDIENTE';
                                    $clase_estado = ($estado === 'LIQUIDADO') ? 'estado-liquidado' : 'estado-pendiente';
                                    ?>
                                    <span class="estado-badge <?php echo $clase_estado; ?>">
                                        <?php echo $estado; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <!-- Ver: Disponible para todos -->
                                        <a href="ver_vales.php?id=<?php echo $vale['id']; ?>" 
                                           class="btn-accion btn-ver" target="_blank" title="Ver Vale">
                                            <span class="icono">👁️</span>
                                            <span class="texto">Ver</span>
                                        </a>
                                        
                                        <!-- Bitácora: Disponible para todos -->
                                        <a href="bitacora.php?id=<?php echo $vale['id']; ?>" 
                                           class="btn-accion btn-bitacora" title="Ver Bitácora">
                                            <span class="icono">📜</span>
                                            <span class="texto">Bitácora</span>
                                        </a>
                                        
                                        <?php if ($esAdministrador): ?>
                                        <!-- Editar: Solo administradores -->
                                        <a href="editar_vales.php?id=<?php echo $vale['id']; ?>" 
                                           class="btn-accion btn-editar" title="Editar Vale">
                                            <span class="icono">✏️</span>
                                            <span class="texto">Editar</span>
                                        </a>
                                        
                                        <!-- Eliminar: Solo administradores -->
                                        <a href="eliminar_vales.php?id=<?php echo $vale['id']; ?>" 
                                           class="btn-accion btn-eliminar" 
                                           onclick="return confirmarEliminacion('<?php echo htmlspecialchars($vale['numero_vale']); ?>');"
                                           title="Eliminar Vale">
                                            <span class="icono">🗑️</span>
                                            <span class="texto">Eliminar</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>   
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <h3>No se encontraron vales</h3>
                    <p>No hay vales registrados con los criterios de búsqueda seleccionados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>