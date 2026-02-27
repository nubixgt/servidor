<?php
/**
 * MANEJO DE INVENTARIO
 * Sistema de Supervisión v6.0.6
 * Gestión de Salidas e Ingresos de Bodega
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar autenticación y permisos
requireLogin();
requireAdmin();

$pageTitle = 'Manejo de Inventario';

// Obtener base de datos
$db = Database::getInstance()->getConnection();

// Obtener proyectos activos para el select
try {
    $stmtProyectos = $db->query("
        SELECT id, nombre 
        FROM proyectos 
        WHERE estado = 'activo' 
        ORDER BY nombre ASC
    ");
    $proyectos = $stmtProyectos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $proyectos = [];
}

// Obtener trabajadores activos para el select
try {
    $stmtTrabajadores = $db->query("
        SELECT id, nombre 
        FROM trabajadores 
        WHERE estado = 'activo' 
        ORDER BY nombre ASC
    ");
    $trabajadores = $stmtTrabajadores->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $trabajadores = [];
}

// Obtener movimientos con información relacionada
try {
    $stmt = $db->query("
        SELECT 
            mi.id,
            mi.producto,
            mi.tipo_gestion,
            mi.fecha_entrega,
            mi.observaciones,
            mi.fecha_creacion,
            p.nombre as proyecto_nombre,
            t.nombre as trabajador_nombre,
            u.usuario as usuario_creador,
            COUNT(mf.id) as total_fotos
        FROM manejo_inventario mi
        LEFT JOIN proyectos p ON mi.proyecto_id = p.id
        LEFT JOIN trabajadores t ON mi.trabajador_id = t.id
        LEFT JOIN usuarios u ON mi.usuario_id = u.id
        LEFT JOIN manejo_inventario_fotografias mf ON mi.id = mf.manejo_id
        GROUP BY mi.id
        ORDER BY mi.fecha_creacion DESC
    ");
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $movimientos = [];
    $error = $e->getMessage();
}

// Calcular estadísticas
$totalMovimientos = count($movimientos);
$totalSalidas = 0;
$totalIngresos = 0;

foreach ($movimientos as $mov) {
    if ($mov['tipo_gestion'] === 'Salida de Bodega') {
        $totalSalidas++;
    } else {
        $totalIngresos++;
    }
}

// CSS: SweetAlert2 + DataTables + Select2 + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    '/assets/css/pages/manejo_inventario.css'
];

// JS: jQuery, DataTables, Select2, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/manejo_inventario.js'
];

require_once '../../includes/header.php';
?>

<?php require_once '../../includes/navbar_admin.php'; ?>

<main class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>📦 Manejo de Inventario</h1>
                    <p>Control de salidas e ingresos de bodega</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Movimiento</span>
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Movimientos -->
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de Movimientos</div>
                    <div class="stat-value" data-target="<?php echo $totalMovimientos; ?>">0</div>
                </div>
            </div>

            <!-- Salidas de Bodega -->
            <div class="stat-card stat-salidas">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
                        <path d="M2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
                        <path d="M7 21h10"></path>
                        <path d="M12 3v18"></path>
                        <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Salidas de Bodega</div>
                    <div class="stat-value" data-target="<?php echo $totalSalidas; ?>">0</div>
                </div>
            </div>

            <!-- Ingresos de Bodega -->
            <div class="stat-card stat-ingresos">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7h-9"></path>
                        <path d="M14 17H5"></path>
                        <circle cx="17" cy="17" r="3"></circle>
                        <circle cx="7" cy="7" r="3"></circle>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Ingresos de Bodega</div>
                    <div class="stat-value" data-target="<?php echo $totalIngresos; ?>">0</div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <h3>Listado de Movimientos</h3>
                </div>
            </div>

            <div class="table-container">
                <table id="tablaManejoInventario" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Tipo de Gestión</th>
                            <th>Proyecto</th>
                            <th>Responsable</th>
                            <th>Fecha Entrega</th>
                            <th>Fotos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr data-id="<?php echo $mov['id']; ?>"
                                    data-producto="<?php echo htmlspecialchars($mov['producto']); ?>"
                                    data-tipo="<?php echo htmlspecialchars($mov['tipo_gestion']); ?>"
                                    data-proyecto="<?php echo htmlspecialchars($mov['proyecto_nombre'] ?? 'Sin proyecto'); ?>"
                                    data-trabajador="<?php echo htmlspecialchars($mov['trabajador_nombre'] ?? 'Sin asignar'); ?>"
                                    data-fecha-entrega="<?php echo htmlspecialchars($mov['fecha_entrega']); ?>"
                                    data-observaciones="<?php echo htmlspecialchars($mov['observaciones'] ?? ''); ?>"
                                    data-total-fotos="<?php echo $mov['total_fotos']; ?>">
                                    
                                    <!-- ID -->
                                    <td>
                                        <span class="id-badge">#<?php echo str_pad($mov['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    
                                    <!-- Producto -->
                                    <td>
                                        <div class="producto-cell">
                                            <div class="producto-avatar">
                                                📦
                                            </div>
                                            <strong><?php echo htmlspecialchars($mov['producto']); ?></strong>
                                        </div>
                                    </td>
                                    
                                    <!-- Tipo de Gestión -->
                                    <td>
                                        <?php 
                                        $tipoClass = $mov['tipo_gestion'] === 'Salida de Bodega' ? 'tipo-salida' : 'tipo-ingreso';
                                        ?>
                                        <span class="tipo-badge <?php echo $tipoClass; ?>">
                                            <?php echo htmlspecialchars($mov['tipo_gestion']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Proyecto -->
                                    <td><?php echo htmlspecialchars($mov['proyecto_nombre'] ?? 'Sin proyecto'); ?></td>
                                    
                                    <!-- Responsable -->
                                    <td><?php echo htmlspecialchars($mov['trabajador_nombre'] ?? 'Sin asignar'); ?></td>
                                    
                                    <!-- Fecha Entrega -->
                                    <td><?php echo date('d/m/Y', strtotime($mov['fecha_entrega'])); ?></td>
                                    
                                    <!-- Fotos -->
                                    <td>
                                        <span class="badge-fotos">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <?php echo $mov['total_fotos']; ?> foto<?php echo $mov['total_fotos'] != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Acciones -->
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="verMovimiento(<?php echo $mov['id']; ?>)" title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" onclick="editarMovimiento(<?php echo $mov['id']; ?>)" title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="eliminarMovimiento(<?php echo $mov['id']; ?>)" title="Eliminar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <p style="color: #9ca3af; font-size: 16px;">No hay movimientos registrados</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nuevo/Editar Movimiento -->
<div id="modalMovimiento" class="modal">
    <div class="modal-content modal-manejo-lg">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Movimiento de Inventario</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formMovimiento" enctype="multipart/form-data">
                <input type="hidden" id="movimiento_id" name="movimiento_id">
                
                <!-- Producto -->
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="producto">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                            </svg>
                            <span>Producto *</span>
                        </label>
                        <select id="producto" name="producto" required>
                            <option value="">-- Seleccione un producto --</option>
                            <option value="Excavadora hidráulica">Excavadora hidráulica</option>
                            <option value="Retroexcavadora">Retroexcavadora</option>
                            <option value="Patrol">Patrol</option>
                            <option value="Motoniveladora">Motoniveladora</option>
                            <option value="Minicargador">Minicargador</option>
                            <option value="Cargador frontal">Cargador frontal</option>
                        </select>
                        <small class="form-help">Seleccione el producto a gestionar</small>
                    </div>
                </div>
                
                <!-- Tipo de Gestión -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_gestion">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                            <span>Tipo de Gestión *</span>
                        </label>
                        <select id="tipo_gestion" name="tipo_gestion" required>
                            <option value="">-- Seleccione el tipo --</option>
                            <option value="Salida de Bodega">Salida de Bodega</option>
                            <option value="Ingreso de Bodega">Ingreso de Bodega</option>
                        </select>
                        <small class="form-help">Indique si es entrada o salida</small>
                    </div>
                    
                    <!-- Fecha de Entrega -->
                    <div class="form-group">
                        <label for="fecha_entrega">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Fecha de Entrega *</span>
                        </label>
                        <input type="date" id="fecha_entrega" name="fecha_entrega" required>
                        <small class="form-help">Fecha en que se realizará la gestión</small>
                    </div>
                </div>
                
                <!-- Proyecto y Responsable -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="proyecto_id">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Proyecto al que se enviará *</span>
                        </label>
                        <select id="proyecto_id" name="proyecto_id" required class="select2">
                            <option value="">-- Seleccione un proyecto --</option>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?php echo $proyecto['id']; ?>">
                                    <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Proyecto de destino</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="trabajador_id">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Responsable al que se asignará *</span>
                        </label>
                        <select id="trabajador_id" name="trabajador_id" required class="select2">
                            <option value="">-- Seleccione un responsable --</option>
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <option value="<?php echo $trabajador['id']; ?>">
                                    <?php echo htmlspecialchars($trabajador['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Persona responsable</small>
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
                            <span>Fotografías * (Mínimo 1, Máximo 2)</span>
                        </label>
                        <input type="file" id="fotografias" name="fotografias[]" 
                               accept="image/jpeg,image/jpg,image/png,image/webp" 
                               multiple>
                        <small class="form-help">Formatos: JPG, PNG, WEBP. Máximo 5MB por foto.</small>
                        <div id="previewContainer" class="preview-container"></div>
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
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Observaciones</span>
                        </label>
                        <textarea id="observaciones" name="observaciones" 
                                  rows="4" 
                                  placeholder="Observaciones adicionales (opcional)"></textarea>
                        <small class="form-help">Información adicional sobre el movimiento</small>
                    </div>
                </div>
                
                <!-- Botones -->
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
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Guardar Movimiento</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>