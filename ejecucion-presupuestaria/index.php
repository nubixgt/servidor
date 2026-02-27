<?php
/**
 * Dashboard Principal
 * Sistema de Ejecución Presupuestaria - MAGA
 */

$pageTitle = 'Dashboard Principal';
$currentPage = 'dashboard';

require_once 'config/database.php';

$db = getDB();

// NOTA: $anioSeleccionado viene del header.php
// Si no existe (por alguna razón), usar 2025 por defecto
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// Obtener totales solo de Unidades Ejecutoras (tipo_ejecucion_id = 1) del año seleccionado
$sqlTotales = "SELECT 
    COALESCE(SUM(asignado), 0) as total_asignado,
    COALESCE(SUM(modificado), 0) as total_modificado,
    COALESCE(SUM(vigente), 0) as total_vigente,
    COALESCE(SUM(devengado), 0) as total_devengado,
    COALESCE(SUM(saldo_por_devengar), 0) as total_saldo
FROM ejecucion_principal
WHERE tipo_ejecucion_id = 1 AND anio = ?";

$stmtTotales = $db->prepare($sqlTotales);
$stmtTotales->execute([$anioSeleccionado]);
$totales = $stmtTotales->fetch();

// Calcular porcentaje de ejecución
$porcentajeEjecucion = $totales['total_vigente'] > 0
    ? ($totales['total_devengado'] / $totales['total_vigente']) * 100
    : 0;

// Obtener meta de ejecución al día (desde los datos importados)
$metaAlDia = getMetaEjecucionAlDia();

// Obtener datos para la tabla principal usando la vista
$sqlDatos = "SELECT 
    te.nombre as tipo,
    COALESCE(
        CONCAT(ue.codigo, ' \"', COALESCE(ue.nombre_corto, ue.nombre), '\"'),
        CONCAT(p.codigo, ' \"', p.nombre, '\"'),
        CONCAT(gg.codigo, ' \"', gg.nombre, '\"'),
        CONCAT(ff.codigo, ' \"', ff.nombre, '\"')
    ) as progra_uni_gasto_finan,
    ep.asignado,
    ep.modificado,
    ep.vigente,
    ep.devengado,
    ep.saldo_por_devengar,
    ep.porcentaje_ejecucion,
    ep.porcentaje_relativo
FROM ejecucion_principal ep
JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id
LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
LEFT JOIN programas p ON ep.programa_id = p.id
LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
WHERE ep.anio = ?
ORDER BY te.id, ep.vigente DESC";

try {
    $stmtDatos = $db->prepare($sqlDatos);
    $stmtDatos->execute([$anioSeleccionado]);
    $datosPorTipo = $stmtDatos->fetchAll();
} catch (Exception $e) {
    $datosPorTipo = [];
}

// Datos para gráfica de Ejecución por Unidad Ejecutora (Top 10 por % ejecución)
$sqlUnidades = "SELECT 
    COALESCE(ue.nombre_corto, ue.nombre, 'Unidad Sin Nombre') as nombre,
    ep.vigente,
    ep.devengado,
    ep.porcentaje_ejecucion
FROM ejecucion_principal ep
LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
WHERE (ep.unidad_ejecutora_id IS NOT NULL OR ep.tipo_ejecucion_id = 1) 
  AND ep.vigente > 0
  AND ep.anio = ?
ORDER BY ep.vigente DESC
LIMIT 8";

try {
    $stmtUnidades = $db->prepare($sqlUnidades);
    $stmtUnidades->execute([$anioSeleccionado]);
    $datosUnidades = $stmtUnidades->fetchAll();
} catch (Exception $e) {
    $datosUnidades = [];
}

// Datos para gráfica comparativa Vigente vs Devengado por Grupo
$sqlGrupos = "SELECT 
    COALESCE(gg.nombre, 'Grupo Sin Nombre') as nombre,
    SUM(ep.vigente) as vigente,
    SUM(ep.devengado) as devengado,
    CASE WHEN SUM(ep.vigente) > 0 THEN (SUM(ep.devengado) / SUM(ep.vigente)) * 100 ELSE 0 END as porcentaje
FROM ejecucion_principal ep
LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
WHERE (ep.grupo_gasto_id IS NOT NULL OR ep.tipo_ejecucion_id = 3)
  AND ep.vigente > 0
  AND ep.anio = ?
