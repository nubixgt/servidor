<?php
/**
 * modules/tecnico/supervisiones.php
 * Listado de Supervisiones para Técnicos
 * Sistema de Supervisión v6.0.4
 * Los técnicos solo ven SUS propias supervisiones
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireTecnico();

$db = Database::getInstance()->getConnection();

// ⭐ Obtener solo las supervisiones del técnico actual
$usuarioId = $_SESSION['user_id'];

try {
    // Total de supervisiones del técnico
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM supervisiones WHERE usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $totalSupervision = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    error_log($e->getMessage());
    $totalSupervision = 0;
}

$pageTitle = 'Mis Supervisiones';

// CSS - IGUAL QUE ADMIN
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/supervisiones.css' // ⭐ MISMO ARCHIVO QUE ADMIN
];

// JS - IGUAL QUE ADMIN (pero con script de técnico)
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    'https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js',
    SITE_URL . '/assets/js/navbar_tecnico.js',
    SITE_URL . '/assets/js/pages/supervisiones-tecnico.js'
];

require_once '../../includes/header.php';
require_once '../../includes/navbar_tecnico.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Header de la página - IGUAL QUE ADMIN -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>Mis Supervisiones</h1>
                    <p>Gestiona las supervisiones que has registrado</p>
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
                    <a href="<?php echo SITE_URL; ?>/modules/tecnico/nueva-supervision.php" class="btn-new">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Nueva Supervisión</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtro de Fechas - IGUAL QUE ADMIN -->
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
        
        <!-- Estadísticas Rápidas - IGUAL QUE ADMIN -->
        <div class="stats-grid stats-single">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Mis Supervisiones</div>
                    <div class="stat-value" data-target="<?php echo $totalSupervision; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Supervisiones - IGUAL QUE ADMIN -->
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
                        <!-- Datos cargados por DataTables vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>