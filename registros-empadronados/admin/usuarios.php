<?php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Solo administradores
verificarAcceso([ROL_ADMINISTRADOR]);

$pdo = obtenerConexion();
$mensaje = '';
$error = '';

// Procesar acciones (MISMO FLUJO ORIGINAL)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear_usuario') {
        $nombres = limpiarDato($_POST['nombres'] ?? '');
        $apellidos = limpiarDato($_POST['apellidos'] ?? '');
        $usuario = limpiarDato($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $dpi = limpiarDato($_POST['dpi'] ?? '');
        $telefono = limpiarDato($_POST['telefono'] ?? '');
        $departamento = limpiarDato($_POST['departamento'] ?? '');
        $municipio = limpiarDato($_POST['municipio'] ?? '');
        $rol = limpiarDato($_POST['rol'] ?? '');

        $nombreCompleto = trim($nombres . ' ' . $apellidos);

        if (empty($nombres) || empty($apellidos) || empty($usuario) || empty($contrasena) || empty($rol)) {
            $error = 'Complete todos los campos obligatorios';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Usuario = :usuario");
                $stmt->execute([':usuario' => $usuario]);

                if ($stmt->fetchColumn() > 0) {
                    $error = 'El usuario ya existe';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (NombreCompleto, Usuario, Contrasena, DPI, Telefono, 
                                              Departamento, Municipio, Rol, Estado, FechaCreacion, UltimoAcceso)
                        VALUES (:nombre, :usuario, :contrasena, :dpi, :telefono, :departamento, :municipio, :rol, 'Activo', NOW(), NOW())
                    ");
                    $stmt->execute([
                        ':nombre' => $nombreCompleto,
                        ':usuario' => $usuario,
                        ':contrasena' => $contrasena,
                        ':dpi' => $dpi,
                        ':telefono' => $telefono,
                        ':departamento' => $departamento,
                        ':municipio' => $municipio,
                        ':rol' => $rol
                    ]);

                    $mensaje = 'Usuario creado correctamente';
                }
            } catch (PDOException $e) {
                error_log("Error al crear usuario: " . $e->getMessage());
                $error = 'Error al crear el usuario';
            }
        }
    } elseif ($accion === 'editar_usuario') {
        $usuarioId = $_POST['usuario_id'] ?? 0;
        $nombres = limpiarDato($_POST['nombres'] ?? '');
        $apellidos = limpiarDato($_POST['apellidos'] ?? '');
        $usuario = limpiarDato($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $dpi = limpiarDato($_POST['dpi'] ?? '');
        $telefono = limpiarDato($_POST['telefono'] ?? '');
        $departamento = limpiarDato($_POST['departamento'] ?? '');
        $municipio = limpiarDato($_POST['municipio'] ?? '');
        $rol = limpiarDato($_POST['rol'] ?? '');

        $nombreCompleto = trim($nombres . ' ' . $apellidos);

        if (empty($nombres) || empty($apellidos) || empty($usuario) || empty($rol)) {
            $error = 'Complete todos los campos obligatorios';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Usuario = :usuario AND id != :id");
                $stmt->execute([':usuario' => $usuario, ':id' => $usuarioId]);

                if ($stmt->fetchColumn() > 0) {
                    $error = 'El usuario ya existe en otro registro';
                } else {
                    if (!empty($contrasena)) {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET NombreCompleto = :nombre, 
                                Usuario = :usuario, 
                                Contrasena = :contrasena,
                                DPI = :dpi, 
                                Telefono = :telefono, 
                                Departamento = :departamento, 
                                Municipio = :municipio, 
                                Rol = :rol
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':nombre' => $nombreCompleto,
                            ':usuario' => $usuario,
                            ':contrasena' => $contrasena,
                            ':dpi' => $dpi,
                            ':telefono' => $telefono,
                            ':departamento' => $departamento,
                            ':municipio' => $municipio,
                            ':rol' => $rol,
                            ':id' => $usuarioId
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET NombreCompleto = :nombre, 
                                Usuario = :usuario, 
                                DPI = :dpi, 
                                Telefono = :telefono, 
                                Departamento = :departamento, 
                                Municipio = :municipio, 
                                Rol = :rol
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':nombre' => $nombreCompleto,
                            ':usuario' => $usuario,
                            ':dpi' => $dpi,
                            ':telefono' => $telefono,
                            ':departamento' => $departamento,
                            ':municipio' => $municipio,
                            ':rol' => $rol,
                            ':id' => $usuarioId
                        ]);
                    }

                    $mensaje = 'Usuario actualizado correctamente';
                }
            } catch (PDOException $e) {
                error_log("Error al editar usuario: " . $e->getMessage());
                $error = 'Error al actualizar el usuario';
            }
        }
    } elseif ($accion === 'cambiar_estado') {
        $usuarioId = $_POST['usuario_id'] ?? 0;
        $nuevoEstado = $_POST['estado'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET Estado = :estado WHERE id = :id");
            $stmt->execute([':estado' => $nuevoEstado, ':id' => $usuarioId]);
            $mensaje = 'Estado actualizado correctamente';
        } catch (PDOException $e) {
            error_log("Error al cambiar estado: " . $e->getMessage());
            $error = 'Error al cambiar el estado';
        }
    }
}

