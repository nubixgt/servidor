<?php
/**
 * Módulo de Gestión de Usuarios
 * MAGA - Sistema de Vales de Caja Chica
 * Solo accesible para administradores
 */

require_once 'config.php';
require_once 'auth.php';

// Verificar que sea administrador
requiereAdmin();

$usuarioActual = getUsuarioActual();
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    
    // Crear usuario
    if ($_POST['accion'] === 'crear') {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $rol = $_POST['rol'] ?? 'USER';
        
        // Validaciones
        if (empty($usuario) || empty($password) || empty($nombre_completo)) {
            $mensaje = 'Todos los campos son obligatorios';
            $tipo_mensaje = 'error';
        } elseif (strlen($password) < 6) {
            $mensaje = 'La contraseña debe tener al menos 6 caracteres';
            $tipo_mensaje = 'error';
        } elseif ($password !== $confirmar_password) {
            $mensaje = 'Las contraseñas no coinciden';
            $tipo_mensaje = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
            $mensaje = 'El usuario solo puede contener letras, números y guiones bajos';
            $tipo_mensaje = 'error';
        } else {
            $resultado = crearUsuario($usuario, $password, $nombre_completo, $rol);
            if ($resultado['success']) {
                $mensaje = 'Usuario creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = $resultado['message'];
                $tipo_mensaje = 'error';
            }
        }
    }
    
    // Cambiar estado de usuario
    if ($_POST['accion'] === 'toggle_estado') {
        $id = intval($_POST['user_id'] ?? 0);
        $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0);
        
        // No permitir desactivar al propio usuario
        if ($id === $_SESSION['user_id']) {
            $mensaje = 'No puedes desactivar tu propia cuenta';
            $tipo_mensaje = 'error';
        } else {
            $resultado = cambiarEstadoUsuario($id, $nuevo_estado);
            if ($resultado['success']) {
                $mensaje = $nuevo_estado ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al cambiar estado del usuario';
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Obtener lista de usuarios
$usuarios = listarUsuarios();

// Contar estadísticas
$total_usuarios = count($usuarios);
$admins = array_filter($usuarios, function($u) { return $u['rol'] === 'ADMIN'; });
$activos = array_filter($usuarios, function($u) { return $u['activo'] == 1; });
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema MAGA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --maga-azul-oscuro: #1e3a5f;
            --maga-azul-medio: #2a4a6f;
            --maga-cyan: #0abde3;
            --maga-cyan-claro: #48d1ff;
            --maga-cyan-oscuro: #0097c7;
            --color-primario: #1e3a5f;
            --color-acento: #0abde3;
            --color-exito: #10b981;
            --color-peligro: #ef4444;
            --color-warning: #f59e0b;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-principal: #f7fafc;
            --bg-blanco: #ffffff;
            --sombra-suave: 0 1px 3px rgba(0,0,0,0.12);
            --sombra-grande: 0 10px 40px rgba(0,0,0,0.15);
            --transicion: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 50%, var(--maga-cyan-oscuro) 100%);
            min-height: 100vh;
            padding: 20px;
            animation: gradientShift 15s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* BARRA DE USUARIO */
        .user-bar {
            background: var(--bg-blanco);
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: var(--sombra-grande);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.3);
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--color-texto);
            font-size: 15px;
        }
        
        .role-badge {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            color: var(--maga-azul-oscuro);
        }
        
        .btn-action:hover {
            background: var(--maga-azul-oscuro);
            color: white;
            border-color: var(--maga-azul-oscuro);
            transform: translateY(-1px);
        }
        
        /* HEADER */
        .header-card {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 30px 40px;
            margin-bottom: 25px;
            box-shadow: var(--sombra-grande);
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primario) 0%, var(--color-acento) 100%);
        }
        
        .header-card img {
            height: 60px;
            width: auto;
        }
        
        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 4px;
        }
        
        .header-title p {
            font-size: 14px;
            color: var(--color-texto-claro);
        }
        
        /* ESTADÍSTICAS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--bg-blanco);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--sombra-grande);
            text-align: center;
            transition: var(--transicion);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--color-primario);
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--color-texto-claro);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        /* GRID PRINCIPAL */
        .content-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: var(--bg-blanco);
            border-radius: 16px;
            box-shadow: var(--sombra-grande);
            overflow: hidden;
        }
        
        .card-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-header-icon {
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .card-header h2 {
            font-size: 18px;
            font-weight: 700;
        }
        
        .card-body {
            padding: 30px;
        }
        
        /* FORMULARIO */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: var(--transicion);
            font-family: inherit;
            background: #fafafa;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--maga-cyan);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.1);
        }
        
        .required {
            color: var(--color-peligro);
        }
        
        .helper-text {
            font-size: 12px;
            color: var(--color-texto-claro);
            margin-top: 6px;
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-cyan) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(10, 189, 227, 0.4);
        }
        
        /* ALERTAS */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #c53030;
            border: 2px solid #fc8181;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border: 2px solid #6ee7b7;
        }
        
        /* TABLA DE USUARIOS */
        .table-wrapper {
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        
        .users-table th {
            text-align: left;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: var(--color-texto);
        }
        
        .users-table tr:hover td {
            background: #f7fafc;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: rgba(139, 92, 246, 0.15);
            color: #7c3aed;
        }
        
        .badge-user {
            background: rgba(59, 130, 246, 0.15);
            color: #2563eb;
        }
        
        .badge-active {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }
        
        .badge-inactive {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }
        
        /* BOTONES DE ACCIÓN */
        .btn-toggle {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
        }
        
        .btn-activate {
            background: var(--color-exito);
            color: white;
        }
        
        .btn-activate:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-deactivate {
            background: var(--color-warning);
            color: white;
        }
        
        .btn-deactivate:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .btn-disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-cell-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 600;
        }
        
        .user-cell-info strong {
            display: block;
            color: var(--color-primario);
        }
        
        .user-cell-info small {
            color: var(--color-texto-claro);
            font-size: 12px;
        }
        
        .no-users {
            text-align: center;
            padding: 50px 30px;
            color: var(--color-texto-claro);
        }
        
        .no-users p {
            font-size: 16px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            body { padding: 15px; }
            
            .user-bar {
                flex-direction: column;
                text-align: center;
            }
            
            .header-card {
                flex-direction: column;
                text-align: center;
                padding: 25px 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <!-- BARRA DE USUARIO -->
        <div class="user-bar">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioActual['nombre_completo']); ?></span>
                    <span class="role-badge">🔑 Administrador</span>
                </div>
            </div>
            <div class="user-actions">
                <a href="index.php" class="btn-action">Crear Vale</a>
                <a href="listar_vales.php" class="btn-action">Ver Vales</a>
                <a href="logout.php" class="btn-action">Cerrar Sesión</a>
            </div>
        </div>
        
        <!-- HEADER -->
        <div class="header-card">
            <img src="MagaLogo.png" alt="MAGA">
            <div class="header-title">
                <h1>👥 Gestión de Usuarios</h1>
                <p>Sistema de Vales de Caja Chica - VISAR</p>
            </div>
        </div>
        
        <!-- ESTADÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔑</div>
                <div class="stat-value"><?php echo count($admins); ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo count($activos); ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
        </div>
        
        <!-- CONTENIDO PRINCIPAL -->
        <div class="content-grid">
            
            <!-- FORMULARIO CREAR USUARIO -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon">➕</div>
                    <h2>Crear Nuevo Usuario</h2>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                            <?php echo $tipo_mensaje === 'success' ? '✅' : '⚠️'; ?>
                            <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="crear">
                        
                        <div class="form-group">
                            <label for="nombre_completo">Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required
                                   placeholder="Nombre y apellidos">
                        </div>
                        
                        <div class="form-group">
                            <label for="usuario">Usuario <span class="required">*</span></label>
                            <input type="text" id="usuario" name="usuario" required
                                   placeholder="nombre_usuario" pattern="[a-zA-Z0-9_]+">
                            <div class="helper-text">Solo letras, números y guiones bajos</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="6"
                                   placeholder="Mínimo 6 caracteres">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_password">Confirmar Contraseña <span class="required">*</span></label>
                            <input type="password" id="confirmar_password" name="confirmar_password" required
                                   placeholder="Repita la contraseña">
                        </div>
                        
                        <div class="form-group">
                            <label for="rol">Rol del Usuario</label>
                            <select id="rol" name="rol">
                                <option value="USER">👤 Usuario Normal (solo ver)</option>
                                <option value="ADMIN">🔑 Administrador (acceso completo)</option>
                            </select>
                            <div class="helper-text">Los usuarios normales solo pueden ver vales, no crearlos ni editarlos</div>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- LISTA DE USUARIOS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon">📋</div>
                    <h2>Usuarios Registrados</h2>
                </div>
                
                <?php if (empty($usuarios)): ?>
                    <div class="no-users">
                        <p>No hay usuarios registrados</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-cell-avatar">
                                                    <?php echo strtoupper(substr($user['nombre_completo'], 0, 1)); ?>
                                                </div>
                                                <div class="user-cell-info">
                                                    <strong><?php echo htmlspecialchars($user['nombre_completo']); ?></strong>
                                                    <small>@<?php echo htmlspecialchars($user['usuario']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($user['rol']); ?>">
                                                <?php echo $user['rol'] === 'ADMIN' ? '🔑 Admin' : '👤 Usuario'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['activo'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['activo'] ? '✅ Activo' : '❌ Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <button class="btn-toggle btn-disabled" disabled title="No puedes modificar tu propia cuenta">
                                                    Tu cuenta
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="accion" value="toggle_estado">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="<?php echo $user['activo'] ? '0' : '1'; ?>">
                                                    <button type="submit" class="btn-toggle <?php echo $user['activo'] ? 'btn-deactivate' : 'btn-activate'; ?>"
                                                            onclick="return confirm('<?php echo $user['activo'] ? '¿Desactivar este usuario?' : '¿Activar este usuario?'; ?>')">
                                                        <?php echo $user['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Validar contraseñas
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            
            if (password !== confirmar) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>