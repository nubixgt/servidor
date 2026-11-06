<?php
/**
 * «Generar mi semana automáticamente»: lo que hace el servidor cada hora.
 *
 * Para cada atleta con el interruptor encendido (Database/027): si es la víspera del
 * primer día de su siguiente semana, a partir de las 21:00 de SU hora, genera esa
 * semana sin preguntas del coach ni check-in, la guarda y la sube a su reloj. La
 * lógica está en `App\Services\AutoWeekService`; esto sólo da las vueltas.
 *
 * Después de lanzar, se queda preguntando cada 30 s por lo que haya en marcha
 * (generar tarda varios minutos; subir, uno) hasta 50 minutos: así la semana se
 * guarda y se sube en cuanto termina, no una hora después. Lo que quede pendiente lo
 * recoge la vuelta siguiente, que sabe en qué iba cada atleta por la base.
 *
 * Un candado impide que dos vueltas se pisen si una tarda más de una hora.
 *
 * ## Uso
 *
 *   php Backend/scripts/semanas_automaticas.php            # una vuelta (lo que lanza el cron)
 *   php Backend/scripts/semanas_automaticas.php --estado   # sólo dice cómo está cada atleta; no lanza nada
 *
 * ## Cron (servidor Linux), cada hora en el minuto 5
 *
 *   5 * * * * /usr/bin/php /ruta/al/backend/scripts/semanas_automaticas.php >> /ruta/logs/semanas_automaticas.log 2>&1
 *
 * Cada generación cuesta lo de una semana normal (~0,30 USD) y va a su libro de
 * gastos como cualquier otra.
 */

require_once __DIR__ . '/../autoload.php';

use App\Repositories\AutoWeekRepository;
use App\Repositories\FeedbackRepository;
use App\Services\AutoWeekService;
use App\Services\SpendingAlertService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const VUELTA_MAX_S = 50 * 60;
const PAUSA_S = 30;

$soloEstado = in_array('--estado', $argv, true);
$log = static function (string $linea): void {
    echo gmdate('Y-m-d H:i:s') . ' UTC  ' . $linea . PHP_EOL;
};

// La alerta de gasto (Beta 3.0, F2): un aviso al administrador por cada atleta que pasó
// de 1 USD hoy, una sola vez por atleta y día. Va antes de todo lo demás: no depende de
// que haya semanas automáticas, y nunca tumba la vuelta.
if (!$soloEstado) {
    try {
        $avisos = (new SpendingAlertService())->avisarAlAdministrador();
        if ($avisos > 0) {
            $log("Alerta de gasto: {$avisos} aviso(s) nuevo(s) al administrador.");
        }
    } catch (\Throwable $e) {
        $log('La alerta de gasto falló: ' . $e->getMessage());
    }
    // Los diagnósticos de «Ayuda» se guardan 90 días (Legal, punto 9). Tampoco tumba la vuelta.
    try {
        $purgados = (new FeedbackRepository())->purgarDiagnosticos();
        if ($purgados > 0) {
            $log("Diagnósticos de Ayuda borrados por tener más de 90 días: {$purgados}.");
        }
    } catch (\Throwable $e) {
        $log('Borrar los diagnósticos viejos falló: ' . $e->getMessage());
    }
}

$repo = new AutoWeekRepository();
if (!$repo->disponible()) {
    $log('La tabla athlete_auto_weeks no existe: falta correr Database/027_semana_automatica.sql. Nada que hacer.');
    exit(0);
}

if ($soloEstado) {
    foreach ($repo->enabled() as $fila) {
        $log(sprintf('u%d  zona=%s  semana=%s  etapa=%s  intentos=%d  %s',
            $fila['user_id'], $fila['timezone'] ?? '-', $fila['week_start'] ?? '-', $fila['stage'] ?? '-',
            (int) $fila['attempts'], $fila['message'] ?? ''));
    }
    exit(0);
}

$candado = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'powercoach_semanas_automaticas.lock', 'c');
if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
    $log('Otra vuelta sigue en marcha; ésta no hace nada.');
    exit(0);
}

$servicio = new AutoWeekService();
$inicio = time();
$pendientes = null;   // null = primera pasada: todos los encendidos
do {
    $filas = $repo->enabled();
    if ($pendientes !== null) {
        $filas = array_values(array_filter($filas, fn($f) => isset($pendientes[(int) $f['user_id']])));
    }
    $pendientes = [];
    foreach ($filas as $fila) {
        try {
            if ($servicio->paso($fila, $log)) {
                $pendientes[(int) $fila['user_id']] = true;
            }
        } catch (\Throwable $e) {
            // Un atleta que falla no puede dejar sin semana a los demás.
            $log("u{$fila['user_id']}: error inesperado: " . $e->getMessage());
        }
    }
    if ($pendientes && time() - $inicio + PAUSA_S < VUELTA_MAX_S) {
        sleep(PAUSA_S);
    } else {
        break;
    }
} while (true);

if ($pendientes) {
    $log(count($pendientes) . ' atleta(s) siguen en marcha; los recoge la próxima vuelta.');
}
flock($candado, LOCK_UN);
