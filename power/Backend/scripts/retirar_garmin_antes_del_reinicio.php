<?php
/**
 * Retira de los relojes de los atletas los entrenos que la app les subió,
 * ANTES de reiniciar la beta.
 *
 * ## Por qué hace falta
 *
 * El reinicio de la Beta 2.0 borra las cuentas, los perfiles y `_runtime/`. El
 * registro de subidas (`garmin_uploads.json`, en `_runtime/storage/u<id>/`) es
 * lo único que dice qué entrenos del calendario de Garmin creó esta app. Si se
 * borra antes, los entrenos se quedan en el reloj para siempre y ya nadie puede
 * distinguirlos de los del atleta.
 *
 * ## Qué toca y qué no
 *
 * - Sólo los `workoutId` anotados en ese registro (la regla de
 *   `services/garmin/upload_ledger.py`). Nada que el atleta se creara a mano.
 * - Nunca las actividades ya entrenadas.
 * - Sólo atletas con la contraseña de Garmin GUARDADA (cifrada). A quien no la
 *   guardó hay que pedirle que borre su plan desde la app marcando "quitar
 *   también de Garmin", o que borre los entrenos en Garmin Connect.
 *
 * ## Uso (en el servidor, con el AiService arriba)
 *
 *   php Backend/scripts/retirar_garmin_antes_del_reinicio.php            # sólo lista
 *   php Backend/scripts/retirar_garmin_antes_del_reinicio.php --ejecutar # retira
 *
 * Sin `--ejecutar` no se conecta a Garmin: dice a quién se le retiraría.
 */

require_once __DIR__ . '/../autoload.php';

use App\Services\ProfileService;
use App\Services\PythonBridgeService;
use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$ejecutar = in_array('--ejecutar', $argv, true);
$pdo = Database::getInstance()->getConnection();

$filas = $pdo->query(
    "SELECT u.id, u.email, p.garmin_email,
            (p.garmin_password_enc IS NOT NULL AND p.garmin_password_enc <> '') AS con_clave
     FROM users u
     LEFT JOIN athlete_profiles p ON p.user_id = u.id
     ORDER BY u.id"
)->fetchAll();

$perfiles = new ProfileService();
$puente = new PythonBridgeService();
$sinClave = [];
$total = 0;

foreach ($filas as $f) {
    $id = (int) $f['id'];
    if (empty($f['garmin_email'])) {
        continue;
    }
    if (!(int) $f['con_clave']) {
        $sinClave[] = "{$f['email']} (Garmin {$f['garmin_email']})";
        continue;
    }
    if (!$ejecutar) {
        echo "Se le retirarían los entrenos anotados a: {$f['email']} (usuario $id)\n";
        continue;
    }
    $res = $puente->garminRetire([
        'user_id' => $id,
        'profile' => $perfiles->getForUser($id),
        'garmin_password' => $perfiles->garminPasswordBlob($id) ?? '',
        'weekly_plan' => null,
        'todo' => true,
    ]);
    $d = $res['data']['data'] ?? [];
    $retirados = (int) ($d['retirados'] ?? 0);
    $total += $retirados;
    $error = $d['error'] ?? ($res['ok'] ? null : ($res['error'] ?? 'sin respuesta del AiService'));
    echo sprintf(
        "%s: %d retirados, %d pendientes%s\n",
        $f['email'],
        $retirados,
        (int) ($d['pendientes'] ?? 0),
        $error ? " — ERROR: $error" : ''
    );
}

if ($sinClave) {
    echo "\nSin contraseña guardada (hay que pedírselo a ellos):\n  - " . implode("\n  - ", $sinClave) . "\n";
}
echo $ejecutar ? "\nTotal retirados: $total\n" : "\nNada se ha tocado. Repite con --ejecutar para retirar.\n";
