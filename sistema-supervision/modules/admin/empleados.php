<?php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas rápidas
try {
    // Total de trabajadores activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM trabajadores WHERE estado = 'activo'");
    $totalActivos = $stmt->fetch()['total'];
    
    // Total de trabajadores inactivos
    $stmt = $db->query("SELECT COUNT(*) as total FROM trabajadores WHERE estado = 'inactivo'");
    $totalInactivos = $stmt->fetch()['total'];
    
    // Total de contratistas
    $stmt = $db->query("SELECT COUNT(*) as total FROM contratistas WHERE estado = 'activo'");
    $totalContratistas = $stmt->fetch()['total'];
    
    // Obtener todos los trabajadores con información del contratista
    $stmt = $db->query("
        SELECT 
            t.id,
            t.nombre,
            t.telefono,
            t.puesto,
            t.dpi,
            t.fecha_nacimiento,
            t.fecha_contratacion,
            t.salario,
            t.horas_extra,
            t.modalidad,
            t.estado,
            t.fechaCreacion,
            t.contratista_id,
            c.nombre as contratista_nombre
        FROM trabajadores t
        LEFT JOIN contratistas c ON t.contratista_id = c.id
        ORDER BY t.fechaCreacion DESC
    ");
    $trabajadores = $stmt->fetchAll();
    
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
    $trabajadores = [];
    $contratistas = [];
    $totalActivos = 0;
    $totalInactivos = 0;
    $totalContratistas = 0;
}

$pageTitle = 'Gestión de Trabajadores';

// CSS: SweetAlert2 + DataTables + estilos locales
$extraCSS = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    '/assets/css/pages/empleados.css'
];

