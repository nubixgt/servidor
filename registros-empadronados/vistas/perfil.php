<?php
// vistas/perfil.php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar acceso
verificarAcceso();

$pdo = obtenerConexion();
$usuarioId = $_SESSION['usuario_id'];

// Obtener datos del usuario
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $usuarioId]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error al obtener usuario: " . $e->getMessage());
    $usuario = null;
}

$iniciales = obtenerIniciales($usuario['NombreCompleto'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema SICO GT</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- 🔥 SISTEMA DE DISEÑO PREMIUM - SICO GT -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    
    <style>
        /* Card de perfil */
        .profile-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            text-align: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin: 0 auto var(--spacing-lg);
            box-shadow: var(--shadow-lg);
            border: 4px solid white;
        }
        
        .profile-name {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .profile-username {
            color: var(--text-secondary);
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-md);
        }
        
        .profile-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Info card */
        .info-card {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .info-card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--border-light);
            margin-bottom: var(--spacing-lg);
        }
        
        .info-card-header i {
            font-size: 1.5rem;
            color: var(--color-primary);
        }
        
        .info-card-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        
        /* Info item */
        .info-item {
            display: flex;
            align-items: flex-start;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--border-light);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, rgba(212, 165, 116, 0.1), rgba(212, 165, 116, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-md);
            flex-shrink: 0;
        }
        
        .info-item-icon i {
            color: var(--color-primary);
            font-size: 1.25rem;
        }
        
        .info-item-content {
            flex: 1;
        }
        
        .info-item-label {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .info-item-value {
            font-size: var(--font-size-lg);
            color: var(--text-primary);
            font-weight: 600;
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
                        <h1 class="page-title">Mi Perfil</h1>
                        <p class="page-subtitle">Información personal de tu cuenta</p>
                    </div>
                </div>
                
                <div class="topbar-right">
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($usuario['NombreCompleto']); ?></p>
                            <p class="user-role"><?php echo htmlspecialchars($usuario['Rol']); ?></p>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content">
                
                <div class="row">
                    <!-- Columna izquierda: Tarjeta de perfil -->
                    <div class="col-lg-4">
                        <div class="profile-card animate-in">
                            <div class="profile-avatar">
                                <?php echo htmlspecialchars($iniciales); ?>
                            </div>
                            
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars($usuario['NombreCompleto']); ?>
                            </h2>
                            
                            <p class="profile-username">
                                @<?php echo htmlspecialchars($usuario['Usuario']); ?>
                            </p>
                            
                            <span class="profile-badge">
                                <?php echo htmlspecialchars($usuario['Rol']); ?>
                            </span>
                            
                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-light);">
                                <div style="display: flex; justify-content: space-around; text-align: center;">
                                    <div>
                                        <i class="bi bi-calendar-check" style="font-size: 1.5rem; color: var(--color-primary); display: block; margin-bottom: 0.5rem;"></i>
                                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600;">Miembro desde</p>
                                        <p style="margin: 0; font-weight: 700; color: var(--text-primary);">
                                            <?php echo date('Y', strtotime($usuario['fecha_creacion'] ?? 'now')); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <i class="bi bi-shield-check" style="font-size: 1.5rem; color: var(--color-primary); display: block; margin-bottom: 0.5rem;"></i>
                                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600;">Estado</p>
                                        <p style="margin: 0; font-weight: 700; color: #10b981;">Activo</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna derecha: Información detallada -->
                    <div class="col-lg-8">
                        
                        <!-- Información Personal -->
                        <div class="info-card animate-in animate-delay-1">
                            <div class="info-card-header">
                                <i class="bi bi-person-fill"></i>
                                <h3 class="info-card-title">Información Personal</h3>
                            </div>
                            
                            <div class="info-items">
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Nombre Completo</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['NombreCompleto']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-at"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Usuario</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['Usuario']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-card-text"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">DPI</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['DPI'] ?: 'No registrado'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-telephone"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Teléfono</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['Telefono'] ?: 'No registrado'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de Ubicación -->
                        <div class="info-card animate-in animate-delay-2">
                            <div class="info-card-header">
                                <i class="bi bi-geo-alt-fill"></i>
                                <h3 class="info-card-title">Ubicación Asignada</h3>
                            </div>
                            
                            <div class="info-items">
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-map"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Departamento</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['Departamento'] ?: 'Nacional'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-pin-map"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Municipio</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['Municipio'] ?: 'Todos'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-item-icon">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div class="info-item-content">
                                        <div class="info-item-label">Rol en el Sistema</div>
                                        <div class="info-item-value">
                                            <?php echo htmlspecialchars($usuario['Rol']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Nota informativa -->
                        <div class="info-card animate-in" style="background: linear-gradient(135deg, rgba(212, 165, 116, 0.1), rgba(212, 165, 116, 0.05)); border: 2px solid var(--color-primary);">
                            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                <i class="bi bi-info-circle-fill" style="font-size: 2rem; color: var(--color-primary); flex-shrink: 0;"></i>
                                <div>
                                    <h5 style="color: var(--text-primary); margin: 0 0 0.5rem 0; font-weight: 700;">
                                        Información de Solo Lectura
                                    </h5>
                                    <p style="color: var(--text-secondary); margin: 0; line-height: 1.6;">
                                        Esta es tu información personal registrada en el sistema. Para realizar cambios en tus datos, 
                                        por favor contacta al administrador del sistema o dirígete a la sección de 
                                        <strong>Editar Perfil</strong> en el menú principal.
                                    </p>
                                </div>
                            </div>
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
    <script src="../assets/js/cerrar_sesion.js"></script>
    
    <script>
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