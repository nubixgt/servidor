<?php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas rápidas
try {   
    // Total de supervisiones
    $stmt = $db->query("SELECT COUNT(*) as total FROM supervisiones");
    $totalSupervision = $stmt->fetch()['total'];
    
    // Obtener todas las supervisiones con información relacionada
    $stmt = $db->query("
        SELECT 
            s.id,
            s.fecha_supervision,
            s.estado,
            s.telefono,
            s.observaciones,
            p.nombre as proyecto_nombre,
            c.nombre as contratista_nombre,
            t.nombre as trabajador_nombre,
            s.fecha_creacion
        FROM supervisiones s
        INNER JOIN proyectos p ON s.proyecto_id = p.id
        INNER JOIN contratistas c ON s.contratista_id = c.id
        INNER JOIN trabajadores t ON s.trabajador_id = t.id
        ORDER BY s.fecha_supervision DESC
    ");
    $supervisiones = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $supervisiones = [];
    $totalPendientes = 0;
    $totalCompletadas = 0;
    $totalSupervision = 0;
}

$pageTitle = 'Gestión de Supervisiones';

// CSS: CDN de SweetAlert2 + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/supervisiones.css'
];

// JS: jQuery, DataTables, SweetAlert2, SheetJS + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    'https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js',
    '/assets/js/pages/supervisiones.js'
];

require_once '../../includes/header.php';
?>

<?php require_once '../../includes/navbar_admin.php'; ?>

<main class="main-content">
    <div class="container">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>Gestión de Supervisiones</h1>
                    <p>Administra todas las supervisiones registradas</p>
                </div>
                <div class="header-buttons">
                    <button class="btn-excel" onclick="exportarExcel()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Exportar Excel</span>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/modules/admin/nueva-supervision.php" class="btn-new">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Nueva Supervisión</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtro de Fechas -->
        <div class="filter-section">
            <div class="filter-header">
                <div class="filter-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3>Filtrar por Rango de Fechas</h3>
                </div>
                <button class="btn-clear-filter" onclick="limpiarFiltros()" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Limpiar Filtros</span>
                </button>
            </div>
            
            <div class="filter-inputs">
                <div class="filter-group">
                    <label for="fechaInicio">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Fecha Inicio
                    </label>
                    <input type="date" id="fechaInicio" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label for="fechaFin">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Fecha Fin
                    </label>
                    <input type="date" id="fechaFin" class="filter-input">
                </div>
                
                <div class="filter-actions">
                    <button class="btn-filter" onclick="aplicarFiltros()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Aplicar Filtro</span>
                    </button>
                </div>
            </div>
            
            <div class="filter-info" id="filterInfo" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span id="filterInfoText"></span>
            </div>
        </div>
        
        <!-- Estadísticas Rápidas -->
        <div class="stats-grid stats-single">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de Supervisiones</div>
                    <div class="stat-value" data-target="<?php echo $totalSupervision; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Supervisiones -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <h3>Lista de Supervisiones</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="supervisionesTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Proyecto</th>
                            <th>Contratista</th>
                            <th>Trabajador</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($supervisiones)): ?>
                            <?php foreach ($supervisiones as $supervision): ?>
                                <tr data-id="<?php echo $supervision['id']; ?>"
                                    data-fecha="<?php echo htmlspecialchars($supervision['fecha_supervision']); ?>"
                                    data-proyecto="<?php echo htmlspecialchars($supervision['proyecto_nombre']); ?>"
                                    data-contratista="<?php echo htmlspecialchars($supervision['contratista_nombre']); ?>"
                                    data-trabajador="<?php echo htmlspecialchars($supervision['trabajador_nombre']); ?>"
                                    data-telefono="<?php echo htmlspecialchars($supervision['telefono'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($supervision['estado']); ?>"
                                    data-observaciones="<?php echo htmlspecialchars($supervision['observaciones'] ?? ''); ?>">
                                    <td><span class="id-badge">#<?php echo $supervision['id']; ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($supervision['fecha_supervision'])); ?></td>
                                    <td class="td-proyecto">
                                        <strong><?php echo htmlspecialchars($supervision['proyecto_nombre']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($supervision['contratista_nombre']); ?></td>
                                    <td>
                                        <div class="worker-cell">
                                            <div class="worker-avatar">
                                                <?php echo strtoupper(substr($supervision['trabajador_nombre'], 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($supervision['trabajador_nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verSupervision(<?php echo $supervision['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarSupervision(<?php echo $supervision['id']; ?>)"
                                                    title="Eliminar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>