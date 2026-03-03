<?php
/**
 * TOBANIK - Módulo de Cooperativas
 * VIDER - MAGA Guatemala
 * Sistema de Crédito para Cooperativas
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$db = Database::getInstance();
$currentPage = 'tobanik';

// Obtener departamentos para filtro
$departamentos = $db->fetchAll("SELECT id, nombre FROM departamentos ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>TOBANIK - Cooperativas | VIDER - MAGA Guatemala</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .tobanik-page {
            padding: 2rem;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Header del módulo */
        .module-header {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #1a5f2a 0%, #2d7a3e 50%, #4ade80 100%);
            color: white;
            border-radius: 20px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(45, 122, 62, 0.3);
        }

        .module-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmerTitle 3s ease-in-out infinite;
        }

        @keyframes shimmerTitle {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .module-header h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .module-header p {
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
        }

        .module-header .header-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        /* Filtros */
        .filters-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filters-section label {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-section select {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            color: var(--text-primary);
            font-size: 0.95rem;
            min-width: 220px;
            cursor: pointer;
        }

        .filters-section select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.2);
        }

        .btn-filter {
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #1a5f2a, #2d7a3e);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(45, 122, 62, 0.4);
        }

        .btn-clear {
            background: linear-gradient(135deg, #64748b, #94a3b8);
        }

        .btn-clear:hover {
            box-shadow: 0 5px 20px rgba(100, 116, 139, 0.4);
        }

        /* KPIs Grid */
        .kpis-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.35s ease;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a5f2a, #4ade80);
        }

        .kpi-card.blue::before {
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }

        .kpi-card.yellow::before {
            background: linear-gradient(90deg, #ca8a04, #fcd34d);
        }

        .kpi-card.purple::before {
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            border-color: rgba(74, 222, 128, 0.5);
        }

        .kpi-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .kpi-card:nth-child(1) .kpi-icon { color: #4ade80; }
        .kpi-card:nth-child(2) .kpi-icon { color: #60a5fa; }
        .kpi-card:nth-child(3) .kpi-icon { color: #fcd34d; }
        .kpi-card:nth-child(4) .kpi-icon { color: #a78bfa; }

        .kpi-value {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .kpi-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            border-color: rgba(74, 222, 128, 0.4);
        }

        .chart-card h3 {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-card h3 i {
            color: #4ade80;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .chart-card.full-width {
            grid-column: span 2;
        }

        /* Tabla de cooperativas */
        .table-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-header h3 {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-header h3 i {
            color: #4ade80;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 0.5rem 1rem;
        }

        .search-box input {
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 0.9rem;
            width: 200px;
        }

        .search-box input:focus {
            outline: none;
        }

        .search-box input::placeholder {
            color: var(--text-secondary);
        }

        .search-box i {
            color: var(--text-secondary);
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .data-table thead {
            background: linear-gradient(135deg, #1a5f2a, #2d7a3e);
            color: white;
        }

        .data-table th {
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }

        .data-table th:first-child {
            border-radius: 10px 0 0 0;
        }

        .data-table th:last-child {
            border-radius: 0 10px 0 0;
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--glass-border);
            transition: background 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: rgba(74, 222, 128, 0.1);
        }

        .data-table td {
            padding: 0.85rem 0.75rem;
            color: var(--text-primary);
        }

        .data-table .monto {
            font-family: var(--font-display);
            font-weight: 600;
            color: #4ade80;
        }

        .data-table .numero {
            font-family: var(--font-display);
            font-weight: 600;
            color: #60a5fa;
        }

        .badge-depto {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        /* Loading */
        .loading-overlay {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--glass-border);
            border-top-color: #4ade80;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Top cooperativas lista */
        .top-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .top-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .top-item:hover {
            background: rgba(74, 222, 128, 0.15);
        }

        .top-rank {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            background: linear-gradient(135deg, #1a5f2a, #2d7a3e);
            color: white;
        }

        .top-item:nth-child(1) .top-rank { background: linear-gradient(135deg, #ca8a04, #fcd34d); }
        .top-item:nth-child(2) .top-rank { background: linear-gradient(135deg, #64748b, #94a3b8); }
        .top-item:nth-child(3) .top-rank { background: linear-gradient(135deg, #b45309, #f59e0b); }

        .top-info {
            flex: 1;
            min-width: 0;
        }

        .top-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .top-sede {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .top-monto {
            font-family: var(--font-display);
            font-weight: 700;
            color: #4ade80;
            font-size: 0.95rem;
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .kpis-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .chart-card.full-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .tobanik-page {
                padding: 1rem;
            }
            .module-header h1 {
                font-size: 1.75rem;
            }
            .kpis-grid {
                grid-template-columns: 1fr;
            }
            .kpi-value {
                font-size: 1.5rem;
            }
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filters-section select {
                min-width: 100%;
            }
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                width: 100%;
            }
            .search-box input {
                width: 100%;
            }
        }

        /* Tema claro */
        [data-theme="light"] .kpi-card,
        [data-theme="light"] .chart-card,
        [data-theme="light"] .table-section,
        [data-theme="light"] .filters-section {
            background: rgba(255, 255, 255, 0.85);
        }

        [data-theme="light"] .data-table tbody tr:hover {
            background: rgba(74, 222, 128, 0.15);
        }

        [data-theme="light"] .top-item {
            background: rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .top-item:hover {
            background: rgba(74, 222, 128, 0.15);
        }

        [data-theme="light"] .search-box {
            background: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="tobanik-page">
            <!-- Header del módulo -->
            <div class="module-header">
                <i class="fas fa-handshake header-icon"></i>
                <h1>TOBANIK - Cooperativas</h1>
                <p>Sistema de Crédito y Apoyo a Cooperativas Agrícolas de Guatemala</p>
            </div>

            <!-- Filtros -->
            <div class="filters-section">
                <label>
                    <i class="fas fa-filter"></i> Filtrar por:
                </label>
                <select id="filtro-departamento">
                    <option value="">Todos los Departamentos</option>
                    <?php foreach ($departamentos as $depto): ?>
                        <option value="<?= $depto['id'] ?>"><?= htmlspecialchars($depto['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-filter" onclick="cargarDatos()">
                    <i class="fas fa-search"></i> Aplicar
                </button>
                <button class="btn-filter btn-clear" onclick="limpiarFiltros()">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>

            <!-- KPIs -->
            <div class="kpis-grid">
                <div class="kpi-card">
                    <i class="fas fa-users kpi-icon"></i>
                    <div class="kpi-value" id="kpi-cooperativas">0</div>
                    <div class="kpi-label">Total Cooperativas</div>
                </div>
                <div class="kpi-card blue">
                    <i class="fas fa-user-friends kpi-icon"></i>
                    <div class="kpi-value" id="kpi-productores">0</div>
                    <div class="kpi-label">Total Productores</div>
                </div>
                <div class="kpi-card yellow">
                    <i class="fas fa-coins kpi-icon"></i>
                    <div class="kpi-value" id="kpi-monto-colocado">Q 0</div>
                    <div class="kpi-label">Monto Colocado</div>
                </div>
                <div class="kpi-card purple">
                    <i class="fas fa-hand-holding-usd kpi-icon"></i>
                    <div class="kpi-value" id="kpi-monto-otorgado">Q 0</div>
                    <div class="kpi-label">Monto Otorgado</div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-grid">
                <!-- Top Cooperativas -->
                <div class="chart-card">
                    <h3><i class="fas fa-trophy"></i> Top 10 Cooperativas por Monto</h3>
                    <div class="chart-container">
                        <canvas id="chart-top-cooperativas"></canvas>
                    </div>
                </div>

                <!-- Distribución por Departamento -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Productores por Departamento</h3>
                    <div class="chart-container">
                        <canvas id="chart-productores"></canvas>
                    </div>
                </div>

                <!-- Montos por Departamento -->
                <div class="chart-card">
                    <h3><i class="fas fa-map-marked-alt"></i> Montos por Departamento</h3>
                    <div class="chart-container">
                        <canvas id="chart-departamentos"></canvas>
                    </div>
                </div>

                <!-- Distribución por Rango -->
                <div class="chart-card">
                    <h3><i class="fas fa-layer-group"></i> Cooperativas por Rango de Monto</h3>
                    <div class="chart-container">
                        <canvas id="chart-rangos"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla de Cooperativas -->
            <div class="table-section">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Listado de Cooperativas</h3>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscar-cooperativa" placeholder="Buscar cooperativa..." onkeyup="filtrarTabla()">
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="tabla-cooperativas">
                        <thead>
                            <tr>
                                <th>Cooperativa</th>
                                <th>Sede</th>
                                <th>Departamento</th>
                                <th>Productores</th>
                                <th>Monto Colocado</th>
                                <th>Monto Otorgado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-cooperativas">
                            <tr>
                                <td colspan="6">
                                    <div class="loading-overlay">
                                        <div class="spinner"></div>
                                        <span>Cargando datos...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Variables globales para gráficos
        let chartTopCooperativas = null;
        let chartProductores = null;
        let chartDepartamentos = null;
        let chartRangos = null;
        let todasLasCooperativas = [];

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            cargarDatos();
        });

        // Cargar datos desde la API
        async function cargarDatos() {
            const departamento = document.getElementById('filtro-departamento').value;
            const params = departamento ? `?departamento=${departamento}` : '';

            try {
                const response = await fetch(`api/get_tobanik_stats.php${params}`);
                const data = await response.json();

                if (data.success) {
                    actualizarKPIs(data.totales);
                    actualizarGraficos(data);
                    actualizarTabla(data.todasCooperativas);
                    todasLasCooperativas = data.todasCooperativas;
                } else {
                    console.error('Error:', data.message);
                    mostrarEstadoVacio();
                }
            } catch (error) {
                console.error('Error al cargar datos:', error);
                mostrarEstadoVacio();
            }
        }

        // Actualizar KPIs
        function actualizarKPIs(totales) {
            document.getElementById('kpi-cooperativas').textContent = formatNumber(totales.total_cooperativas);
            document.getElementById('kpi-productores').textContent = formatNumber(totales.total_productores);
            document.getElementById('kpi-monto-colocado').textContent = formatCurrency(totales.total_monto_colocado);
            document.getElementById('kpi-monto-otorgado').textContent = formatCurrency(totales.total_monto_otorgado);
        }

        // Actualizar gráficos
        function actualizarGraficos(data) {
            // Destruir gráficos existentes
            if (chartTopCooperativas) chartTopCooperativas.destroy();
            if (chartProductores) chartProductores.destroy();
            if (chartDepartamentos) chartDepartamentos.destroy();
            if (chartRangos) chartRangos.destroy();

            // Colores para gráficos
            const colores = [
                '#4ade80', '#60a5fa', '#fcd34d', '#a78bfa', '#f472b6',
                '#34d399', '#38bdf8', '#facc15', '#c084fc', '#fb7185'
            ];

            const coloresTransparentes = colores.map(c => c + '80');

            // Top Cooperativas (Barras horizontales)
            const ctxTop = document.getElementById('chart-top-cooperativas').getContext('2d');
            chartTopCooperativas = new Chart(ctxTop, {
                type: 'bar',
                data: {
                    labels: data.topCooperativas.map(c => truncarTexto(c.nombre_cooperativa, 25)),
                    datasets: [{
                        label: 'Monto Colocado',
                        data: data.topCooperativas.map(c => parseFloat(c.monto_colocado)),
                        backgroundColor: coloresTransparentes,
                        borderColor: colores,
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => 'Q ' + formatNumber(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: (val) => 'Q' + formatCompact(val),
                                color: 'rgba(255,255,255,0.7)'
                            },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        },
                        y: {
                            ticks: { color: 'rgba(255,255,255,0.7)' },
                            grid: { display: false }
                        }
                    }
                }
            });

            // Productores por Departamento (Dona)
            const ctxProd = document.getElementById('chart-productores').getContext('2d');
            chartProductores = new Chart(ctxProd, {
                type: 'doughnut',
                data: {
                    labels: data.distribucionProductores.map(d => d.departamento),
                    datasets: [{
                        data: data.distribucionProductores.map(d => parseInt(d.productores)),
                        backgroundColor: colores,
                        borderColor: 'rgba(20, 30, 45, 0.8)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: 'rgba(255,255,255,0.8)', padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.label + ': ' + formatNumber(ctx.raw) + ' productores'
                            }
                        }
                    }
                }
            });

            // Montos por Departamento (Barras)
            const ctxDepto = document.getElementById('chart-departamentos').getContext('2d');
            chartDepartamentos = new Chart(ctxDepto, {
                type: 'bar',
                data: {
                    labels: data.porDepartamento.slice(0, 10).map(d => d.departamento),
                    datasets: [{
                        label: 'Monto Colocado',
                        data: data.porDepartamento.slice(0, 10).map(d => parseFloat(d.monto_colocado)),
                        backgroundColor: 'rgba(74, 222, 128, 0.6)',
                        borderColor: '#4ade80',
                        borderWidth: 2,
                        borderRadius: 6
                    }, {
                        label: 'Monto Otorgado',
                        data: data.porDepartamento.slice(0, 10).map(d => parseFloat(d.monto_otorgado)),
                        backgroundColor: 'rgba(96, 165, 250, 0.6)',
                        borderColor: '#60a5fa',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: 'rgba(255,255,255,0.8)' }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ': Q ' + formatNumber(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'rgba(255,255,255,0.7)', maxRotation: 45 },
                            grid: { display: false }
                        },
                        y: {
                            ticks: {
                                callback: (val) => 'Q' + formatCompact(val),
                                color: 'rgba(255,255,255,0.7)'
                            },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        }
                    }
                }
            });

            // Rangos de Monto (Dona)
            const ctxRangos = document.getElementById('chart-rangos').getContext('2d');
            chartRangos = new Chart(ctxRangos, {
                type: 'pie',
                data: {
                    labels: data.rangoMontos.map(r => r.rango),
                    datasets: [{
                        data: data.rangoMontos.map(r => parseInt(r.cantidad)),
                        backgroundColor: ['#64748b', '#4ade80', '#60a5fa', '#fcd34d', '#a78bfa', '#f472b6'],
                        borderColor: 'rgba(20, 30, 45, 0.8)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: 'rgba(255,255,255,0.8)', padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.label + ': ' + ctx.raw + ' cooperativas'
                            }
                        }
                    }
                }
            });
        }

        // Actualizar tabla
        function actualizarTabla(cooperativas) {
            const tbody = document.getElementById('tbody-cooperativas');

            if (!cooperativas || cooperativas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No hay datos disponibles</h3>
                                <p>No se encontraron cooperativas con los filtros seleccionados</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = cooperativas.map(c => `
                <tr>
                    <td><strong>${escapeHtml(c.nombre_cooperativa)}</strong></td>
                    <td>${escapeHtml(c.sede || '-')}</td>
                    <td><span class="badge-depto">${escapeHtml(c.departamento || 'N/A')}</span></td>
                    <td class="numero">${formatNumber(c.cantidad_productores)}</td>
                    <td class="monto">Q ${formatNumber(c.monto_colocado)}</td>
                    <td class="monto">Q ${formatNumber(c.monto_otorgado)}</td>
                </tr>
            `).join('');
        }

        // Filtrar tabla
        function filtrarTabla() {
            const busqueda = document.getElementById('buscar-cooperativa').value.toLowerCase();
            const filtradas = todasLasCooperativas.filter(c => 
                c.nombre_cooperativa.toLowerCase().includes(busqueda) ||
                (c.sede && c.sede.toLowerCase().includes(busqueda)) ||
                (c.departamento && c.departamento.toLowerCase().includes(busqueda))
            );
            actualizarTabla(filtradas);
        }

        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById('filtro-departamento').value = '';
            document.getElementById('buscar-cooperativa').value = '';
            cargarDatos();
        }

        // Estado vacío
        function mostrarEstadoVacio() {
            document.getElementById('kpi-cooperativas').textContent = '0';
            document.getElementById('kpi-productores').textContent = '0';
            document.getElementById('kpi-monto-colocado').textContent = 'Q 0';
            document.getElementById('kpi-monto-otorgado').textContent = 'Q 0';

            document.getElementById('tbody-cooperativas').innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <h3>Sin datos de cooperativas</h3>
                            <p>Importe un archivo Excel con la hoja TOBANIK para ver los datos</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        // Utilidades
        function formatNumber(num) {
            if (num === null || num === undefined) return '0';
            return parseFloat(num).toLocaleString('es-GT', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function formatCurrency(num) {
            if (num === null || num === undefined) return 'Q 0';
            const valor = parseFloat(num);
            if (valor >= 1000000) {
                return 'Q ' + (valor / 1000000).toFixed(2) + 'M';
            } else if (valor >= 1000) {
                return 'Q ' + (valor / 1000).toFixed(1) + 'K';
            }
            return 'Q ' + formatNumber(valor);
        }

        function formatCompact(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
            return num.toString();
        }

        function truncarTexto(texto, maxLen) {
            if (!texto) return '';
            return texto.length > maxLen ? texto.substring(0, maxLen) + '...' : texto;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>