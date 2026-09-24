<?php
/**
 * Configuración · Sistema de Información VISAN (DAAN / MAGA)
 */

// Carpeta de la base de datos SQLite (se crea sola). Queda protegida con .htaccess (Apache/cPanel).
// Se puede mover fuera de public_html con la variable de entorno VISAN_DATA_DIR.
define('DATA_DIR', getenv('VISAN_DATA_DIR') ?: dirname(__DIR__) . '/storage');
define('DB_FILE', DATA_DIR . '/visan.sqlite');

// Tamaño máximo del Excel a importar
define('MAX_UPLOAD_BYTES', 15 * 1024 * 1024);

// Bloqueo por intentos fallidos de inicio de sesión
define('MAX_INTENTOS_LOGIN', 5);
define('BLOQUEO_SEGUNDOS', 300);

// Usuarios creados solo la primera vez que se inicializa la base de datos.
// Cambie estas contraseñas desde el módulo "Usuarios y permisos" después del primer ingreso.
const USUARIOS_INICIALES = [
    [
        'usuario' => 'admin',
        'nombre' => 'Administrador VISAN',
        'password' => 'U8vqgQUVoJ',
        'rol' => 'admin',
    ],
    [
        'usuario' => 'miguel',
        'nombre' => 'Miguel',
        'password' => '2fSFN26DCy',
        'rol' => 'editor',
        'permisos' => [
            'modulos' => ['ejecucion', 'programacion', 'conred', 'bodegas'],
            'modalidades' => ['nda', 'mc', 'judicial', 'apa', 'reserva', 'insan'],
            'departamentos' => [],
            'importar' => true,
        ],
    ],
];
