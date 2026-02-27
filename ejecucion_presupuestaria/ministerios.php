<?php
/**
 * Comparativa de Ministerios
 * Sistema de Ejecución Presupuestaria - MAGA
 */

$pageTitle = 'Ministerios';
$currentPage = 'ministerios';

require_once 'config/database.php';

$db = getDB();

// Obtener año seleccionado desde la sesión
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// Obtener datos de ministerios desde la base de datos del año seleccionado
$stmt = $db->prepare("
    SELECT 
        m.nombre,
        m.siglas,
        em.vigente,
        em.devengado,
        em.porcentaje_ejecucion as ejecucion
    FROM ejecucion_ministerios em
    JOIN ministerios m ON em.ministerio_id = m.id
    WHERE em.anio = ?
    ORDER BY em.porcentaje_ejecucion DESC
");
$stmt->execute([$anioSeleccionado]);
$ministerios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular posición de MAGA
$posicionMaga = 0;
$ejecucionMaga = 0;
$totalMinisterios = count($ministerios);

foreach ($ministerios as $index => $m) {
    if (stripos($m['siglas'], 'MAGA') !== false || stripos($m['nombre'], 'AGRICULTURA') !== false) {
        $posicionMaga = $index + 1;
        $ejecucionMaga = $m['ejecucion'];
        break;
    }
}

require_once 'includes/header.php';
?>

<?php if (!empty($ministerios)): ?>
<!-- Indicador de posición de MAGA -->
<div class="alert alert-info mb-3" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
        <strong>MAGA</strong> se encuentra en la posición <strong>#<?= $posicionMaga ?: 'N/A' ?></strong> de <?= $totalMinisterios ?> ministerios en ejecución presupuestaria
    </div>
    <div class="kpi-badge" style="animation: none;">
        <div class="kpi-badge-label">% Ejecución MAGA</div>
        <div class="kpi-badge-value"><?= number_format($ejecucionMaga, 2) ?>%</div>
    </div>
</div>
<?php endif; ?>

<!-- Gráfica comparativa -->
<?php if (!empty($ministerios)): ?>
<div class="charts-grid" style="grid-template-columns: 1fr;">
    <div class="chart-card">
        <div class="chart-header">
            <h4><i class="fas fa-chart-bar"></i> Comparativa de Ejecución por Ministerio</h4>
        </div>
        <div class="chart-body" style="height: 500px;">
            <canvas id="chartMinisterios"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de Ministerios -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-landmark"></i> Ejecución Presupuestaria por Ministerio</h3>
        <?php if (!empty($ministerios)): ?>
        <div class="table-actions">
            <button class="btn btn-primary" onclick="exportToExcel('tablaMinisterios', 'ministerios')" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="tablaMinisterios">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th class="sortable">Ministerio</th>
                    <th class="sortable numeric">Vigente</th>
                    <th class="sortable numeric">Devengado</th>
                    <th class="sortable numeric">% Ejecución</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ministerios)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 3rem; color: var(--text-secondary);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                        <p style="margin: 0; font-size: 1.1rem;">No hay datos de ministerios</p>
                        <p style="margin: 0.5rem 0 0; font-size: 0.9rem; opacity: 0.7;">Importa datos desde la sección <a href="importar.php" style="color: var(--primary-color);">Importar Datos</a> seleccionando "MINISTERIOS"</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($ministerios as $index => $m): 
                    $colorEjecucion = getSemaforoColor($m['ejecucion']);
                    $esMaga = stripos($m['siglas'], 'MAGA') !== false || stripos($m['nombre'], 'AGRICULTURA') !== false;
                ?>
                <tr class="<?= $esMaga ? 'highlight-row' : '' ?>">
                    <td class="text-center">
                        <?php if ($index < 3): ?>
                            <i class="fas fa-medal" style="color: <?= $index == 0 ? '#FFD700' : ($index == 1 ? '#C0C0C0' : '#CD7F32') ?>;"></i>
                        <?php else: ?>
                            <?= $index + 1 ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($m['siglas']) ?></strong>
                        <?php if ($esMaga): ?>
                            <span class="badge-maga">Nosotros</span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($m['nombre']) ?></small>
                    </td>
                    <td class="numeric"><?= formatMoney($m['vigente']) ?></td>
                    <td class="numeric"><?= formatMoney($m['devengado']) ?></td>
                    <td class="numeric">
                        <span class="percent-badge <?= $colorEjecucion ?>"><?= formatPercent($m['ejecucion']) ?></span>
                        <div class="mini-progress">
                            <div class="mini-progress-bar" style="width: <?= min($m['ejecucion'], 100) ?>%; background: var(--<?= $colorEjecucion ?>-color);"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.highlight-row {
    background: rgba(49, 130, 206, 0.08) !important;
    border-left: 4px solid var(--secondary-color);
}

.highlight-row:hover {
    background: rgba(49, 130, 206, 0.12) !important;
}

.badge-maga {
    display: inline-block;
    background: var(--secondary-color);
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 600;
    margin-left: 0.5rem;
    vertical-align: middle;
}
</style>

<?php
$ministeriosJson = json_encode(array_map(function($m) {
    return [
        'siglas' => $m['siglas'],
        'ejecucion' => floatval($m['ejecucion']),
        'esMaga' => stripos($m['siglas'], 'MAGA') !== false || stripos($m['nombre'], 'AGRICULTURA') !== false
    ];
}, $ministerios));

if (!empty($ministerios)):
$extraScripts = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ministerios = $ministeriosJson;
    
    if (ministerios.length === 0) return;
    
    // Crear gráfica
    const ctx = document.getElementById('chartMinisterios');
    if (!ctx) return;
    
    const colores = ministerios.map(m => m.esMaga ? '#3182ce' : '#1a365d');
    const bordeColores = ministerios.map(m => m.esMaga ? '#2c5282' : '#0d1b2a');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ministerios.map(m => m.siglas),
            datasets: [{
                label: '% Ejecución',
                data: ministerios.map(m => m.ejecucion),
                backgroundColor: colores,
                borderColor: bordeColores,
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => context.raw.toFixed(2) + '%'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: (value) => value + '%'
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    
    // Inicializar tabla ordenable
    new DataTable('tablaMinisterios');
});
</script>
SCRIPT;
else:
$extraScripts = '';
endif;

require_once 'includes/footer.php';
?>