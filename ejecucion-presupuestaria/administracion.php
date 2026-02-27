<?php
/**
 * Módulo de Administración
 * Sistema de Ejecución Presupuestaria - MAGA
 * SOLO ADMINISTRADORES Y EDITORES
 */

$pageTitle = 'Administración';
$currentPage = 'administracion';

require_once 'config/database.php';

// Verificar sesión antes de cualquier cosa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea administrador o editor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'editor')) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$mensaje = '';
$tipoMensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'guardar_principal') {
            $id = intval($_POST['id']);
            $campos = [
                'asignado' => floatval($_POST['asignado']),
                'modificado' => floatval($_POST['modificado']),
                'vigente' => floatval($_POST['vigente']),
                'devengado' => floatval($_POST['devengado']),
                'saldo_por_devengar' => floatval($_POST['saldo_por_devengar']),
                'porcentaje_ejecucion' => floatval($_POST['porcentaje_ejecucion']),
                'porcentaje_relativo' => floatval($_POST['porcentaje_relativo'])
            ];

            // Obtener datos anteriores para bitácora
            $stmt = $db->prepare("SELECT * FROM ejecucion_principal WHERE id = ?");
            $stmt->execute([$id]);
            $datosAnteriores = $stmt->fetch();

            if ($id > 0 && $datosAnteriores) {
                // Actualizar
                $sql = "UPDATE ejecucion_principal SET 
                        asignado = ?, modificado = ?, vigente = ?, devengado = ?,
                        saldo_por_devengar = ?, porcentaje_ejecucion = ?, porcentaje_relativo = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $campos['asignado'],
                    $campos['modificado'],
                    $campos['vigente'],
                    $campos['devengado'],
                    $campos['saldo_por_devengar'],
                    $campos['porcentaje_ejecucion'],
                    $campos['porcentaje_relativo'],
                    $id
                ]);

                // Identificar campos modificados
                $camposModificados = [];
                foreach ($campos as $campo => $valor) {
                    if ($datosAnteriores[$campo] != $valor) {
                        $camposModificados[] = $campo;
                    }
                }

                // Registrar en bitácora
                registrarBitacora(
                    'ejecucion_principal',
                    $id,
                    'UPDATE',
                    $datosAnteriores,
                    $campos,
                    implode(', ', $camposModificados)
                );

                $mensaje = 'Registro actualizado correctamente';
                $tipoMensaje = 'success';
            }
        }

        if ($accion === 'guardar_detalle') {
            $id = intval($_POST['id']);
            $campos = [
                'vigente' => floatval($_POST['vigente']),
                'devengado' => floatval($_POST['devengado']),
                'saldo_por_devengar' => floatval($_POST['saldo_por_devengar']),
                'porcentaje_ejecucion' => floatval($_POST['porcentaje_ejecucion']),
                'porcentaje_relativo' => floatval($_POST['porcentaje_relativo'])
            ];

            $stmt = $db->prepare("SELECT * FROM ejecucion_detalle WHERE id = ?");
            $stmt->execute([$id]);
            $datosAnteriores = $stmt->fetch();

            if ($id > 0 && $datosAnteriores) {
                $sql = "UPDATE ejecucion_detalle SET 
                        vigente = ?, devengado = ?, saldo_por_devengar = ?,
                        porcentaje_ejecucion = ?, porcentaje_relativo = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $campos['vigente'],
                    $campos['devengado'],
                    $campos['saldo_por_devengar'],
                    $campos['porcentaje_ejecucion'],
                    $campos['porcentaje_relativo'],
                    $id
                ]);

                $camposModificados = [];
                foreach ($campos as $campo => $valor) {
                    if ($datosAnteriores[$campo] != $valor) {
                        $camposModificados[] = $campo;
                    }
                }

                registrarBitacora(
                    'ejecucion_detalle',
                    $id,
                    'UPDATE',
                    $datosAnteriores,
                    $campos,
                    implode(', ', $camposModificados)
                );

                $mensaje = 'Registro de detalle actualizado correctamente';
                $tipoMensaje = 'success';
            }
        }

    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener datos para edición
$tablaSeleccionada = $_GET['tabla'] ?? 'principal';
$registroId = isset($_GET['editar']) ? intval($_GET['editar']) : 0;
$registroEditar = null;

