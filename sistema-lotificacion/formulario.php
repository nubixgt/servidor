<?php
// formulario.php

use PhpOffice\PhpSpreadsheet\Calculation\TextData\Trim;

require_once 'config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

$mensaje = '';
$tipoMensaje = ''; // success o error

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $telefono_americano = trim($_POST['telefono_americano'] ?? '');
    $como_se_entero = trim($_POST['como_se_entero'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($como_se_entero)) {
        $mensaje = 'Por favor, completa todos los campos obligatorios';
        $tipoMensaje = 'error';
    } else {
        // Validar formato de teléfono guatemalteco si se proporcionó
        if (!empty($telefono) && !preg_match('/^\+502 \d{4}-\d{4}$/', $telefono)) {
            $mensaje = 'El teléfono guatemalteco debe tener el formato +502 0000-0000';
            $tipoMensaje = 'error';
        }
        // Validar correo si se proporcionó
        else if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'El correo electrónico no tiene un formato válido';
            $tipoMensaje = 'error';
        } else {
            try {
                $db = new Database();
                $conn = $db->getConnection();

                $query = "INSERT INTO registros (nombre, apellido, telefono, telefono_americano, como_se_entero, correo, comentario, usuario_id) 
                          VALUES (:nombre, :apellido, :telefono, :telefono_americano, :como_se_entero, :correo, :comentario, :usuario_id)";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':telefono_americano', $telefono_americano);
                $stmt->bindParam(':como_se_entero', $como_se_entero);
                $stmt->bindParam(':correo', $correo);
                $stmt->bindParam(':comentario', $comentario);
                $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);

                if ($stmt->execute()) {


                    // ✅ Mensaje de texto
                    $nombreCliente = Trim($nombre." ".$apellido);
                    $mensajetxtW = "🌳🏡 Hola, *$nombreCliente*\\n\\n".
                                    "Ha sido un gusto contar con tu presencia en la inauguración de Lotificación La Ceiba.\\n".
                                    "Esperamos que la experiencia te haya permitido conocer de primera mano el concepto, el avance y la visión del proyecto.\\n\\n".
                                    "Por este medio quedamos a tu disposición para brindarte toda la información sobre los lotes disponibles, incluyendo:\\n\\n".
                                    "📐 Medidas\\n".
                                    "💰 Precios\\n".
                                    "📍 Ubicación exacta\\n".
                                    "💳 Opciones de pago\\n\\n".
                                    "👉 Escríbenos qué información te gustaría recibir o si deseas coordinar una visita personalizada.\\n".
                                    "Será un gusto acompañarte en el siguiente paso. ✨🏡";

                     // LIMPIAR y NORMALIZAR TELÉFONO GUATEMALA
                    $telefonoGT = trim($telefono);
                    $telefonoGT = preg_replace('/\s+/u', '', $telefonoGT); // borra espacios invisibles
                    $telefonoGT = preg_replace('/[^0-9]/u', '', $telefonoGT); // borra todo lo que no sea número
                    echo "ssiii";
                    if (!empty($telefonoGT) && strlen($telefonoGT) >= 8) {

                        // Payload Guatemala
                        $payloadGT = [
                            "phone" => "+".$telefonoGT,
                            "priority" => "urgent",
                            "device" => "691c9cbbc9d11d53fdac2a69",
                            "message" => $mensajetxtW,
                            "media" => [
                                "url" => "http://villaslaceibagt.com/assets/images/202512131106.jpeg"
                            ]
                        ];
                        
                        enviarWassenger($payloadGT);
                    }



                    // LIMPIAR y NORMALIZAR TELÉFONO USA
                    $telefonoUSA = trim($telefono_americano);
                    $telefonoUSA = preg_replace('/\s+/u', '', $telefonoUSA); 
                    $telefonoUSA = preg_replace('/[^0-9]/u', '', $telefonoUSA);

                    if (!empty($telefonoUSA) && strlen($telefonoUSA) >= 10) {

                        // Payload USA
                        $payloadUSA = [
                            "phone" => "+".$telefonoUSA,
                            "priority" => "urgent",
                            "device" => "691c9cbbc9d11d53fdac2a69",
                            "message" => $mensajetxtW,
                            "media" => [
                                "url" => "http://villaslaceibagt.com/assets/images/2025112141138.jpeg"
                            ]
                        ];

                        enviarWassenger($payloadUSA);
                    }
                    
                   
                    $mensaje = 'Registro guardado exitosamente';
                    $tipoMensaje = 'success';
                    // Limpiar el formulario después de guardar
                    $_POST = array();
                    // Recargar estadísticas
                    header("Location: formulario.php?success=1");
                    exit();
                } else {
                    $mensaje = 'Error al guardar el registro';
                    $tipoMensaje = 'error';
                }
            } catch (PDOException $e) {
                $mensaje = 'Error en el sistema. Por favor, intenta más tarde.';
                $tipoMensaje = 'error';
            }
        }
    }
}

