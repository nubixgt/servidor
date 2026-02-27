<?php
/**
 * Ejecución por Unidad Ejecutora y Grupo de Gasto
 * Sistema de Ejecución Presupuestaria - MAGA
 */

$pageTitle = 'Unidades Ejecutoras';
$currentPage = 'unidades';

require_once 'config/database.php';

$db = getDB();

// Obtener año seleccionado desde la sesión
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// Obtener unidades ejecutoras para el filtro
try {
    $unidades = $db->query("SELECT id, codigo, nombre_corto, nombre FROM unidades_ejecutoras WHERE activo = 1 ORDER BY codigo")->fetchAll();
} catch (Exception $e) {
    $unidades = [];
}

// Obtener filtros primero
$unidadSeleccionada = isset($_GET['unidad']) ? intval($_GET['unidad']) : null;
$tipoEjecucionFiltro = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Obtener totales (filtrados si hay unidad seleccionada) del año seleccionado
$sqlTotales = "SELECT 
    SUM(vigente) as total_vigente,
    SUM(devengado) as total_devengado,
    SUM(saldo_por_devengar) as total_saldo
FROM ejecucion_detalle
WHERE anio = ?";

$params = [$anioSeleccionado];

if ($unidadSeleccionada) {
    $sqlTotales .= " AND unidad_ejecutora_id = ?";
    $params[] = $unidadSeleccionada;
}

try {
    $stmtTotales = $db->prepare($sqlTotales);
    $stmtTotales->execute($params);
    $totales = $stmtTotales->fetch();
    if (!$totales || !$totales['total_vigente']) {
        $totales = ['total_vigente' => 0, 'total_devengado' => 0, 'total_saldo' => 0];
    }
} catch (Exception $e) {
    $totales = ['total_vigente' => 0, 'total_devengado' => 0, 'total_saldo' => 0];
}

// Obtener nombre de la unidad seleccionada para mostrar
$nombreUnidadSeleccionada = null;
if ($unidadSeleccionada) {
    foreach ($unidades as $u) {
        if ($u['id'] == $unidadSeleccionada) {
            $nombreUnidadSeleccionada = $u['codigo'] . ' - ' . $u['nombre_corto'];
            break;
        }
    }
}

$sqlDetalle = "SELECT 
    ed.id,
    ue.codigo as unidad_codigo,
    ue.nombre_corto as unidad,
    ed.tipo_registro,
    COALESCE(
        CONCAT(gg.codigo, ' \"', gg.nombre, '\"'),
        CONCAT(ff.codigo, ' \"', ff.nombre, '\"')
    ) as tipo_gasto_financiamiento,
    ed.vigente,
    ed.devengado,
    ed.saldo_por_devengar,
    ed.porcentaje_ejecucion,
    ed.porcentaje_relativo
FROM ejecucion_detalle ed
JOIN unidades_ejecutoras ue ON ed.unidad_ejecutora_id = ue.id
LEFT JOIN grupos_gasto gg ON ed.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ed.fuente_financiamiento_id = ff.id
WHERE ed.anio = ?";

$paramsDetalle = [$anioSeleccionado];

if ($unidadSeleccionada) {
    $sqlDetalle .= " AND ed.unidad_ejecutora_id = ?";
    $paramsDetalle[] = $unidadSeleccionada;
}

if ($tipoEjecucionFiltro) {
    $sqlDetalle .= " AND ed.tipo_registro = ?";
    $paramsDetalle[] = $tipoEjecucionFiltro;
}

$sqlDetalle .= " ORDER BY ed.vigente DESC";

try {
    $stmtDetalle = $db->prepare($sqlDetalle);
    $stmtDetalle->execute($paramsDetalle);
    $detalle = $stmtDetalle->fetchAll();
} catch (Exception $e) {
    $detalle = [];
}

require_once 'includes/header.php';
?>

<!-- KPIs -->
<?php if ($nombreUnidadSeleccionada): ?>
    <div class="filter-indicator"
        style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 0.75rem 1.25rem; border-radius: var(--radius-lg); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
        <i class="fas fa-filter"></i>
        <span>Mostrando totales de: <strong><?= htmlspecialchars($nombreUnidadSeleccionada) ?></strong></span>
    </div>
<?php endif; ?>
<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="kpi-card stagger-item">
        <div class="kpi-icon">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="kpi-label">Vigente<?= $nombreUnidadSeleccionada ? ' (Filtrado)' : '' ?></div>
        <div class="kpi-value" data-count="<?= $totales['total_vigente'] ?? 0 ?>">Q 0.00</div>
    </div>

    <div class="kpi-card stagger-item">
        <div class="kpi-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="kpi-label">Devengado<?= $nombreUnidadSeleccionada ? ' (Filtrado)' : '' ?></div>
        <div class="kpi-value" data-count="<?= $totales['total_devengado'] ?? 0 ?>">Q 0.00</div>
    </div>

    <div class="kpi-card highlight stagger-item">
        <div class="kpi-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="kpi-label">Saldo por Devengar<?= $nombreUnidadSeleccionada ? ' (Filtrado)' : '' ?></div>
        <div class="kpi-value" data-count="<?= $totales['total_saldo'] ?? 0 ?>">Q 0.00</div>
    </div>
</div>

