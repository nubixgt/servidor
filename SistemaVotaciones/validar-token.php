<?php
/**
 * VALIDADOR DE TOKEN SSO - SISTEMA DE VOTACIONES
 * 
 * Este archivo recibe y valida el token JWT del sistema principal (MAGA)
 * y crea una sesión local en este sistema.
 * 
 * URL del sistema: http://159.65.168.91/SistemaVotaciones
 */

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración
require_once __DIR__ . '/config.php';

// ===== CONFIGURACIÓN SSO =====
// IMPORTANTE: Esta clave DEBE ser IDÉNTICA a la del sistema central (MAGA)
define('JWT_SECRET_SSO', 'VrJgS+XQwaEcNSWqjJtIYEaaupb3KEJp9ros65EDTQA=');

// ===== FUNCIONES JWT =====

/**
 * Codifica en base64url (compatible con JWT)
 */
function base64UrlEncode($data) {
    $base64 = base64_encode($data);
    return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
}

/**
 * Decodifica de base64url
 */
function base64UrlDecode($data) {
    $base64 = str_replace(['-', '_'], ['+', '/'], $data);
    $padding = strlen($base64) % 4;
    if ($padding) {
        $base64 .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($base64);
}

/**
 * Valida un token JWT
 * 
 * @param string $token Token JWT recibido
 * @return array|false Datos del usuario o false si es inválido
 */
function validarTokenJWT($token) {
    $secret = JWT_SECRET_SSO;
    
    error_log("========================================");
    error_log("🔍 [SSO-Votaciones] Validando token JWT...");
    error_log("   Token (primeros 50 chars): " . substr($token, 0, 50) . "...");
    error_log("   JWT_SECRET (primeros 20 chars): " . substr($secret, 0, 20) . "...");
    
    // Validar formato del token (debe tener 3 partes: header.payload.signature)
    $partes = explode('.', $token);
    if (count($partes) !== 3) {
        error_log("❌ [SSO-Votaciones] Token inválido: formato incorrecto (debe tener 3 partes)");
        return false;
    }
    
    list($headerB64, $payloadB64, $signatureB64) = $partes;
    
    // Verificar la firma usando base64url (IMPORTANTE)
    $signatureVerify = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);
    $signatureVerify = base64UrlEncode($signatureVerify);
    
    if ($signatureB64 !== $signatureVerify) {
        error_log("❌ [SSO-Votaciones] Token JWT inválido: firma no coincide");
        error_log("   Firma esperada: " . substr($signatureVerify, 0, 30) . "...");
        error_log("   Firma recibida: " . substr($signatureB64, 0, 30) . "...");
        error_log("   ⚠️ VERIFICA que JWT_SECRET sea IDÉNTICA en ambos sistemas");
        return false;
    }
    
    // Decodificar el payload
    $payload = json_decode(base64UrlDecode($payloadB64), true);
    
    if (!$payload) {
        error_log("❌ [SSO-Votaciones] Token JWT inválido: no se pudo decodificar el payload");
        return false;
    }
    
    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        error_log("❌ [SSO-Votaciones] Token JWT expirado");
        error_log("   Expiración: " . date('Y-m-d H:i:s', $payload['exp']));
        error_log("   Ahora: " . date('Y-m-d H:i:s', time()));
        return false;
    }
    
    error_log("✅ [SSO-Votaciones] Token JWT válido");
    error_log("   Usuario: " . ($payload['usuario'] ?? 'desconocido'));
    error_log("   Nombre: " . ($payload['nombre_completo'] ?? 'N/A'));
    error_log("   Rol: " . ($payload['rol'] ?? 'N/A'));
    
    return $payload;
}

/**
 * Busca o crea un usuario en la base de datos local
 * 
 * @param array $datosUsuario Datos del usuario desde el JWT
 * @return array|false Datos completos del usuario o false si hay error
 */