if ($registroId > 0) {
    if ($tablaSeleccionada === 'principal') {
        $sql = "SELECT ep.*, 
                COALESCE(ue.nombre_corto, p.nombre, gg.nombre, ff.nombre) as nombre_registro,
                te.nombre as tipo_ejecucion
                FROM ejecucion_principal ep
                LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
                LEFT JOIN programas p ON ep.programa_id = p.id
                LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
                LEFT JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
                LEFT JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id
                WHERE ep.id = ?";
    } else {
        $sql = "SELECT ed.*, ue.nombre_corto as unidad,
                COALESCE(gg.nombre, ff.nombre) as nombre_registro
                FROM ejecucion_detalle ed
                JOIN unidades_ejecutoras ue ON ed.unidad_ejecutora_id = ue.id
                LEFT JOIN grupos_gasto gg ON ed.grupo_gasto_id = gg.id
                LEFT JOIN fuentes_financiamiento ff ON ed.fuente_financiamiento_id = ff.id
                WHERE ed.id = ?";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$registroId]);
    $registroEditar = $stmt->fetch();
}

// Obtener lista de registros
if ($tablaSeleccionada === 'principal') {
    $sqlLista = "SELECT ep.id, 
                 COALESCE(
                    CONCAT(ue.codigo, ' \"', ue.nombre_corto, '\"'),
                    CONCAT(p.codigo, ' \"', p.nombre, '\"'),
                    CONCAT(gg.codigo, ' \"', gg.nombre, '\"'),
                    CONCAT(ff.codigo, ' \"', ff.nombre, '\"')
                 ) as descripcion,
                 te.nombre as tipo,
                 ep.vigente, ep.devengado, ep.porcentaje_ejecucion
                 FROM ejecucion_principal ep
                 LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
                 LEFT JOIN programas p ON ep.programa_id = p.id
                 LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
                 LEFT JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
                 LEFT JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id
                 ORDER BY ep.asignado DESC";
} else {
    $sqlLista = "SELECT ed.id,
                 CONCAT(ue.codigo, ' - ', COALESCE(gg.nombre, ff.nombre)) as descripcion,
                 ed.tipo_registro as tipo,
                 ed.vigente, ed.devengado, ed.porcentaje_ejecucion
                 FROM ejecucion_detalle ed
                 JOIN unidades_ejecutoras ue ON ed.unidad_ejecutora_id = ue.id
                 LEFT JOIN grupos_gasto gg ON ed.grupo_gasto_id = gg.id
                 LEFT JOIN fuentes_financiamiento ff ON ed.fuente_financiamiento_id = ff.id
                 ORDER BY ed.vigente DESC";
}

try {
    $registros = $db->query($sqlLista)->fetchAll();
} catch (Exception $e) {
    $registros = [];
}

require_once 'includes/header.php';
?>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> mb-3">
        <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<!-- Selector de tabla -->
<div class="filters-container mb-3">
    <div class="filter-group">
        <label>Seleccionar tabla a administrar:</label>
        <select onchange="window.location='?tabla=' + this.value">
            <option value="principal" <?= $tablaSeleccionada === 'principal' ? 'selected' : '' ?>>Ejecución Principal
            </option>
            <option value="detalle" <?= $tablaSeleccionada === 'detalle' ? 'selected' : '' ?>>Detalle por Unidad</option>
        </select>
    </div>
</div>

<?php if ($registroEditar): ?>
    <!-- Formulario de edición -->
    <div class="form-card">
        <div class="form-header">
            <h3><i class="fas fa-edit"></i> Editar Registro #<?= $registroId ?></h3>
        </div>
        <form method="POST" class="form-body">
            <input type="hidden" name="accion" value="guardar_<?= $tablaSeleccionada ?>">
            <input type="hidden" name="id" value="<?= $registroId ?>">

            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i>
                <strong><?= htmlspecialchars($registroEditar['nombre_registro'] ?? $registroEditar['descripcion'] ?? 'Registro') ?></strong>
                <?php if (isset($registroEditar['tipo_ejecucion'])): ?>
                    <br><small>Tipo: <?= htmlspecialchars($registroEditar['tipo_ejecucion']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <?php if ($tablaSeleccionada === 'principal'): ?>
                    <div class="form-group">
                        <label>Asignado <span class="required">*</span></label>
                        <input type="number" name="asignado" class="form-control" step="0.01"
                            value="<?= $registroEditar['asignado'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Modificado</label>
                        <input type="number" name="modificado" class="form-control" step="0.01"
                            value="<?= $registroEditar['modificado'] ?>">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Vigente <span class="required">*</span></label>
                    <input type="number" name="vigente" class="form-control" step="0.01"
                        value="<?= $registroEditar['vigente'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Devengado <span class="required">*</span></label>
                    <input type="number" name="devengado" class="form-control" step="0.01"
                        value="<?= $registroEditar['devengado'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Saldo por Devengar</label>
                    <input type="number" name="saldo_por_devengar" class="form-control" step="0.01"
                        value="<?= $registroEditar['saldo_por_devengar'] ?>">
                </div>

                <div class="form-group">
                    <label>% Ejecución</label>
                    <input type="number" name="porcentaje_ejecucion" class="form-control" step="0.0001"
                        value="<?= $registroEditar['porcentaje_ejecucion'] ?>">
                </div>

                <div class="form-group">
                    <label>% Relativo</label>
                    <input type="number" name="porcentaje_relativo" class="form-control" step="0.0001"
                        value="<?= $registroEditar['porcentaje_relativo'] ?>">
                </div>
            </div>

            <div class="form-footer">
                <a href="?tabla=<?= $tablaSeleccionada ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Lista de registros -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-database"></i> Registros de
            <?= $tablaSeleccionada === 'principal' ? 'Ejecución Principal' : 'Detalle por Unidad' ?>
        </h3>
        <div class="table-actions">
            <input type="text" id="buscarRegistro" placeholder="Buscar..." class="form-control"
                style="width: 200px; padding: 0.5rem;" oninput="filtrarRegistros(this.value)">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="tablaRegistros">
            <thead>
                <tr>
                    <th class="id-column">ID</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th class="numeric">Vigente</th>
                    <th class="numeric">Devengado</th>
                    <th class="numeric">% Ejecución</th>
                    <th style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No hay registros. <a href="importar.php">Importar datos desde Excel</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td class="id-column"><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['descripcion'] ?? 'Sin descripción') ?></td>
                            <td><span class="badge-tipo"><?= htmlspecialchars($r['tipo'] ?? '') ?></span></td>
                            <td class="numeric"><?= formatMoney($r['vigente']) ?></td>
                            <td class="numeric"><?= formatMoney($r['devengado']) ?></td>
                            <td class="numeric">
                                <span class="percent-badge <?= getSemaforoColor($r['porcentaje_ejecucion']) ?>">
                                    <?= formatPercent($r['porcentaje_ejecucion']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="?tabla=<?= $tablaSeleccionada ?>&editar=<?= $r['id'] ?>" class="btn btn-primary"
                                    style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .badge-tipo {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        background: rgba(49, 130, 206, 0.1);
        color: #3182ce;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>

<?php
$extraScripts = <<<'SCRIPT'
<script>
function filtrarRegistros(texto) {
    const filas = document.querySelectorAll('#tablaRegistros tbody tr');
    const busqueda = texto.toLowerCase();
    
    filas.forEach(fila => {
        const contenido = fila.textContent.toLowerCase();
        fila.style.display = contenido.includes(busqueda) ? '' : 'none';
    });
}

// Calcular saldo automáticamente
document.addEventListener('DOMContentLoaded', function() {
    const vigente = document.querySelector('input[name="vigente"]');
    const devengado = document.querySelector('input[name="devengado"]');
    const saldo = document.querySelector('input[name="saldo_por_devengar"]');
    const porcentaje = document.querySelector('input[name="porcentaje_ejecucion"]');
    
    function calcular() {
        if (vigente && devengado && saldo) {
            const v = parseFloat(vigente.value) || 0;
            const d = parseFloat(devengado.value) || 0;
            saldo.value = (v - d).toFixed(2);
            
            if (porcentaje && v > 0) {
                porcentaje.value = ((d / v) * 100).toFixed(4);
            }
        }
    }
    
    if (vigente) vigente.addEventListener('input', calcular);
    if (devengado) devengado.addEventListener('input', calcular);
});
</script>
SCRIPT;

require_once 'includes/footer.php';
?>