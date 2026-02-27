<?php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas rápidas
try {
    // Total de proyectos activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM proyectos WHERE estado = 'activo'");
    $totalActivos = $stmt->fetch()['total'];
    
    // Total de proyectos completados
    $stmt = $db->query("SELECT COUNT(*) as total FROM proyectos WHERE estado = 'completado'");
    $totalCompletados = $stmt->fetch()['total'];
    
    // Total de proyectos en pausa
    $stmt = $db->query("SELECT COUNT(*) as total FROM proyectos WHERE estado = 'pausado'");
    $totalPausados = $stmt->fetch()['total'];
    
    // Obtener todos los proyectos
    $stmt = $db->query("
        SELECT 
            id,
            nombre,
            tipo,
            ubicacion,
            descripcion,
            estado,
            fecha_inicio,
            fecha_fin_estimada,
            fecha_fin_real,
            presupuesto,
            consejo,
            muni,
            odc,
            cliente,
            fecha_creacion
        FROM proyectos
        ORDER BY fecha_creacion DESC
    ");
    $proyectos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $proyectos = [];
    $totalActivos = 0;
    $totalCompletados = 0;
    $totalPausados = 0;
}

$pageTitle = 'Gestión de Proyectos';

// CSS: SweetAlert2 + DataTables + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/proyectos.css'
];

