<?php
/**
 * Núcleo del API · Sistema de Información VISAN (DAAN / MAGA)
 * Conexión SQLite, sesión, respuestas JSON y reglas de permisos.
 */

require_once __DIR__ . '/config.php';

date_default_timezone_set('America/Guatemala');
error_reporting(E_ALL);
ini_set('display_errors', '0');

// =====================================================
// RESPUESTAS JSON
// =====================================================
function responder($datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function fallar(string $mensaje, int $codigo = 400): void
{
    responder(['ok' => false, 'error' => $mensaje], $codigo);
}

set_exception_handler(function (Throwable $e) {
    error_log('[VISAN] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fallar('Error interno del servidor.', 500);
});

function leerJSON(): array
{
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo ?: '[]', true);
    return is_array($datos) ? $datos : [];
}

/** Las peticiones que modifican datos deben ser JSON (o multipart para archivos) y del mismo origen. */
function exigirPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        fallar('Método no permitido.', 405);
    }
    if (($_SERVER['HTTP_X_VISAN'] ?? '') !== '1') {
        fallar('Solicitud no válida.', 403);
    }
}

// =====================================================
// BASE DE DATOS (SQLite)
// =====================================================
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0770, true)) {
        throw new RuntimeException('No se pudo crear el directorio de datos: ' . DATA_DIR);
    }
    if (!file_exists(DATA_DIR . '/.htaccess')) {
        file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    $nueva = !file_exists(DB_FILE);
    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    crearEsquema($pdo);
    if ($nueva || !$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()) {
        sembrarDatos($pdo);
    }
    return $pdo;
}

function crearEsquema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT NOT NULL UNIQUE COLLATE NOCASE,
            nombre TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            rol TEXT NOT NULL CHECK (rol IN ('admin','editor','visualizador')),
            permisos TEXT NOT NULL DEFAULT '{}',
            activo INTEGER NOT NULL DEFAULT 1,
            intentos_fallidos INTEGER NOT NULL DEFAULT 0,
            bloqueado_hasta INTEGER NOT NULL DEFAULT 0,
            ultimo_acceso TEXT,
            creado TEXT NOT NULL DEFAULT (datetime('now','-6 hours'))
        );
        CREATE TABLE IF NOT EXISTS matriz (
            cod_mun INTEGER PRIMARY KEY,
            departamento TEXT NOT NULL,
            municipio TEXT NOT NULL,
            orden INTEGER NOT NULL DEFAULT 0,
            datos TEXT NOT NULL,
            actualizado TEXT,
            actualizado_por TEXT
        );
        CREATE TABLE IF NOT EXISTS meta (
            clave TEXT PRIMARY KEY,
            valor TEXT
        );
        CREATE TABLE IF NOT EXISTS bitacora (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha TEXT NOT NULL DEFAULT (datetime('now','-6 hours')),
            usuario TEXT,
            accion TEXT NOT NULL,
            detalle TEXT
        );
    ");
}

/** Primera ejecución: usuarios iniciales y la matriz semilla (Excel del 23 sep). */
function sembrarDatos(PDO $pdo): void
{
    $ins = $pdo->prepare('INSERT OR IGNORE INTO usuarios (usuario, nombre, password_hash, rol, permisos) VALUES (?,?,?,?,?)');
    foreach (USUARIOS_INICIALES as $u) {
        $ins->execute([$u['usuario'], $u['nombre'], password_hash($u['password'], PASSWORD_DEFAULT), $u['rol'], json_encode($u['permisos'] ?? new stdClass())]);
    }
    $semilla = __DIR__ . '/../seed/matriz.json';
    if (file_exists($semilla) && !$pdo->query('SELECT COUNT(*) FROM matriz')->fetchColumn()) {
        $s = json_decode(file_get_contents($semilla), true);
        guardarMatriz($pdo, $s['filas'], 'sistema');
        setMeta('fecha_corte', $s['fecha_corte'] ?? date('Y-m-d'));
        setMeta('ultimo_import', json_encode(['archivo' => $s['archivo'] ?? 'semilla', 'fecha' => date('Y-m-d H:i:s'), 'usuario' => 'sistema', 'filas' => count($s['filas'])]));
    }
    registrar('sistema', 'instalacion', 'Base de datos inicializada');
}

