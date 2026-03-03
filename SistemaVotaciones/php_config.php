<?php
/**
 * CONFIGURACIÓN PHP OPTIMIZADA
 * Archivo: php_config_mejorado.php
 * 
 * Este archivo ajusta la configuración de PHP para permitir carga múltiple
 * Incluir al inicio de TODOS los archivos de carga
 */

// 1. AUMENTAR TIEMPO DE EJECUCIÓN
// 10 minutos para procesar múltiples archivos grandes
@ini_set('max_execution_time', 600);
@set_time_limit(600);
@ini_set('max_input_time', 600);

// 2. AUMENTAR LÍMITE DE ARCHIVOS
// Permitir hasta 100 archivos simultáneos
@ini_set('max_file_uploads', 100);

// 3. AUMENTAR MEMORIA
// 512MB para procesar muchos archivos
@ini_set('memory_limit', '512M');

// 4. AUMENTAR TAMAÑO DE POST
// 500MB total para múltiples archivos
@ini_set('post_max_size', '500M');
@ini_set('upload_max_filesize', '50M');

// 5. CONFIGURACIÓN DE ERRORES
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
@error_reporting(E_ALL);

// 6. EVITAR TIMEOUT DEL NAVEGADOR
// Enviar algo cada 30 segundos para mantener la conexión viva
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 'On');
@ob_implicit_flush(true);

// 7. DESHABILITAR LÍMITES DE RECURSOS (si es posible)
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}

/**
 * Función para verificar la configuración actual
 * Útil para debugging
 */
function verificarConfiguracionPHP() {
    $config = [
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
    ];
    
    return $config;
}

/**
 * Función para mantener viva la conexión
 * Envía un espacio cada 5 segundos
 */
function mantenerConexionViva() {
    static $ultimoEnvio = 0;
    $ahora = time();
    
    if ($ahora - $ultimoEnvio >= 5) {
        echo ' '; // Enviar un espacio
        @ob_flush();
        @flush();
        $ultimoEnvio = $ahora;
    }
}

/**
 * Función para convertir tamaño de string a bytes
 */
function convertirABytes($valor) {
    $valor = trim($valor);
    $ultimo = strtolower($valor[strlen($valor)-1]);
    $valor = (int)$valor;
    
    switch($ultimo) {
        case 'g':
            $valor *= 1024;
        case 'm':
            $valor *= 1024;
        case 'k':
            $valor *= 1024;
    }
    
    return $valor;
}

/**
 * Verificar límites reales del sistema
 */
function obtenerLimitesReales() {
    $postMax = convertirABytes(ini_get('post_max_size'));
    $uploadMax = convertirABytes(ini_get('upload_max_filesize'));
    $maxFiles = (int)ini_get('max_file_uploads');
    
    return [
        'max_archivos' => $maxFiles,
        'tamano_max_archivo_mb' => round($uploadMax / 1048576, 2),
        'tamano_max_post_mb' => round($postMax / 1048576, 2),
        'archivos_simultaneos_seguros' => min($maxFiles, floor($postMax / $uploadMax)),
    ];
}

// Log de configuración aplicada (solo en desarrollo)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log('Configuración PHP aplicada: ' . json_encode(verificarConfiguracionPHP()));
}