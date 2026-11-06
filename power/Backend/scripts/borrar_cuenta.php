<?php
/**
 * Borra la cuenta entera de UN atleta, cuando lo pide.
 *
 * ## Por qué existe
 *
 * Las condiciones de la Beta 2.0 (Support/Legal.php, punto 9) prometen borrar la
 * cuenta en un máximo de 30 días a quien lo pida desde Opinar, conservando sólo el
 * registro contable sin datos que le identifiquen. Hasta aquí no había forma de
 * cumplirlo: la app deja borrar planes y perfil, y el panel de administración
 * sólo aprueba o rechaza.
 *
 * ## Qué hace, en este orden
 *
 * 1. Si tiene la contraseña de Garmin guardada, retira de su reloj los entrenos
 *    que la app subió (sólo los anotados; nunca actividades ni entrenos suyos).
 * 2. Avisa al AiService de que el plan se fue, para que borre sus trazas y el
 *    resultado de sus trabajos.
 * 3. En MySQL, en una transacción: sus eventos de uso fuera, su gasto se suma a
 *    la fila anónima `user_id = 0`, y la fila de `users` fuera -- perfil,
 *    planes, historial, check-ins, cuaderno, competiciones, lesiones, mensajes y
 *    avisos descartados caen en cascada.
 * 4. Dice qué carpeta del AiService queda por borrar a mano en el servidor.
 *
 * ## Uso
 *
 *   php Backend/scripts/borrar_cuenta.php atleta@correo.com             # dice qué borraría
 *   php Backend/scripts/borrar_cuenta.php atleta@correo.com --ejecutar  # borra
 */

require_once __DIR__ . '/../autoload.php';

use App\Services\ProfileService;
use App\Services\PythonBridgeService;
use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = $argv[1] ?? '';
$ejecutar = in_array('--ejecutar', $argv, true);
if ($email === '' || str_starts_with($email, '--')) {
    fwrite(STDERR, "Uso: php Backend/scripts/borrar_cuenta.php correo@atleta.com [--ejecutar]\n");
    exit(1);
}

$pdo = Database::getInstance()->getConnection();
$st = $pdo->prepare(
    "SELECT u.id, u.email, u.name, u.role, p.garmin_email,
            (p.garmin_password_enc IS NOT NULL AND p.garmin_password_enc <> '') AS con_clave
     FROM users u LEFT JOIN athlete_profiles p ON p.user_id = u.id
     WHERE u.email = :e"
);
$st->execute(['e' => $email]);
$u = $st->fetch();
if (!$u) {
    fwrite(STDERR, "No hay ninguna cuenta con el correo $email\n");
    exit(1);
}
$id = (int) $u['id'];
if ($u['role'] === 'admin') {
    fwrite(STDERR, "$email es administrador: no se borra con este script.\n");
    exit(1);
}

$contar = fn(string $sql) => (function () use ($pdo, $sql, $id) {
    $q = $pdo->prepare($sql);
    $q->execute(['id' => $id]);
    return (int) $q->fetchColumn();
})();

echo "Cuenta: {$u['name']} <{$u['email']}> (usuario $id)\n";
echo "  planes:    " . $contar('SELECT COUNT(*) FROM athlete_plans WHERE user_id = :id') . "\n";
echo "  historial: " . $contar('SELECT COUNT(*) FROM athlete_plan_history WHERE user_id = :id') . "\n";
echo "  check-ins: " . $contar('SELECT COUNT(*) FROM athlete_checkins WHERE user_id = :id') . "\n";
echo "  cuaderno:  " . $contar('SELECT COUNT(*) FROM athlete_notebook WHERE user_id = :id') . "\n";
echo "  eventos:   " . $contar('SELECT COUNT(*) FROM app_events WHERE user_id = :id') . "\n";
echo "  Garmin:    " . ((int) $u['con_clave'] ? "contraseña guardada: se retirarán sus entrenos" : "sin contraseña guardada: sus entrenos se quedan en el reloj") . "\n";

if (!$ejecutar) {
    echo "\nNada se ha tocado. Repite con --ejecutar para borrar.\n";
    exit(0);
}

$puente = new PythonBridgeService();
if ((int) $u['con_clave']) {
    $perfiles = new ProfileService();
    $res = $puente->garminRetire([
        'user_id' => $id,
        'profile' => $perfiles->getForUser($id),
        'garmin_password' => $perfiles->garminPasswordBlob($id) ?? '',
        'weekly_plan' => null,
        'todo' => true,
    ]);
    $d = $res['data']['data'] ?? [];
    echo "Garmin: " . (int) ($d['retirados'] ?? 0) . " retirados, " . (int) ($d['pendientes'] ?? 0) . " pendientes"
        . (!empty($d['error']) ? " — ERROR: {$d['error']}" : '') . "\n";
}
$res = $puente->planDeleted($id, 'all');
echo "AiService (trazas y resultados): " . ($res['ok'] ? 'borrados' : 'SIN RESPUESTA — ' . ($res['error'] ?? '')) . "\n";

$pdo->beginTransaction();
try {
    // El gasto se conserva, sin decir de quién era. La tabla derivada con alias
    // propios es a propósito: leer y escribir `athlete_usage` en la misma
    // sentencia hace ambiguo `cost_usd` en el ON DUPLICATE KEY (lo cazó el
    // ensayo con un atleta sintético, 2026-09-14).
    $pdo->prepare(
        "INSERT INTO athlete_usage (user_id, month, cost_usd, runs, tokens, by_kind)
         SELECT uid, mes, costo, corridas, fichas, NULL FROM (
             SELECT 0 AS uid, month AS mes, cost_usd AS costo, runs AS corridas, tokens AS fichas
             FROM athlete_usage WHERE user_id = :id
         ) AS gasto
         ON DUPLICATE KEY UPDATE cost_usd = athlete_usage.cost_usd + gasto.costo,
                                 runs = athlete_usage.runs + gasto.corridas,
                                 tokens = athlete_usage.tokens + gasto.fichas"
    )->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM athlete_usage WHERE user_id = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM app_events WHERE user_id = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR en MySQL, no se borró nada de la base: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nCuenta borrada de la base. Falta, en el servidor:\n";
echo "  rm -rf /home/visionwe/aiservice/app/_runtime/storage/u$id\n";
// Los trabajos de generación guardan en spec.json lo que recibió el worker
// (perfil, cuaderno, check-ins) hasta que caducan a los 3 días. Las condiciones
// prometen el borrado; se quitan ya en vez de esperar a la limpieza.
echo "  grep -l '\"user_id\": {$id}[,}]' /home/visionwe/aiservice/app/_runtime/jobs/*/spec.json /home/visionwe/aiservice/app/_runtime/upload_jobs/*/spec.json 2>/dev/null | xargs -r -n1 dirname | xargs -r rm -rf\n";