// JS: jQuery, DataTables, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/proyectos.js'
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
                    <h1>Gestión de Proyectos</h1>
                    <p>Administra todos los proyectos de construcción</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Proyecto</span>
                </button>
            </div>
        </div>
        
        <!-- Estadísticas Rápidas -->
        <div class="stats-grid">
            <div class="stat-card stat-active">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Activos</div>
                    <div class="stat-value" data-target="<?php echo $totalActivos; ?>">0</div>
                </div>
            </div>

            <div class="stat-card stat-completed">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Completados</div>
                    <div class="stat-value" data-target="<?php echo $totalCompletados; ?>">0</div>
                </div>
            </div>

            <div class="stat-card stat-paused">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="10" y1="15" x2="10" y2="9"></line>
                        <line x1="14" y1="15" x2="14" y2="9"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pausados</div>
                    <div class="stat-value" data-target="<?php echo $totalPausados; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Proyectos -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <h3>Lista de Proyectos</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="proyectosTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($proyectos)): ?>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <tr data-id="<?php echo $proyecto['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($proyecto['nombre']); ?>"
                                    data-tipo="<?php echo htmlspecialchars($proyecto['tipo']); ?>"
                                    data-ubicacion="<?php echo htmlspecialchars($proyecto['ubicacion'] ?? ''); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($proyecto['descripcion'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($proyecto['estado']); ?>"
                                    data-fecha-inicio="<?php echo htmlspecialchars($proyecto['fecha_inicio'] ?? ''); ?>"
                                    data-fecha-fin-estimada="<?php echo htmlspecialchars($proyecto['fecha_fin_estimada'] ?? ''); ?>"
                                    data-fecha-fin-real="<?php echo htmlspecialchars($proyecto['fecha_fin_real'] ?? ''); ?>"
                                    data-presupuesto="<?php echo htmlspecialchars($proyecto['presupuesto'] ?? ''); ?>"
                                    data-consejo="<?php echo htmlspecialchars($proyecto['consejo'] ?? ''); ?>"
                                    data-muni="<?php echo htmlspecialchars($proyecto['muni'] ?? ''); ?>"
                                    data-odc="<?php echo htmlspecialchars($proyecto['odc'] ?? ''); ?>"
                                    data-cliente="<?php echo htmlspecialchars($proyecto['cliente'] ?? ''); ?>"
                                    data-fecha="<?php echo date('d/m/Y', strtotime($proyecto['fecha_creacion'])); ?>">
                                    <td><span class="id-badge">#<?php echo $proyecto['id']; ?></span></td>
                                    <td>
                                        <div class="project-cell">
                                            <div class="project-avatar">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                            <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-tipo badge-tipo-<?php echo str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['-', 'a', 'e', 'i', 'o', 'u'], strtolower($proyecto['tipo'])); ?>">
                                            <?php echo htmlspecialchars($proyecto['tipo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($proyecto['ubicacion'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $proyecto['estado']; ?>">
                                            <?php echo ucfirst($proyecto['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verProyecto(<?php echo $proyecto['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" 
                                                    onclick="editarProyecto(<?php echo $proyecto['id']; ?>)"
                                                    title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarProyecto(<?php echo $proyecto['id']; ?>)"
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

<!-- Modal para Nuevo/Editar Proyecto -->
<div id="modalProyecto" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Proyecto</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formProyecto">
                <input type="hidden" id="proyecto_id" name="id">
                
                <!-- Fila 1: Nombre y Tipo -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Nombre del Proyecto *</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Edificio Plaza Central">
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="3" x2="9" y2="21"></line>
                            </svg>
                            <span>Tipo de Proyecto *</span>
                        </label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Edificio Residencial">Edificio Residencial</option>
                            <option value="Edificio Comercial">Edificio Comercial</option>
                            <option value="Carretera">Carretera</option>
                            <option value="Puente">Puente</option>
                            <option value="Infraestructura Hidráulica">Infraestructura Hidráulica</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                
                <!-- Fila 2: Ubicación y Cliente -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Ubicación</span>
                        </label>
                        <input type="text" id="ubicacion" name="ubicacion" 
                               placeholder="Ej: Zona 10, Ciudad de Guatemala">
                    </div>
                    
                    <div class="form-group">
                        <label for="cliente">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Cliente</span>
                        </label>
                        <input type="text" id="cliente" name="cliente" 
                               placeholder="Ej: Nombre del cliente">
                    </div>
                </div>
                
                <!-- Fila 3: Descripción -->
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label for="descripcion">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Descripción</span>
                        </label>
                        <textarea id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Descripción detallada del proyecto"></textarea>
                    </div>
                </div>
                
                <!-- Fila 4: Fechas -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Fecha de Inicio</span>
                        </label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin_estimada">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Fecha Fin Estimada</span>
                        </label>
                        <input type="date" id="fecha_fin_estimada" name="fecha_fin_estimada">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin_real">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Fecha Fin Real</span>
                        </label>
                        <input type="date" id="fecha_fin_real" name="fecha_fin_real">
                    </div>
                </div>
                
                <!-- Fila 5: Consejo y Muni -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="consejo">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                            </svg>
                            <span>Consejo</span>
                        </label>
                        <input type="text" id="consejo" name="consejo" 
                               placeholder="Ej: 750000.00">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Se mostrará como Q0,000.00
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="muni">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                            </svg>
                            <span>Muni</span>
                        </label>
                        <input type="text" id="muni" name="muni" 
                               placeholder="Ej: 750000.00">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Se mostrará como Q0,000.00
                        </small>
                    </div>
                </div>
                
                <!-- Fila 6: Presupuesto (calculado) y ODC -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="presupuesto">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                            </svg>
                            <span>Presupuesto (Q)</span>
                        </label>
                        <input type="text" id="presupuesto" name="presupuesto" readonly
                               placeholder="Q0.00" style="background-color: #f3f4f6; cursor: not-allowed;">
                        <small style="color: #d97706; font-size: 12px; margin-top: 4px; display: block; font-weight: 600;">
                            ⚡ Calculado automáticamente (Consejo + Muni)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="odc">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                            </svg>
                            <span>ODC</span>
                        </label>
                        <input type="text" id="odc" name="odc" 
                               placeholder="Ej: 250000.00">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Se mostrará como Q0,000.00
                        </small>
                    </div>
                </div>
                
                <!-- Fila 7: Estado -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                            <span>Estado</span>
                        </label>
                        <select id="estado" name="estado">
                            <option value="activo">Activo</option>
                            <option value="completado">Completado</option>
                            <option value="pausado">Pausado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Cancelar</span>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>