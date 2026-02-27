<?php
/**
 * Configuración de la Aplicación
 * Sistema de Ejecución Presupuestaria - MAGA
 */

// Información de la aplicación
define('APP_NAME', 'Sistema de Ejecución Presupuestaria');
define('APP_VERSION', '1.0.0');

// Información institucional
define('INSTITUCION', 'Ministerio de Agricultura, Ganadería y Alimentación');
define('INSTITUCION_SIGLAS', 'MAGA');
define('PERIODO_ACTUAL', '2025');

// Configuración de la zona horaria
date_default_timezone_set('America/Guatemala');

// Configuración de errores (producción)
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Funciones auxiliares globales
 */

if (!function_exists('formatMoney')) {
    /**
     * Formatear cantidad como moneda guatemalteca
     */
    function formatMoney($amount)
    {
        return 'Q ' . number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('formatPercent')) {
    /**
     * Formatear porcentaje
     */
    function formatPercent($percent)
    {
        return number_format($percent, 2, '.', ',') . '%';
    }
}

if (!function_exists('getSemaforoColor')) {
    /**
     * Obtener color del semáforo según porcentaje
     */
    function getSemaforoColor($porcentaje)
    {
        if ($porcentaje >= 80) return 'success';
        if ($porcentaje >= 50) return 'warning';
        return 'danger';
    }
}

if (!function_exists('getEstadoEjecucion')) {
    /**
     * Obtener estado de ejecución según porcentaje
     */
    function getEstadoEjecucion($porcentaje)
    {
        if ($porcentaje >= 80) return 'Excelente';
        if ($porcentaje >= 60) return 'Bueno';
        if ($porcentaje >= 40) return 'Regular';
        return 'Bajo';
    }
}

if (!function_exists('getMetaEjecucionAlDia')) {
    /**
     * Obtener meta de ejecución al día (%) desde los datos importados
     */
    function getMetaEjecucionAlDia($anio = null)
    {
        try {
            $db = getDB();
            
            // Si no se proporciona año, usar el de la sesión o 2025 por defecto
            if ($anio === null) {
                $anio = $_SESSION['anio_seleccionado'] ?? 2025;
            }
            
            // Obtener el primer valor no nulo de porcentaje_ejecucion_al_dia del año especificado
            $stmt = $db->prepare("SELECT porcentaje_ejecucion_al_dia FROM ejecucion_principal 
                               WHERE porcentaje_ejecucion_al_dia IS NOT NULL 
                               AND porcentaje_ejecucion_al_dia > 0
                               AND anio = ?
                               LIMIT 1");
            $stmt->execute([$anio]);
            $result = $stmt->fetch();
            return $result ? (float) $result['porcentaje_ejecucion_al_dia'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

/**
 * Registrar acción en bitácora
 */
function registrarBitacora($tablaAfectada, $registroId, $accion, $datosAnteriores = null, $datosNuevos = null, $descripcion = '')
{
    try {
        $db = getDB();

        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = $db->prepare("
            INSERT INTO bitacora 
            (usuario_id, tabla_afectada, registro_id, accion, datos_anteriores, datos_nuevos, ip_address, user_agent, descripcion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $usuarioId,
            $tablaAfectada,
            $registroId,
            $accion,
            $datosAnteriores ? json_encode($datosAnteriores) : null,
            $datosNuevos ? json_encode($datosNuevos) : null,
            $ip,
            $userAgent,
            $descripcion
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Error al registrar en bitácora: " . $e->getMessage());
        return false;
    }
}