function buscarOCrearUsuario($datosUsuario) {
    try {
        $db = getDB();
        
        // Primero intentar buscar el usuario por username
        $stmt = $db->prepare("
            SELECT id, username, nombre_completo, tipo_usuario, activo 
            FROM usuarios 
            WHERE username = ? 
            LIMIT 1
        ");
        $stmt->execute([$datosUsuario['usuario']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            error_log("✅ [SSO-Votaciones] Usuario encontrado en BD local: " . $datosUsuario['usuario']);
            
            // Verificar que esté activo
            if ($usuario['activo'] != 1) {
                error_log("❌ [SSO-Votaciones] Usuario inactivo: " . $datosUsuario['usuario']);
                return false;
            }
            
            return $usuario;
        } else {
            // Usuario no existe, crearlo
            error_log("ℹ️ [SSO-Votaciones] Usuario no existe, creando en BD local: " . $datosUsuario['usuario']);
            
            // Mapear rol del sistema principal al sistema de votaciones
            // Sistema principal usa: 'admin', 'usuario'
            // Este sistema usa: 'admin', 'usuario'
            $tipo_usuario = 'usuario'; // Por defecto
            if (isset($datosUsuario['rol'])) {
                if ($datosUsuario['rol'] == 'admin' || $datosUsuario['rol'] == 'administrador') {
                    $tipo_usuario = 'admin';
                }
            }
            
            // Usar nombre_completo del token
            $nombre_completo = $datosUsuario['nombre_completo'] ?? $datosUsuario['usuario'];
            
            // Crear usuario con contraseña dummy (no se usará para SSO)
            $password_dummy = password_hash('sso_user_' . uniqid(), PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO usuarios (username, nombre_completo, password, tipo_usuario, activo, fecha_creacion) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $datosUsuario['usuario'],
                $nombre_completo,
                $password_dummy,
                $tipo_usuario
            ]);
            
            $nuevoId = $db->lastInsertId();
            
            // Obtener el usuario recién creado
            $stmt = $db->prepare("
                SELECT id, username, nombre_completo, tipo_usuario, activo 
                FROM usuarios 
                WHERE id = ? 
                LIMIT 1
            ");
            $stmt->execute([$nuevoId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                error_log("✅ [SSO-Votaciones] Usuario creado exitosamente: " . $datosUsuario['usuario']);
                error_log("   ID: " . $usuario['id']);
                error_log("   Nombre: " . $usuario['nombre_completo']);
                error_log("   Tipo: " . $usuario['tipo_usuario']);
                return $usuario;
            } else {
                error_log("❌ [SSO-Votaciones] Error: No se pudo crear el usuario");
                return false;
            }
        }
    } catch (Exception $e) {
        error_log("❌ [SSO-Votaciones] Error en BD: " . $e->getMessage());
        return false;
    }
}

// ===== PROCESAMIENTO DEL TOKEN =====

error_log("========================================");
error_log("🔐 [SSO-Votaciones] INICIO VALIDACIÓN TOKEN");
error_log("========================================");

// Obtener el token del parámetro GET
$token = $_GET['token'] ?? '';

if (empty($token)) {
    error_log("❌ [SSO-Votaciones] No se recibió token en la URL");
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Error de autenticación</title>
            <style>
                body { font-family: "Inter", Arial, sans-serif; text-align: center; padding: 50px; background: linear-gradient(180deg, #1B5BA8 0%, #0F3A6B 100%); color: white; min-height: 100vh; margin: 0; }
                .error-box { background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; max-width: 500px; margin: 50px auto; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
                h1 { font-size: 1.5rem; margin-bottom: 1rem; }
                p { margin-bottom: 1.5rem; opacity: 0.9; }
                a { color: #ffd700; text-decoration: none; font-weight: bold; padding: 10px 20px; background: rgba(255,215,0,0.2); border-radius: 10px; display: inline-block; }
                a:hover { background: rgba(255,215,0,0.3); }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>❌ Error de Autenticación</h1>
                <p>No se recibió token de autenticación.</p>
                <a href="http://172.22.64.138/MagaCorregido/">← Volver al sistema principal</a>
            </div>
        </body>
        </html>
    ');
}

// Validar el token JWT
$datosToken = validarTokenJWT($token);

if (!$datosToken) {
    error_log("❌ [SSO-Votaciones] Token inválido o expirado");
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Token inválido</title>
            <style>
                body { font-family: "Inter", Arial, sans-serif; text-align: center; padding: 50px; background: linear-gradient(180deg, #1B5BA8 0%, #0F3A6B 100%); color: white; min-height: 100vh; margin: 0; }
                .error-box { background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; max-width: 500px; margin: 50px auto; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
                h1 { font-size: 1.5rem; margin-bottom: 1rem; }
                p { margin-bottom: 1.5rem; opacity: 0.9; }
                a { color: #ffd700; text-decoration: none; font-weight: bold; padding: 10px 20px; background: rgba(255,215,0,0.2); border-radius: 10px; display: inline-block; }
                a:hover { background: rgba(255,215,0,0.3); }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>❌ Token Inválido</h1>
                <p>El token de autenticación es inválido o ha expirado.</p>
                <a href="http://172.22.64.138/MagaCorregido/">← Volver al sistema principal</a>
            </div>
        </body>
        </html>
    ');
}

// Buscar o crear el usuario en la base de datos local
$usuario = buscarOCrearUsuario($datosToken);

if (!$usuario) {
    error_log("❌ [SSO-Votaciones] No se pudo obtener/crear usuario en BD local");
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Error al crear usuario</title>
            <style>
                body { font-family: "Inter", Arial, sans-serif; text-align: center; padding: 50px; background: linear-gradient(180deg, #1B5BA8 0%, #0F3A6B 100%); color: white; min-height: 100vh; margin: 0; }
                .error-box { background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; max-width: 500px; margin: 50px auto; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
                h1 { font-size: 1.5rem; margin-bottom: 1rem; }
                p { margin-bottom: 1.5rem; opacity: 0.9; }
                a { color: #ffd700; text-decoration: none; font-weight: bold; padding: 10px 20px; background: rgba(255,215,0,0.2); border-radius: 10px; display: inline-block; }
                a:hover { background: rgba(255,215,0,0.3); }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>❌ Error al Crear Usuario</h1>
                <p>No se pudo autenticar en el sistema.</p>
                <a href="http://172.22.64.138/MagaCorregido/">← Volver al sistema principal</a>
            </div>
        </body>
        </html>
    ');
}

// ===== CREAR SESIÓN LOCAL =====
// Variables que espera auth.php:
// - $_SESSION['usuario_id']
// - $_SESSION['tipo_usuario'] (para esAdmin())

session_regenerate_id(true);

$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['username'] = $usuario['username'];
$_SESSION['nombre_completo'] = $usuario['nombre_completo'];
$_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];  // 'admin' o 'usuario'
$_SESSION['login_time'] = time();
$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
$_SESSION['auth_method'] = 'sso';
$_SESSION['sso_login'] = true;

error_log("✅ [SSO-Votaciones] Sesión local creada exitosamente");
error_log("   ID: " . $usuario['id']);
error_log("   Usuario: " . $usuario['username']);
error_log("   Nombre: " . $usuario['nombre_completo']);
error_log("   Tipo: " . $usuario['tipo_usuario']);

// Actualizar último acceso
try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE usuarios SET fecha_ultimo_acceso = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
} catch (Exception $e) {
    error_log("⚠️ [SSO-Votaciones] No se pudo actualizar último acceso: " . $e->getMessage());
}

error_log("========================================");
error_log("✅ [SSO-Votaciones] SSO EXITOSO - Redirigiendo a index.php");
error_log("========================================");

// Redirigir al dashboard principal
header('Location: index.php');
exit();
?>