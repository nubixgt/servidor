<?php
/**
 * Header del Sistema
 * Sistema de Ejecución Presupuestaria - MAGA
 */

require_once __DIR__ . '/../config/database.php';

// Verificar sesión - redirigir a login si no está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Gestionar año seleccionado
if (isset($_GET['cambiar_anio'])) {
    $_SESSION['anio_seleccionado'] = intval($_GET['cambiar_anio']);
    // Redirigir a la misma página sin el parámetro
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

// Año por defecto 2025
if (!isset($_SESSION['anio_seleccionado'])) {
    $_SESSION['anio_seleccionado'] = 2025;
}

$anioSeleccionado = $_SESSION['anio_seleccionado'];

$pageTitle = $pageTitle ?? 'Dashboard';

// Obtener meta de ejecución al día desde los datos importados del año seleccionado
$metaEjecucionAlDia = getMetaEjecucionAlDia($anioSeleccionado);

// Obtener nombre del usuario logueado
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioRol = $_SESSION['usuario_rol'] ?? 'viewer';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= APP_NAME ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SheetJS para exportar Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">

    <?php if (isset($extraStyles)): ?>
        <?= $extraStyles ?>
    <?php endif; ?>
</head>

<body>
    <!-- Header Principal -->
    <header class="header">
        <div class="header-top">
            <div class="logo-container">
                <img src="assets/img/maga_logo.png" alt="logo del maga" onerror="this.style.display='none'">
                <div class="header-title">
                    <p><?= INSTITUCION_SIGLAS ?></p>
                    <h1><?= INSTITUCION ?></h1>
                </div>
            </div>

            <div class="header-info">
                <div class="year-buttons-container">
                    <a href="?cambiar_anio=2025" class="year-button <?= $anioSeleccionado == 2025 ? 'active' : '' ?>">
                        <i class="fas fa-calendar"></i>
                        <span>EJECUCIÓN PRESUPUESTARIA 2025</span>
                    </a>
                    <a href="?cambiar_anio=2026" class="year-button year-button-secondary <?= $anioSeleccionado == 2026 ? 'active' : '' ?>">
                        <i class="fas fa-calendar-plus"></i>
                        <span>EJECUCIÓN PRESUPUESTARIA 2026</span>
                    </a>
                </div>
            </div>

            <div class="header-actions">
                <?php if ($metaEjecucionAlDia > 0): ?>
                    <div class="kpi-badge">
                        <div class="kpi-badge-label">Meta al Día</div>
                        <div class="kpi-badge-value"><?= number_format($metaEjecucionAlDia, 2, ',', '.') ?>%</div>
                    </div>
                <?php endif; ?>

                <!-- Usuario logueado -->
                <div class="user-badge" title="<?= htmlspecialchars($usuarioNombre) ?>">
                    <i class="fas fa-user-circle"></i>
                    <span class="user-name"><?= htmlspecialchars($usuarioNombre) ?></span>
                </div>

                <button class="btn btn-secondary" onclick="toggleTheme()" title="Cambiar tema">
                    <i class="fas fa-moon"></i>
                </button>

                <a href="logout.php" class="btn btn-secondary btn-logout" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>

                <button class="menu-toggle" onclick="toggleMobileMenu()" title="Menú">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

        </div>

        <!-- Navegación Principal -->
        <nav class="nav-container">
            <button class="nav-close-btn" onclick="toggleMobileMenu()" title="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
            <div class="main-nav">
                <a href="index.php" class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard Principal</span>
                </a>
                <a href="unidades.php" class="nav-link <?= ($currentPage ?? '') === 'unidades' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i>
                    <span>Unidades Ejecutoras</span>
                </a>
                <a href="ministerios.php"
                    class="nav-link <?= ($currentPage ?? '') === 'ministerios' ? 'active' : '' ?>">
                    <i class="fas fa-landmark"></i>
                    <span>Ministerios</span>
                </a>
                
                <?php if ($usuarioRol === 'admin' || $usuarioRol === 'editor'): ?>
                <a href="administracion.php"
                    class="nav-link <?= ($currentPage ?? '') === 'administracion' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Administración</span>
                </a>
                <?php endif; ?>
                
                <a href="bitacora.php" class="nav-link <?= ($currentPage ?? '') === 'bitacora' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Bitácora</span>
                </a>
                
                <?php if ($usuarioRol === 'admin'): ?>
                <a href="importar.php" class="nav-link <?= ($currentPage ?? '') === 'importar' ? 'active' : '' ?>">
                    <i class="fas fa-file-import"></i>
                    <span>Importar Datos</span>
                </a>
                <a href="usuarios.php" class="nav-link <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Usuarios</span>
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Estilos para los botones de año -->
    <style>
        .year-buttons-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            justify-content: center;
        }

        .year-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .year-button i {
            font-size: 1.1rem;
        }

        .year-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .year-button.active {
            background: white;
            color: var(--primary-color);
            border-color: white;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .year-button.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .year-button-secondary.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .year-button span {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 992px) {
            .year-buttons-container {
                flex-direction: column;
                gap: 0.5rem;
            }

            .year-button {
                width: 100%;
                justify-content: center;
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
            }
        }

        @media (max-width: 768px) {
            .year-buttons-container {
                gap: 0.4rem;
            }

            .year-button {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem;
            }

            .year-button span {
                display: none;
            }

            .year-button::after {
                content: attr(data-year);
            }

            .year-button[href*="2025"]::after {
                content: "2025";
            }

            .year-button[href*="2026"]::after {
                content: "2026";
            }
        }
    </style>

    <!-- Contenido Principal -->
    <main class="main-content">