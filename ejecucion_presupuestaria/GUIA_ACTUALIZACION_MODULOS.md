# Guía de Actualización de Módulos para Filtro de Año

Esta guía te ayudará a actualizar otros módulos del sistema (como index.php, ministerios.php, unidades.php, etc.) para incluir el filtro de año.

## 1. Agregar Selector de Año en el Dashboard (index.php)

### HTML - Agregar selector de año

Agrega este código al inicio de la página (después del header):

```php
<?php
// Obtener el año seleccionado (por defecto el año actual o 2025)
$anioSeleccionado = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;
?>

<!-- Selector de Año -->
<div style="background: var(--bg-card); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: var(--shadow-card);">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-calendar-alt" style="color: var(--primary-color); font-size: 1.25rem;"></i>
            <strong style="font-size: 1.1rem;">Año Fiscal:</strong>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="?anio=2025" 
               class="btn <?= $anioSeleccionado == 2025 ? 'btn-primary' : 'btn-outline' ?>"
               style="min-width: 100px;">
                <i class="fas fa-calendar"></i> 2025
            </a>
            <a href="?anio=2026" 
               class="btn <?= $anioSeleccionado == 2026 ? 'btn-primary' : 'btn-outline' ?>"
               style="min-width: 100px;">
                <i class="fas fa-calendar-plus"></i> 2026
            </a>
        </div>
    </div>
</div>
```

### SQL - Actualizar consultas del dashboard

Modifica todas las consultas para incluir el filtro de año:

```php
// Antes:
$stmt = $db->query("SELECT SUM(vigente) as total_vigente FROM ejecucion_principal");

// Después:
$stmt = $db->prepare("SELECT SUM(vigente) as total_vigente FROM ejecucion_principal WHERE anio = ?");
$stmt->execute([$anioSeleccionado]);
```

## 2. Actualizar Módulo de Ministerios (ministerios.php)

### Agregar filtro de año en las consultas

```php
<?php
$anioSeleccionado = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;

// En la consulta principal
$sql = "SELECT 
    em.id,
    em.anio,
    m.nombre as ministerio,
    m.siglas,
    em.asignado,
    em.vigente,
    em.devengado,
    em.porcentaje_ejecucion
FROM ejecucion_ministerios em
JOIN ministerios m ON em.ministerio_id = m.id
WHERE em.anio = ?
ORDER BY em.devengado DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
?>
```

## 3. Actualizar Módulo de Unidades (unidades.php)

```php
<?php
$anioSeleccionado = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;

$sql = "SELECT 
    ep.id,
    ep.anio,
    ue.codigo,
    ue.nombre,
    ue.nombre_corto,
    SUM(ep.vigente) as total_vigente,
    SUM(ep.devengado) as total_devengado,
    AVG(ep.porcentaje_ejecucion) as promedio_ejecucion
FROM ejecucion_principal ep
JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
WHERE ep.tipo_ejecucion_id = 1 AND ep.anio = ?
GROUP BY ue.id, ep.anio
ORDER BY total_devengado DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
?>
```

## 4. Actualizar API (api/index.php)

Si tienes una API REST, actualiza los endpoints para incluir el parámetro de año:

```php
// GET /api/ejecucion?anio=2025
if ($endpoint === 'ejecucion') {
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;
    
    $sql = "SELECT * FROM v_ejecucion_principal WHERE anio = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$anio]);
    
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_stringify([
        'success' => true,
        'anio' => $anio,
        'data' => $datos
    ]);
}
```

## 5. Actualizar Reportes Excel

Si generas reportes en Excel, incluye el año en el nombre del archivo:

```php
function generarReporteExcel($anio = 2025) {
    $filename = "Ejecucion_Presupuestaria_{$anio}_" . date('Ymd') . ".xlsx";
    
    // Consulta con filtro de año
    $sql = "SELECT * FROM v_ejecucion_principal WHERE anio = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$anio]);
    
    // ... resto del código de generación de Excel
}
```

## 6. Ejemplo Completo: Actualización de index.php