GROUP BY gg.id, gg.nombre
ORDER BY vigente DESC
LIMIT 6";

try {
    $stmtGrupos = $db->prepare($sqlGrupos);
    $stmtGrupos->execute([$anioSeleccionado]);
    $datosGrupos = $stmtGrupos->fetchAll();
} catch (Exception $e) {
    $datosGrupos = [];
}



require_once 'includes/header.php';
?>

<!-- KPIs Principales -->
<div class="kpi-grid">
    <div class="kpi-card stagger-item">
        <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="kpi-label">Total Asignado</div>
        <div class="kpi-value" data-count="<?= $totales['total_asignado'] ?>">
            Q 0.00
        </div>
    </div>

    <div class="kpi-card stagger-item">
        <div class="kpi-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="kpi-label">Total Modificado</div>
        <div class="kpi-value <?= $totales['total_modificado'] < 0 ? 'negative' : '' ?>"
            data-count="<?= $totales['total_modificado'] ?>">
            Q 0.00
        </div>
    </div>

    <div class="kpi-card stagger-item">
        <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
        <div class="kpi-label">Total Vigente</div>
        <div class="kpi-value" data-count="<?= $totales['total_vigente'] ?>">
            Q 0.00
        </div>
    </div>

    <div class="kpi-card stagger-item">
        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-label">Total Devengado</div>
        <div class="kpi-value" data-count="<?= $totales['total_devengado'] ?>">
            Q 0.00
        </div>
    </div>

    <div class="kpi-card highlight stagger-item">
        <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="kpi-label">Saldo por Devengar</div>
        <div class="kpi-value" data-count="<?= $totales['total_saldo'] ?>">
            Q 0.00
        </div>
    </div>
</div>

<!-- Indicador de Ejecución -->
<?php if ($metaAlDia > 0): ?>
    <div class="execution-indicator"
        style="background: var(--bg-card); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: var(--shadow-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="margin: 0;"><i class="fas fa-chart-line"></i> Ejecución Presupuestaria</h4>
            <div>
                <span class="percent-badge <?= getSemaforoColor($porcentajeEjecucion) ?>" style="font-size: 1.2rem;">
                    <?= formatPercent($porcentajeEjecucion) ?>
                </span>
                <span style="margin-left: 1rem; color: var(--text-secondary);">
                    Meta al día: <strong>
                        <?= formatPercent($metaAlDia) ?>
                    </strong>
                </span>
            </div>
        </div>
        <div style="background: #e2e8f0; border-radius: 10px; height: 20px; overflow: hidden; position: relative;">
            <div
                style="position: absolute; left: <?= min($metaAlDia, 100) ?>%; top: 0; bottom: 0; width: 3px; background: #1a365d; z-index: 2;">
            </div>
            <div
                style="height: 100%; width: <?= min($porcentajeEjecucion, 100) ?>%; background: linear-gradient(90deg, var(--<?= getSemaforoColor($porcentajeEjecucion) ?>-color), var(--<?= getSemaforoColor($porcentajeEjecucion) ?>-color)); border-radius: 10px; transition: width 1s ease;">
            </div>
        </div>
        <div
            style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">
            <span>0%</span>
            <span>
                <?= $porcentajeEjecucion >= $metaAlDia ? '✅ Por encima de la meta' : '⚠️ Por debajo de la meta' ?>
            </span>
            <span>100%</span>
        </div>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="filters-container">
    <div class="filter-group">
        <label for="filterTipo">Tipo de Ejecución</label>
        <select id="filterTipo" onchange="filtrarTabla()">
            <option value="">Todos</option>
            <option value="Unidad Ejecutora">Unidad Ejecutora</option>
            <option value="Programa">Programa</option>
            <option value="Grupo de Gasto">Grupo de Gasto</option>
            <option value="Fuente de Financiamiento">Fuente de Financiamiento</option>
        </select>
    </div>

    <div class="filter-group">
        <label for="filterSearch">Buscar</label>
        <input type="text" id="filterSearch" placeholder="Buscar..." oninput="filtrarTabla()">
    </div>

    <button class="btn btn-secondary" onclick="limpiarFiltros()">
        <i class="fas fa-times"></i> Borrar Filtros
    </button>

    <button class="btn btn-primary" onclick="exportToExcel('tablaPrincipal', 'ejecucion_presupuestaria')">
        <i class="fas fa-file-excel"></i> Exportar Excel
    </button>
