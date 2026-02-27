<?php
// vistas/municipios.php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar acceso
verificarAcceso();

$pdo = obtenerConexion();
$rol = obtenerRolUsuario();
$departamentoUsuario = obtenerDepartamentoUsuario();
$municipioUsuario = obtenerMunicipioUsuario();

// Obtener departamento del filtro (si viene de la página de departamentos)
$departamentoFiltro = $_GET['departamento'] ?? null;

// Construir consulta según el rol
$whereClause = "WHERE 1=1";
$params = [];

if ($rol === ROL_ALCALDE) {
    $whereClause .= " AND departamento = :departamento AND municipio = :municipio";
    $params[':departamento'] = $departamentoUsuario;
    $params[':municipio'] = $municipioUsuario;
} elseif ($rol === ROL_DIPUTADO) {
    $whereClause .= " AND departamento = :departamento";
    $params[':departamento'] = $departamentoUsuario;
} elseif ($departamentoFiltro) {
    $whereClause .= " AND departamento = :departamento";
    $params[':departamento'] = $departamentoFiltro;
}

// Obtener lista de departamentos para el filtro
$stmtDepts = $pdo->query("SELECT DISTINCT departamento FROM empadronados ORDER BY departamento");
$departamentos = $stmtDepts->fetchAll(PDO::FETCH_COLUMN);

