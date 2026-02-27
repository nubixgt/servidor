<?php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Solo administradores
verificarAcceso([ROL_ADMINISTRADOR]);

$pdo = obtenerConexion();

// Crear tabla de logs si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            accion VARCHAR(100) NOT NULL,
            descripcion TEXT,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_usuario (usuario_id),
            INDEX idx_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    error_log("Error al crear tabla logs: " . $e->getMessage());
}

// Obtener filtros
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$usuarioFiltro = $_GET['usuario'] ?? '';

// Construir consulta
$whereClause = "WHERE l.fecha BETWEEN :fecha_inicio AND :fecha_fin";
$params = [
    ':fecha_inicio' => $fechaInicio . ' 00:00:00',
    ':fecha_fin' => $fechaFin . ' 23:59:59'
];

if (!empty($usuarioFiltro)) {
    $whereClause .= " AND u.Usuario LIKE :usuario";
    $params[':usuario'] = "%$usuarioFiltro%";
}

// Obtener logs
try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.Usuario, u.NombreCompleto, u.Rol
        FROM logs l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        $whereClause
        ORDER BY l.fecha DESC
        LIMIT 500
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener logs: " . $e->getMessage());
    $logs = [];
}

// Obtener estadísticas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM logs l
        $whereClause
    ");
    $stmt->execute($params);
    $totalLogs = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $totalLogs = 0;
}

