<?php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas rápidas
try {
    // Total de proveedores activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM proveedores WHERE estado = 'activo'");
    $totalActivos = $stmt->fetch()['total'];
    
    // Total de proveedores inactivos
    $stmt = $db->query("SELECT COUNT(*) as total FROM proveedores WHERE estado = 'inactivo'");
    $totalInactivos = $stmt->fetch()['total'];
    
    // Total general
    $stmt = $db->query("SELECT COUNT(*) as total FROM proveedores");
    $totalGeneral = $stmt->fetch()['total'];
    
    // Obtener todos los proveedores
    $stmt = $db->query("
        SELECT 
            id,
            nombre,
            nit,
            telefono,
            observaciones,
            estado,
            fechaCreacion
        FROM proveedores
        ORDER BY fechaCreacion DESC
    ");
    $proveedores = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $proveedores = [];
    $totalActivos = 0;
    $totalInactivos = 0;
    $totalGeneral = 0;
}

$pageTitle = 'Gestión de Proveedores';

// CSS: SweetAlert2 + DataTables + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/proveedores.css'
];

// JS: jQuery, DataTables, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/proveedores.js'
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
                    <h1>Gestión de Proveedores</h1>
                    <p>Administra todos los proveedores del sistema</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Proveedor</span>
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

            <div class="stat-card stat-inactive">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Inactivos</div>
                    <div class="stat-value" data-target="<?php echo $totalInactivos; ?>">0</div>
                </div>
            </div>

            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total General</div>
                    <div class="stat-value" data-target="<?php echo $totalGeneral; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Proveedores -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <h3>Lista de Proveedores</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="proveedoresTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>NIT</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($proveedores)): ?>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <tr data-id="<?php echo $proveedor['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                    data-nit="<?php echo htmlspecialchars($proveedor['nit'] ?? ''); ?>"
                                    data-telefono="<?php echo htmlspecialchars($proveedor['telefono'] ?? ''); ?>"
                                    data-observaciones="<?php echo htmlspecialchars($proveedor['observaciones'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($proveedor['estado']); ?>"
                                    data-fecha="<?php echo date('d/m/Y', strtotime($proveedor['fechaCreacion'])); ?>">
                                    <td><span class="id-badge">#<?php echo $proveedor['id']; ?></span></td>
                                    <td>
                                        <div class="provider-cell">
                                            <div class="provider-avatar">
                                                <?php echo strtoupper(substr($proveedor['nombre'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($proveedor['nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($proveedor['nit'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($proveedor['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $proveedor['estado']; ?>">
                                            <?php echo ucfirst($proveedor['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verProveedor(<?php echo $proveedor['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" 
                                                    onclick="editarProveedor(<?php echo $proveedor['id']; ?>)"
                                                    title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarProveedor(<?php echo $proveedor['id']; ?>)"
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

<!-- Modal para Nuevo/Editar Proveedor -->
<div id="modalProveedor" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Proveedor</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formProveedor">
                <input type="hidden" id="proveedor_id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Nombre del Proveedor *</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Distribuidora XYZ S.A.">
                    </div>
                    
                    <div class="form-group">
                        <label for="nit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="M7 15h0M12 15h0"></path>
                            </svg>
                            <span>NIT</span>
                        </label>
                        <input type="text" id="nit" name="nit" 
                               placeholder="Ej: 1234567-8">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
                            </svg>
                            <span>Teléfono</span>
                        </label>
                        <input type="tel" id="telefono" name="telefono" 
                               placeholder="Ej: 45289012" maxlength="8" pattern="[0-9]{8}">
                    </div>
                    
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
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
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
                        <textarea id="observaciones" name="observaciones" rows="4"
                                  placeholder="Notas adicionales sobre el proveedor..."></textarea>
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