</div>

<!-- Tabla Principal -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-table"></i> Ejecución Presupuestaria</h3>
        <div class="table-actions">
            <span class="text-light" id="totalRegistros">
                <?= count($datosPorTipo) ?> registros
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="tablaPrincipal">
            <thead>
                <tr>
                    <th class="sortable">Progra Uni Gasto Finan <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">Asignado <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">Modificado <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">Vigente <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">Devengado <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">Saldo por Devengar <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">% Ejecución <i class="fas fa-sort sort-icon"></i></th>
                    <th class="sortable numeric">% Relativo <i class="fas fa-sort sort-icon"></i></th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <?php if (empty($datosPorTipo)): ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 3rem; color: var(--text-secondary);">
                            <i class="fas fa-inbox"
                                style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                            <p style="margin: 0; font-size: 1.1rem;">No hay datos de ejecución presupuestaria</p>
                            <p style="margin: 0.5rem 0 0; font-size: 0.9rem; opacity: 0.7;">Importa datos desde la sección
                                <a href="importar.php" style="color: var(--primary-color);">Importar Datos</a>
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datosPorTipo as $fila):
                        $colorEjecucion = getSemaforoColor($fila['porcentaje_ejecucion']);
                        ?>
                        <tr data-tipo="<?= htmlspecialchars($fila['tipo']) ?>">
                            <td>
                                <?= htmlspecialchars($fila['progra_uni_gasto_finan']) ?>
                            </td>
                            <td class="numeric">
                                <?= formatMoney($fila['asignado']) ?>
                            </td>
                            <td class="numeric <?= $fila['modificado'] < 0 ? 'negative' : '' ?>">
                                <?= formatMoney($fila['modificado']) ?>
                            </td>
                            <td class="numeric">
                                <?= formatMoney($fila['vigente']) ?>
                            </td>
                            <td class="numeric">
                                <?= formatMoney($fila['devengado']) ?>
                            </td>
                            <td class="numeric">
                                <?= formatMoney($fila['saldo_por_devengar']) ?>
                            </td>
                            <td class="numeric">
                                <span class="percent-badge <?= $colorEjecucion ?>">
                                    <?= formatPercent($fila['porcentaje_ejecucion']) ?>
                                </span>
                                <div class="mini-progress">
                                    <div class="mini-progress-bar"
                                        style="width: <?= min($fila['porcentaje_ejecucion'], 100) ?>%; background: var(--<?= $colorEjecucion ?>-color);">
                                    </div>
                                </div>
                            </td>
                            <td class="numeric">
                                <?= formatPercent($fila['porcentaje_relativo']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gráficas -->
<?php if (!empty($datosUnidades) || !empty($datosGrupos)): ?>
    <div class="charts-grid">
        <?php if (!empty($datosUnidades)): ?>
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-building"></i> Top Unidades por Presupuesto Vigente</h4>
                    <span class="chart-subtitle">% de ejecución mostrado en barras</span>
                </div>
                <div class="chart-body">
                    <canvas id="chartUnidades"></canvas>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($datosGrupos)): ?>
            <div class="chart-card">
                <div class="chart-header">
                    <h4><i class="fas fa-coins"></i> Vigente vs Devengado por Grupo</h4>
                    <span class="chart-subtitle">Comparación de presupuesto por grupo de gasto</span>
                </div>
                <div class="chart-body">
                    <canvas id="chartGrupos"></canvas>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
// Preparar datos para JavaScript
$unidadesLabels = array_column($datosUnidades, 'nombre');
$unidadesVigente = array_map('floatval', array_column($datosUnidades, 'vigente'));
$unidadesDevengado = array_map('floatval', array_column($datosUnidades, 'devengado'));
$unidadesPorcentaje = array_map('floatval', array_column($datosUnidades, 'porcentaje_ejecucion'));

$gruposLabels = array_column($datosGrupos, 'nombre');
$gruposVigente = array_map('floatval', array_column($datosGrupos, 'vigente'));
$gruposDevengado = array_map('floatval', array_column($datosGrupos, 'devengado'));

