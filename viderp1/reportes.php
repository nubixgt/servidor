<?php
/**
 * VIDER - MAGA Guatemala
 * Página de Reportes - Generación de informes y exportación
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$db = Database::getInstance();
$currentPage = 'reportes';

// Obtener datos para filtros
$departamentos = $db->fetchAll("SELECT id, nombre FROM departamentos ORDER BY nombre");
$dependencias = $db->fetchAll("SELECT id, nombre, siglas FROM dependencias ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>Reportes | VIDER - MAGA Guatemala</title>
    <style>
        .reports-page {
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-secondary);
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .report-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .report-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .report-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .report-icon.excel {
            background: linear-gradient(135deg, #217346, #2db165);
            color: white;
        }

        .report-icon.pdf {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }

        .report-icon.chart {
            background: linear-gradient(135deg, #0066cc, #4da3ff);
            color: white;
        }

        .report-icon.summary {
            background: linear-gradient(135deg, #f7b928, #ffd700);
            color: white;
        }

        .report-card-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .report-card-subtitle {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .report-card-body {
            margin-bottom: 1.5rem;
        }

        .report-card-body p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .report-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .report-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-option label {
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .report-option select {
            flex: 1;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--surface-primary);
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .report-card-footer {
            display: flex;
            gap: 0.75rem;
        }

        .report-card-footer .btn {
            flex: 1;
        }

        .preview-section {
            margin-top: 2rem;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .preview-header h3 {
            font-family: var(--font-display);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-content {
            min-height: 300px;
            border: 2px dashed var(--glass-border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }

        .preview-content.has-content {
            border-style: solid;
            display: block;
            padding: 1rem;
        }

        .preview-placeholder {
            text-align: center;
        }

        .preview-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .preview-placeholder p {
            font-size: 1rem;
        }

        /* Quick stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-stat {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .quick-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.25rem;
        }

        .quick-stat-icon.green {
            background: rgba(74, 144, 217, 0.15);
            color: #4a90d9;
        }

        .quick-stat-icon.blue {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .quick-stat-icon.yellow {
            background: rgba(247, 185, 40, 0.15);
            color: #f7b928;
        }

        .quick-stat-icon.red {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .quick-stat-value {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .quick-stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        @media (max-width: 1024px) {
            .reports-page {
                padding-top: 4rem;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .reports-page {
                padding: 1rem;
                padding-top: 4.5rem;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .quick-stat {
                padding: 1rem;
            }

            .quick-stat-value {
                font-size: 1.25rem;
            }

            .reports-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .report-card {
                padding: 1.25rem;
            }

            .report-card-footer {
                flex-direction: column;
            }

            .preview-section {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .reports-page {
                padding: 0.75rem;
                padding-top: 4.5rem;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .quick-stat {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 0.75rem;
                text-align: left;
            }

            .quick-stat-icon {
                margin: 0;
            }

            .quick-stat-value {
                font-size: 1.15rem;
            }

            .report-card-header {
                flex-direction: column;
                text-align: center;
            }

            .report-icon {
                margin: 0 auto;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="reports-page">
                <div class="page-header stagger">
                    <h1><i class="fas fa-file-alt"></i> Centro de Reportes</h1>
                    <p>Genera y descarga reportes personalizados de los datos de VIDER</p>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats stagger">
                    <div class="quick-stat">
                        <div class="quick-stat-icon green">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="quick-stat-value" id="stat-exports">0</div>
                        <div class="quick-stat-label">Exportaciones Hoy</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon blue">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="quick-stat-value" id="stat-records">0</div>
                        <div class="quick-stat-label">Registros Disponibles</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon yellow">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="quick-stat-value" id="stat-deps">22</div>
                        <div class="quick-stat-label">Departamentos</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon red">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="quick-stat-value" id="stat-last">-</div>
                        <div class="quick-stat-label">Última Actualización</div>
                    </div>
                </div>

                <!-- Reports Grid -->
                <div class="reports-grid stagger">
                    <!-- Reporte General Excel -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon excel">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Reporte General</h3>
                                <p class="report-card-subtitle">Exportación completa a Excel</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <p>Exporta todos los datos de ejecución física y financiera con información completa de cada
                                registro.</p>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateReport('general', 'excel')">
                                <i class="fas fa-download"></i> Descargar Excel
                            </button>
                            <button class="btn btn-outline" onclick="generateReport('general', 'csv')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                    </div>

                    <!-- Reporte por Departamento -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon chart">
                                <i class="fas fa-map"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Reporte por Departamento</h3>
                                <p class="report-card-subtitle">Datos por área geográfica</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <div class="report-options">
                                <div class="report-option">
                                    <label>Departamento:</label>
                                    <select id="report-dept">
                                        <option value="">Todos los departamentos</option>
                                        <?php foreach ($departamentos as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateDeptReport()">
                                <i class="fas fa-download"></i> Generar Reporte
                            </button>
                        </div>
                    </div>

                    <!-- Reporte por Dependencia -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon summary">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Reporte por Dependencia</h3>
                                <p class="report-card-subtitle">Datos por unidad ejecutora</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <div class="report-options">
                                <div class="report-option">
                                    <label>Dependencia:</label>
                                    <select id="report-dep">
                                        <option value="">Todas las dependencias</option>
                                        <?php foreach ($dependencias as $dep): ?>
                                            <option value="<?= $dep['id'] ?>">
                                                <?= htmlspecialchars($dep['siglas'] ?? substr($dep['nombre'], 0, 30)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateDepReport()">
                                <i class="fas fa-download"></i> Generar Reporte
                            </button>
                        </div>
                    </div>

                    <!-- Resumen Ejecutivo -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon pdf">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Resumen Ejecutivo</h3>
                                <p class="report-card-subtitle">Informe consolidado</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <p>Genera un resumen ejecutivo con las estadísticas principales, gráficos y datos
                                consolidados.</p>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateSummaryReport()">
                                <i class="fas fa-download"></i> Generar PDF
                            </button>
                        </div>
                    </div>

                    <!-- Comparativo -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon chart">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Análisis Comparativo</h3>
                                <p class="report-card-subtitle">Comparación entre áreas</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <p>Compara la ejecución física y financiera entre departamentos o dependencias.</p>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateComparativeReport()">
                                <i class="fas fa-download"></i> Generar Análisis
                            </button>
                        </div>
                    </div>

                    <!-- Beneficiarios -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon summary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 class="report-card-title">Reporte de Beneficiarios</h3>
                                <p class="report-card-subtitle">Análisis por género y área</p>
                            </div>
                        </div>
                        <div class="report-card-body">
                            <p>Análisis detallado de beneficiarios por género, departamento y tipo de intervención.</p>
                        </div>
                        <div class="report-card-footer">
                            <button class="btn btn-primary" onclick="generateBeneficiariesReport()">
                                <i class="fas fa-download"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="preview-section stagger">
                    <div class="preview-header">
                        <h3><i class="fas fa-eye"></i> Vista Previa</h3>
                        <button class="btn btn-sm" id="btn-fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    <div class="preview-content" id="preview-content">
                        <div class="preview-placeholder">
                            <i class="fas fa-file-alt"></i>
                            <p>Selecciona un reporte para ver la vista previa</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initMobileMenu();
            loadStats();
            animateCards();
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

        async function loadStats() {
            try {
                const response = await fetch('api/get_dashboard_stats.php');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('stat-records').textContent =
                        (result.data.total_beneficiarios || 0).toLocaleString('es-GT');
                    document.getElementById('stat-deps').textContent =
                        Object.keys(result.data.por_departamento || {}).length;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function animateCards() {
            document.querySelectorAll('.report-card').forEach((card, index) => {
                card.style.animation = `fadeIn 0.5s ease ${index * 0.1}s forwards`;
                card.style.opacity = '0';
            });
        }

        async function generateReport(type, format) {
            showToast('Generando reporte...', 'info');

            try {
                const response = await fetch('api/export.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ format, type })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    downloadBlob(blob, `VIDER_${type}_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : 'csv'}`);
                    showToast('Reporte generado exitosamente', 'success');
                } else {
                    showToast('Error al generar reporte', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            }
        }

        async function generateDeptReport() {
            const deptId = document.getElementById('report-dept').value;
            const deptName = document.getElementById('report-dept').selectedOptions[0].text;

            showToast(`Generando reporte para ${deptName}...`, 'info');

            try {
                const url = deptId
                    ? `api/export.php?departamento_id=${deptId}`
                    : 'api/export.php';

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ format: 'excel', departamento_id: deptId })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const filename = deptId
                        ? `VIDER_${deptName.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`
                        : `VIDER_Todos_Departamentos_${new Date().toISOString().split('T')[0]}.xlsx`;
                    downloadBlob(blob, filename);
                    showToast('Reporte generado exitosamente', 'success');
                } else {
                    showToast('Error al generar reporte', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            }
        }

        async function generateDepReport() {
            const depId = document.getElementById('report-dep').value;
            const depName = document.getElementById('report-dep').selectedOptions[0].text;

            showToast(`Generando reporte para ${depName}...`, 'info');

            try {
                const response = await fetch('api/export.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ format: 'excel', dependencia_id: depId })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    downloadBlob(blob, `VIDER_${depName.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`);
                    showToast('Reporte generado exitosamente', 'success');
                } else {
                    showToast('Error al generar reporte', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión', 'error');
            }
        }

        function generateSummaryReport() {
            showPreview('summary');
            showToast('Generando resumen ejecutivo...', 'info');

            // Generate PDF summary (would require a PDF library)
            setTimeout(() => {
                showToast('Funcionalidad PDF en desarrollo', 'info');
            }, 1000);
        }

        function generateComparativeReport() {
            showPreview('comparative');
            showToast('Generando análisis comparativo...', 'info');
        }

        function generateBeneficiariesReport() {
            showPreview('beneficiaries');
            generateReport('beneficiarios', 'excel');
        }

        async function showPreview(type) {
            const container = document.getElementById('preview-content');
            container.classList.add('has-content');

            container.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner" style="margin: 0 auto;"></div><p style="margin-top: 1rem;">Cargando vista previa...</p></div>';

            try {
                const response = await fetch('api/get_dashboard_stats.php');
                const result = await response.json();

                if (result.success) {
                    container.innerHTML = generatePreviewContent(type, result.data);
                }
            } catch (error) {
                container.innerHTML = '<div class="preview-placeholder"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar vista previa</p></div>';
            }
        }

        function generatePreviewContent(type, data) {
            const totalBeneficiarios = data.total_beneficiarios || 0;
            const totalHombres = data.total_hombres || 0;
            const totalMujeres = data.total_mujeres || 0;

            return `
                <div style="padding: 1rem;">
                    <h4 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-chart-pie"></i> Resumen de Datos
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div style="background: var(--surface-secondary); padding: 1rem; border-radius: 12px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${totalBeneficiarios.toLocaleString('es-GT')}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Total Beneficiarios</div>
                        </div>
                        <div style="background: var(--surface-secondary); padding: 1rem; border-radius: 12px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;">${totalHombres.toLocaleString('es-GT')}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Hombres</div>
                        </div>
                        <div style="background: var(--surface-secondary); padding: 1rem; border-radius: 12px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #ec4899;">${totalMujeres.toLocaleString('es-GT')}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Mujeres</div>
                        </div>
                    </div>
                    <canvas id="preview-chart" style="margin-top: 1.5rem; max-height: 200px;"></canvas>
                </div>
            `;
        }

        function downloadBlob(blob, filename) {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
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