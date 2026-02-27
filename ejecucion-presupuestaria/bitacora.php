<?php
/**
 * Bitácora - Historial de Cambios
 * Sistema de Ejecución Presupuestaria - MAGA
 */

$pageTitle = 'Bitácora';
$currentPage = 'bitacora';

require_once 'config/database.php';

$db = getDB();

// Filtros
$filtroTabla = $_GET['tabla'] ?? '';
$filtroAccion = $_GET['accion'] ?? '';
$filtroFechaDesde = $_GET['fecha_desde'] ?? '';
$filtroFechaHasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta
$sql = "SELECT b.*, u.nombre as usuario_nombre, u.email as usuario_email
        FROM bitacora b
        LEFT JOIN usuarios u ON b.usuario_id = u.id
        WHERE 1=1";

$params = [];

if ($filtroTabla) {
    $sql .= " AND b.tabla_afectada = ?";
    $params[] = $filtroTabla;
}

if ($filtroAccion) {
    $sql .= " AND b.accion = ?";
    $params[] = $filtroAccion;
}

if ($filtroFechaDesde) {
    $sql .= " AND DATE(b.created_at) >= ?";
    $params[] = $filtroFechaDesde;
}

if ($filtroFechaHasta) {
    $sql .= " AND DATE(b.created_at) <= ?";
    $params[] = $filtroFechaHasta;
}

$sql .= " ORDER BY b.created_at DESC LIMIT 100";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll();
} catch (Exception $e) {
    $registros = [];
}

// Estadísticas
try {
    $stats = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN accion = 'INSERT' THEN 1 ELSE 0 END) as inserts,
        SUM(CASE WHEN accion = 'UPDATE' THEN 1 ELSE 0 END) as updates,
        SUM(CASE WHEN accion = 'DELETE' THEN 1 ELSE 0 END) as deletes,
        COUNT(DISTINCT DATE(created_at)) as dias_activos
        FROM bitacora")->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'inserts' => 0, 'updates' => 0, 'deletes' => 0, 'dias_activos' => 0];
}

require_once 'includes/header.php';
?>

<!-- Estadísticas de Bitácora -->
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="kpi-card stagger-item">
        <div class="kpi-icon" style="background: rgba(49, 130, 206, 0.1); color: #3182ce;">
            <i class="fas fa-history"></i>
        </div>
        <div class="kpi-label">Total Registros</div>
        <div class="kpi-value" data-countup data-value="<?= $stats['total'] ?>" data-decimals="0">0</div>
    </div>
    
    <div class="kpi-card stagger-item">
        <div class="kpi-icon" style="background: rgba(56, 161, 105, 0.1); color: #38a169;">
            <i class="fas fa-plus-circle"></i>
        </div>
        <div class="kpi-label">Inserciones</div>
        <div class="kpi-value" style="color: var(--success-color);" data-countup data-value="<?= $stats['inserts'] ?>" data-decimals="0">0</div>
    </div>
    
    <div class="kpi-card stagger-item">
        <div class="kpi-icon" style="background: rgba(214, 158, 46, 0.1); color: #d69e2e;">
            <i class="fas fa-edit"></i>
        </div>
        <div class="kpi-label">Actualizaciones</div>
        <div class="kpi-value" style="color: var(--warning-color);" data-countup data-value="<?= $stats['updates'] ?>" data-decimals="0">0</div>
    </div>
    
    <div class="kpi-card stagger-item">
        <div class="kpi-icon" style="background: rgba(229, 62, 62, 0.1); color: #e53e3e;">
            <i class="fas fa-trash"></i>
        </div>
        <div class="kpi-label">Eliminaciones</div>
        <div class="kpi-value" style="color: var(--danger-color);" data-countup data-value="<?= $stats['deletes'] ?>" data-decimals="0">0</div>
    </div>
</div>

<!-- Filtros -->
<div class="filters-container">
    <form method="GET" class="d-flex gap-2" style="display: flex; flex-wrap: wrap; gap: 1rem; width: 100%; align-items: flex-end;">
        <div class="filter-group">
            <label for="tabla">Tabla</label>
            <select name="tabla" id="tabla">
                <option value="">Todas</option>
                <option value="ejecucion_principal" <?= $filtroTabla === 'ejecucion_principal' ? 'selected' : '' ?>>Ejecución Principal</option>
                <option value="ejecucion_detalle" <?= $filtroTabla === 'ejecucion_detalle' ? 'selected' : '' ?>>Ejecución Detalle</option>
                <option value="ejecucion_ministerios" <?= $filtroTabla === 'ejecucion_ministerios' ? 'selected' : '' ?>>Ministerios</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="accion">Acción</label>
            <select name="accion" id="accion">
                <option value="">Todas</option>
                <option value="INSERT" <?= $filtroAccion === 'INSERT' ? 'selected' : '' ?>>Inserción</option>
                <option value="UPDATE" <?= $filtroAccion === 'UPDATE' ? 'selected' : '' ?>>Actualización</option>
                <option value="DELETE" <?= $filtroAccion === 'DELETE' ? 'selected' : '' ?>>Eliminación</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="fecha_desde">Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" value="<?= htmlspecialchars($filtroFechaDesde) ?>">
        </div>
        
        <div class="filter-group">
            <label for="fecha_hasta">Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?= htmlspecialchars($filtroFechaHasta) ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Filtrar
        </button>
        
        <a href="bitacora.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Limpiar
        </a>
    </form>
