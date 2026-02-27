<?php
/**
 * modules/tecnico/inventario.php
 * Gestión de Inventario para Técnicos Completos
 * Sistema de Supervisión v6.0.4
 * Los técnicos solo ven SUS propios equipos
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireTecnico();
verificarAccesoModulo('inventario'); // ⭐ Solo técnicos completos

$db = Database::getInstance()->getConnection();
$usuarioId = $_SESSION['user_id'];

// ⭐ Obtener estadísticas DEL TÉCNICO
try {
    // Total de equipos activos del técnico
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario WHERE estado = 'activo' AND usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $totalActivos = $stmt->fetch()['total'];
    
    // Total de equipos en mantenimiento del técnico
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario WHERE estado = 'en_mantenimiento' AND usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $totalMantenimiento = $stmt->fetch()['total'];
    
    // Total de equipos fuera de servicio del técnico
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario WHERE estado = 'fuera_servicio' AND usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $totalFueraServicio = $stmt->fetch()['total'];
    
    // ⭐ Obtener solo los equipos DEL TÉCNICO
    $stmt = $db->prepare("
        SELECT 
            i.id,
            i.tipo_equipo,
            i.ubicacion_texto,
            i.ubicacion_latitud,
            i.ubicacion_longitud,
            i.observaciones,
            i.estado,
            i.fecha_creacion,
            p.nombre as proyecto_nombre,
            c.nombre as contratista_nombre,
            (SELECT COUNT(*) FROM inventario_fotografias WHERE inventario_id = i.id) as total_fotos
        FROM inventario i
        LEFT JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN contratistas c ON i.contratista_id = c.id
        WHERE i.usuario_id = :usuario_id
        ORDER BY i.fecha_creacion DESC
    ");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $equipos = $stmt->fetchAll();
    
    // Obtener lista de proyectos para el formulario
    $stmt = $db->query("
        SELECT id, nombre 
        FROM proyectos 
        WHERE estado = 'activo' 
        ORDER BY nombre ASC
    ");
    $proyectos = $stmt->fetchAll();
    
    // Obtener lista de contratistas para el formulario
    $stmt = $db->query("
        SELECT id, nombre 
        FROM contratistas 
        WHERE estado = 'activo' 
        ORDER BY nombre ASC
    ");
    $contratistas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $equipos = [];
    $proyectos = [];
    $contratistas = [];
    $totalActivos = 0;
    $totalMantenimiento = 0;
    $totalFueraServicio = 0;
}

$pageTitle = 'Mi Inventario';

// CSS: SweetAlert2 + DataTables + Select2 + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    SITE_URL . '/assets/css/pages/inventario.css'
];

// JS: jQuery, DataTables, Select2, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    SITE_URL . '/assets/js/navbar_tecnico.js', // ⭐ IMPORTANTE
    SITE_URL . '/assets/js/pages/inventario-tecnico.js'
];

require_once '../../includes/header.php';
require_once '../../includes/navbar_tecnico.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>📦 Mi Inventario</h1>
                    <p>Gestiona tus equipos y maquinaria</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Equipo</span>
                </button>
            </div>
        </div>
        
        <!-- Estadísticas Rápidas -->
        <div class="stats-grid">
            <div class="stat-card stat-active">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Activos</div>
                    <div class="stat-value" data-target="<?php echo $totalActivos; ?>">0</div>
                </div>
            </div>

            <div class="stat-card stat-maintenance">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">En Mantenimiento</div>
                    <div class="stat-value" data-target="<?php echo $totalMantenimiento; ?>">0</div>
                </div>
            </div>

            <div class="stat-card stat-out-service">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Fuera de Servicio</div>
                    <div class="stat-value" data-target="<?php echo $totalFueraServicio; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Equipos -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                    </svg>
                    <h3>Mis Equipos</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="inventarioTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo de Equipo</th>
                            <th>Proyecto</th>
                            <th>Contratista</th>
                            <th>Ubicación</th>
                            <th>Fotos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($equipos)): ?>
                            <?php foreach ($equipos as $equipo): ?>
                                <tr data-id="<?php echo $equipo['id']; ?>"
                                    data-tipo="<?php echo htmlspecialchars($equipo['tipo_equipo']); ?>"
                                    data-proyecto="<?php echo htmlspecialchars($equipo['proyecto_nombre'] ?? ''); ?>"
                                    data-contratista="<?php echo htmlspecialchars($equipo['contratista_nombre'] ?? ''); ?>"
                                    data-ubicacion="<?php echo htmlspecialchars($equipo['ubicacion_texto'] ?? ''); ?>"
                                    data-lat="<?php echo htmlspecialchars($equipo['ubicacion_latitud'] ?? ''); ?>"
                                    data-lng="<?php echo htmlspecialchars($equipo['ubicacion_longitud'] ?? ''); ?>"
                                    data-observaciones="<?php echo htmlspecialchars($equipo['observaciones'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($equipo['estado']); ?>"
                                    data-fotos="<?php echo $equipo['total_fotos']; ?>"
                                    data-fecha="<?php echo date('d/m/Y', strtotime($equipo['fecha_creacion'])); ?>">
                                    <td><span class="id-badge">#<?php echo $equipo['id']; ?></span></td>
                                    <td>
                                        <div class="equipo-cell">
                                            <div class="equipo-avatar">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                                                </svg>
                                            </div>
                                            <strong><?php echo htmlspecialchars($equipo['tipo_equipo']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($equipo['proyecto_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($equipo['contratista_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td>
                                        <?php if ($equipo['ubicacion_latitud'] && $equipo['ubicacion_longitud']): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $equipo['ubicacion_latitud']; ?>,<?php echo $equipo['ubicacion_longitud']; ?>" 
                                               target="_blank" class="location-link">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                                    <circle cx="12" cy="10" r="3"></circle>
                                                </svg>
                                                Ver ubicación
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin ubicación</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-fotos">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <?php echo $equipo['total_fotos']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $equipo['estado']; ?>">
                                            <?php 
                                                $estadoTexto = [
                                                    'activo' => 'Activo',
                                                    'en_mantenimiento' => 'En Mantenimiento',
                                                    'fuera_servicio' => 'Fuera de Servicio',
                                                    'dado_baja' => 'Dado de Baja'
                                                ];
                                                echo $estadoTexto[$equipo['estado']] ?? ucfirst($equipo['estado']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verEquipo(<?php echo $equipo['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" 
                                                    onclick="editarEquipo(<?php echo $equipo['id']; ?>)"
                                                    title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarEquipo(<?php echo $equipo['id']; ?>)"
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

<!-- Modal para Nuevo/Editar Equipo -->
<div id="modalInventario" class="modal">
    <div class="modal-content modal-inventario-lg">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Equipo</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formInventario" enctype="multipart/form-data">
                <input type="hidden" id="inventario_id" name="id">
                
                <!-- Tipo de Equipo -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="tipo_equipo">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                            </svg>
                            <span>Tipo de Equipo *</span>
                        </label>
                        <input type="text" id="tipo_equipo" name="tipo_equipo" required 
                               placeholder="Ej: Excavadora, Bulldozer, Martillo Hidráulico">
                        <small class="form-help">Escribe el tipo de equipo o maquinaria</small>
                    </div>
                </div>
                
                <!-- Ubicación del Equipo -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="ubicacion_texto">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Ubicación del Equipo</span>
                        </label>
                        <div class="location-container">
                            <input type="text" id="ubicacion_texto" name="ubicacion_texto" 
                                   placeholder="Escribe la ubicación o captura con GPS">
                            <button type="button" class="btn btn-location" onclick="capturarUbicacion()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="10" r="3"></circle>
                                    <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 10-16 0c0 3 2.7 6.9 8 11.7z"></path>
                                </svg>
                                <span>Capturar GPS</span>
                            </button>
                        </div>
                        <input type="hidden" id="ubicacion_latitud" name="ubicacion_latitud">
                        <input type="hidden" id="ubicacion_longitud" name="ubicacion_longitud">
                        <small class="form-help">Puedes escribir la ubicación manualmente o usar el GPS</small>
                        <div id="location-status" class="location-status"></div>
                        <div id="location-map" class="location-map" style="display: none;">
                            <p class="location-coords">
                                <strong>Coordenadas GPS:</strong> 
                                <span id="coords-display">-</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Proyecto Asignado -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="proyecto_id">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Proyecto Asignado</span>
                        </label>
                        <select id="proyecto_id" name="proyecto_id" class="select2-search">
                            <option value="">Sin asignar</option>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?php echo $proyecto['id']; ?>">
                                    <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Puedes buscar escribiendo el nombre del proyecto</small>
                    </div>
                </div>
                
                <!-- Contratista -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="contratista_id">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span>Contratista</span>
                        </label>
                        <select id="contratista_id" name="contratista_id" class="select2-search">
                            <option value="">Sin asignar</option>
                            <?php foreach ($contratistas as $contratista): ?>
                                <option value="<?php echo $contratista['id']; ?>">
                                    <?php echo htmlspecialchars($contratista['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Puedes buscar escribiendo el nombre del contratista</small>
                    </div>
                </div>
                
                <!-- Fotografías -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="fotografias">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span>Fotografías (Mínimo 1, Máximo 3)</span>
                        </label>
                        <input type="file" id="fotografias" name="fotografias[]" 
                               accept="image/jpeg,image/jpg,image/png,image/webp,application/pdf" 
                               multiple onchange="previsualizarImagenes(event)">
                        <small class="form-help">
                            Formatos: JPG, PNG, WEBP, PDF. Tamaño máximo: 5MB por archivo
                        </small>
                        
                        <!-- Previsualización de imágenes -->
                        <div id="preview-container" class="preview-container"></div>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="observaciones">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span>Observaciones</span>
                        </label>
                        <textarea id="observaciones" name="observaciones" rows="4" 
                                  placeholder="Escribe cualquier observación relevante sobre el equipo..."></textarea>
                    </div>
                </div>
                
                <!-- Estado -->
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
                            <option value="en_mantenimiento">En Mantenimiento</option>
                            <option value="fuera_servicio">Fuera de Servicio</option>
                            <option value="dado_baja">Dado de Baja</option>
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