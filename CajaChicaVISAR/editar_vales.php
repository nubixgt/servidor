<?php
require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

// ===== PROTECCIÓN: Solo administradores pueden editar =====
if (!esAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para editar vales. Solo los administradores pueden realizar esta acción.';
    header('Location: listar_vales.php');
    exit();
}

$usuarioActual = getUsuarioActual();

function registrarCambioBitacora($db, $vale_id, $numero_vale, $accion, $estado_anterior = null, $estado_nuevo = null, $campo = null, $valor_ant = null, $valor_nvo = null, $obs = null) {
    $usuario = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Sistema';
    $fecha_hora = date('Y-m-d H:i:s'); // Hora correcta de Guatemala (America/Guatemala)
    
    $stmt = $db->prepare("
        INSERT INTO bitacora_vales (vale_id, numero_vale, usuario, accion, estado_anterior, estado_nuevo, campo_modificado, valor_anterior, valor_nuevo, observacion, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$vale_id, $numero_vale, $usuario, $accion, $estado_anterior, $estado_nuevo, $campo, $valor_ant, $valor_nvo, $obs, $fecha_hora]);
}

$vale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$vale = null;
$errores = [];

// Si hay un ID, cargar los datos del vale
if ($vale_id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM vales WHERE id = ?");
        $stmt->execute([$vale_id]);
        $vale = $stmt->fetch();
        
        if (!$vale) {
            $_SESSION['error'] = "Vale no encontrado";
            header("Location: listar_vales.php");
            exit();
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Error al cargar el vale: " . $e->getMessage();
        header("Location: listar_vales.php");
        exit();
    }
} else {
    $_SESSION['error'] = "ID de vale no especificado";
    header("Location: listar_vales.php");
    exit();
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_vale = trim($_POST['numero_vale']);
    $nombre_solicitante = trim($_POST['nombre_solicitante']);
    $departamento = trim($_POST['departamento']);
    $otros_departamento = trim($_POST['otros_departamento'] ?? '');
    $categoria = trim($_POST['categoria']);
    $descripcion = trim($_POST['descripcion']);
    $monto = floatval($_POST['monto']);
    $estado = trim($_POST['estado']);
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validaciones
    if (empty($numero_vale)) $errores[] = "El número de vale es requerido";
    if (empty($nombre_solicitante)) $errores[] = "El nombre del solicitante es requerido";
    if (empty($departamento)) $errores[] = "El departamento es requerido";
    if (empty($categoria)) $errores[] = "La categoría es requerida";
    if (empty($descripcion)) $errores[] = "La descripción es requerida";
    if ($monto <= 0) $errores[] = "El monto debe ser mayor a cero";
    if (!in_array($estado, ['PENDIENTE', 'LIQUIDADO'])) $errores[] = "Estado inválido";
    
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // ==========================================
            // REGISTRAR CAMBIOS EN BITÁCORA
            // ==========================================
            
            // Detectar cambio de estado
            if ($vale['estado'] !== $estado) {
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'CAMBIO_ESTADO', 
                    $vale['estado'], 
                    $estado, 
                    null, 
                    null, 
                    null, 
                    'Estado actualizado desde el formulario de edición'
                );
            }
            
            // Detectar cambio de monto
            if ($vale['monto'] != $monto) {
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'EDITADO', 
                    null, 
                    null, 
                    'Monto', 
                    'Q. ' . number_format($vale['monto'], 2), 
                    'Q. ' . number_format($monto, 2), 
                    null
                );
            }
            
            // Detectar cambio de descripción
            if ($vale['descripcion'] !== $descripcion) {
                $desc_anterior = strlen($vale['descripcion']) > 50 ? substr($vale['descripcion'], 0, 50) . '...' : $vale['descripcion'];
                $desc_nueva = strlen($descripcion) > 50 ? substr($descripcion, 0, 50) . '...' : $descripcion;
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'EDITADO', 
                    null, 
                    null, 
                    'Descripción', 
                    $desc_anterior, 
                    $desc_nueva, 
                    null
                );
            }
            
            // Detectar cambio de departamento
            if ($vale['departamento'] !== $departamento) {
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'EDITADO', 
                    null, 
                    null, 
                    'Departamento', 
                    $vale['departamento'], 
                    $departamento, 
                    null
                );
            }
            
            // Detectar cambio de categoría
            if ($vale['categoria'] !== $categoria) {
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'EDITADO', 
                    null, 
                    null, 
                    'Categoría', 
                    $vale['categoria'], 
                    $categoria, 
                    null
                );
            }
            
            // Detectar cambio de nombre solicitante
            if ($vale['nombre_solicitante'] !== $nombre_solicitante) {
                registrarCambioBitacora(
                    $db, 
                    $vale_id, 
                    $numero_vale, 
                    'EDITADO', 
                    null, 
                    null, 
                    'Solicitante', 
                    $vale['nombre_solicitante'], 
                    $nombre_solicitante, 
                    null
                );
            }
            
            // ==========================================
            // ACTUALIZAR VALE
            // ==========================================
            
            $stmt = $db->prepare("
                UPDATE vales SET
                    numero_vale = ?,
                    nombre_solicitante = ?,
                    departamento = ?,
                    otros_departamento = ?,
                    categoria = ?,
                    descripcion = ?,
                    monto = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $numero_vale,
                $nombre_solicitante,
                $departamento,
                $otros_departamento,
                $categoria,
                $descripcion,
                $monto,
                $estado,
                $observaciones,
                $vale_id
            ]);
            
            $_SESSION['success'] = "Vale actualizado exitosamente";
            header("Location: listar_vales.php");
            exit();
            
        } catch(Exception $e) {
            $errores[] = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Si no se ha enviado el formulario, usar los valores del vale
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $estado_actual = isset($vale['estado']) ? $vale['estado'] : 'PENDIENTE';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vale - MAGA</title>
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
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-blanco: #ffffff;
            --sombra-grande: 0 10px 40px rgba(0,0,0,0.15);
            --transicion: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            max-width: 900px;
            margin: 0 auto;
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
        }

        .form-card {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--sombra-grande);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            border-bottom: 3px solid var(--color-acento);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 16px;
            color: var(--color-texto-claro);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.08) 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .alert-icon {
            font-size: 20px;
            margin-right: 12px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .required {
            color: var(--color-peligro);
            font-weight: 700;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: var(--transicion);
            font-family: inherit;
            background: #fafafa;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--color-acento);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        #otros_departamento_container {
            margin-top: 16px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SECCIÓN DE ESTADO */
        .estado-section {
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.1) 0%, rgba(72, 209, 255, 0.05) 100%);
            border: 2px solid var(--color-acento);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .estado-section h3 {
            font-size: 18px;
            color: var(--color-primario);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .estado-section h3::before {
            content: '🔄';
            margin-right: 10px;
            font-size: 22px;
        }

        .estado-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .estado-option {
            position: relative;
        }

        .estado-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .estado-option label {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transicion);
            font-weight: 600;
        }

        .estado-option input[type="radio"]:checked + label {
            border-color: var(--color-acento);
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.15) 0%, rgba(72, 209, 255, 0.08) 100%);
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.3);
            transform: translateY(-2px);
        }

        .estado-option label:hover {
            border-color: var(--color-acento);
            transform: translateY(-2px);
        }

        .estado-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .estado-pendiente-icon {
            background: #ef4444;
            color: white;
        }

        .estado-liquidado-icon {
            background: #10b981;
            color: white;
        }

        .buttons-container {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-cyan) 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.4);
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .user-bar { flex-direction: column; text-align: center; }
            .form-card { padding: 25px 20px; }
            .form-row, .estado-options { grid-template-columns: 1fr; }
            .buttons-container { flex-direction: column; }
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
                <a href="listar_vales.php" class="btn-action">← Volver al Listado</a>
                <a href="logout.php" class="btn-action">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="form-card">
            <div class="form-header">
                <h1>✏️ Editar Vale de Caja Chica</h1>
                <p>Vale N°: <strong><?php echo htmlspecialchars($vale['numero_vale']); ?></strong></p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div>
                        <strong>Se encontraron los siguientes errores:</strong>
                        <ul style="margin-top: 8px; margin-left: 20px;">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- ESTADO -->
                <div class="estado-section">
                    <h3>Estado del Vale</h3>
                    <div class="estado-options">
                        <div class="estado-option">
                            <input type="radio" name="estado" value="PENDIENTE" id="estado_pendiente" 
                                   <?php echo (isset($estado_actual) && $estado_actual === 'PENDIENTE') || (!isset($estado_actual)) ? 'checked' : ''; ?>>
                            <label for="estado_pendiente">
                                <span class="estado-icon estado-pendiente-icon">⏳</span>
                                PENDIENTE
                            </label>
                        </div>
                        <div class="estado-option">
                            <input type="radio" name="estado" value="LIQUIDADO" id="estado_liquidado"
                                   <?php echo (isset($estado_actual) && $estado_actual === 'LIQUIDADO') ? 'checked' : ''; ?>>
                            <label for="estado_liquidado">
                                <span class="estado-icon estado-liquidado-icon">✓</span>
                                LIQUIDADO
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Número de Vale -->
                <div class="form-group">
                    <label>Número de Vale <span class="required">*</span></label>
                    <input type="text" name="numero_vale" value="<?php echo htmlspecialchars($vale['numero_vale']); ?>" required>
                </div>

                <!-- Nombre Solicitante -->
                <div class="form-group">
                    <label>Nombre del Solicitante <span class="required">*</span></label>
                    <input type="text" name="nombre_solicitante" value="<?php echo htmlspecialchars($vale['nombre_solicitante']); ?>" required>
                </div>

                <!-- Departamento y Categoría -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Departamento <span class="required">*</span></label>
                        <select name="departamento" id="departamento" required>
                            <option value="">Seleccione...</option>
                            <option value="VICEDESPACHO" <?php echo $vale['departamento'] === 'VICEDESPACHO' ? 'selected' : ''; ?>>Vicedespacho</option>
                            <option value="DIRECCIÓN VEGETAL" <?php echo $vale['departamento'] === 'DIRECCIÓN VEGETAL' ? 'selected' : ''; ?>>Dirección Vegetal</option>
                            <option value="DIRECCIÓN ANIMAL" <?php echo $vale['departamento'] === 'DIRECCIÓN ANIMAL' ? 'selected' : ''; ?>>Dirección Animal</option>
                            <option value="INOCUIDAD ALIMENTARIA" <?php echo $vale['departamento'] === 'INOCUIDAD ALIMENTARIA' ? 'selected' : ''; ?>>Inocuidad Alimentaria</option>
                            <option value="UDAFA-VISAR" <?php echo $vale['departamento'] === 'UDAFA-VISAR' ? 'selected' : ''; ?>>UDAFA-VISAR</option>
                            <option value="OTROS" <?php echo $vale['departamento'] === 'OTROS' ? 'selected' : ''; ?>>Otros</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Categoría <span class="required">*</span></label>
                        <select name="categoria" required>
                            <option value="">Seleccione...</option>
                            <option value="ALIMENTOS PERSONALES" <?php echo $vale['categoria'] === 'ALIMENTOS PERSONALES' ? 'selected' : ''; ?>>Alimentos Personales</option>
                            <option value="INSUMOS" <?php echo $vale['categoria'] === 'INSUMOS' ? 'selected' : ''; ?>>Insumos</option>
                            <option value="EQUIPO" <?php echo $vale['categoria'] === 'EQUIPO' ? 'selected' : ''; ?>>Equipo</option>
                            <option value="LIBRERIA" <?php echo $vale['categoria'] === 'LIBRERIA' ? 'selected' : ''; ?>>Librería</option>
                            <option value="MATERIALES DE CONSTRUCCION" <?php echo $vale['categoria'] === 'MATERIALES DE CONSTRUCCION' ? 'selected' : ''; ?>>Materiales de Construcción</option>
                        </select>
                    </div>
                </div>

                <!-- Otros Departamento -->
                <div id="otros_departamento_container" style="display: <?php echo $vale['departamento'] === 'OTROS' ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label>Especifique el Departamento</label>
                        <input type="text" name="otros_departamento" id="otros_departamento" 
                               value="<?php echo htmlspecialchars($vale['otros_departamento'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label>Descripción del Gasto <span class="required">*</span></label>
                    <textarea name="descripcion" required><?php echo htmlspecialchars($vale['descripcion']); ?></textarea>
                </div>

                <!-- Monto -->
                <div class="form-group">
                    <label>Monto (Q) <span class="required">*</span></label>
                    <input type="number" name="monto" step="0.01" min="0.01" 
                           value="<?php echo number_format($vale['monto'], 2, '.', ''); ?>" required>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                    <label>Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($vale['observaciones'] ?? ''); ?></textarea>
                </div>

                <!-- Botones -->
                <div class="buttons-container">
                    <a href="listar_vales.php" class="btn btn-cancel">← Cancelar</a>
                    <button type="submit" class="btn btn-submit">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('departamento').addEventListener('change', function() {
            const otrosContainer = document.getElementById('otros_departamento_container');
            const otrosInput = document.getElementById('otros_departamento');
            
            if (this.value === 'OTROS') {
                otrosContainer.style.display = 'block';
                otrosInput.required = true;
            } else {
                otrosContainer.style.display = 'none';
                otrosInput.required = false;
                otrosInput.value = '';
            }
        });
    </script>
</body>
</html>