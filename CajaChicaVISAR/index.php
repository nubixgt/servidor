<?php
require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

// Obtener usuario actual
$usuarioActual = getUsuarioActual();

// ===== RESTRICCIÓN: Usuarios normales no pueden crear vales =====
// Solo ADMIN puede acceder a esta página (crear vales)
if (!esAdmin()) {
    header('Location: listar_vales.php');
    exit();
}

// Función para generar número de vale
function generarNumeroVale() {
    $db = getDB();
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'ultimo_numero'");
    $ultimo = $stmt->fetchColumn();
    $nuevo = intval($ultimo) + 1;
    
    $stmt = $db->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'ultimo_numero'");
    $stmt->execute([$nuevo]);
    
    $prefijo = $db->query("SELECT valor FROM configuracion WHERE clave = 'prefijo_vale'")->fetchColumn();
    return $prefijo . '-' . str_pad($nuevo, 6, '0', STR_PAD_LEFT);
}

// Procesar formulario de vale
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = getDB();
        
        $numero_vale = generarNumeroVale();
        $departamento = $_POST['departamento'];
        $otros_departamento = ($departamento === 'OTROS') ? $_POST['otros_departamento'] : null;
        
        // Concatenar nombre y apellido
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $nombre_solicitante = $nombre . ' ' . $apellido;
        
        $categoria = $_POST['categoria'];
        $descripcion = $_POST['descripcion'];
        $monto = floatval($_POST['monto']);
        $fecha_solicitud = $_POST['fecha_solicitud'];
        $usuario_creador = $_SESSION['nombre_completo'] ?? 'Sistema';
        
        $sql = "INSERT INTO vales (numero_vale, departamento, otros_departamento, nombre_solicitante, 
                categoria, descripcion, monto, fecha_solicitud, usuario_creador, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $numero_vale, $departamento, $otros_departamento, $nombre_solicitante,
            $categoria, $descripcion, $monto, $fecha_solicitud, $usuario_creador, 'PENDIENTE'
        ]);
        
        $vale_id = $db->lastInsertId();
        
        header("Location: ver_vales.php?id=" . $vale_id);
        exit();
        
    } catch(Exception $e) {
        $error = "Error al guardar el vale: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Vale de Caja Chica - MAGA</title>
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
            --color-secundario: #2c5282;
            --color-acento: #0abde3;
            --color-exito: #10b981;
            --color-peligro: #ef4444;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-principal: #f7fafc;
            --bg-blanco: #ffffff;
            --sombra-suave: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --sombra-media: 0 4px 6px rgba(0,0,0,0.1);
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
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--sombra-grande);
            text-align: center;
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
        
        .logo-container {
            margin-bottom: 25px;
        }
        
        .logo-container img {
            max-width: 450px;
            height: auto;
        }
        
        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 8px;
        }
        
        .header-subtitle {
            font-size: 16px;
            color: var(--color-texto-claro);
        }
        
        .system-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(10, 189, 227, 0.4);
        }
        
        /* FORMULARIO */
        .form-card {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--sombra-grande);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .section-number {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            margin-right: 15px;
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.3);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-primario);
        }
        
        .form-section {
            margin-bottom: 35px;
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
        
        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
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
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--color-acento);
            background: white;
            box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        
        .option-card {
            position: relative;
        }
        
        .option-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        .option-card label {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            background: #fafafa;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transicion);
            font-weight: 500;
            font-size: 14px;
        }
        
        .option-card input[type="radio"]:checked + label {
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.15) 0%, rgba(72, 209, 255, 0.08) 100%);
            border-color: var(--maga-cyan);
            color: var(--maga-azul-oscuro);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.2);
        }
        
        .option-card label:hover {
            border-color: var(--maga-cyan);
            background: white;
        }
        
        .option-icon {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e0;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            transition: var(--transicion);
        }
        
        .option-card input[type="radio"]:checked + label .option-icon {
            border-color: var(--maga-cyan);
            background: var(--maga-cyan);
        }
        
        .option-card input[type="radio"]:checked + label .option-icon::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
        }
        
        .categoria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        
        .categoria-card input[type="radio"]:checked + label {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1) 0%, rgba(56, 161, 105, 0.05) 100%);
            border-color: var(--color-exito);
        }
        
        .categoria-card input[type="radio"]:checked + label .option-icon {
            border-color: var(--color-exito);
            background: var(--color-exito);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        #otros_departamento_container {
            margin-top: 16px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-cyan) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            margin-top: 30px;
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(10, 189, 227, 0.5);
        }
        
        .btn-secondary {
            width: 100%;
            padding: 14px;
            background: white;
            color: var(--color-primario);
            border: 2px solid var(--color-primario);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
            margin-top: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background: var(--color-primario);
            color: white;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #c53030;
            border: 2px solid #fc8181;
        }
        
        .alert-icon {
            font-size: 20px;
            margin-right: 12px;
        }
        
        .required {
            color: var(--color-peligro);
        }
        
        .helper-text {
            font-size: 13px;
            color: var(--color-texto-claro);
            margin-top: 6px;
        }

        /* Preview del nombre completo */
        .nombre-preview {
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.1) 0%, rgba(72, 209, 255, 0.05) 100%);
            border: 2px solid var(--maga-cyan);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 15px;
            display: none;
        }

        .nombre-preview.visible {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .nombre-preview-label {
            font-size: 12px;
            color: var(--color-texto-claro);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .nombre-preview-value {
            font-size: 16px;
            color: var(--maga-azul-oscuro);
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .user-bar { flex-direction: column; text-align: center; }
            .header-card { padding: 25px 20px; }
            .logo-container img { max-width: 100%; }
            .header-title { font-size: 22px; }
            .form-card { padding: 25px 20px; }
            .form-row { grid-template-columns: 1fr; }
            .options-grid, .categoria-grid { grid-template-columns: 1fr; }
            
            /* Fix para campos de fecha en móvil */
            input[type="date"],
            input[type="number"] {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                font-size: 16px; /* Evita zoom en iOS */
            }
            
            .form-group {
                width: 100%;
                overflow: hidden;
            }
        }
        
        .btn-submit.loading {
            pointer-events: none;
            opacity: 0.7;
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
                <a href="usuarios.php" class="btn-action">Gestión de Usuarios</a>
                <a href="listar_vales.php" class="btn-action">Ver Vales</a>
                <a href="logout.php" class="btn-action">Cerrar Sesión</a>
            </div>
        </div>
        
        <!-- HEADER CON LOGO -->
        <div class="header-card">
            <div class="logo-container">
                <img src="MagaLogo.png" alt="Ministerio de Agricultura, Ganadería y Alimentación">
            </div>
            <h1 class="header-title">Sistema de Vales de Caja Chica</h1>
            <p class="header-subtitle">VISAR - Viceministerio de Sanidad Agropecuaria y Regulaciones</p>
            <div class="system-badge">Formulario de Solicitud</div>
        </div>
        
        <!-- FORMULARIO -->
        <div class="form-card">
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <strong><?php echo htmlspecialchars($error); ?></strong>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="valeForm">
                
                <!-- SECCIÓN 1: DEPENDENCIA -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-number">1</div>
                        <div class="section-title">Dependencia</div>
                    </div>
                    
                    <div class="options-grid">
                        <div class="option-card">
                            <input type="radio" name="departamento" value="VICEDESPACHO" id="vicedespacho" required>
                            <label for="vicedespacho">
                                <span class="option-icon"></span>
                                Vicedespacho - VISAR
                            </label>
                        </div>
                        <div class="option-card">
                            <input type="radio" name="departamento" value="DIRECCIÓN VEGETAL" id="vegetal">
                            <label for="vegetal">
                                <span class="option-icon"></span>
                                Dirección Vegetal
                            </label>
                        </div>
                        <div class="option-card">
                            <input type="radio" name="departamento" value="DIRECCIÓN ANIMAL" id="animal">
                            <label for="animal">
                                <span class="option-icon"></span>
                                Dirección Animal
                            </label>
                        </div>
                        <div class="option-card">
                            <input type="radio" name="departamento" value="INOCUIDAD ALIMENTARIA" id="inocuidad">
                            <label for="inocuidad">
                                <span class="option-icon"></span>
                                Inocuidad Alimentaria
                            </label>
                        </div>
                        <div class="option-card">
                            <input type="radio" name="departamento" value="UDAFA-VISAR" id="udafa">
                            <label for="udafa">
                                <span class="option-icon"></span>
                                UDAFA-VISAR
                            </label>
                        </div>
                        <div class="option-card">
                            <input type="radio" name="departamento" value="OTROS" id="otros">
                            <label for="otros">
                                <span class="option-icon"></span>
                                Otros
                            </label>
                        </div>
                    </div>
                    
                    <div id="otros_departamento_container" style="display: none;">
                        <input type="text" name="otros_departamento" id="otros_departamento" 
                               placeholder="Especifique la dependencia">
                    </div>
                </div>
                
                <!-- SECCIÓN 2: INFORMACIÓN DEL SOLICITANTE -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-number">2</div>
                        <div class="section-title">Información del Solicitante</div>
                    </div>
                    
                    <!-- Nombre y Apellido en dos campos separados -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">
                                Nombres <span class="required">*</span>
                            </label>
                            <input type="text" name="nombre" id="nombre" required 
                                   placeholder="Ej: Juan Carlos"
                                   oninput="actualizarPreviewNombre()">
                            <div class="helper-text">Ingrese el/los nombre(s)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido">
                                Apellidos <span class="required">*</span>
                            </label>
                            <input type="text" name="apellido" id="apellido" required 
                                   placeholder="Ej: Pérez García"
                                   oninput="actualizarPreviewNombre()">
                            <div class="helper-text">Ingrese el/los apellido(s)</div>
                        </div>
                    </div>

                    <!-- Preview del nombre completo -->
                    <div class="nombre-preview" id="nombrePreview">
                        <div class="nombre-preview-label">📝 Nombre completo del solicitante:</div>
                        <div class="nombre-preview-value" id="nombreCompletoPreview"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_solicitud">
                                Fecha de Solicitud <span class="required">*</span>
                            </label>
                            <input type="date" name="fecha_solicitud" id="fecha_solicitud" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="monto">
                                Monto (Q) <span class="required">*</span>
                            </label>
                            <input type="number" name="monto" id="monto" step="0.01" min="0" required 
                                   placeholder="0.00">
                            <div class="helper-text">Monto en Quetzales</div>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 3: CATEGORÍA -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-number">3</div>
                        <div class="section-title">Categoría del Gasto</div>
                    </div>
                    
                    <div class="categoria-grid">
                        <div class="option-card categoria-card">
                            <input type="radio" name="categoria" value="ALIMENTOS PERSONALES" id="cat_alim" required>
                            <label for="cat_alim">
                                <span class="option-icon"></span>
                                Alimentos Personales
                            </label>
                        </div>
                        <div class="option-card categoria-card">
                            <input type="radio" name="categoria" value="INSUMOS" id="cat_ins">
                            <label for="cat_ins">
                                <span class="option-icon"></span>
                                Insumos
                            </label>
                        </div>
                        <div class="option-card categoria-card">
                            <input type="radio" name="categoria" value="EQUIPO" id="cat_equi">
                            <label for="cat_equi">
                                <span class="option-icon"></span>
                                Equipo
                            </label>
                        </div>
                        <div class="option-card categoria-card">
                            <input type="radio" name="categoria" value="LIBRERIA" id="cat_lib">
                            <label for="cat_lib">
                                <span class="option-icon"></span>
                                Librería
                            </label>
                        </div>
                        <div class="option-card categoria-card">
                            <input type="radio" name="categoria" value="MATERIALES DE CONSTRUCCION" id="cat_const">
                            <label for="cat_const">
                                <span class="option-icon"></span>
                                Materiales de Construcción
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 4: DESCRIPCIÓN -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-number">4</div>
                        <div class="section-title">Descripción del Gasto</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">
                            Descripción Detallada <span class="required">*</span>
                        </label>
                        <textarea name="descripcion" id="descripcion" required 
                                  placeholder="Describa detalladamente los artículos o servicios solicitados"></textarea>
                        <div class="helper-text">Incluya cantidades, especificaciones y cualquier detalle relevante</div>
                    </div>
                </div>
                
                <!-- BOTONES -->
                <button type="submit" class="btn-submit">
                    Generar Vale y PDF
                </button>
                
                <a href="listar_vales.php" class="btn-secondary">
                    Ver Listado de Vales
                </a>
            </form>
        </div>
    </div>
    
    <script>
        // Mostrar/ocultar campo "Otros departamento"
        document.querySelectorAll('input[name="departamento"]').forEach(radio => {
            radio.addEventListener('change', function() {
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
        });

        // Actualizar preview del nombre completo
        function actualizarPreviewNombre() {
            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const preview = document.getElementById('nombrePreview');
            const previewValue = document.getElementById('nombreCompletoPreview');

            if (nombre || apellido) {
                const nombreCompleto = (nombre + ' ' + apellido).trim();
                previewValue.textContent = nombreCompleto;
                preview.classList.add('visible');
            } else {
                preview.classList.remove('visible');
            }
        }
        
        // Validación del formulario
        document.getElementById('valeForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const monto = parseFloat(document.getElementById('monto').value);

            // Validar que nombre tenga al menos 2 caracteres
            if (nombre.length < 2) {
                e.preventDefault();
                alert('El nombre debe tener al menos 2 caracteres');
                document.getElementById('nombre').focus();
                return false;
            }

            // Validar que apellido tenga al menos 2 caracteres
            if (apellido.length < 2) {
                e.preventDefault();
                alert('El apellido debe tener al menos 2 caracteres');
                document.getElementById('apellido').focus();
                return false;
            }

            if (monto <= 0) {
                e.preventDefault();
                alert('El monto debe ser mayor a 0');
                return false;
            }
            
            const btnSubmit = this.querySelector('.btn-submit');
            btnSubmit.classList.add('loading');
            btnSubmit.textContent = 'Generando vale...';
        });
    </script>
</body>
</html>







