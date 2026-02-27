<?php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas rápidas
try {
    // Total de contratistas activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM contratistas WHERE estado = 'activo'");
    $totalActivos = $stmt->fetch()['total'];
    
    // Total de contratistas inactivos
    $stmt = $db->query("SELECT COUNT(*) as total FROM contratistas WHERE estado = 'inactivo'");
    $totalInactivos = $stmt->fetch()['total'];
    
    // Total de trabajadores
    $stmt = $db->query("SELECT COUNT(*) as total FROM trabajadores WHERE estado = 'activo'");
    $totalTrabajadores = $stmt->fetch()['total'];
    
    // Obtener todos los contratistas con el conteo de trabajadores
    $stmt = $db->query("
        SELECT 
            c.id,
            c.nombre,
            c.nit,
            c.direccion,
            c.telefono,
            c.email,
            c.contactoPrincipal,
            c.estado,
            c.fechaCreacion,
            COUNT(t.id) as total_empleados
        FROM contratistas c
        LEFT JOIN trabajadores t ON c.id = t.contratista_id AND t.estado = 'activo'
        GROUP BY c.id
        ORDER BY c.nombre ASC
    ");
    $contratistas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $contratistas = [];
    $totalActivos = 0;
    $totalInactivos = 0;
    $totalTrabajadores = 0;
}

$pageTitle = 'Gestión de Contratistas';

// CSS: SweetAlert2 + DataTables + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/contratistas.css'
];

// JS: jQuery, DataTables, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/contratistas.js'
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
                    <h1>Gestión de Contratistas</h1>
                    <p>Administra todas las empresas contratistas</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Contratista</span>
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

            <div class="stat-card stat-workers">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Trabajadores</div>
                    <div class="stat-value" data-target="<?php echo $totalTrabajadores; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Contratistas -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <h3>Lista de Contratistas</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="contratistasTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>NIT</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Empleados</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contratistas)): ?>
                            <?php foreach ($contratistas as $contratista): ?>
                                <tr data-id="<?php echo $contratista['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($contratista['nombre']); ?>"
                                    data-nit="<?php echo htmlspecialchars($contratista['nit']); ?>"
                                    data-direccion="<?php echo htmlspecialchars($contratista['direccion'] ?? ''); ?>"
                                    data-telefono="<?php echo htmlspecialchars($contratista['telefono'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($contratista['email'] ?? ''); ?>"
                                    data-contacto="<?php echo htmlspecialchars($contratista['contactoPrincipal'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($contratista['estado']); ?>"
                                    data-empleados="<?php echo $contratista['total_empleados']; ?>"
                                    data-fecha="<?php echo date('d/m/Y', strtotime($contratista['fechaCreacion'])); ?>">
                                    <td><span class="id-badge">#<?php echo $contratista['id']; ?></span></td>
                                    <td>
                                        <div class="company-cell">
                                            <div class="company-avatar">
                                                <?php echo strtoupper(substr($contratista['nombre'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($contratista['nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($contratista['nit']); ?></td>
                                    <td><?php echo htmlspecialchars($contratista['contactoPrincipal'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge-empleados">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"></path>
                                            </svg>
                                            <?php echo $contratista['total_empleados']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $contratista['estado']; ?>">
                                            <?php echo ucfirst($contratista['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verContratista(<?php echo $contratista['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" 
                                                    onclick="editarContratista(<?php echo $contratista['id']; ?>)"
                                                    title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarContratista(<?php echo $contratista['id']; ?>)"
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

<!-- Modal para Nuevo/Editar Contratista -->
<div id="modalContratista" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Contratista</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formContratista">
                <input type="hidden" id="contratista_id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Nombre de la Empresa *</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Constructora ABC S.A.">
                    </div>
                    
                    <div class="form-group">
                        <label for="nit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="M7 15h0M12 15h0"></path>
                            </svg>
                            <span>NIT *</span>
                        </label>
                        <input type="text" id="nit" name="nit" required 
                               placeholder="Ej: 12345678-9">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contactoPrincipal">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Contacto Principal *</span>
                        </label>
                        <input type="text" id="contactoPrincipal" name="contactoPrincipal" required 
                               placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
                            </svg>
                            <span>Teléfono</span>
                        </label>
                        <input type="tel" id="telefono" name="telefono" 
                               placeholder="Ej: 12345678" maxlength="8">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <span>Email</span>
                        </label>
                        <input type="email" id="email" name="email" 
                               placeholder="Ej: contacto@empresa.com">
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
                            <option value="suspendido">Suspendido</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-group-full">
                        <label for="direccion">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Dirección</span>
                        </label>
                        <textarea id="direccion" name="direccion" rows="3" 
                                  placeholder="Ej: Zona 10, Ciudad de Guatemala"></textarea>
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