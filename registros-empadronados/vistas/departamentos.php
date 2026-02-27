<?php
// vistas/departamentos.php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar acceso - solo usuarios con permiso pueden ver departamentos
verificarAcceso([ROL_ADMINISTRADOR, ROL_PRESIDENTE, ROL_DIPUTADO]);

$pdo = obtenerConexion();
$rol = obtenerRolUsuario();
$departamentoUsuario = obtenerDepartamentoUsuario();

// Construir consulta según el rol
$whereClause = "WHERE 1=1";
$params = [];

if ($rol === ROL_DIPUTADO) {
    $whereClause .= " AND departamento = :departamento";
    $params[':departamento'] = $departamentoUsuario;
}

// Obtener datos por departamento
try {
    $stmt = $pdo->prepare("
        SELECT 
            departamento,
            COUNT(DISTINCT municipio) as num_municipios,
            SUM(total) as total_personas,
            SUM(total_mujeres) as total_mujeres,
            SUM(total_hombres) as total_hombres,
            SUM(mujeres_alfabetas) as mujeres_alfabetas,
            SUM(mujeres_analfabetas) as mujeres_analfabetas,
            SUM(hombres_alfabetas) as hombres_alfabetas,
            SUM(hombres_analfabetas) as hombres_analfabetas
        FROM empadronados 
        $whereClause
        GROUP BY departamento
        ORDER BY total_personas DESC
    ");
    $stmt->execute($params);
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener departamentos: " . $e->getMessage());
    $departamentos = [];
}

$iniciales = obtenerIniciales(obtenerNombreUsuario());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos - Sistema SICO GT</title>
    
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
        /* Estilos específicos para la tabla de departamentos */
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
                        <h1 class="page-title">Departamentos</h1>
                        <p class="page-subtitle">Información por departamento de Guatemala</p>
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
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="bi bi-search me-2"></i>
                                Buscar Departamento
                            </label>
                            <input type="text" id="buscarDepartamento" class="form-control" 
                                   placeholder="Escriba para buscar...">
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                
                <!-- Tabla de departamentos -->
                <div class="table-container animate-in">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="bi bi-table"></i>
                            Listado de Departamentos
                        </h3>
                        <span class="badge badge-primary">
                            <?php echo count($departamentos); ?> departamento(s)
                        </span>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="tablaDepartamentos" class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Departamento</th>
                                    <th class="text-center" style="width: 100px;">Municipios</th>
                                    <th class="text-center" style="width: 200px;">Mujeres</th>
                                    <th class="text-center" style="width: 200px;">Hombres</th>
                                    <th class="text-end" style="width: 130px;">Total Personas</th>
                                    <th class="text-center" style="width: 120px;">% Alfabetismo</th>
                                    <th class="text-center" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departamentos as $index => $dept): 
                                    $totalAlfabetas = $dept['mujeres_alfabetas'] + $dept['hombres_alfabetas'];
                                    $totalPersonas = $dept['total_personas'];
                                    $porcentajeAlfabetismo = $totalPersonas > 0 ? round(($totalAlfabetas / $totalPersonas) * 100, 1) : 0;
                                    
                                    // Calcular porcentajes de mujeres y hombres
                                    $porcentajeMujeres = $totalPersonas > 0 ? round(($dept['total_mujeres'] / $totalPersonas) * 100, 1) : 0;
                                    $porcentajeHombres = $totalPersonas > 0 ? round(($dept['total_hombres'] / $totalPersonas) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #d4a574, #c9a167); color: white; font-weight: 600;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($dept['departamento']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background: linear-gradient(135deg, #d4a574, #c9a167); color: white; font-weight: 600;">
                                            <?php echo $dept['num_municipios']; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- COLUMNA MUJERES con barra rosada -->
                                    <td data-order="<?php echo $dept['total_mujeres']; ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="min-width: 75px; font-size: 0.85rem; font-weight: 600; color: #1a1a1a;">
                                                <?php echo formatearNumero($dept['total_mujeres']); ?>
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
                                    <td data-order="<?php echo $dept['total_hombres']; ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="min-width: 75px; font-size: 0.85rem; font-weight: 600; color: #1a1a1a;">
                                                <?php echo formatearNumero($dept['total_hombres']); ?>
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
                                    <td class="text-end" data-order="<?php echo $dept['total_personas']; ?>">
                                        <strong style="font-size: 1rem; color: var(--text-primary);">
                                            <?php echo formatearNumero($dept['total_personas']); ?>
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
                                                onclick="verDetalle('<?php echo htmlspecialchars($dept['departamento']); ?>')"
                                                title="Ver municipios"
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
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="../assets/js/cerrar_sesion.js"></script>
    
    <script>
        // Inicializar DataTable
        let tabla = $('#tablaDepartamentos').DataTable({
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
        
        // Buscar departamento
        $('#buscarDepartamento').on('keyup', function() {
            tabla.search(this.value).draw();
        });
        
        // Ver detalle
        function verDetalle(departamento) {
            window.location.href = `municipios.php?departamento=${encodeURIComponent(departamento)}`;
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