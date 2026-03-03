<?php
/**
 * Gestión de Usuarios - VIDER
 * MAGA Guatemala
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php?error=No tiene permisos');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$currentPage = 'usuarios';

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($usuarios);
$admins = count(array_filter($usuarios, function($u) { return $u['rol'] === 'admin'; }));
$tecnicos = count(array_filter($usuarios, function($u) { return $u['rol'] === 'tecnico'; }));
$activos = count(array_filter($usuarios, function($u) { return $u['activo'] == 1; }));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Usuarios | VIDER</title>
    <style>
        .container{padding:1.5rem;max-width:1400px;margin:0 auto}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
        .header h1{font-size:1.5rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:0.5rem}
        .header h1 i{color:#4a90d9}
        .btn-new{padding:0.7rem 1.2rem;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.5rem}
        .btn-new:hover{box-shadow:0 5px 20px rgba(34,197,94,0.4)}
        .alert{padding:1rem;border-radius:10px;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem}
        .alert-success{background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3)}
        .alert-error{background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3)}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
        .stat{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:0.75rem}
        .stat-icon{width:45px;height:45px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff}
        .stat-icon.blue{background:linear-gradient(135deg,#3b82f6,#60a5fa)}
        .stat-icon.red{background:linear-gradient(135deg,#ef4444,#f87171)}
        .stat-icon.green{background:linear-gradient(135deg,#22c55e,#4ade80)}
        .stat-icon.purple{background:linear-gradient(135deg,#8b5cf6,#a78bfa)}
        .stat h3{font-size:1.3rem;font-weight:700;color:var(--text-primary)}
        .stat p{font-size:0.8rem;color:var(--text-secondary)}
        .card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:16px;overflow:hidden}
        .card-head{padding:1rem 1.25rem;border-bottom:1px solid var(--glass-border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
        .card-head h2{font-size:1rem;font-weight:600;color:var(--text-primary)}
        .search{position:relative}
        .search input{padding:0.5rem 1rem 0.5rem 2.25rem;background:rgba(0,0,0,0.2);border:1px solid var(--glass-border);border-radius:8px;color:var(--text-primary);width:200px}
        .search input:focus{outline:none;border-color:#4a90d9}
        .search i{position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--text-secondary)}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        thead{background:rgba(74,144,217,0.1)}
        th{padding:0.85rem 1rem;text-align:left;font-weight:600;font-size:0.8rem;color:var(--text-primary);text-transform:uppercase}
        tbody tr{border-bottom:1px solid var(--glass-border)}
        tbody tr:hover{background:rgba(74,144,217,0.05)}
        td{padding:0.85rem 1rem;font-size:0.9rem;color:var(--text-primary)}
        .user-cell{display:flex;align-items:center;gap:0.75rem}
        .avatar{width:38px;height:38px;border-radius:8px;background:linear-gradient(135deg,#4a90d9,#60a5fa);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem}
        .user-name{font-weight:600}
        .user-sub{font-size:0.8rem;color:var(--text-secondary)}
        .badge{display:inline-block;padding:0.25rem 0.6rem;border-radius:6px;font-size:0.7rem;font-weight:600;text-transform:uppercase}
        .badge-admin{background:rgba(239,68,68,0.2);color:#f87171}
        .badge-tecnico{background:rgba(34,197,94,0.2);color:#4ade80}
        .badge-activo{background:rgba(34,197,94,0.2);color:#4ade80}
        .badge-inactivo{background:rgba(239,68,68,0.2);color:#f87171}
        .actions{display:flex;gap:0.4rem}
        .btn-act{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center}
        .btn-edit{background:rgba(59,130,246,0.2);color:#60a5fa}
        .btn-edit:hover{background:rgba(59,130,246,0.35)}
        .btn-toggle{background:rgba(251,191,36,0.2);color:#fbbf24}
        .btn-toggle:hover{background:rgba(251,191,36,0.35)}
        .btn-delete{background:rgba(239,68,68,0.2);color:#f87171}
        .btn-delete:hover{background:rgba(239,68,68,0.35)}
        .text-muted{color:var(--text-secondary)}

        /* MODAL CORREGIDO */
        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal-bg.show {
            display: flex;
        }

        .modal-box {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        [data-theme="light"] .modal-box {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .modal-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(74, 144, 217, 0.1);
        }

        [data-theme="light"] .modal-head {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-head h3 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        [data-theme="light"] .modal-head h3 {
            color: #1e293b;
        }

        .modal-head h3 i {
            color: #4a90d9;
        }

        .btn-close {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .btn-close:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        [data-theme="light"] .form-group label {
            color: #64748b;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group select {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a90d9;
            box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.2);
        }

        .form-group input::placeholder {
            color: #64748b;
        }

        .form-group select option {
            background: #1e293b;
            color: #f1f5f9;
        }

        [data-theme="light"] .form-group select option {
            background: #ffffff;
            color: #1e293b;
        }

        .form-group small {
            display: block;
            margin-top: 0.4rem;
            font-size: 0.75rem;
            color: #64748b;
        }

        .modal-foot {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: rgba(0, 0, 0, 0.1);
        }

        [data-theme="light"] .modal-foot {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            background: #f8fafc;
        }

        .btn-cancel {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #f1f5f9;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        [data-theme="light"] .btn-cancel {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-save {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-save:hover {
            box-shadow: 0 5px 20px rgba(34, 197, 94, 0.4);
            transform: translateY(-1px);
        }

        @media(max-width:768px){
            .container{padding:1rem}
            .header{flex-direction:column;align-items:stretch}
            th:nth-child(3),td:nth-child(3),th:nth-child(5),td:nth-child(5){display:none}
            .modal-box{max-width:95%;margin:0.5rem}
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="header">
                <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
                <button class="btn-new" onclick="abrirModal()"><i class="fas fa-plus"></i> Nuevo Usuario</button>
            </div>

            <div class="stats">
                <div class="stat"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div><h3><?= $total ?></h3><p>Total</p></div></div>
                <div class="stat"><div class="stat-icon red"><i class="fas fa-user-shield"></i></div><div><h3><?= $admins ?></h3><p>Admins</p></div></div>
                <div class="stat"><div class="stat-icon green"><i class="fas fa-user-cog"></i></div><div><h3><?= $tecnicos ?></h3><p>Técnicos</p></div></div>
                <div class="stat"><div class="stat-icon purple"><i class="fas fa-user-check"></i></div><div><h3><?= $activos ?></h3><p>Activos</p></div></div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2><i class="fas fa-list"></i> Usuarios</h2>
                    <div class="search"><i class="fas fa-search"></i><input type="text" id="buscar" placeholder="Buscar..." onkeyup="filtrar()"></div>
                </div>
                <div class="table-wrap">
                    <table id="tabla">
                        <thead><tr><th>Usuario</th><th>Rol</th><th>Email</th><th>Estado</th><th>Último Acceso</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td><div class="user-cell"><div class="avatar"><?= strtoupper(substr($u['nombre_completo']?:$u['username'],0,2)) ?></div><div><div class="user-name"><?= htmlspecialchars($u['nombre_completo']?:$u['username']) ?></div><div class="user-sub">@<?= htmlspecialchars($u['username']) ?></div></div></div></td>
                            <td><span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol']==='admin'?'Admin':'Técnico' ?></span></td>
                            <td><?= htmlspecialchars($u['email']?:'-') ?></td>
                            <td><span class="badge <?= $u['activo']?'badge-activo':'badge-inactivo' ?>"><?= $u['activo']?'Activo':'Inactivo' ?></span></td>
                            <td><?= $u['ultimo_acceso']?date('d/m/Y H:i',strtotime($u['ultimo_acceso'])):'<span class="text-muted">Nunca</span>' ?></td>
                            <td><div class="actions">
                                <button class="btn-act btn-edit" onclick='editar(<?= json_encode($u) ?>)' title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-act btn-toggle" onclick="toggle(<?= $u['id'] ?>,<?= $u['activo']?'false':'true' ?>)" title="<?= $u['activo']?'Desactivar':'Activar' ?>"><i class="fas fa-<?= $u['activo']?'ban':'check' ?>"></i></button>
                                <?php if($u['id']!=$_SESSION['user_id']): ?><button class="btn-act btn-delete" onclick="eliminar(<?= $u['id'] ?>)" title="Eliminar"><i class="fas fa-trash"></i></button><?php endif; ?>
                            </div></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL -->
    <div class="modal-bg" id="modal">
        <div class="modal-box">
            <div class="modal-head">
                <h3><i class="fas fa-user-plus"></i> <span id="modalTitulo">Nuevo Usuario</span></h3>
                <button class="btn-close" onclick="cerrar()" type="button"><i class="fas fa-times"></i></button>
            </div>
            <form id="form" action="api/users_manage.php" method="POST">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="user_id" id="user_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Usuario *</label>
                        <input type="text" name="username" id="username" required placeholder="Nombre de usuario único">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" name="nombre_completo" id="nombre_completo" required placeholder="Nombre y apellidos">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="rol" id="rol" required>
                            <option value="tecnico">Técnico (puede cargar información)</option>
                            <option value="admin">Administrador (gestiona usuarios)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contraseña <span id="reqPass">*</span></label>
                        <input type="password" name="password" id="password" placeholder="Mínimo 6 caracteres">
                        <small id="helpPass" style="display:none">Dejar en blanco para mantener la contraseña actual</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Repita la contraseña">
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="cerrar()">Cancelar</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function abrirModal() {
        document.getElementById('modalTitulo').textContent = 'Nuevo Usuario';
        document.getElementById('action').value = 'create';
        document.getElementById('user_id').value = '';
        document.getElementById('form').reset();
        document.getElementById('password').required = true;
        document.getElementById('reqPass').style.display = 'inline';
        document.getElementById('helpPass').style.display = 'none';
        document.getElementById('modal').classList.add('show');
    }

    function cerrar() {
        document.getElementById('modal').classList.remove('show');
    }

    function editar(u) {
        document.getElementById('modalTitulo').textContent = 'Editar Usuario';
        document.getElementById('action').value = 'update';
        document.getElementById('user_id').value = u.id;
        document.getElementById('username').value = u.username;
        document.getElementById('nombre_completo').value = u.nombre_completo || '';
        document.getElementById('email').value = u.email || '';
        document.getElementById('rol').value = u.rol;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('reqPass').style.display = 'none';
        document.getElementById('helpPass').style.display = 'block';
        document.getElementById('confirm_password').value = '';
        document.getElementById('modal').classList.add('show');
    }

    function toggle(id, act) {
        if (!confirm(act ? '¿Activar este usuario?' : '¿Desactivar este usuario?')) return;
        
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = 'api/users_manage.php';
        f.innerHTML = '<input type="hidden" name="action" value="toggle">' +
                      '<input type="hidden" name="user_id" value="' + id + '">' +
                      '<input type="hidden" name="activate" value="' + act + '">';
        document.body.appendChild(f);
        f.submit();
    }

    function eliminar(id) {
        if (!confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')) return;
        
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = 'api/users_manage.php';
        f.innerHTML = '<input type="hidden" name="action" value="delete">' +
                      '<input type="hidden" name="user_id" value="' + id + '">';
        document.body.appendChild(f);
        f.submit();
    }

    function filtrar() {
        var texto = document.getElementById('buscar').value.toLowerCase();
        var filas = document.querySelectorAll('#tabla tbody tr');
        filas.forEach(function(fila) {
            var contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(texto) ? '' : 'none';
        });
    }

    // Validación del formulario
    document.getElementById('form').addEventListener('submit', function(e) {
        var pass = document.getElementById('password').value;
        var confirm = document.getElementById('confirm_password').value;
        var accion = document.getElementById('action').value;

        if (accion === 'create' && pass.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres');
            return;
        }

        if (pass && pass !== confirm) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return;
        }
    });

    // Cerrar modal con Escape o click fuera
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrar();
    });

    document.getElementById('modal').addEventListener('click', function(e) {
        if (e.target.id === 'modal') cerrar();
    });
    </script>
</body>
</html>