<!-- Filtro de Unidad Ejecutora -->
<div class="filters-container">
    <div class="filter-group" style="flex: 2;">
        <label for="filterUnidad">Unidad Ejecutora</label>
        <select id="filterUnidad" onchange="filtrarPorUnidad()">
            <option value="">Todas las Unidades</option>
            <?php foreach ($unidades as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $unidadSeleccionada == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['codigo'] . ' - ' . $u['nombre_corto']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="btn btn-secondary" onclick="limpiarFiltros()">
        <i class="fas fa-times"></i> Borrar Filtros
    </button>
</div>

<!-- Contenido con sidebar -->
<div class="content-with-sidebar">
    <!-- Panel lateral de filtros -->
    <aside class="sidebar-filters">
        <div class="sidebar-header">
            <i class="fas fa-filter"></i> Tipo Ejecución
        </div>
        <div class="sidebar-content">
            <div class="filter-option <?= $tipoEjecucionFiltro == '' ? 'active' : '' ?>" onclick="filtrarPorTipo('')">
                <i class="fas fa-list"></i> Todos
            </div>
            <div class="filter-option <?= $tipoEjecucionFiltro == 'Grupo de gasto' ? 'active' : '' ?>"
                onclick="filtrarPorTipo('Grupo de gasto')">
                <i class="fas fa-layer-group"></i> Grupo de gasto
            </div>
            <div class="filter-option <?= $tipoEjecucionFiltro == 'Fuente de financiamiento' ? 'active' : '' ?>"
                onclick="filtrarPorTipo('Fuente de financiamiento')">
                <i class="fas fa-money-bill-wave"></i> Fuente de financiamiento
            </div>
        </div>
    </aside>

    <!-- Tabla principal -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-table"></i> Detalle por Unidad Ejecutora</h3>
            <div class="table-actions">
                <button class="btn btn-primary" onclick="exportToExcel('tablaDetalle', 'unidades_ejecutoras')"
                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="tablaDetalle">
                <thead>
                    <tr>
                        <th class="sortable">Tipo Ejecución</th>
                        <th class="sortable">Tipo Gasto / Financiamiento</th>
                        <th class="sortable numeric">Vigente <i class="fas fa-sort sort-icon"></i></th>
                        <th class="sortable numeric">Devengado <i class="fas fa-sort sort-icon"></i></th>
                        <th class="sortable numeric">Saldo por Devengar</th>
                        <th class="sortable numeric">% Ejecución</th>
                        <th class="sortable numeric">% Relativo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalle)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 3rem; color: var(--text-secondary);">
                                <i class="fas fa-inbox"
                                    style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                <p style="margin: 0; font-size: 1.1rem;">No hay datos de unidades ejecutoras</p>
                                <p style="margin: 0.5rem 0 0; font-size: 0.9rem; opacity: 0.7;">Importa datos desde la
                                    sección <a href="importar.php" style="color: var(--primary-color);">Importar Datos</a>
                                    seleccionando "UniEjeYGru_Gas"</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalle as $fila):
                            $colorEjecucion = getSemaforoColor($fila['porcentaje_ejecucion']);
                            ?>
                            <tr data-tipo="<?= htmlspecialchars($fila['tipo_registro']) ?>">
                                <td>
                                    <span
                                        class="badge-tipo <?= $fila['tipo_registro'] == 'Grupo de gasto' ? 'grupo' : 'fuente' ?>">
                                        <?= htmlspecialchars($fila['tipo_registro']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($fila['tipo_gasto_financiamiento']) ?></td>
                                <td class="numeric"><?= formatMoney($fila['vigente']) ?></td>
                                <td class="numeric"><?= formatMoney($fila['devengado']) ?></td>
                                <td class="numeric"><?= formatMoney($fila['saldo_por_devengar']) ?></td>
                                <td class="numeric">
                                    <span
                                        class="percent-badge <?= $colorEjecucion ?>"><?= formatPercent($fila['porcentaje_ejecucion']) ?></span>
                                </td>
                                <td class="numeric"><?= formatPercent($fila['porcentaje_relativo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .badge-tipo {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-tipo.grupo {
        background: rgba(49, 130, 206, 0.1);
        color: #3182ce;
    }

    .badge-tipo.fuente {
        background: rgba(56, 161, 105, 0.1);
        color: #38a169;
    }
</style>

<?php
$extraScripts = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = new DataTable('tablaDetalle');
    
    // Animación de conteo para KPIs
    animateCounters();
});

// Función de animación de conteo numérico
function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    const duration = 2000; // 2 segundos
    
    counters.forEach(counter => {
        const target = parseFloat(counter.dataset.count) || 0;
        const isNegative = target < 0;
        const absTarget = Math.abs(target);
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Función de easing (ease-out cubic)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const currentValue = absTarget * easeOut;
            
            // Formatear como moneda guatemalteca
            const formatted = new Intl.NumberFormat('es-GT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(currentValue);
            
            counter.textContent = (isNegative ? '-Q ' : 'Q ') + formatted;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        // Iniciar animación con pequeño delay basado en posición
        const delay = Array.from(counters).indexOf(counter) * 100;
        setTimeout(() => requestAnimationFrame(updateCounter), delay);
    });
}

function filtrarPorUnidad() {
    const unidad = document.getElementById('filterUnidad').value;
    const url = new URL(window.location);
    
    if (unidad) {
        url.searchParams.set('unidad', unidad);
    } else {
        url.searchParams.delete('unidad');
    }
    
    window.location = url;
}

function filtrarPorTipo(tipo) {
    const url = new URL(window.location);
    
    if (tipo) {
        url.searchParams.set('tipo', tipo);
    } else {
        url.searchParams.delete('tipo');
    }
    
    window.location = url;
}

function limpiarFiltros() {
    window.location = 'unidades.php';
}
</script>
SCRIPT;

require_once 'includes/footer.php';
?>