// Convertir a JSON para JavaScript
$unidadesLabelsJson = json_encode($unidadesLabels, JSON_UNESCAPED_UNICODE);
$unidadesVigenteJson = json_encode($unidadesVigente);
$unidadesDevengadoJson = json_encode($unidadesDevengado);
$unidadesPorcentajeJson = json_encode($unidadesPorcentaje);

$gruposLabelsJson = json_encode($gruposLabels, JSON_UNESCAPED_UNICODE);
$gruposVigenteJson = json_encode($gruposVigente);
$gruposDevengadoJson = json_encode($gruposDevengado);

$extraScripts = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable para ordenamiento interactivo
    const table = new DataTable('tablaPrincipal');
    
    actualizarContadorRegistros();
    
    // Animación de conteo para KPIs
    animateCounters();
    
    // Crear gráfica de Unidades - Barras horizontales con % ejecución
    const ctxUnidades = document.getElementById('chartUnidades');
    if (ctxUnidades && typeof Chart !== 'undefined') {
        const labelsUnidades = {$unidadesLabelsJson};
        const vigenteUnidades = {$unidadesVigenteJson};
        const devengadoUnidades = {$unidadesDevengadoJson};
        const porcentajeUnidades = {$unidadesPorcentajeJson};
        
        if (labelsUnidades.length > 0) {
            new Chart(ctxUnidades, {
                type: 'bar',
                data: {
                    labels: labelsUnidades,
                    datasets: [{
                        label: 'Vigente',
                        data: vigenteUnidades,
                        backgroundColor: 'rgba(26, 54, 93, 0.8)',
                        borderRadius: 4
                    }, {
                        label: 'Devengado',
                        data: devengadoUnidades,
                        backgroundColor: 'rgba(56, 161, 105, 0.8)',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                afterBody: function(context) {
                                    const idx = context[0].dataIndex;
                                    return 'Ejecución: ' + porcentajeUnidades[idx].toFixed(2) + '%';
                                },
                                label: function(context) {
                                    return context.dataset.label + ': Q ' + context.raw.toLocaleString('es-GT');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Q ' + (value / 1000000).toFixed(0) + 'M';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Crear gráfica de Grupos - Barras comparativas
    const ctxGrupos = document.getElementById('chartGrupos');
    if (ctxGrupos && typeof Chart !== 'undefined') {
        const labelsGrupos = {$gruposLabelsJson};
        const vigenteGrupos = {$gruposVigenteJson};
        const devengadoGrupos = {$gruposDevengadoJson};
        
        if (labelsGrupos.length > 0) {
            new Chart(ctxGrupos, {
                type: 'bar',
                data: {
                    labels: labelsGrupos,
                    datasets: [{
                        label: 'Vigente',
                        data: vigenteGrupos,
                        backgroundColor: 'rgba(49, 130, 206, 0.8)',
                        borderRadius: 6
                    }, {
                        label: 'Devengado',
                        data: devengadoGrupos,
                        backgroundColor: 'rgba(99, 179, 237, 0.8)',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Q ' + context.raw.toLocaleString('es-GT');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Q ' + (value / 1000000).toFixed(0) + 'M';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});

function filtrarTabla() {
    const tipo = document.getElementById('filterTipo').value.toLowerCase();
    const busqueda = document.getElementById('filterSearch').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaBody tr');
    
    filas.forEach(fila => {
        const tipoFila = fila.dataset.tipo?.toLowerCase() || '';
        const texto = fila.textContent.toLowerCase();
        
        const coincideTipo = !tipo || tipoFila.includes(tipo);
        const coincideBusqueda = !busqueda || texto.includes(busqueda);
        
        fila.style.display = (coincideTipo && coincideBusqueda) ? '' : 'none';
    });
    
    actualizarContadorRegistros();
}

function limpiarFiltros() {
    document.getElementById('filterTipo').value = '';
    document.getElementById('filterSearch').value = '';
    filtrarTabla();
}

function actualizarContadorRegistros() {
    const filas = document.querySelectorAll('#tablaBody tr[data-tipo]');
    const visibles = Array.from(filas).filter(f => f.style.display !== 'none').length;
    document.getElementById('totalRegistros').textContent = visibles + ' registros';
}

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
</script>
SCRIPT;

require_once 'includes/footer.php';
?>