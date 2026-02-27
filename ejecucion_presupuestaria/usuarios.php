<?php
/**
 * Gestión de Usuarios
 * Sistema de Ejecución Presupuestaria - MAGA
 * Solo accesible para administradores
 */

$pageTitle = 'Gestión de Usuarios';
$currentPage = 'usuarios';

require_once 'config/database.php';

// Incluir header (esto verificará la sesión)
require_once 'includes/header.php';

// Verificar que sea administrador
if ($_SESSION['usuario_rol'] !== 'admin') {
    echo '<div class="alert alert-danger"><i class="fas fa-lock"></i> No tiene permisos para acceder a esta sección.</div>';
    require_once 'includes/footer.php';
    exit;
}

$db = getDB();
$mensaje = '';
$tipoMensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion === 'crear') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'viewer';
            
            if (empty($nombre) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            // Verificar email único
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe un usuario con ese correo electrónico');
            }
            
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $email, $hash, $rol]);
            
            $mensaje = "Usuario '$nombre' creado exitosamente";
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'editar') {
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'viewer';
            $activo = isset($_POST['activo']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            
            // No permitir desactivar o cambiar rol del propio usuario
            if ($id === $_SESSION['usuario_id'] && (!$activo || $rol !== 'admin')) {
                throw new Exception('No puede desactivar o cambiar su propio rol de administrador');
            }
            
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $hash, $rol, $activo, $id]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $activo, $id]);
            }
            
            $mensaje = "Usuario actualizado exitosamente";
            $tipoMensaje = 'success';
            
        } elseif ($accion === 'eliminar') {
            $id = intval($_POST['id']);
            
            // No permitir eliminar el propio usuario
            if ($id === $_SESSION['usuario_id']) {
                throw new Exception('No puede eliminar su propia cuenta');
            }
            
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = "Usuario eliminado";
            $tipoMensaje = 'success';
        }
        
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// Obtener usuario para editar
$usuarioEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([intval($_GET['editar'])]);
    $usuarioEditar = $stmt->fetch();
}

// Obtener lista de usuarios
$usuarios = $db->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();
?>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?>">
        <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<!-- Formulario de creación/edición -->
<div class="form-card">
    <div class="form-header">
        <h3>
            <i class="fas fa-<?= $usuarioEditar ? 'edit' : 'user-plus' ?>"></i>
            <?= $usuarioEditar ? 'Editar Usuario' : 'Crear Nuevo Usuario' ?>
        </h3>
    </div>
    <form method="POST" class="form-body">
        <input type="hidden" name="accion" value="<?= $usuarioEditar ? 'editar' : 'crear' ?>">
        <?php if ($usuarioEditar): ?>
            <input type="hidden" name="id" value="<?= $usuarioEditar['id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre Completo <span class="required">*</span></label>
                <input type="text" name="nombre" class="form-control" 
                       value="<?= htmlspecialchars($usuarioEditar['nombre'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Correo Electrónico <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($usuarioEditar['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña <?= $usuarioEditar ? '(dejar en blanco para mantener)' : '<span class="required">*</span>' ?></label>
                <input type="password" name="password" class="form-control" 
                       <?= $usuarioEditar ? '' : 'required' ?>>
            </div>
            
            <div class="form-group">
                <label>Rol <span class="required">*</span></label>
                <select name="rol" class="form-control" required>
                    <option value="admin" <?= ($usuarioEditar['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>
                        Administrador - Acceso completo
                    </option>
                    <option value="editor" <?= ($usuarioEditar['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>
                        Editor - Puede editar datos
                    </option>
                    <option value="viewer" <?= ($usuarioEditar['rol'] ?? 'viewer') === 'viewer' ? 'selected' : '' ?>>
                        Visor - Solo lectura
                    </option>
                </select>
            </div>
            
            <?php if ($usuarioEditar): ?>
                <div class="form-group">
                    <label>Estado</label>
                    <div style="padding: 0.75rem 0;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="activo" value="1" 
                                   <?= $usuarioEditar['activo'] ? 'checked' : '' ?>
                                   style="width: 20px; height: 20px;">
                            <span>Usuario activo</span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-footer">
            <?php if ($usuarioEditar): ?>
                <a href="usuarios.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $usuarioEditar ? 'Guardar Cambios' : 'Crear Usuario' ?>
            </button>
        </div>
    </form>
</div>

<!-- Información de roles -->
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>Roles del sistema:</strong><br>
        <small>
            • <strong>Administrador:</strong> Acceso completo al sistema, puede importar datos, editar registros y gestionar usuarios.<br>
            • <strong>Editor:</strong> Puede editar datos de ejecución pero no importar ni gestionar usuarios.<br>
            • <strong>Visor:</strong> Solo puede ver el dashboard y reportes, sin acceso a importar, administración ni usuarios.
        </small>
    </div>
</div>

<!-- Lista de usuarios -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-users"></i> Usuarios del Sistema</h3>
        <div class="table-actions">
            <span class="text-light"><?= count($usuarios) ?> usuarios</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último Acceso</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($u['nombre']) ?></strong>
                            <?php if ($u['id'] === $_SESSION['usuario_id']): ?>
                                <span class="badge-tipo" style="margin-left: 5px;">Tú</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php
                            $rolClases = [
                                'admin' => 'success',
                                'editor' => 'warning', 
                                'viewer' => 'info'
                            ];
                            $rolNombres = [
                                'admin' => 'Administrador',
                                'editor' => 'Editor',
                                'viewer' => 'Visor'
                            ];
                            ?>
                            <span class="percent-badge <?= $rolClases[$u['rol']] ?? 'info' ?>">
                                <?= $rolNombres[$u['rol']] ?? $u['rol'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['activo']): ?>
                                <span style="color: var(--success-color);"><i class="fas fa-check-circle"></i> Activo</span>
                            <?php else: ?>
                                <span style="color: var(--danger-color);"><i class="fas fa-times-circle"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['ultimo_acceso']): ?>
                                <?= date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="?editar=<?= $u['id'] ?>" class="btn btn-primary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('¿Eliminar este usuario?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}
.btn-danger {
    background: linear-gradient(135deg, #e53e3e, #fc8181);
    color: white;
}
.btn-danger:hover {
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.4);
}
.percent-badge.info {
    background: linear-gradient(135deg, rgba(49, 130, 206, 0.15), rgba(99, 179, 237, 0.15));
    color: var(--secondary-color);
}
</style>

<?php require_once 'includes/footer.php'; ?>
