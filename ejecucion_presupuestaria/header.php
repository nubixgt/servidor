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

// Obtener meta de ejecución al día desde los datos importados
$metaEjecucionAlDia = getMetaEjecucionAlDia();

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
                <div class="header-title-dropdown">
                    <h2 class="header-main-title clickable" id="headerTitleDropdown">
                        <span>EJECUCIÓN PRESUPUESTARIA <?= $anioSeleccionado ?></span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </h2>
                    <div class="year-dropdown-menu" id="yearDropdownMenu">
                        <a href="?cambiar_anio=2025" class="year-option <?= $anioSeleccionado == 2025 ? 'active' : '' ?>">
                            <i class="fas fa-calendar"></i>
                            <div class="year-option-content">
                                <strong>Datos 2025</strong>
                                <small>Año fiscal 2025</small>
                            </div>
                            <?php if ($anioSeleccionado == 2025): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </a>
                        <a href="?cambiar_anio=2026" class="year-option <?= $anioSeleccionado == 2026 ? 'active' : '' ?>">
                            <i class="fas fa-calendar-plus"></i>
                            <div class="year-option-content">
                                <strong>Datos 2026</strong>
                                <small>Año fiscal 2026</small>
                            </div>
                            <?php if ($anioSeleccionado == 2026): ?>
                                <i class="fas fa-check"></i>
                            <?php endif; ?>
                        </a>
                    </div>
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

    <!-- Estilos para el dropdown de año -->
    <style>
        .header-title-dropdown {
            position: relative;
            display: inline-block;
        }

        .header-main-title.clickable {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .header-main-title.clickable:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dropdown-icon {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .header-title-dropdown.open .dropdown-icon {
            transform: rotate(180deg);
        }

        .year-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .header-title-dropdown.open .year-dropdown-menu,
        .header-title-dropdown:hover .year-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .year-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }

        .year-option:last-child {
            margin-bottom: 0;
        }

        .year-option:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(4px);
        }

        .year-option.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .year-option i:first-child {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        .year-option-content {
            flex: 1;
        }

        .year-option-content strong {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 0.15rem;
        }

        .year-option-content small {
            display: block;
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .year-option .fa-check {
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .year-dropdown-menu {
                left: 0;
                transform: translateX(0);
                right: 0;
                margin: 0 1rem;
                min-width: auto;
            }

            .header-title-dropdown.open .year-dropdown-menu,
            .header-title-dropdown:hover .year-dropdown-menu {
                transform: translateX(0) translateY(0);
            }
        }
    </style>

    <!-- Script para el dropdown -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownContainer = document.querySelector('.header-title-dropdown');
            const dropdownTrigger = document.getElementById('headerTitleDropdown');
            const dropdownMenu = document.getElementById('yearDropdownMenu');

            if (dropdownTrigger && dropdownMenu) {
                // Toggle al hacer clic
                dropdownTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownContainer.classList.toggle('open');
                });

                // Cerrar al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!dropdownContainer.contains(e.target)) {
                        dropdownContainer.classList.remove('open');
                    }
                });

                // Prevenir cierre al hacer clic dentro del menú
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>

    <!-- Contenido Principal -->
    <main class="main-content">