</div>

<!-- Timeline de cambios -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-stream"></i> Historial de Cambios</h3>
        <div class="table-actions">
            <span class="text-light"><?= count($registros) ?> registros encontrados</span>
        </div>
    </div>
    
    <div style="padding: 1.5rem;">
        <?php if (empty($registros)): ?>
            <div class="text-center text-muted" style="padding: 3rem;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No hay registros en la bitácora</p>
                <p><small>Los cambios realizados en el sistema aparecerán aquí</small></p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($registros as $r): 
                    $iconClass = '';
                    $colorClass = '';
                    switch ($r['accion']) {
                        case 'INSERT':
                            $iconClass = 'fa-plus-circle';
                            $colorClass = 'insert';
                            $accionTexto = 'Nuevo registro';
                            break;
                        case 'UPDATE':
                            $iconClass = 'fa-edit';
                            $colorClass = 'update';
                            $accionTexto = 'Actualización';
                            break;
                        case 'DELETE':
                            $iconClass = 'fa-trash';
                            $colorClass = 'delete';
                            $accionTexto = 'Eliminación';
                            break;
                    }
                ?>
                <div class="timeline-item <?= $colorClass ?>">
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <div class="timeline-action">
                                <i class="fas <?= $iconClass ?>"></i>
                                <?= $accionTexto ?> en <strong><?= htmlspecialchars($r['tabla_afectada']) ?></strong>
                                <span class="badge-id">#<?= $r['registro_id'] ?></span>
                            </div>
                            <div class="timeline-date">
                                <i class="far fa-clock"></i>
                                <?= date('d/m/Y H:i:s', strtotime($r['created_at'])) ?>
                            </div>
                        </div>
                        
                        <?php if ($r['campos_modificados']): ?>
                        <div class="timeline-details">
                            <strong>Campos modificados:</strong> <?= htmlspecialchars($r['campos_modificados']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($r['datos_anteriores'] || $r['datos_nuevos']): ?>
                        <div class="timeline-changes">
                            <button class="btn-toggle-details" onclick="toggleDetails(this)">
                                <i class="fas fa-chevron-down"></i> Ver detalles
                            </button>
                            <div class="details-content" style="display: none;">
                                <?php if ($r['datos_anteriores']): ?>
                                <div class="data-block old">
                                    <strong><i class="fas fa-minus-circle"></i> Datos anteriores:</strong>
                                    <pre><?= htmlspecialchars(json_encode(json_decode($r['datos_anteriores']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($r['datos_nuevos']): ?>
                                <div class="data-block new">
                                    <strong><i class="fas fa-plus-circle"></i> Datos nuevos:</strong>
                                    <pre><?= htmlspecialchars(json_encode(json_decode($r['datos_nuevos']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-user">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($r['usuario_nombre'] ?? 'Sistema') ?>
                            <?php if ($r['ip_address']): ?>
                                <span class="ip-badge"><i class="fas fa-globe"></i> <?= htmlspecialchars($r['ip_address']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.badge-id {
    display: inline-block;
    background: rgba(49, 130, 206, 0.1);
    color: #3182ce;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.ip-badge {
    display: inline-block;
    background: #f7fafc;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.btn-toggle-details {
    background: none;
    border: none;
    color: var(--secondary-color);
    cursor: pointer;
    font-size: 0.875rem;
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-toggle-details:hover {
    text-decoration: underline;
}

.btn-toggle-details i {
    transition: transform 0.3s ease;
}

.btn-toggle-details.open i {
    transform: rotate(180deg);
}

.details-content {
    margin-top: 1rem;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.data-block {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.data-block.old {
    border-left: 3px solid var(--danger-color);
}

.data-block.new {
    border-left: 3px solid var(--success-color);
}

.data-block pre {
    margin: 0.5rem 0 0 0;
    font-size: 0.75rem;
    overflow-x: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.timeline-changes {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<?php
$extraScripts = <<<'SCRIPT'
<script>
function toggleDetails(btn) {
    const content = btn.nextElementSibling;
    const isHidden = content.style.display === 'none';
    
    content.style.display = isHidden ? 'block' : 'none';
    btn.classList.toggle('open', isHidden);
    btn.innerHTML = isHidden 
        ? '<i class="fas fa-chevron-up"></i> Ocultar detalles'
        : '<i class="fas fa-chevron-down"></i> Ver detalles';
}
</script>
SCRIPT;

require_once 'includes/footer.php';
?>