```php
<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once 'config/database.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// NUEVO: Obtener año seleccionado
$anioSeleccionado = isset($_GET['anio']) ? intval($_GET['anio']) : 2025;

// NUEVO: Consulta de totales con filtro de año
$sql = "SELECT 
    SUM(vigente) as total_vigente,
    SUM(devengado) as total_devengado,
    AVG(porcentaje_ejecucion) as promedio_ejecucion,
    COUNT(*) as total_registros
FROM ejecucion_principal 
WHERE anio = ?";

$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
$totales = $stmt->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<!-- NUEVO: Selector de Año -->
<div class="year-selector">
    <div class="year-selector-content">
        <div class="year-label">
            <i class="fas fa-calendar-alt"></i>
            <strong>Año Fiscal:</strong>
        </div>
        <div class="year-buttons">
            <a href="?anio=2025" class="btn <?= $anioSeleccionado == 2025 ? 'btn-primary' : 'btn-outline' ?>">
                <i class="fas fa-calendar"></i> 2025
            </a>
            <a href="?anio=2026" class="btn <?= $anioSeleccionado == 2026 ? 'btn-primary' : 'btn-outline' ?>">
                <i class="fas fa-calendar-plus"></i> 2026
            </a>
        </div>
    </div>
</div>

<!-- KPIs con datos del año seleccionado -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-blue">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <h4>Presupuesto Vigente <?= $anioSeleccionado ?></h4>
            <h2>Q <?= number_format($totales['total_vigente'], 2) ?></h2>
        </div>
    </div>
    
    <!-- ... más KPIs -->
</div>

<style>
.year-selector {
    background: var(--bg-card);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-card);
}

.year-selector-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.year-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
}

.year-label i {
    color: var(--primary-color);
    font-size: 1.25rem;
}

.year-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .year-selector-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
```

## 7. Función Helper para Año

Crea una función helper para facilitar el manejo del año en todo el sistema:

```php
// En config/helpers.php (crear si no existe)
<?php
/**
 * Obtener el año seleccionado desde GET o sesión
 */
function getAnioSeleccionado() {
    if (isset($_GET['anio'])) {
        $_SESSION['anio_seleccionado'] = intval($_GET['anio']);
    }
    
    if (!isset($_SESSION['anio_seleccionado'])) {
        $_SESSION['anio_seleccionado'] = 2025;
    }
    
    return $_SESSION['anio_seleccionado'];
}

/**
 * Generar URL con año
 */
function urlConAnio($pagina, $parametros = []) {
    $anio = getAnioSeleccionado();
    $parametros['anio'] = $anio;
    
    $query = http_build_query($parametros);
    return $pagina . '?' . $query;
}
?>
```

Luego incluye este archivo en tus páginas:

```php
require_once 'config/helpers.php';

$anioSeleccionado = getAnioSeleccionado();
```

## 8. Componente Reutilizable para Selector de Año

Crea un componente reutilizable en `includes/year_selector.php`:

```php
<?php
$anioActual = isset($anioSeleccionado) ? $anioSeleccionado : getAnioSeleccionado();
?>

<div class="year-selector-component">
    <div class="year-selector-wrapper">
        <div class="year-label">
            <i class="fas fa-calendar-alt"></i>
            <strong>Año Fiscal:</strong>
        </div>
        <div class="year-options">
            <a href="<?= $_SERVER['PHP_SELF'] ?>?anio=2025" 
               class="year-btn <?= $anioActual == 2025 ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> 2025
            </a>
            <a href="<?= $_SERVER['PHP_SELF'] ?>?anio=2026" 
               class="year-btn <?= $anioActual == 2026 ? 'active' : '' ?>">
                <i class="fas fa-calendar-plus"></i> 2026
            </a>
        </div>
    </div>
</div>

<style>
.year-selector-component {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.year-selector-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.year-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-size: 1.1rem;
}

.year-label i {
    font-size: 1.25rem;
}

.year-options {
    display: flex;
    gap: 0.5rem;
}

.year-btn {
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
    min-width: 100px;
    text-align: center;
}

.year-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.year-btn.active {
    background: white;
    color: #667eea;
    border-color: white;
    font-weight: 600;
}

@media (max-width: 768px) {
    .year-selector-wrapper {
        flex-direction: column;
        text-align: center;
    }
}
</style>
```

Luego inclúyelo en tus páginas:

```php
<?php require_once 'includes/year_selector.php'; ?>
```

## Checklist de Actualización

- [ ] Ejecutar script SQL `actualizar_anio.sql`
- [ ] Actualizar `importar.php`
- [ ] Agregar selector de año en `index.php`
- [ ] Actualizar consultas en `ministerios.php`
- [ ] Actualizar consultas en `unidades.php`
- [ ] Actualizar consultas en la API
- [ ] Crear función helper `getAnioSeleccionado()`
- [ ] Crear componente reutilizable de selector de año
- [ ] Actualizar todos los reportes para incluir filtro de año
- [ ] Probar importación de datos 2025 y 2026
- [ ] Verificar que las consultas filtran correctamente por año

---
¡Listo! Con estos cambios tu sistema estará completamente actualizado para manejar múltiples años.