// Mostrar mensaje de éxito si viene de redirect
if (isset($_GET['success'])) {
    $mensaje = 'Registro guardado exitosamente';
    $tipoMensaje = 'success';
}

// Obtener estadísticas para el dashboard
$totalRegistros = 0;
$totalVallas = 0;
$totalRedes = 0;
$totalAmigos = 0;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Total de registros del usuario
    $queryTotal = "SELECT COUNT(*) as total FROM registros WHERE usuario_id = :usuario_id";
    $stmtTotal = $conn->prepare($queryTotal);
    $stmtTotal->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmtTotal->execute();
    $totalRegistros = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total por fuente
    $queryFuentes = "SELECT 
                        SUM(CASE WHEN como_se_entero = 'Vallas publicitarias' THEN 1 ELSE 0 END) as vallas,
                        SUM(CASE WHEN como_se_entero = 'Redes sociales' THEN 1 ELSE 0 END) as redes,
                        SUM(CASE WHEN como_se_entero = 'Por amigos' THEN 1 ELSE 0 END) as amigos
                     FROM registros 
                     WHERE usuario_id = :usuario_id";
    $stmtFuentes = $conn->prepare($queryFuentes);
    $stmtFuentes->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmtFuentes->execute();
    $fuentes = $stmtFuentes->fetch(PDO::FETCH_ASSOC);
    
    $totalVallas = $fuentes['vallas'] ?? 0;
    $totalRedes = $fuentes['redes'] ?? 0;
    $totalAmigos = $fuentes['amigos'] ?? 0;
    
} catch (PDOException $e) {
    // Error silencioso
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Lotificación</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/formulario.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Fondo animado -->
    <div class="animated-background">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
    </div>

    <!-- Navbar Lateral -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="main-content">
        <div class="container">
            <!-- Header superior -->
            <div class="top-header">
                <div class="logo-section">
                    <h1 class="page-title">Dashboard Principal</h1>
                </div>
                <div class="user-section">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></div>
                        <div class="user-role">Administrador</div>
                    </div>
                    <div class="user-avatar">👤</div>
                </div>
            </div>

            <!-- Grid de estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon-wrapper">📊</div>
                        <div class="stat-trend"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalRegistros; ?></div>
                        <div class="stat-label">Total de Registros</div>
                        <div class="stat-description">Registros totales del sistema</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon-wrapper">🪧</div>
                        <div class="stat-trend"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalVallas; ?></div>
                        <div class="stat-label">Vallas Publicitarias</div>
                        <div class="stat-description">Leads de publicidad exterior</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon-wrapper">📱</div>
                        <div class="stat-trend"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalRedes; ?></div>
                        <div class="stat-label">Redes Sociales</div>
                        <div class="stat-description">Marketing digital activo</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon-wrapper">👥</div>
                        <div class="stat-trend"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalAmigos; ?></div>
                        <div class="stat-label">Por Amigos</div>
                        <div class="stat-description">Referencias personales</div>
                    </div>
                </div>
            </div>

            <!-- Formulario de registro -->
            <div class="form-wrapper">
                <div class="form-card">
                    <div class="form-header">
                        <h2 class="form-title">Nuevo Registro</h2>
                        <p class="form-subtitle">Complete la información del cliente potencial</p>
                    </div>

                    <form id="registroForm" method="POST" action="formulario.php">
                        <div class="form-grid">
                            <!-- Nombre -->
                            <div class="form-group">
                                <label for="nombre">
                                    Nombre <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="nombre" 
                                    name="nombre" 
                                    placeholder="Ingrese el nombre" 
                                    required
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Apellido -->
                            <div class="form-group">
                                <label for="apellido">
                                    Apellido <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="apellido" 
                                    name="apellido" 
                                    placeholder="Ingrese el apellido" 
                                    required
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Teléfono Guatemala -->
                            <div class="form-group">
                                <label for="telefono">
                                    Teléfono Guatemala
                                </label>
                                <input 
                                    type="tel" 
                                    id="telefono" 
                                    name="telefono" 
                                    placeholder="+502 0000-0000"
                                    maxlength="14"
                                    value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                                >
                                <small class="helper-text">Formato: +502 0000-0000</small>
                            </div>

                            <!-- Teléfono USA -->
                            <div class="form-group">
                                <label for="telefono_americano">
                                    Teléfono USA
                                </label>
                                <input 
                                    type="tel" 
                                    id="telefono_americano" 
                                    name="telefono_americano" 
                                    placeholder="+1 000-000-0000"
                                    maxlength="17"
                                    value="<?php echo htmlspecialchars($_POST['telefono_americano'] ?? ''); ?>"
                                >
                                <small class="helper-text">Formato: +1 000-000-0000</small>
                            </div>

                            <!-- ¿Cómo te enteraste? -->
                            <div class="form-group">
                                <label for="como_se_entero">
                                    ¿Cómo se enteró? <span class="required">*</span>
                                </label>
                                <select id="como_se_entero" name="como_se_entero" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="Vallas publicitarias" <?php echo (isset($_POST['como_se_entero']) && $_POST['como_se_entero'] === 'Vallas publicitarias') ? 'selected' : ''; ?>>Vallas Publicitarias</option>
                                    <option value="Redes sociales" <?php echo (isset($_POST['como_se_entero']) && $_POST['como_se_entero'] === 'Redes sociales') ? 'selected' : ''; ?>>Redes Sociales</option>
                                    <option value="Por amigos" <?php echo (isset($_POST['como_se_entero']) && $_POST['como_se_entero'] === 'Por amigos') ? 'selected' : ''; ?>>Por Amigos</option>
                                </select>
                            </div>

                            <!-- Correo Electrónico -->
                            <div class="form-group">
                                <label for="correo">
                                    Correo Electrónico
                                </label>
                                <input 
                                    type="email" 
                                    id="correo" 
                                    name="correo" 
                                    placeholder="ejemplo@correo.com"
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>"
                                >
                                <small class="helper-text">Formato: usuario@dominio.com</small>
                            </div>

                            <!-- Comentario -->
                            <div class="form-group full-width">
                                <label for="comentario">
                                    Comentarios Adicionales
                                </label>
                                <textarea 
                                    id="comentario" 
                                    name="comentario" 
                                    placeholder="Ingrese observaciones o comentarios importantes..."
                                    maxlength="500"
                                ><?php echo htmlspecialchars($_POST['comentario'] ?? ''); ?></textarea>
                                <small class="helper-text" id="caracteresRestantes">Máximo 500 caracteres</small>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnVerRegistros">Ver Registros</button>
                            <button type="reset" class="btn btn-tertiary" id="btnLimpiar">Limpiar</button>
                            <button type="submit" class="btn btn-primary">Guardar Registro</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar mensaje de login exitoso
        <?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Bienvenido!',
                text: 'Has iniciado sesión correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        <?php endif; ?>

        // Mostrar mensaje de éxito al guardar
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Registro guardado correctamente',
                confirmButtonColor: '#38bdf8'
            });
        <?php endif; ?>
    </script>
    <script src="js/formulario.js"></script>
</body>
</html>