// Obtener datos de municipios
try {
    $stmt = $pdo->prepare("
        SELECT 
            municipio,
            departamento,
            total,
            total_mujeres,
            total_hombres,
            mujeres_alfabetas,
            mujeres_analfabetas,
            hombres_alfabetas,
            hombres_analfabetas,
            edad_18a25,
            edad_26a30,
            edad_31a35,
            edad_36a40,
            edad_41a45,
            edad_46a50,
            edad_51a55,
            edad_56a60,
            edad_61a65,
            edad_66a70,
            edad_mayoroigual70
        FROM empadronados 
        $whereClause
        ORDER BY total DESC
    ");
    $stmt->execute($params);
    $municipios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener municipios: " . $e->getMessage());
    $municipios = [];
}

$iniciales = obtenerIniciales(obtenerNombreUsuario());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipios - Sistema SICO GT</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- 🔥 SISTEMA DE DISEÑO PREMIUM - SICO GT -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    
    <style>
        /* Estilos específicos para la tabla de municipios */
        .progress-bar-pink {
            background: linear-gradient(135deg, #ec4899, #db2777) !important;
        }
        
        .progress-bar-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        }
        
        .table-container {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .table thead th {
            background: linear-gradient(180deg, rgba(212, 165, 116, 0.1), rgba(212, 165, 116, 0.05));
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            padding: 1rem;
        }
        
        .table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: all var(--transition-base);
        }
        
        .table tbody tr:hover {
            background: rgba(212, 165, 116, 0.05);
            transform: translateX(4px);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .progress {
            background-color: rgba(212, 165, 116, 0.15);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        
        /* Hover effect para botones dorados */
        button[style*="linear-gradient"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 165, 116, 0.4);
            filter: brightness(1.1);
        }
        
        button[style*="linear-gradient"]:active {
            transform: translateY(0);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--border-light);
        }
        
        .chart-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .chart-title i {
            color: var(--color-primary);
        }
        
        /* Filtros */
        .filters-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        /* Animaciones */
        .animate-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="page-title-wrapper">
                        <h1 class="page-title">Municipios</h1>
                        <p class="page-subtitle">Información detallada por municipio</p>
                    </div>
                </div>
                
                <div class="topbar-right">
                    <div class="topbar-actions">
                        <button class="btn-icon" title="Imprimir" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        <button class="btn-icon" title="Exportar" onclick="exportarTabla()">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                        <div class="user-info">
                            <p class="user-name"><?php echo obtenerNombreUsuario(); ?></p>
                            <p class="user-role"><?php echo obtenerRolUsuario(); ?></p>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                
                <!-- Filtros -->
                <div class="filters-card animate-in">
                    <div class="row align-items-end g-3">
                        <?php if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE): ?>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-map me-2"></i>
                                Filtrar por Departamento
                            </label>
                            <select id="filtroDepartamento" class="form-select">
                                <option value="">Todos los departamentos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                            <?php echo $dept === $departamentoFiltro ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-search me-2"></i>
                                Buscar Municipio
                            </label>
                            <input type="text" id="buscarMunicipio" class="form-control" 
                                   placeholder="Escriba para buscar...">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-funnel me-2"></i>
                                Ordenar por
                            </label>
                            <select id="ordenarPor" class="form-select">
                                <option value="poblacion">Mayor Población</option>
                                <option value="alfabetismo">Mayor Alfabetismo</option>
                                <option value="nombre">Nombre (A-Z)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <button class="btn w-100" 
                                    onclick="aplicarFiltros()"
                                    style="background: linear-gradient(135deg, #d4a574, #c9a167); 
                                           color: white; 
                                           border: none; 
                                           font-weight: 600;
                                           padding: 0.625rem 1rem;
                                           border-radius: 0.5rem;
                                           transition: all 0.3s ease;">
                                <i class="bi bi-check-circle me-2"></i>
                                Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de municipios -->
                <div class="table-container animate-in">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="bi bi-table"></i>
                            Listado de Municipios
                        </h3>
                        <span class="badge badge-primary">
                            <?php echo count($municipios); ?> municipio(s)
                        </span>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="tablaMunicipios" class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Municipio</th>
                                    <th>Departamento</th>
                                    <th class="text-center" style="width: 200px;">Mujeres</th>
                                    <th class="text-center" style="width: 200px;">Hombres</th>
                                    <th class="text-end" style="width: 130px;">Total Personas</th>
                                    <th class="text-center" style="width: 120px;">% Alfabetismo</th>
                                    <th class="text-center" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($municipios as $index => $muni): 
                                    $totalAlfabetas = $muni['mujeres_alfabetas'] + $muni['hombres_alfabetas'];
                                    $totalPersonas = $muni['total'];
                                    $porcentajeAlfabetismo = $totalPersonas > 0 ? round(($totalAlfabetas / $totalPersonas) * 100, 1) : 0;
                                    
                                    // Calcular porcentajes de mujeres y hombres
                                    $porcentajeMujeres = $totalPersonas > 0 ? round(($muni['total_mujeres'] / $totalPersonas) * 100, 1) : 0;
                                    $porcentajeHombres = $totalPersonas > 0 ? round(($muni['total_hombres'] / $totalPersonas) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #d4a574, #c9a167); color: white; font-weight: 600;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($muni['municipio']); ?></strong>
                                    </td>
                                    <td>
                                        <span style="color: #1a1a1a; font-weight: 500;"><?php echo htmlspecialchars($muni['departamento']); ?></span>
                                    </td>
                                    
                                    <!-- COLUMNA MUJERES con barra rosada -->
                                    <td data-order="<?php echo $muni['total_mujeres']; ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="min-width: 75px; font-size: 0.85rem; font-weight: 600; color: #1a1a1a;">
                                                <?php echo formatearNumero($muni['total_mujeres']); ?>
                                            </span>
                                            <div class="progress flex-grow-1" style="height: 26px; min-width: 70px;">
                                                <div class="progress-bar progress-bar-pink" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $porcentajeMujeres; ?>%"
                                                     aria-valuenow="<?php echo $porcentajeMujeres; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <strong style="font-weight: 700; font-size: 0.8rem;"><?php echo $porcentajeMujeres; ?>%</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- COLUMNA HOMBRES con barra azul -->
                                    <td data-order="<?php echo $muni['total_hombres']; ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="min-width: 75px; font-size: 0.85rem; font-weight: 600; color: #1a1a1a;">
                                                <?php echo formatearNumero($muni['total_hombres']); ?>
                                            </span>
                                            <div class="progress flex-grow-1" style="height: 26px; min-width: 70px;">
                                                <div class="progress-bar progress-bar-blue" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $porcentajeHombres; ?>%"
                                                     aria-valuenow="<?php echo $porcentajeHombres; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <strong style="font-weight: 700; font-size: 0.8rem;"><?php echo $porcentajeHombres; ?>%</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- COLUMNA TOTAL PERSONAS -->
                                    <td class="text-end" data-order="<?php echo $muni['total']; ?>">
                                        <strong style="font-size: 1rem; color: var(--text-primary);">
                                            <?php echo formatearNumero($muni['total']); ?>
                                        </strong>
                                    </td>
                                    
                                    <!-- COLUMNA ALFABETISMO -->
                                    <td>
                                        <div class="progress" style="height: 22px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $porcentajeAlfabetismo; ?>%"
                                                 aria-valuenow="<?php echo $porcentajeAlfabetismo; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <span style="font-weight: 600;"><?php echo $porcentajeAlfabetismo; ?>%</span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- COLUMNA ACCIONES -->
                                    <td class="text-center">
                                        <button class="btn btn-sm" 
                                                onclick="verDetalleMunicipio('<?php echo htmlspecialchars($muni['municipio']); ?>', '<?php echo htmlspecialchars($muni['departamento']); ?>')"
                                                title="Ver detalle"
                                                style="background: linear-gradient(135deg, #d4a574, #c9a167); 
                                                       color: white; 
                                                       border: none; 
                                                       font-weight: 600;
                                                       padding: 0.375rem 0.75rem;
                                                       border-radius: 0.375rem;
                                                       transition: all 0.3s ease;">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div> <!-- Cierra .content -->
        </main> <!-- Cierra .main-content -->
    </div> <!-- Cierra .dashboard-wrapper -->
    
    <!-- Modal de detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background: var(--bg-glass); backdrop-filter: blur(20px); border: 1px solid var(--border-color);">
                <div class="modal-header" style="background: linear-gradient(135deg, #d4a574, #c9a167); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>
                        Detalle del Municipio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoModal">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="../assets/js/cerrar_sesion.js"></script>
    
    <script>
        // Función para formatear números
        function formatearNumero(numero) {
            return new Intl.NumberFormat('es-GT').format(numero);
        }
        
        // Inicializar DataTable
        let tabla = $('#tablaMunicipios').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[5, 'desc']], // Ordenar por Total Personas (columna 5)
            columnDefs: [
                { 
                    orderable: false, 
                    targets: [7] // Deshabilitar orden en columna Acciones
                }
            ]
        });
        
        // Buscar municipio
        $('#buscarMunicipio').on('keyup', function() {
            tabla.search(this.value).draw();
        });
        
        // Filtrar por departamento
        $('#filtroDepartamento').on('change', function() {
            const departamento = this.value;
            if (departamento) {
                window.location.href = `municipios.php?departamento=${encodeURIComponent(departamento)}`;
            } else {
                window.location.href = 'municipios.php';
            }
        });
        
        // Ver detalle del municipio
        function verDetalleMunicipio(municipio, departamento) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            const contenido = document.getElementById('contenidoModal');
            
            contenido.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" style="color: #d4a574 !important;"></div><p class="mt-3">Cargando información...</p></div>';
            modal.show();
            
            // Buscar los datos del municipio
            const muniData = <?php echo json_encode($municipios); ?>.find(m => m.municipio === municipio && m.departamento === departamento);
            
            if (muniData) {
                const totalAlfabetas = parseInt(muniData.mujeres_alfabetas) + parseInt(muniData.hombres_alfabetas);
                const porcentajeAlf = ((totalAlfabetas / muniData.total) * 100).toFixed(1);
                const totalAnalfabetas = muniData.total - totalAlfabetas;
                const porcentajeAnalf = ((totalAnalfabetas / muniData.total) * 100).toFixed(1);
                
                contenido.innerHTML = `
                    <div class="row g-4">
                        <div class="col-12">
                            <div style="background: linear-gradient(135deg, #d4a574, #c9a167); padding: 1.5rem; border-radius: 0.75rem;">
                                <h3 style="color: white; margin: 0; font-weight: 700;">${municipio}</h3>
                                <p style="color: rgba(255,255,255,0.9); margin: 0.5rem 0 0 0;">${departamento}</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; text-align: center;">
                                <i class="bi bi-people-fill" style="font-size: 2rem; color: #3b82f6;"></i>
                                <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0; font-weight: 800;">${formatearNumero(muniData.total)}</h3>
                                <p style="color: var(--text-secondary); margin: 0; text-transform: uppercase; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">Población Total</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; text-align: center;">
                                <i class="bi bi-gender-female" style="font-size: 2rem; color: #ec4899;"></i>
                                <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0; font-weight: 800;">${formatearNumero(muniData.total_mujeres)}</h3>
                                <p style="color: var(--text-secondary); margin: 0; text-transform: uppercase; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">Mujeres</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; text-align: center;">
                                <i class="bi bi-gender-male" style="font-size: 2rem; color: #3b82f6;"></i>
                                <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0; font-weight: 800;">${formatearNumero(muniData.total_hombres)}</h3>
                                <p style="color: var(--text-secondary); margin: 0; text-transform: uppercase; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">Hombres</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem;">
                                <h5 style="color: var(--text-primary); margin: 0 0 1rem 0; font-weight: 700;">
                                    <i class="bi bi-book-fill me-2" style="color: #10b981;"></i>
                                    Alfabetismo
                                </h5>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span style="color: var(--text-secondary); font-weight: 600;">Alfabetas</span>
                                        <span style="color: var(--text-primary); font-weight: 700;">${formatearNumero(totalAlfabetas)} (${porcentajeAlf}%)</span>
                                    </div>
                                    <div class="progress" style="height: 12px; background: rgba(212, 165, 116, 0.15);">
                                        <div class="progress-bar" style="width: ${porcentajeAlf}%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span style="color: var(--text-secondary); font-weight: 600;">Analfabetas</span>
                                        <span style="color: var(--text-primary); font-weight: 700;">${formatearNumero(totalAnalfabetas)} (${porcentajeAnalf}%)</span>
                                    </div>
                                    <div class="progress" style="height: 12px; background: rgba(212, 165, 116, 0.15);">
                                        <div class="progress-bar" style="width: ${porcentajeAnalf}%; background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div style="background: var(--bg-glass); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem;">
                                <h5 style="color: var(--text-primary); margin: 0 0 1rem 0; font-weight: 700;">
                                    <i class="bi bi-calendar-range me-2" style="color: #d4a574;"></i>
                                    Rangos de Edad Principales
                                </h5>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span style="color: var(--text-secondary);">18-25 años:</span>
                                        <strong style="color: var(--text-primary);">${formatearNumero(muniData.edad_18a25)}</strong>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span style="color: var(--text-secondary);">26-30 años:</span>
                                        <strong style="color: var(--text-primary);">${formatearNumero(muniData.edad_26a30)}</strong>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span style="color: var(--text-secondary);">31-35 años:</span>
                                        <strong style="color: var(--text-primary);">${formatearNumero(muniData.edad_31a35)}</strong>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <span style="color: var(--text-secondary);">36-40 años:</span>
                                        <strong style="color: var(--text-primary);">${formatearNumero(muniData.edad_36a40)}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        // Aplicar filtros
        function aplicarFiltros() {
            const ordenar = $('#ordenarPor').val();
            
            if (ordenar === 'poblacion') {
                tabla.order([5, 'desc']).draw(); // Total Personas descendente
            } else if (ordenar === 'alfabetismo') {
                tabla.order([6, 'desc']).draw(); // Alfabetismo descendente
            } else if (ordenar === 'nombre') {
                tabla.order([1, 'asc']).draw(); // Nombre ascendente
            }
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Filtros aplicados correctamente',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
        
        // Exportar tabla
        function exportarTabla() {
            Swal.fire({
                title: 'Exportar Datos',
                text: '¿En qué formato desea exportar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                cancelButtonText: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                confirmButtonColor: '#d4a574',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generando Excel...',
                        text: 'Por favor espere',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: 'Archivo Excel generado correctamente',
                            icon: 'success',
                            confirmButtonColor: '#d4a574'
                        });
                    }, 1500);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: 'Generando PDF...',
                        text: 'Por favor espere',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: 'Archivo PDF generado correctamente',
                            icon: 'success',
                            confirmButtonColor: '#d4a574'
                        });
                    }, 1500);
                }
            });
        }
        
        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', () => {
            // Focus visible al tabular
            document.body.addEventListener('keydown', e => {
                if(e.key === 'Tab') document.documentElement.classList.add('show-focus');
            }, { once: true });
        });
    </script>
</body>
</html>