// JS: jQuery, DataTables, SweetAlert2 + local
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    '/assets/js/pages/empleados.js'
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
                    <h1>Gestión de Trabajadores</h1>
                    <p>Administra todos los empleados del sistema</p>
                </div>
                <button class="btn-new" onclick="abrirModalNuevo()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Nuevo Trabajador</span>
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

            <div class="stat-card stat-contractors">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Contratistas</div>
                    <div class="stat-value" data-target="<?php echo $totalContratistas; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Trabajadores -->
        <div class="table-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"></path>
                    </svg>
                    <h3>Lista de Trabajadores</h3>
                </div>
            </div>
            
            <div class="table-container">
                <table id="trabajadoresTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Contratista</th>
                            <th>Puesto</th>
                            <th>Modalidad</th>
                            <th>Salario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trabajadores)): ?>
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <tr data-id="<?php echo $trabajador['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($trabajador['nombre']); ?>"
                                    data-contratista-id="<?php echo htmlspecialchars($trabajador['contratista_id']); ?>"
                                    data-contratista-nombre="<?php echo htmlspecialchars($trabajador['contratista_nombre'] ?? 'Sin asignar'); ?>"
                                    data-puesto="<?php echo htmlspecialchars($trabajador['puesto'] ?? ''); ?>"
                                    data-dpi="<?php echo htmlspecialchars($trabajador['dpi'] ?? ''); ?>"
                                    data-telefono="<?php echo htmlspecialchars($trabajador['telefono'] ?? ''); ?>"
                                    data-fecha-nacimiento="<?php echo htmlspecialchars($trabajador['fecha_nacimiento'] ?? ''); ?>"
                                    data-fecha-contratacion="<?php echo htmlspecialchars($trabajador['fecha_contratacion'] ?? ''); ?>"
                                    data-salario="<?php echo htmlspecialchars($trabajador['salario'] ?? ''); ?>"
                                    data-horas-extra="<?php echo htmlspecialchars($trabajador['horas_extra'] ?? '0'); ?>"
                                    data-modalidad="<?php echo htmlspecialchars($trabajador['modalidad'] ?? ''); ?>"
                                    data-estado="<?php echo htmlspecialchars($trabajador['estado']); ?>"
                                    data-fecha="<?php echo date('d/m/Y', strtotime($trabajador['fechaCreacion'])); ?>">
                                    <td><span class="id-badge">#<?php echo $trabajador['id']; ?></span></td>
                                    <td>
                                        <div class="worker-cell">
                                            <div class="worker-avatar">
                                                <?php echo strtoupper(substr($trabajador['nombre'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($trabajador['nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($trabajador['contratista_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($trabajador['puesto'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($trabajador['modalidad'])): ?>
                                            <span class="modalidad-badge modalidad-<?php echo strtolower(str_replace(' ', '-', $trabajador['modalidad'])); ?>">
                                                <?php echo htmlspecialchars($trabajador['modalidad']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="modalidad-badge">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($trabajador['salario'])) {
                                            echo 'Q' . number_format($trabajador['salario'], 2);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $trabajador['estado']; ?>">
                                            <?php echo ucfirst($trabajador['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    onclick="verTrabajador(<?php echo $trabajador['id']; ?>)"
                                                    title="Ver detalles">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-edit" 
                                                    onclick="editarTrabajador(<?php echo $trabajador['id']; ?>)"
                                                    title="Editar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-delete" 
                                                    onclick="eliminarTrabajador(<?php echo $trabajador['id']; ?>)"
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

<!-- Modal para Nuevo/Editar Trabajador -->
<div id="modalTrabajador" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Trabajador</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formTrabajador">
                <input type="hidden" id="trabajador_id" name="id">
                
                <!-- Fila 1: Nombre y Contratista -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Nombre Completo *</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Juan Carlos Pérez">
                    </div>
                    
                    <div class="form-group">
                        <label for="contratista_id">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Contratista *</span>
                        </label>
                        <select id="contratista_id" name="contratista_id" required>
                            <option value="">Seleccione un contratista</option>
                            <?php foreach ($contratistas as $contratista): ?>
                                <option value="<?php echo $contratista['id']; ?>">
                                    <?php echo htmlspecialchars($contratista['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Fila 2: Puesto y DPI -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="puesto">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                            </svg>
                            <span>Puesto/Cargo *</span>
                        </label>
                        <input type="text" id="puesto" name="puesto" required 
                               placeholder="Ej: Operador de Maquinaria">
                    </div>
                    
                    <div class="form-group">
                        <label for="dpi">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="M7 15h0M12 15h0"></path>
                            </svg>
                            <span>DPI *</span>
                        </label>
                        <input type="text" id="dpi" name="dpi" required
                               placeholder="Ej: 2156789012345" 
                               maxlength="13"
                               pattern="[0-9]{13}">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            13 dígitos exactos
                        </small>
                    </div>
                </div>
                
                <!-- Fila 3: Teléfono y Fecha de Nacimiento -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
                            </svg>
                            <span>Teléfono *</span>
                        </label>
                        <input type="text" id="telefono" name="telefono" required
                               placeholder="Ej: 45289012" 
                               maxlength="8"
                               pattern="[0-9]{8}">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            8 dígitos exactos
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_nacimiento">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Fecha de Nacimiento</span>
                        </label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                </div>
                
                <!-- Fila 4: Fecha de Contratación y Salario -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_contratacion">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Fecha de Contratación</span>
                        </label>
                        <input type="date" id="fecha_contratacion" name="fecha_contratacion">
                    </div>
                    
                    <div class="form-group">
                        <label for="salario">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                            </svg>
                            <span>Salario</span>
                        </label>
                        <input type="text" id="salario" name="salario" 
                               placeholder="Ej: 3500.00">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Se mostrará como Q0,000.00
                        </small>
                    </div>
                </div>
                
                <!-- Fila 5: Horas Extra y Modalidad -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="horas_extra">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Horas Extra</span>
                        </label>
                        <input type="number" id="horas_extra" name="horas_extra" 
                               placeholder="Ej: 30" min="0" step="1">
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Solo números enteros
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalidad">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Modalidad</span>
                        </label>
                        <select id="modalidad" name="modalidad">
                            <option value="">Seleccione una modalidad</option>
                            <option value="Plan 24">Plan 24</option>
                            <option value="Mes">Mes</option>
                            <option value="Destajo">Destajo</option>
                        </select>
                    </div>
                </div>
                
                <!-- Fila 6: Estado -->
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
                            <option value="inactivo">Inactivo</option>
                            <option value="suspendido">Suspendido</option>
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