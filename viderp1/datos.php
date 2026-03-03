<?php
/**
 * VIDER - MAGA Guatemala
 * Página de Datos - Tabla completa con filtros avanzados
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$db = Database::getInstance();
$currentPage = 'datos';

// Obtener datos para filtros
$departamentos = $db->fetchAll("SELECT id, nombre FROM departamentos ORDER BY nombre");
$dependencias = $db->fetchAll("SELECT id, nombre, siglas FROM dependencias ORDER BY nombre");
$productos = $db->fetchAll("SELECT id, nombre FROM productos ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>Datos | VIDER - MAGA Guatemala</title>
    <style>
        .data-page {
            padding: 2rem;
            max-width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        .filters-panel {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: border-color 0.35s ease, box-shadow 0.35s ease;
        }

        .filters-panel:hover {
            border-color: rgba(74, 144, 217, 0.35);
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .filters-header h3 {
            font-family: var(--font-display);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 0;
        }

        .filter-group label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            min-width: 0;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid var(--glass-border);
        }

        .filter-actions .btn {
            white-space: nowrap;
        }

        .data-table-container {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: border-color 0.35s ease, box-shadow 0.35s ease;
            max-width: 100%;
        }

        .data-table-container:hover {
            border-color: rgba(74, 144, 217, 0.35);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .table-header h3 {
            font-family: var(--font-display);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .records-count {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .records-count strong {
            color: #4a90d9;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 2.5rem;
            width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .data-table-wrapper {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
            max-width: 100%;
        }

        .data-table {
            width: 100%;
            min-width: 1500px;
        }

        .data-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(20, 30, 45, 0.95);
        }

        .data-table th {
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--glass-border);
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }

        .data-table th:hover {
            color: #4a90d9;
            background: rgba(74, 144, 217, 0.1);
        }

        .data-table th.sorted-asc::after {
            content: ' ↑';
            color: #4a90d9;
        }

        .data-table th.sorted-desc::after {
            content: ' ↓';
            color: #4a90d9;
        }

        .data-table td {
            padding: 0.875rem 0.75rem;
            font-size: 0.875rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--glass-border);
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: rgba(74, 144, 217, 0.08);
        }

        .cell-department,
        .cell-municipality {
            font-weight: 500;
        }

        .cell-dependency {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cell-number {
            text-align: right;
            font-family: var(--font-display);
            font-weight: 500;
        }

        .cell-percent {
            text-align: center;
        }

        .progress-mini {
            width: 60px;
            height: 6px;
            background: var(--surface-tertiary);
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 0.5rem;
        }

        .progress-mini-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .progress-low {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .progress-medium {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .progress-high {
            background: linear-gradient(90deg, #22c55e, #4ade80);
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--glass-border);
        }

        .pagination-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .page-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid rgba(74, 144, 217, 0.2);
            background: rgba(20, 30, 45, 0.85);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn:hover:not(:disabled) {
            background: #4a90d9;
            color: white;
            border-color: #4a90d9;
            box-shadow: 0 4px 15px rgba(74, 144, 217, 0.3);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-btn.active {
            background: linear-gradient(135deg, #1a3a5c, #4a90d9);
            color: white;
            border-color: #4a90d9;
        }

        .page-size-select {
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(74, 144, 217, 0.2);
            background: rgba(20, 30, 45, 0.85);
            color: var(--text-primary);
            cursor: pointer;
        }

        .page-size-select:focus {
            outline: none;
            border-color: #4a90d9;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(8, 12, 18, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(74, 144, 217, 0.2);
            border-top-color: #4a90d9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Quick stats - 4 columnas en escritorio */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-stat {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 144, 217, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: border-color 0.35s ease, box-shadow 0.35s ease;
            position: relative;
            overflow: hidden;
        }

        .quick-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1a3a5c, #4a90d9);
        }

        .quick-stat:hover {
            box-shadow: 0 10px 25px rgba(74, 144, 217, 0.2);
            border-color: rgba(74, 144, 217, 0.4);
        }

        .quick-stat-value {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 700;
            color: #4a90d9;
            margin-bottom: 0.25rem;
        }

        .quick-stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Large tablets and small desktops */
        @media (max-width: 1200px) {
            .quick-stats {
                grid-template-columns: repeat(4, 1fr);
            }

            .filters-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Tablets */
        @media (max-width: 1024px) {
            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-actions {
                flex-direction: row;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .data-page {
                padding: 1rem;
                padding-top: 4rem;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-group input,
            .filter-group select {
                font-size: 0.85rem;
                padding: 0.75rem;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
            }

            .search-box input {
                width: 100%;
            }

            .filter-actions {
                flex-direction: column;
                width: 100%;
            }

            .filter-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .data-page {
                padding: 0.75rem;
                padding-top: 4rem;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .quick-stat {
                padding: 0.75rem;
            }

            .quick-stat-value {
                font-size: 1.25rem;
            }

            .quick-stat-label {
                font-size: 0.75rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filter-group label {
                font-size: 0.75rem;
            }

            .table-header {
                padding: 1rem;
            }

            .table-header h3 {
                font-size: 0.9rem;
            }

            .table-info {
                flex-direction: column;
                gap: 0.75rem;
                width: 100%;
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                width: 100%;
            }

            .records-count {
                font-size: 0.8rem;
                text-align: center;
            }

            #btn-export {
                width: 100%;
                justify-content: center;
            }

            .pagination {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .pagination-info {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
                width: 100%;
            }

            .page-size-select {
                margin-left: 0;
                width: 100%;
            }

            .pagination-controls {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-btn {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }

            .data-table {
                min-width: 800px;
            }

            .data-table th,
            .data-table td {
                padding: 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="data-page">
                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-value" id="stat-registros">0</div>
                        <div class="quick-stat-label">Total Registros</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-value" id="stat-beneficiarios">0</div>
                        <div class="quick-stat-label">Beneficiarios</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-value" id="stat-departamentos">0</div>
                        <div class="quick-stat-label">Departamentos</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-value" id="stat-municipios">0</div>
                        <div class="quick-stat-label">Municipios</div>
                    </div>
                </div>

                <!-- Filters Panel -->
                <div class="filters-panel">
                    <div class="filters-header">
                        <h3><i class="fas fa-filter"></i> Filtros Avanzados</h3>
                        <button class="btn btn-sm" id="toggle-filters">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>

                    <div class="filters-body" id="filters-body">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label>Departamento</label>
                                <select id="filter-departamento" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($departamentos as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Municipio</label>
                                <select id="filter-municipio" class="form-control" disabled>
                                    <option value="">Todos</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Dependencia</label>
                                <select id="filter-dependencia" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($dependencias as $dep): ?>
                                        <option value="<?= $dep['id'] ?>">
                                            <?= htmlspecialchars($dep['siglas'] ?? $dep['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Producto</label>
                                <select id="filter-producto" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?= $prod['id'] ?>">
                                            <?= htmlspecialchars(substr($prod['nombre'], 0, 60)) ?>...
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>% Ejecución Mínimo</label>
                                <input type="number" id="filter-ejecucion-min" class="form-control" placeholder="0"
                                    min="0" max="100">
                            </div>

                            <div class="filter-group">
                                <label>% Ejecución Máximo</label>
                                <input type="number" id="filter-ejecucion-max" class="form-control" placeholder="100"
                                    min="0" max="100">
                            </div>

                            <div class="filter-group">
                                <label>Beneficiarios Mínimo</label>
                                <input type="number" id="filter-beneficiarios-min" class="form-control" placeholder="0"
                                    min="0">
                            </div>

                            <div class="filter-group">
                                <label>Beneficiarios Máximo</label>
                                <input type="number" id="filter-beneficiarios-max" class="form-control"
                                    placeholder="Sin límite" min="0">
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button class="btn btn-outline" id="btn-clear-filters">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                            <button class="btn btn-primary" id="btn-apply-filters">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Registros de Ejecución</h3>
                        <div class="table-info">
                            <span class="records-count">
                                Mostrando <strong id="showing-start">0</strong> - <strong id="showing-end">0</strong> de
                                <strong id="total-records">0</strong> registros
                            </span>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search-input" class="form-control"
                                    placeholder="Buscar en tabla...">
                            </div>
                            <button class="btn btn-primary" id="btn-export">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>

                    <div class="data-table-wrapper" style="position: relative;">
                        <div class="loading-overlay" id="loading-overlay">
                            <div class="spinner"></div>
                        </div>

                        <table class="data-table" id="data-table">
                            <thead>
                                <tr>
                                    <th data-sort="departamento">Departamento</th>
                                    <th data-sort="municipio">Municipio</th>
                                    <th data-sort="dependencia">Dependencia</th>
                                    <th data-sort="producto">Producto</th>
                                    <th data-sort="programado">Programado</th>
                                    <th data-sort="ejecutado">Ejecutado</th>
                                    <th data-sort="porcentaje">% Ejec.</th>
                                    <th data-sort="hombres">Hombres</th>
                                    <th data-sort="mujeres">Mujeres</th>
                                    <th data-sort="total">Total</th>
                                    <th data-sort="vigente">Vigente Q.</th>
                                    <th data-sort="fin_ejec">Fin. Ejec. Q.</th>
                                    <th data-sort="fin_porc">Fin. %</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <div class="pagination-info">
                            <span id="pagination-text">Página 1 de 1</span>
                            <select class="page-size-select" id="page-size">
                                <option value="25">25 por página</option>
                                <option value="50" selected>50 por página</option>
                                <option value="100">100 por página</option>
                                <option value="250">250 por página</option>
                            </select>
                        </div>
                        <div class="pagination-controls" id="pagination-controls">
                            <!-- Generated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data management
        let allData = [];
        let filteredData = [];
        let currentPage = 1;
        let pageSize = 50;
        let sortColumn = 'departamento';
        let sortDirection = 'asc';

        document.addEventListener('DOMContentLoaded', function () {
            initMobileMenu();
            loadData();
            initializeFilters();
            initializeSearch();
            initializeSorting();
            initializeExport();
            animateStats();
        });

        // Mobile Menu Functions
        function initMobileMenu() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebar-close');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            if (!menuToggle || !sidebar) return;
            function openSidebar() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('show');
                menuToggle.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
                menuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
            menuToggle.addEventListener('click', openSidebar);
            sidebarClose?.addEventListener('click', closeSidebar);
            sidebarOverlay?.addEventListener('click', closeSidebar);
            sidebar.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => { if (window.innerWidth <= 1024) closeSidebar(); });
            });
            window.addEventListener('resize', () => { if (window.innerWidth > 1024) closeSidebar(); });
        }

        async function loadData() {
            showLoading(true);

            try {
                const response = await fetch('api/get_filtered_data.php');

                // Check if the response is OK (status 200-299)
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
                }

                // Try to parse JSON
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error. Response:', text.substring(0, 500));
                    showToast('Error: Respuesta del servidor inválida', 'error');
                    return;
                }

                if (result.success) {
                    allData = result.data || [];
                    filteredData = [...allData];
                    updateStats(result.stats || {});
                    renderTable();
                    if (allData.length === 0) {
                        showToast('No hay datos disponibles. Importe datos desde el módulo Importar.', 'info');
                    }
                } else {
                    console.error('API Error:', result.message);
                    showToast(result.message || 'Error al cargar datos', 'error');
                }
            } catch (error) {
                console.error('Error de conexión:', error);
                showToast('Error de conexión: ' + error.message, 'error');
            } finally {
                showLoading(false);
            }
        }

        function updateStats(stats) {
            animateValue('stat-registros', stats.total_registros || 0);
            animateValue('stat-beneficiarios', stats.total_beneficiarios || 0);
            animateValue('stat-departamentos', stats.total_departamentos || 0);
            animateValue('stat-municipios', stats.total_municipios || 0);
        }

        function animateValue(elementId, endValue, duration = 1000) {
            const element = document.getElementById(elementId);
            const startValue = 0;
            const startTime = performance.now();

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(startValue + (endValue - startValue) * easeProgress);
                element.textContent = current.toLocaleString('es-GT');

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        }

        function animateStats() {
            document.querySelectorAll('.quick-stat').forEach((stat, index) => {
                stat.style.opacity = '0';
                setTimeout(() => {
                    stat.style.transition = 'opacity 0.5s ease';
                    stat.style.opacity = '1';
                }, index * 100);
            });
        }

        function renderTable() {
            const tbody = document.getElementById('table-body');
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageData = filteredData.slice(start, end);

            if (pageData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No hay datos</h3>
                                <p>No se encontraron registros con los filtros aplicados</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pageData.map(row => `
                    <tr>
                        <td class="cell-department">${escapeHtml(row.departamento)}</td>
                        <td class="cell-municipality">${escapeHtml(row.municipio)}</td>
                        <td class="cell-dependency" title="${escapeHtml(row.dependencia)}">${escapeHtml(row.siglas || row.dependencia?.substring(0, 20) || '-')}</td>
                        <td class="cell-dependency" title="${escapeHtml(row.producto)}">${escapeHtml(row.producto?.substring(0, 30) || '-')}...</td>
                        <td class="cell-number">${formatNumber(row.programado)}</td>
                        <td class="cell-number">${formatNumber(row.ejecutado)}</td>
                        <td class="cell-percent">
                            <div class="progress-mini">
                                <div class="progress-mini-fill ${getProgressClass(row.porcentaje_ejecucion)}" style="width: ${Math.min(row.porcentaje_ejecucion || 0, 100)}%"></div>
                            </div>
                            ${formatPercent(row.porcentaje_ejecucion)}
                        </td>
                        <td class="cell-number">${formatNumber(row.hombres)}</td>
                        <td class="cell-number">${formatNumber(row.mujeres)}</td>
                        <td class="cell-number">${formatNumber(row.total_personas)}</td>
                        <td class="cell-number">${formatCurrency(row.vigente_financiera)}</td>
                        <td class="cell-number">${formatCurrency(row.financiera_ejecutado)}</td>
                        <td class="cell-percent">${formatPercent(row.financiera_porcentaje)}</td>
                    </tr>
                `).join('');
            }

            updatePagination();
        }

        function getProgressClass(percent) {
            if (percent < 33) return 'progress-low';
            if (percent < 66) return 'progress-medium';
            return 'progress-high';
        }

        function updatePagination() {
            const totalPages = Math.ceil(filteredData.length / pageSize) || 1;
            const start = (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, filteredData.length);

            document.getElementById('showing-start').textContent = filteredData.length > 0 ? start : 0;
            document.getElementById('showing-end').textContent = end;
            document.getElementById('total-records').textContent = filteredData.length.toLocaleString('es-GT');
            document.getElementById('pagination-text').textContent = `Página ${currentPage} de ${totalPages}`;

            const controls = document.getElementById('pagination-controls');
            let html = '';

            // Previous button
            html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

            // Page numbers
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                html += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
                if (startPage > 2) html += `<span class="page-btn" style="border: none;">...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += `<span class="page-btn" style="border: none;">...</span>`;
                html += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
            }

            // Next button
            html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

            controls.innerHTML = html;
        }

        function goToPage(page) {
            const totalPages = Math.ceil(filteredData.length / pageSize);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderTable();
                document.querySelector('.data-table-wrapper').scrollTop = 0;
            }
        }

        function initializeFilters() {
            // Department change -> load municipalities
            document.getElementById('filter-departamento').addEventListener('change', async function () {
                const deptId = this.value;
                const munSelect = document.getElementById('filter-municipio');

                if (deptId) {
                    munSelect.disabled = false;
                    try {
                        const response = await fetch(`api/get_municipios.php?departamento_id=${deptId}`);
                        const result = await response.json();

                        munSelect.innerHTML = '<option value="">Todos</option>';
                        if (result.success) {
                            result.data.forEach(mun => {
                                munSelect.innerHTML += `<option value="${mun.id}">${mun.municipio}</option>`;
                            });
                        }
                    } catch (error) {
                        console.error('Error loading municipalities:', error);
                    }
                } else {
                    munSelect.disabled = true;
                    munSelect.innerHTML = '<option value="">Todos</option>';
                }
            });

            // Apply filters
            document.getElementById('btn-apply-filters').addEventListener('click', applyFilters);

            // Clear filters
            document.getElementById('btn-clear-filters').addEventListener('click', clearFilters);

            // Toggle filters
            document.getElementById('toggle-filters').addEventListener('click', function () {
                const body = document.getElementById('filters-body');
                const icon = this.querySelector('i');
                body.style.display = body.style.display === 'none' ? 'block' : 'none';
                icon.className = body.style.display === 'none' ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
            });

            // Page size change
            document.getElementById('page-size').addEventListener('change', function () {
                pageSize = parseInt(this.value);
                currentPage = 1;
                renderTable();
            });
        }

        function applyFilters() {
            const filters = {
                departamento: document.getElementById('filter-departamento').value,
                municipio: document.getElementById('filter-municipio').value,
                dependencia: document.getElementById('filter-dependencia').value,
                producto: document.getElementById('filter-producto').value,
                ejecucionMin: document.getElementById('filter-ejecucion-min').value,
                ejecucionMax: document.getElementById('filter-ejecucion-max').value,
                beneficiariosMin: document.getElementById('filter-beneficiarios-min').value,
                beneficiariosMax: document.getElementById('filter-beneficiarios-max').value
            };

            filteredData = allData.filter(row => {
                if (filters.departamento && row.departamento_id != filters.departamento) return false;
                if (filters.municipio && row.municipio_id != filters.municipio) return false;
                if (filters.dependencia && row.dependencia_id != filters.dependencia) return false;
                if (filters.producto && row.producto_id != filters.producto) return false;
                if (filters.ejecucionMin && (row.porcentaje_ejecucion || 0) < parseFloat(filters.ejecucionMin)) return false;
                if (filters.ejecucionMax && (row.porcentaje_ejecucion || 0) > parseFloat(filters.ejecucionMax)) return false;
                if (filters.beneficiariosMin && (row.total_personas || 0) < parseInt(filters.beneficiariosMin)) return false;
                if (filters.beneficiariosMax && (row.total_personas || 0) > parseInt(filters.beneficiariosMax)) return false;
                return true;
            });

            currentPage = 1;
            renderTable();
            showToast(`${filteredData.length} registros encontrados`, 'success');
        }

        function clearFilters() {
            document.querySelectorAll('.filters-grid select, .filters-grid input').forEach(el => {
                el.value = '';
            });
            document.getElementById('filter-municipio').disabled = true;
            filteredData = [...allData];
            currentPage = 1;
            renderTable();
            showToast('Filtros eliminados', 'info');
        }

        function initializeSearch() {
            let searchTimeout;
            document.getElementById('search-input').addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const term = this.value.toLowerCase().trim();

                    if (term) {
                        filteredData = allData.filter(row =>
                            (row.departamento || '').toLowerCase().includes(term) ||
                            (row.municipio || '').toLowerCase().includes(term) ||
                            (row.dependencia || '').toLowerCase().includes(term) ||
                            (row.producto || '').toLowerCase().includes(term)
                        );
                    } else {
                        filteredData = [...allData];
                    }

                    currentPage = 1;
                    renderTable();
                }, 300);
            });
        }

        function initializeSorting() {
            document.querySelectorAll('.data-table th[data-sort]').forEach(th => {
                th.addEventListener('click', function () {
                    const column = this.dataset.sort;

                    if (sortColumn === column) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortColumn = column;
                        sortDirection = 'asc';
                    }

                    // Update header styles
                    document.querySelectorAll('.data-table th').forEach(header => {
                        header.classList.remove('sorted-asc', 'sorted-desc');
                    });
                    this.classList.add(`sorted-${sortDirection}`);

                    // Sort data
                    filteredData.sort((a, b) => {
                        let valA = a[column] || '';
                        let valB = b[column] || '';

                        // Numeric columns
                        if (['programado', 'ejecutado', 'porcentaje', 'hombres', 'mujeres', 'total', 'vigente', 'fin_ejec', 'fin_porc'].includes(column)) {
                            valA = parseFloat(valA) || 0;
                            valB = parseFloat(valB) || 0;
                        }

                        if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                        if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    });

                    currentPage = 1;
                    renderTable();
                });
            });
        }

        function initializeExport() {
            document.getElementById('btn-export').addEventListener('click', function () {
                // Create modal for export options
                const modal = document.createElement('div');
                modal.className = 'modal active';
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 400px;">
                        <div class="modal-header">
                            <h3><i class="fas fa-download"></i> Exportar Datos</h3>
                            <button class="modal-close" onclick="this.closest('.modal').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p style="margin-bottom: 1rem; color: var(--text-secondary);">
                                Se exportarán <strong>${filteredData.length.toLocaleString('es-GT')}</strong> registros filtrados
                            </p>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <button class="btn btn-primary" onclick="exportData('excel'); this.closest('.modal').remove();">
                                    <i class="fas fa-file-excel"></i> Exportar a Excel
                                </button>
                                <button class="btn btn-outline" onclick="exportData('csv'); this.closest('.modal').remove();">
                                    <i class="fas fa-file-csv"></i> Exportar a CSV
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            });
        }

        async function exportData(format) {
            showLoading(true);

            try {
                const ids = filteredData.map(row => row.id);
                const response = await fetch('api/export.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ format, ids })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `VIDER_Export_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : 'csv'}`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                    showToast('Exportación completada', 'success');
                } else {
                    showToast('Error al exportar', 'error');
                }
            } catch (error) {
                console.error('Export error:', error);
                showToast('Error al exportar datos', 'error');
            } finally {
                showLoading(false);
            }
        }

        function showLoading(show) {
            document.getElementById('loading-overlay').classList.toggle('active', show);
        }

        function formatNumber(num) {
            const n = parseFloat(num) || 0;
            return n.toLocaleString('es-GT');
        }

        function formatCurrency(num) {
            const n = parseFloat(num) || 0;
            return 'Q ' + n.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatPercent(num) {
            const n = parseFloat(num) || 0;
            return n.toFixed(1) + '%';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>