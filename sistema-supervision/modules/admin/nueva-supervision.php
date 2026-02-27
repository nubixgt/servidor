<?php
// modules/admin/nueva-supervision.php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener listado de proyectos activos
try {
    $stmt = $db->query("
        SELECT id, nombre 
        FROM proyectos 
        WHERE estado = 'activo'
        ORDER BY nombre ASC
    ");
    $proyectos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $proyectos = [];
}

// Obtener listado de contratistas activos
try {
    $stmt = $db->query("
        SELECT id, nombre 
        FROM contratistas 
        WHERE estado = 'activo'
        ORDER BY nombre ASC
    ");
    $contratistas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $contratistas = [];
}

// Obtener listado de trabajadores activos (SOLO NOMBRE)
try {
    $stmt = $db->query("
        SELECT t.id, t.nombre
        FROM trabajadores t
        WHERE t.estado = 'activo'
        ORDER BY t.nombre ASC
    ");
    $trabajadores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $trabajadores = [];
}

$pageTitle = 'Nueva Supervisión';

// CSS: Select2 CDN + SweetAlert2 + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    '/assets/css/pages/nueva-supervision.css'
];

// JS: jQuery, Select2, SweetAlert2 + JS local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/nueva-supervision.js'
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
                    <h1>Nueva Supervisión</h1>
                    <p>Registra una nueva supervisión de campo</p>
                </div>
            </div>
        </div>
        
        <div class="content-grid">
            <!-- Columna del formulario -->
            <div class="form-column">
                <!-- Tarjeta del formulario -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="header-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                <path d="M9 12l2 2 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3>Información de la Supervisión</h3>
                            <p>Complete todos los campos requeridos</p>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <form id="formSupervision">
                            
                            <!-- Selector de Proyecto -->
                            <div class="form-group">
                                <label for="proyecto_id">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                                    </svg>
                                    <span>Proyecto *</span>
                                </label>
                                <select id="proyecto_id" name="proyecto_id" class="select2-search" required>
                                    <option value="">Seleccione un proyecto...</option>
                                    <?php foreach ($proyectos as $proyecto): ?>
                                        <option value="<?php echo $proyecto['id']; ?>">
                                            <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-help">Proyecto a supervisar</small>
                            </div>
                            
                            <!-- Selector de Contratista -->
                            <div class="form-group">
                                <label for="contratista_id">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <span>Contratista *</span>
                                </label>
                                <select id="contratista_id" name="contratista_id" class="select2-search" required>
                                    <option value="">Seleccione un contratista...</option>
                                    <?php foreach ($contratistas as $contratista): ?>
                                        <option value="<?php echo $contratista['id']; ?>">
                                            <?php echo htmlspecialchars($contratista['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-help">Empresa contratista</small>
                            </div>
                            
                            <!-- Selector de Trabajador (SOLO NOMBRE) -->
                            <div class="form-group">
                                <label for="trabajador_id">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                    </svg>
                                    <span>Trabajador *</span>
                                </label>
                                <select id="trabajador_id" name="trabajador_id" class="select2-search" required>
                                    <option value="">Seleccione un trabajador...</option>
                                    <?php foreach ($trabajadores as $trabajador): ?>
                                        <option value="<?php echo $trabajador['id']; ?>">
                                            <?php echo htmlspecialchars($trabajador['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-help">Empleado a supervisar</small>
                            </div>
                            
                            <!-- Campo Teléfono (NUEVO) -->
                            <div class="form-group">
                                <label for="telefono">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
                                    </svg>
                                    <span>Teléfono *</span>
                                </label>
                                <input 
                                    type="tel" 
                                    id="telefono" 
                                    name="telefono" 
                                    class="form-input"
                                    placeholder="0000-0000"
                                    maxlength="20"
                                    required>
                                <small class="form-help">Número de contacto</small>
                            </div>
                            
                            <!-- Campo Observaciones (NUEVO) -->
                            <div class="form-group">
                                <label for="observaciones">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    <span>Observaciones</span>
                                </label>
                                <textarea 
                                    id="observaciones" 
                                    name="observaciones" 
                                    class="form-textarea"
                                    rows="4"
                                    placeholder="Ingrese observaciones adicionales (opcional)"></textarea>
                                <small class="form-help">Comentarios o detalles adicionales</small>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="form-actions">
                                <a href="<?php echo SITE_URL; ?>/modules/admin/supervisiones.php" class="btn btn-secondary">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    <span>Cancelar</span>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                        <polyline points="7 3 7 8 15 8"></polyline>
                                    </svg>
                                    <span>Guardar Supervisión</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="sidebar-column">
                <!-- Vista previa -->
                <div id="preview-card" class="preview-card" style="display: none;">
                    <div class="card-header">
                        <div class="header-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                            </svg>
                        </div>
                        <h4>Resumen</h4>
                    </div>
                    <div class="card-body">
                        <div class="preview-item">
                            <div class="preview-icon project">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                                </svg>
                            </div>
                            <div class="preview-content">
                                <span class="preview-label">Proyecto</span>
                                <span class="preview-value" id="preview-proyecto">-</span>
                            </div>
                        </div>

                        <div class="preview-item">
                            <div class="preview-icon contractor">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="preview-content">
                                <span class="preview-label">Contratista</span>
                                <span class="preview-value" id="preview-contratista">-</span>
                            </div>
                        </div>

                        <div class="preview-item">
                            <div class="preview-icon worker">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="5"></circle>
                                    <path d="M20 21a8 8 0 10-16 0"></path>
                                </svg>
                            </div>
                            <div class="preview-content">
                                <span class="preview-label">Trabajador</span>
                                <span class="preview-value" id="preview-trabajador">-</span>
                            </div>
                        </div>
                        
                        <div class="preview-item">
                            <div class="preview-icon phone">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
                                </svg>
                            </div>
                            <div class="preview-content">
                                <span class="preview-label">Teléfono</span>
                                <span class="preview-value" id="preview-telefono">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de ayuda -->
                <div class="help-card">
                    <div class="card-header">
                        <div class="header-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <h4>Ayuda</h4>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Los campos marcados con * son obligatorios</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="M21 21l-4.35-4.35"></path>
                                </svg>
                                <span>Usa la búsqueda para encontrar rápidamente</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                <span>Solo se muestran registros activos</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>Las observaciones son opcionales</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>