$iniciales = obtenerIniciales(obtenerNombreUsuario());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Actividad - Sistema SICO GT</title>
    
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
        /* Tabla container */
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
        
        /* Filters card */
        .filters-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(40%, -40%);
            transition: all 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: currentColor;
        }
        
        .stat-card:hover::before {
            transform: translate(30%, -30%) scale(1.2);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
        }
        
        .stat-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        
        .stat-label {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .stat-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-info {
            background: rgba(255,255,255,0.8);
            transform: translateX(4px);
        }
        
        /* Badges dorados */
        .badge.bg-primary {
            background: var(--gradient-primary) !important;
        }
        
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268) !important;
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, #0dcaf0, #0aa2c0) !important;
        }
        
        /* Info card */
        .info-card {
            background: linear-gradient(135deg, rgba(212, 165, 116, 0.1), rgba(212, 165, 116, 0.05));
            border: 2px solid var(--color-primary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            backdrop-filter: blur(20px);
        }
        
        /* Animaciones */
        .animate-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-delay-1 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-2 {
            animation-delay: 0.2s;
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
                        <h1 class="page-title">Logs de Actividad</h1>
                        <p class="page-subtitle">Registro de actividades del sistema</p>
                    </div>
                </div>
                
                <div class="topbar-right">
                    <div class="topbar-actions">
                        <button class="btn-icon" 
                                onclick="exportarLogs()"
                                title="Exportar"
                                style="background: var(--gradient-primary); color: white; width: auto; padding: 0.625rem 1.5rem; border-radius: var(--radius-md);">
                            <i class="bi bi-download me-2"></i>
                            <span>Exportar</span>
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
                    <form method="GET" action="">
                        <div class="row align-items-end g-3">
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar me-2"></i>
                                    Fecha Inicio
                                </label>
                                <input type="date" class="form-control" name="fecha_inicio" 
                                       value="<?php echo $fechaInicio; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar me-2"></i>
                                    Fecha Fin
                                </label>
                                <input type="date" class="form-control" name="fecha_fin" 
                                       value="<?php echo $fechaFin; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bi bi-person me-2"></i>
                                    Filtrar por Usuario
                                </label>
                                <input type="text" class="form-control" name="usuario" 
                                       value="<?php echo htmlspecialchars($usuarioFiltro); ?>"
                                       placeholder="Nombre de usuario...">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" 
                                        class="btn w-100"
                                        style="background: linear-gradient(135deg, #d4a574, #c9a167); 
                                               color: white; 
                                               border: none; 
                                               font-weight: 600;
                                               padding: 0.625rem 1rem;
                                               border-radius: 0.5rem;">
                                    <i class="bi bi-funnel me-2"></i>
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Estadísticas -->
                <div class="stats-grid animate-in">
                    <!-- Total Registros -->
                    <div class="stat-card" style="color: #3b82f6;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div class="stat-value" style="background: linear-gradient(135deg, #3b82f6, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <?php echo formatearNumero($totalLogs); ?>
                        </div>
                        <div class="stat-label">Total de Registros</div>
                        <div class="stat-info" style="color: #3b82f6;">
                            <i class="bi bi-database-fill"></i>
                            <span>Base de datos completa</span>
                        </div>
                    </div>
                    
                    <!-- Registros Mostrados -->
                    <div class="stat-card animate-delay-1" style="color: #10b981;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="bi bi-calendar-range"></i>
                        </div>
                        <div class="stat-value" style="background: linear-gradient(135deg, #10b981, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <?php echo count($logs); ?>
                        </div>
                        <div class="stat-label">Registros Mostrados</div>
                        <div class="stat-info" style="color: #10b981;">
                            <i class="bi bi-eye-fill"></i>
                            <span>Filtro actual</span>
                        </div>
                    </div>
                    
                    <!-- Días de Rango -->
                    <div class="stat-card animate-delay-2" style="color: #f59e0b;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="bi bi-calendar-date"></i>
                        </div>
                        <div class="stat-value" style="background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <?php 
                            $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400 + 1;
                            echo round($dias);
                            ?>
                        </div>
                        <div class="stat-label">Días de Rango</div>
                        <div class="stat-info" style="color: #f59e0b;">
                            <i class="bi bi-clock-history"></i>
                            <span>Período seleccionado</span>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de logs -->
                <div class="table-container animate-in">
                    <div class="chart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-md); border-bottom: 2px solid var(--border-light);">
                        <h3 class="chart-title" style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: var(--spacing-sm);">
                            <i class="bi bi-table" style="color: var(--color-primary);"></i>
                            Registro de Actividades
                        </h3>
                        <span class="badge bg-primary"><?php echo count($logs); ?> registro(s)</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="tablaLogs" class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th style="width: 180px;">Fecha/Hora</th>
                                    <th>Usuario</th>
                                    <th style="width: 150px;">Rol</th>
                                    <th style="width: 150px;">Acción</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary" style="font-size: 0.875rem;">
                                                <?php echo $log['id']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small style="color: var(--text-secondary); font-weight: 500;">
                                                <?php echo formatearFechaHora($log['fecha']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong style="color: var(--text-primary);">
                                                <?php echo htmlspecialchars($log['NombreCompleto'] ?? 'Usuario Eliminado'); ?>
                                            </strong>
                                            <br>
                                            <small style="color: var(--text-secondary);">
                                                @<?php echo htmlspecialchars($log['Usuario'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($log['Rol'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($log['accion']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small style="color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($log['descripcion'] ?? '-'); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="bi bi-inbox display-1" style="color: var(--text-disabled);"></i>
                                            <p class="mt-3" style="color: var(--text-secondary);">
                                                No hay registros de actividad en el rango seleccionado
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="info-card animate-in">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="bi bi-info-circle-fill" style="font-size: 2rem; color: var(--color-primary); flex-shrink: 0;"></i>
                        <div>
                            <h5 style="color: var(--text-primary); margin: 0 0 0.5rem 0; font-weight: 700;">
                                Acerca de los Logs
                            </h5>
                            <p style="color: var(--text-secondary); margin: 0; line-height: 1.6;">
                                Los logs de actividad registran todas las acciones importantes realizadas en el sistema.
                                Esta información es útil para auditoría y seguridad. 
                                Se muestran los últimos 500 registros del rango de fechas seleccionado.
                            </p>
                        </div>
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
    <script src="../assets/js/cerrar_sesion.js"></script>
    
    <script>
        // Inicializar DataTable
        $('#tablaLogs').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 50,
            order: [[0, 'desc']]
        });
        
        // Exportar logs
        function exportarLogs() {
            Swal.fire({
                title: 'Exportar Logs',
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