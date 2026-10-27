<?php
/**
 * Recalcula los agregados del tablero (equivale a CALL mart_refrescar()).
 *
 *   php bin/refrescar.php            solo si hay cambios pendientes y pasó el intervalo configurado (para cron)
 *   php bin/refrescar.php --forzar   siempre
 *
 * Ejemplo de cron: ejecutarlo cada 5 minutos con el comando "php /ruta/Backend/bin/refrescar.php"
 */
require_once __DIR__ . '/../autoload.php';

use App\Repositories\ImportacionRepository;
use App\Utils\Database;

date_default_timezone_set((require __DIR__ . '/../config/app.php')['timezone']);
set_time_limit(0);
$db = Database::getInstance();
$forzar = in_array('--forzar', $argv, true);

if (!$forzar) {
    $pendiente = $db->scalar("SELECT valor FROM mart_meta WHERE clave = 'pendiente_refresco'") === '1';
    $minutos = (int) ($db->scalar("SELECT JSON_UNQUOTE(valor) FROM ref_parametro WHERE clave = 'refresco_tablero_min'") ?: 15);
    $ultimo = $db->scalar("SELECT valor FROM mart_meta WHERE clave = 'refrescado_en'");
    $vencido = !$ultimo || strtotime($ultimo) < time() - $minutos * 60;
    if (!$pendiente || !$vencido) {
        echo "Sin cambios pendientes.\n";
        exit(0);
    }
}

(new ImportacionRepository())->refrescarTablero();
echo "Agregados del tablero actualizados.\n";