// Obtener todos los usuarios
try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY FechaCreacion DESC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener usuarios: " . $e->getMessage());
    $usuarios = [];
}

// Departamentos
$stmtDepts = $pdo->query("SELECT DISTINCT departamento FROM empadronados ORDER BY departamento");
$departamentos = $stmtDepts->fetchAll(PDO::FETCH_COLUMN);

$iniciales = obtenerIniciales(obtenerNombreUsuario());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema SICO GT</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- Sistema de diseño -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components.css">

    <style>
        /* ====== Tabla container ====== */
        .table-container {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        .table thead th {
            background: linear-gradient(180deg, rgba(212,165,116,0.1), rgba(212,165,116,0.05));
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            padding: 1rem;
        }
        .table tbody tr { 
            border-bottom: 1px solid var(--border-light); 
            transition: all var(--transition-base); 
        }
        .table tbody tr:hover { 
            background: rgba(212,165,116,0.05); 
            transform: translateX(4px); 
        }
        .table tbody td { 
            padding: 1rem; 
            vertical-align: middle; 
        }

        /* ====== Stats Cards ====== */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); 
            gap: var(--spacing-xl); 
            margin-bottom: var(--spacing-xl); 
        }
        .stat-card { 
            position: relative; 
            overflow: hidden; 
            background: var(--bg-glass); 
            backdrop-filter: blur(20px); 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-xl); 
            padding: 2rem; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            transition: all .4s cubic-bezier(.4,0,.2,1);
        }
        .stat-card::before { 
            content:''; 
            position:absolute; 
            top:0; 
            right:0; 
            width:120px; 
            height:120px; 
            background: radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%); 
            border-radius:50%; 
            transform: translate(40%,-40%); 
            transition: all .4s ease;
        }
        .stat-card:hover { 
            transform: translateY(-12px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(0,0,0,.15); 
            border-color: currentColor;
        }
        .stat-card:hover::before { 
            transform: translate(30%,-30%) scale(1.2); 
        }
        .stat-icon { 
            width:70px; 
            height:70px; 
            border-radius: var(--radius-lg); 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            margin-bottom:1.5rem; 
            box-shadow:0 8px 20px rgba(0,0,0,.15); 
            transition: all .3s ease;
        }
        .stat-card:hover .stat-icon { 
            transform: scale(1.1) rotate(5deg); 
            box-shadow:0 12px 30px rgba(0,0,0,.25);
        }
        .stat-icon i { 
            font-size:2rem; 
            color:#fff; 
        }
        .stat-value { 
            font-size:3rem; 
            font-weight:900; 
            line-height:1; 
            margin-bottom:.75rem; 
            letter-spacing:-.02em; 
        }
        .stat-label { 
            font-size:.875rem; 
            font-weight:700; 
            text-transform:uppercase; 
            letter-spacing:1px; 
            color:var(--text-primary); 
            margin-bottom:1rem; 
        }
        .stat-info { 
            display:flex; 
            align-items:center; 
            gap:.5rem; 
            padding:.75rem 1rem; 
            background:rgba(255,255,255,.5); 
            backdrop-filter: blur(10px); 
            border-radius:var(--radius-md); 
            font-size:.875rem; 
            font-weight:600; 
            transition: all .3s ease;
        }
        .stat-card:hover .stat-info { 
            background:rgba(255,255,255,.8); 
            transform: translateX(4px); 
        }

        .badge.bg-primary { 
            background: var(--gradient-primary) !important; 
        }
        .animate-in { 
            animation: fadeInUp .6s ease-out; 
        }
        @keyframes fadeInUp { 
            from{opacity:0; transform: translateY(20px);} 
            to{opacity:1; transform: translateY(0);} 
        }

        /* ====== Botón Nuevo Usuario ====== */
        .btn-nuevo-usuario {
            display:inline-flex; 
            align-items:center; 
            gap: var(--spacing-sm);
            padding:.875rem 1.75rem; 
            font-size: var(--font-size-base); 
            font-weight:600;
            background: var(--gradient-primary); 
            color:#fff; 
            border:none; 
            border-radius: var(--radius-md);
            cursor:pointer; 
            box-shadow: var(--shadow-gold);
            transition: all .3s cubic-bezier(.16,1,.3,1); 
            position:relative; 
            overflow:hidden;
        }
        .btn-nuevo-usuario::before{
            content:''; 
            position:absolute; 
            top:50%; 
            left:50%; 
            width:0; 
            height:0; 
            border-radius:50%;
            background: rgba(255,255,255,.3); 
            transform: translate(-50%,-50%); 
            transition: width .6s, height .6s;
        }
        .btn-nuevo-usuario:hover{ 
            transform: translateY(-3px); 
            box-shadow: 0 12px 32px rgba(212,165,116,.5); 
        }
        .btn-nuevo-usuario:hover::before{ 
            width:300px; 
            height:300px; 
        }
        .btn-nuevo-usuario i{ 
            font-size:1.125rem; 
            transition: transform .3s ease; 
        }
        .btn-nuevo-usuario:hover i{ 
            transform: rotate(90deg); 
        }

        /* ====== Modal Overlay Premium ====== */
        .modal-overlay {
            display:none; 
            position: fixed; 
            inset:0; 
            background: rgba(0,0,0,.6);
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            align-items:center; 
            justify-content:center; 
            animation: fadeIn .3s ease-out;
        }
        .modal-overlay.active { 
            display:flex; 
        }
        @keyframes fadeIn { 
            from{opacity:0} 
            to{opacity:1} 
        }

        .modal-container {
            background: var(--bg-body); 
            border-radius: var(--radius-xl);
            box-shadow: 0 24px 48px rgba(0,0,0,.2);
            max-width: 900px; 
            width: 96%; 
            max-height: 90vh; 
            overflow:hidden;
            animation: modalSlideIn .4s cubic-bezier(.16,1,.3,1); 
            border:1px solid var(--border-color);
        }
        @keyframes modalSlideIn { 
            from{opacity:0; transform: translateY(-40px) scale(.95);} 
            to{opacity:1; transform: translateY(0) scale(1);} 
        }

        .modal-header-premium {
            padding: var(--spacing-xl);
            background: linear-gradient(135deg, rgba(212,165,116,.1), rgba(212,165,116,.05));
            border-bottom:1px solid var(--border-color); 
            display:flex; 
            align-items:center; 
            justify-content: space-between;
        }
        .modal-header-content { 
            display:flex; 
            align-items:center; 
            gap: var(--spacing-md); 
        }
        .modal-icon { 
            width:48px; 
            height:48px; 
            background: var(--gradient-primary); 
            border-radius: var(--radius-md); 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            color:#fff; 
            font-size:1.5rem; 
            box-shadow: var(--shadow-gold); 
        }
        .modal-title { 
            font-size: var(--font-size-xl); 
            font-weight:700; 
            color: var(--text-primary); 
            margin:0; 
        }
        .modal-subtitle { 
            font-size: var(--font-size-sm); 
            color: var(--text-secondary); 
            margin:0; 
        }
        .btn-close-modal {
            width:36px; 
            height:36px; 
            border:none; 
            background: rgba(212,165,116,.1); 
            color: var(--color-primary);
            border-radius: var(--radius-md); 
            cursor:pointer; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            transition: all .3s ease; 
            font-size:1.25rem;
        }
        .btn-close-modal:hover { 
            background: var(--gradient-primary); 
            color:#fff; 
            transform: rotate(90deg); 
        }

        .modal-body-premium { 
            padding: var(--spacing-xl); 
            max-height: 60vh; 
            overflow-y:auto; 
        }

        /* ====== NUEVO: Formulario con diseño mejorado ====== */
        .form-row { 
            display:grid; 
            grid-template-columns: 1fr 1fr; 
            gap: var(--spacing-lg); 
            margin-bottom: var(--spacing-lg); 
        }
        .form-group-full { 
            grid-column: 1 / -1; 
        }
        .form-group-modal { 
            margin-bottom: var(--spacing-lg); 
        }
        .form-label-modal { 
            display:flex; 
            align-items:center; 
            gap: var(--spacing-sm); 
            font-size: var(--font-size-sm); 
            font-weight:600; 
            color: var(--text-primary); 
            margin-bottom: var(--spacing-sm); 
        }
        .form-label-modal .required { 
            color:#ef4444; 
        }
        .form-control-modal, .form-select-modal {
            width:100%; 
            padding:.875rem var(--spacing-md); 
            font-size: var(--font-size-sm); 
            color: var(--text-primary);
            background: var(--bg-glass); 
            border:2px solid var(--border-color); 
            border-radius: var(--radius-md);
            transition: all .3s ease; 
            font-family: var(--font-family);
        }
        .form-control-modal:focus, .form-select-modal:focus { 
            outline:none; 
            border-color: var(--color-primary); 
            box-shadow: 0 0 0 4px rgba(212,165,116,.15); 
            background: var(--bg-body); 
        }
        .form-select-modal { 
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='%23d4a574' d='M8 11L3 6h10z'/%3E%3C/svg%3E"); 
            background-repeat:no-repeat; 
            background-position: right .875rem center; 
            background-size:16px; 
            padding-right: 2.75rem; 
            cursor:pointer; 
        }

        /* ====== 🔥 NUEVO: Password Input Group Premium ====== */
        .password-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
        }
        .password-input-group .form-control-modal {
            flex: 1;
        }
        .btn-toggle-password {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            background: rgba(212, 165, 116, 0.1);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--color-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 48px;
        }
        .btn-toggle-password:hover {
            background: var(--gradient-primary);
            color: #fff;
            border-color: var(--color-primary);
            transform: scale(1.05);
        }
        .btn-toggle-password i {
            font-size: 1.125rem;
        }

        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .modal-footer-premium { 
            padding: var(--spacing-xl); 
            background: linear-gradient(180deg, transparent, rgba(212,165,116,.05)); 
            border-top:1px solid var(--border-color); 
            display:flex; 
            gap: var(--spacing-md); 
            justify-content:flex-end; 
        }
        .btn-cancelar { 
            padding:.75rem 1.5rem; 
            font-size: var(--font-size-sm); 
            font-weight:600; 
            background:transparent; 
            color: var(--text-secondary); 
            border:2px solid var(--border-color); 
            border-radius: var(--radius-md); 
            cursor:pointer; 
            transition: all .3s ease; 
        }
        .btn-cancelar:hover { 
            background: rgba(212,165,116,.1); 
            border-color: var(--color-primary); 
            color: var(--color-primary); 
        }
        .btn-guardar { 
            padding:.75rem 1.5rem; 
            font-size: var(--font-size-sm); 
            font-weight:600; 
            background: var(--gradient-primary); 
            color:#fff; 
            border:none; 
            border-radius: var(--radius-md); 
            cursor:pointer; 
            box-shadow: var(--shadow-gold); 
            transition: all .3s ease; 
            display:flex; 
            align-items:center; 
            gap: var(--spacing-sm); 
        }
        .btn-guardar:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 24px rgba(212,165,116,.4); 
        }

        @media (max-width: 768px){
            .form-row { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="page-title-wrapper">
                    <h1 class="page-title">Gestión de Usuarios</h1>
                    <p class="page-subtitle">Administrar usuarios del sistema</p>
                </div>
            </div>

            <div class="topbar-right">
                <div class="topbar-actions">
                    <button class="btn-nuevo-usuario" onclick="abrirModalPremium()" title="Nuevo Usuario">
                        <i class="bi bi-plus-circle"></i>
                        <span>Nuevo Usuario</span>
                    </button>
                </div>

                <div class="user-profile">
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                    <div class="user-info">
                        <p class="user-name"><?php echo obtenerNombreUsuario(); ?></p>
                        <p class="user-role"><?php echo obtenerRolUsuario(); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content">

            <!-- Alertas -->
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show animate-in" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show animate-in" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas rápidas -->
            <div class="stats-grid animate-in">
                <div class="stat-card" style="color:#3b82f6;">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#3b82f6,#2563eb);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-value" style="background: linear-gradient(135deg,#3b82f6,#2563eb); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                        <?php echo count($usuarios); ?>
                    </div>
                    <div class="stat-label">Total Usuarios</div>
                    <div class="stat-info" style="color:#3b82f6;">
                        <i class="bi bi-arrow-up-right-circle-fill"></i><span>Sistema completo</span>
                    </div>
                </div>

                <div class="stat-card" style="color:#10b981;">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#10b981,#059669);">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-value" style="background: linear-gradient(135deg,#10b981,#059669); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                        <?php echo count(array_filter($usuarios, fn($u) => $u['Estado'] === 'Activo')); ?>
                    </div>
                    <div class="stat-label">Usuarios Activos</div>
                    <div class="stat-info" style="color:#10b981;">
                        <i class="bi bi-shield-check"></i>
                        <span>
                            <?php 
                            $porcentaje = count($usuarios) > 0 ? round((count(array_filter($usuarios, fn($u) => $u['Estado'] === 'Activo')) / count($usuarios)) * 100) : 0;
                            echo $porcentaje . '%';
                            ?> del total
                        </span>
                    </div>
                </div>

                <div class="stat-card" style="color:#d4a574;">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#d4a574,#c9a167);">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <div class="stat-value" style="background: linear-gradient(135deg,#d4a574,#c9a167); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                        <?php echo count(array_filter($usuarios, fn($u) => $u['Estado'] !== 'Activo')); ?>
                    </div>
                    <div class="stat-label">Usuarios Inactivos</div>
                    <div class="stat-info" style="color:#d4a574;">
                        <i class="bi bi-exclamation-triangle-fill"></i><span>Requieren atención</span>
                    </div>
                </div>
            </div>

            <!-- Tabla usuarios -->
            <div class="table-container animate-in">
                <div class="chart-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--spacing-lg); padding-bottom:var(--spacing-md); border-bottom:2px solid var(--border-light);">
                    <h3 class="chart-title" style="font-size:var(--font-size-xl); font-weight:700; color:var(--text-primary); margin:0; display:flex; align-items:center; gap:var(--spacing-sm);">
                        <i class="bi bi-table" style="color:var(--color-primary);"></i>
                        Lista de Usuarios
                    </h3>
                </div>

                <div class="table-responsive">
                    <table id="tablaUsuarios" class="table table-hover">
                        <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Departamento</th>
                            <th>Estado</th>
                            <th class="text-center" style="width:200px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $usr): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background: linear-gradient(135deg,#d4a574,#c9a167); color:#fff; font-weight:600;">
                                        <?php echo $usr['id']; ?>
                                    </span>
                                </td>
                                <td><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($usr['NombreCompleto']); ?></strong></td>
                                <td><span style="color:var(--text-secondary);"><?php echo htmlspecialchars($usr['Usuario']); ?></span></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($usr['Rol']); ?></span></td>
                                <td><span style="color:var(--text-secondary);"><?php echo htmlspecialchars($usr['Departamento']); ?></span></td>
                                <td>
                                    <?php if ($usr['Estado'] === 'Activo'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php elseif ($usr['Estado'] === 'Suspendido'): ?>
                                        <span class="badge bg-warning">Suspendido</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php
                                            $jsonUsr = htmlspecialchars(
                                                json_encode($usr, JSON_HEX_APOS | JSON_HEX_QUOT),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                            <button class="btn btn-outline-info"
                                                    onclick='editarUsuario(<?php echo $jsonUsr; ?>)'
                                                    title="Editar usuario">
                                                <i class="fa-solid fa-user-pen"></i>
                                            </button>

                                        <?php if ($usr['Estado'] === 'Activo'): ?>
                                            <button class="btn btn-outline-warning"
                                                    onclick="cambiarEstado(<?php echo $usr['id']; ?>, 'Suspendido')"
                                                    title="Suspender">
                                                <i class="fa-solid fa-user-slash"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="cambiarEstado(<?php echo $usr['id']; ?>, 'Inactivo')"
                                                    title="Desactivar">
                                                <i class="fa-solid fa-user-xmark"></i>
                                            </button>
                                        <?php elseif ($usr['Estado'] === 'Suspendido'): ?>
                                            <button class="btn btn-outline-success"
                                                    onclick="cambiarEstado(<?php echo $usr['id']; ?>, 'Activo')"
                                                    title="Activar">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="cambiarEstado(<?php echo $usr['id']; ?>, 'Inactivo')"
                                                    title="Desactivar">
                                                <i class="fa-solid fa-user-xmark"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success"
                                                    onclick="cambiarEstado(<?php echo $usr['id']; ?>, 'Activo')"
                                                    title="Activar">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /.content -->
    </main><!-- /.main-content -->
</div><!-- /.dashboard-wrapper -->

<!-- ===== Modal PREMIUM con Password Input Corregido ===== -->
<div class="modal-overlay" id="modalUsuarioPremium">
    <div class="modal-container">
        <div class="modal-header-premium">
            <div class="modal-header-content">
                <div class="modal-icon"><i class="bi bi-person-plus"></i></div>
                <div>
                    <h2 class="modal-title" id="modalUsuarioTitulo">Crear Nuevo Usuario</h2>
                    <p class="modal-subtitle">Completa la información del usuario</p>
                </div>
            </div>
            <button class="btn-close-modal" type="button" onclick="cerrarModalPremium()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" id="formUsuario">
            <div class="modal-body-premium">
                <input type="hidden" name="accion" id="accionForm" value="crear_usuario">
                <input type="hidden" name="usuario_id" id="usuarioId">

                <div class="form-row">
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-person"></i>
                            Nombres <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control-modal" name="nombres" id="nombres" required placeholder="Ej: Juan Carlos">
                    </div>
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-person"></i>
                            Apellidos <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control-modal" name="apellidos" id="apellidos" required placeholder="Ej: García López">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-person-badge"></i>
                            Usuario <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control-modal" name="usuario" id="usuario" required readonly placeholder="Se genera automáticamente">
                        <div class="form-text">Se genera automáticamente, puede editarlo</div>
                    </div>
                    <div class="form-group-modal">
                        <label class="form-label-modal" id="labelContrasena">
                            <i class="bi bi-key"></i>
                            Contraseña <span class="required">*</span>
                        </label>
                        <!-- 🔥 NUEVO: Input group mejorado -->
                        <div class="password-input-group">
                            <input type="password" class="form-control-modal" name="contrasena" id="contrasena" minlength="6" required readonly placeholder="Se genera automáticamente">
                            <button class="btn-toggle-password" type="button" id="togglePassword" title="Mostrar/Ocultar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="helpContrasena">Se genera automáticamente, puede editarla</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-shield-check"></i>
                            Rol <span class="required">*</span>
                        </label>
                        <select class="form-select-modal" name="rol" id="rol" required>
                            <option value="">Seleccione...</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Presidente">Presidente</option>
                            <option value="Diputado">Diputado</option>
                            <option value="Alcalde">Alcalde</option>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-card-text"></i>
                            DPI
                        </label>
                        <input type="text" class="form-control-modal" name="dpi" id="dpi" placeholder="0000 00000 0000">
                        <div class="form-text">Formato: 0000 00000 0000</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-telephone"></i>
                            Teléfono
                        </label>
                        <input type="text" class="form-control-modal" name="telefono" id="telefono" placeholder="0000-0000">
                        <div class="form-text">Formato: 0000-0000</div>
                    </div>
                    <div class="form-group-modal">
                        <label class="form-label-modal">
                            <i class="bi bi-geo-alt"></i>
                            Departamento
                        </label>
                        <select class="form-select-modal" name="departamento" id="departamento">
                            <option value="">Seleccione...</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-modal form-group-full">
                    <label class="form-label-modal">
                        <i class="bi bi-building"></i>
                        Municipio
                    </label>
                    <select class="form-select-modal" name="municipio" id="municipio" disabled>
                        <option value="">Primero seleccione departamento</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer-premium">
                <button type="button" class="btn-cancelar" onclick="cerrarModalPremium()">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn-guardar">
                    <i class="bi bi-save"></i> <span id="btnSubmitTexto">Crear Usuario</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para cambiar estado -->
<form id="formCambiarEstado" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="cambiar_estado">
    <input type="hidden" name="usuario_id" id="usuarioIdEstado">
    <input type="hidden" name="estado" id="nuevoEstado">
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
<script src="../assets/js/cerrar_sesion.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/usuarios.js"></script>

<script>
    // DataTable
    $('#tablaUsuarios').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']]
    });

    // Cambiar estado
    function cambiarEstado(usuarioId, estado) {
        const estadoTexto = estado === 'Activo' ? 'activar' : (estado === 'Suspendido' ? 'suspender' : 'desactivar');

        Swal.fire({
            title: '¿Confirmar acción?',
            text: `¿Desea ${estadoTexto} este usuario?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d4a574',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('usuarioIdEstado').value = usuarioId;
                document.getElementById('nuevoEstado').value = estado;
                document.getElementById('formCambiarEstado').submit();
            }
        });
    }

    // ===== Modal Premium JS =====
    const overlay = document.getElementById('modalUsuarioPremium');

    function abrirModalPremium() {
    // 👇 Solo limpiar si NO venimos de editar
    const accionActual = document.getElementById('accionForm')?.value || '';
    if (accionActual !== 'editar_usuario' && typeof window.abrirModalCrear === 'function') {
        window.abrirModalCrear();   // modo Crear
    }
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    }

    function cerrarModalPremium() {
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Cerrar al click fuera
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) cerrarModalPremium();
    });

    // ESC para cerrar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            cerrarModalPremium();
        }
    });

    // 🔥 TOGGLE PASSWORD MEJORADO (Evita duplicación con usuarios.js)
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('contrasena');
        
        if (toggleBtn && passwordInput) {
            // Remover cualquier listener previo clonando el botón
            const newToggleBtn = toggleBtn.cloneNode(true);
            toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
            
            // Agregar el listener limpio
            newToggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                }
            });
        }
    });

    // Accesibilidad
    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('keydown', e => {
            if(e.key === 'Tab') document.documentElement.classList.add('show-focus');
        }, { once: true });
    });
</script>
</body>
</html>