function guardarMatriz(PDO $pdo, array $filas, string $usuario): void
{
    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM matriz');
        $ins = $pdo->prepare('INSERT INTO matriz (cod_mun, departamento, municipio, orden, datos, actualizado, actualizado_por) VALUES (?,?,?,?,?,?,?)');
        $ahora = date('Y-m-d H:i:s');
        foreach ($filas as $i => $f) {
            $ins->execute([(int)$f['cod_mun'], $f['departamento'], $f['municipio'], $i, json_encode($f, JSON_UNESCAPED_UNICODE), $ahora, $usuario]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getMeta(string $clave, $defecto = null)
{
    $st = db()->prepare('SELECT valor FROM meta WHERE clave = ?');
    $st->execute([$clave]);
    $v = $st->fetchColumn();
    return $v === false ? $defecto : $v;
}

function setMeta(string $clave, ?string $valor): void
{
    db()->prepare('INSERT INTO meta (clave, valor) VALUES (?, ?) ON CONFLICT(clave) DO UPDATE SET valor = excluded.valor')
        ->execute([$clave, $valor]);
}

function registrar(?string $usuario, string $accion, string $detalle = ''): void
{
    db()->prepare('INSERT INTO bitacora (fecha, usuario, accion, detalle) VALUES (?,?,?,?)')
        ->execute([date('Y-m-d H:i:s'), $usuario, $accion, $detalle]);
}

// =====================================================
// SESIÓN
// =====================================================
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('VISANSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Usuario vigente leído de la BD (así los cambios de rol/permisos aplican sin re-login). */
function usuarioActual(): ?array
{
    iniciarSesion();
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT id, usuario, nombre, rol, permisos, activo FROM usuarios WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || !$u['activo']) {
        $_SESSION = [];
        return null;
    }
    $u['permisos'] = normalizarPermisos(json_decode($u['permisos'], true) ?: [], $u['rol']);
    unset($u['activo']);
    return $u;
}

function exigirUsuario(): array
{
    $u = usuarioActual();
    if (!$u) fallar('Sesión no iniciada o expirada.', 401);
    return $u;
}

function exigirAdmin(): array
{
    $u = exigirUsuario();
    if ($u['rol'] !== 'admin') fallar('Solo el administrador puede realizar esta acción.', 403);
    return $u;
}

// =====================================================
// PERMISOS
// =====================================================
const MODULOS = ['ejecucion', 'programacion', 'conred', 'bodegas'];
const MODALIDADES = ['nda', 'mc', 'judicial', 'apa', 'reserva', 'insan'];

/**
 * Permisos efectivos. El admin siempre tiene todo; el visualizador nada.
 * departamentos vacío = todos los departamentos.
 */
function normalizarPermisos(array $p, string $rol): array
{
    if ($rol === 'admin') {
        return ['modulos' => MODULOS, 'modalidades' => MODALIDADES, 'departamentos' => [], 'importar' => true];
    }
    if ($rol === 'visualizador') {
        return ['modulos' => [], 'modalidades' => [], 'departamentos' => [], 'importar' => false];
    }
    return [
        'modulos' => array_values(array_intersect(MODULOS, (array)($p['modulos'] ?? []))),
        'modalidades' => array_values(array_intersect(MODALIDADES, (array)($p['modalidades'] ?? []))),
        'departamentos' => array_values(array_filter(array_map('strval', (array)($p['departamentos'] ?? [])))),
        'importar' => !empty($p['importar']),
    ];
}

/** Campos editables de la matriz → [módulo, modalidad|null]. */
function campoPermiso(string $campo): ?array
{
    if (preg_match('/^ej_(nda|mc|judicial|apa|reserva|insan)(_fecha)?$/', $campo, $m)) return ['ejecucion', $m[1]];
    if (preg_match('/^prog_(nda|mc|judicial|apa|reserva|insan)(_fecha|_carga|_solicitud)?$/', $campo, $m)) return ['programacion', $m[1]];
    if ($campo === 'prog_anio') return ['programacion', null];
    if (in_array($campo, ['conred', 'conred_solicitud', 'conred_fecha'], true)) return ['conred', null];
    return null;
}

function puedeEditarCampo(array $u, string $campo, string $departamento): bool
{
    $regla = campoPermiso($campo);
    if (!$regla) return false;
    $p = $u['permisos'];
    [$modulo, $modalidad] = $regla;
    if (!in_array($modulo, $p['modulos'], true)) return false;
    if ($modalidad !== null && !in_array($modalidad, $p['modalidades'], true)) return false;
    if ($p['departamentos'] && !in_array($departamento, $p['departamentos'], true)) return false;
    return true;
}
