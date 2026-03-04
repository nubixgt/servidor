<?php
require_once 'config.php';
require_once 'auth.php';
requiereAdmin(); // Solo administradores pueden acceder

$db = getDB();
$mensaje = '';
$tipoMensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion === 'crear') {
            $username = sanitizar($_POST['username']);
            $password = $_POST['password'];
            $nombre_completo = sanitizar($_POST['nombre_completo']);
            $email = sanitizar($_POST['email']);
            $tipo_usuario = $_POST['tipo_usuario'];
            
            // Validaciones
            if (strlen($password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            // Crear usuario
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (username, password, nombre_completo, email, tipo_usuario, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $passwordHash, $nombre_completo, $email, $tipo_usuario, $_SESSION['usuario_id']]);
            
            $mensaje = 'Usuario creado exitosamente';
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'editar') {
            $id = (int)$_POST['id'];
            $nombre_completo = sanitizar($_POST['nombre_completo']);
            $email = sanitizar($_POST['email']);
            $tipo_usuario = $_POST['tipo_usuario'];
            
            $stmt = $db->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, tipo_usuario = ? WHERE id = ?");
            $stmt->execute([$nombre_completo, $email, $tipo_usuario, $id]);
            
            $mensaje = 'Usuario actualizado exitosamente';
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'cambiar_password') {
            $id = (int)$_POST['id'];
            $password = $_POST['password'];
            
            if (strlen($password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $id]);
            
            $mensaje = 'Contraseña actualizada exitosamente';
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'toggle_activo') {
            $id = (int)$_POST['id'];
            
            // No permitir desactivar el propio usuario
            if ($id == $_SESSION['usuario_id']) {
                throw new Exception('No puedes desactivar tu propio usuario');
            }
            
            $stmt = $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = 'Estado del usuario actualizado';
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'eliminar') {
            $id = (int)$_POST['id'];
            
            // No permitir eliminar el propio usuario
            if ($id == $_SESSION['usuario_id']) {
                throw new Exception('No puedes eliminar tu propio usuario');
            }
            
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = 'Usuario eliminado exitosamente';
            $tipoMensaje = 'success';
        }
        
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener lista de usuarios
$stmt = $db->query("SELECT u.*, 
    (SELECT COUNT(*) FROM log_accesos WHERE usuario_id = u.id AND accion = 'login') as total_accesos,
    (SELECT COUNT(*) FROM sesiones_activas WHERE usuario_id = u.id) as sesiones_activas
    FROM usuarios u 
    ORDER BY u.fecha_creacion DESC");
$usuarios = $stmt->fetchAll();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$totalActivos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'admin' AND activo = 1");
$totalAdmins = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT usuario_id) as total FROM sesiones_activas");
$usuariosConectados = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Botón Menú Móvil -->
    <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay para cerrar menú -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php"; ?>
            
            <!-- Main Content -->
            <div class="col-lg-10 main-content p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="mb-1">
                        <i class="bi bi-person-gear me-2"></i>Gestión de Usuarios
                    </h2>
                    <p class="text-muted mb-0">
                        Administración de usuarios del sistema
                    </p>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill me-2"></i>
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <i class="bi bi-people text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo $totalActivos; ?></div>
                                        <small class="text-muted">Usuarios Activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                            <i class="bi bi-shield-check text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo $totalAdmins; ?></div>
                                        <small class="text-muted">Administradores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                            <i class="bi bi-circle-fill text-white" style="font-size: 1rem;"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo $usuariosConectados; ?></div>
                                        <small class="text-muted">En Línea</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón Crear Usuario -->
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                        <i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario
                    </button>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Último Acceso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <?php if ($user['sesiones_activas'] > 0): ?>
                                                    <span class="badge bg-success ms-2">
                                                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> En línea
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user['tipo_usuario'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                    <?php echo ucfirst($user['tipo_usuario']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($user['fecha_ultimo_acceso']) {
                                                    echo formatearFecha($user['fecha_ultimo_acceso'], 'd/m/Y H:i');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="cambiarPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Cambiar contraseña">
                                                        <i class="bi bi-key"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="accion" value="toggle_activo">
                                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-<?php echo $user['activo'] ? 'secondary' : 'success'; ?>" title="<?php echo $user['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                                <i class="bi bi-<?php echo $user['activo'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-outline-danger" onclick="eliminarUsuario(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Usuario *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" name="nombre_completo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuario *</label>
                            <select class="form-select" name="tipo_usuario" required>
                                <option value="usuario">Usuario Normal</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>Editar Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" name="nombre_completo" id="edit_nombre_completo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuario *</label>
                            <select class="form-select" name="tipo_usuario" id="edit_tipo_usuario" required>
                                <option value="usuario">Usuario Normal</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="modalCambiarPassword" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="accion" value="cambiar_password">
                    <input type="hidden" name="id" id="pass_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-key me-2"></i>Cambiar Contraseña
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Usuario: <strong id="pass_username"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-2"></i>Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(usuario) {
            document.getElementById('edit_id').value = usuario.id;
            document.getElementById('edit_nombre_completo').value = usuario.nombre_completo;
            document.getElementById('edit_email').value = usuario.email || '';
            document.getElementById('edit_tipo_usuario').value = usuario.tipo_usuario;
            
            new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
        }

        function cambiarPassword(id, username) {
            document.getElementById('pass_id').value = id;
            document.getElementById('pass_username').textContent = username;
            
            new bootstrap.Modal(document.getElementById('modalCambiarPassword')).show();
        }

        function eliminarUsuario(id, username) {
            if (confirm('¿Estás seguro de eliminar el usuario "' + username + '"?\n\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="accion" value="eliminar">' +
                                '<input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="responsive.js"></script>
</body>
</html>