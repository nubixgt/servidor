<?php
/**
 * ¿Coinciden las dos copias del registro de subidas a Garmin? (Beta 3.0, F5)
 *
 * El registro de verdad es `garmin_uploads.json` del AiService; `athlete_garmin_uploads`
 * (Database/030) es su copia en MySQL, que se escribe al terminar cada subida y al borrar
 * un plan. Antes de que la copia pase a mandar, tiene que haber coincidido en la beta:
 * esto compara, atleta a atleta, qué `workout_id` hay en cada una y en qué sesión.
 *
 * Sólo lee. En producción, con permiso del responsable.
 *
 *   php Backend/scripts/comparar_registro_de_subidas.php        # sale con 1 si difieren
 */

require_once __DIR__ . '/../autoload.php';

use App\Repositories\GarminUploadRepository;
use App\Services\PythonBridgeService;
use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$repo = new GarminUploadRepository();
if (!$repo->disponible()) {
    echo "Falta Database/030_registro_de_subidas.sql: no hay copia que comparar.\n";
    exit(0);
}
$puente = new PythonBridgeService();
$usuarios = array_map('intval', Database::getInstance()->getConnection()
    ->query('SELECT id FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
$distintos = 0;
foreach ($usuarios as $u) {
    $res = $puente->garminLedger($u);
    if (!$res['ok']) {
        echo "u{$u}: el AiService no respondió\n";
        $distintos++;
        continue;
    }
    $clave = fn($e) => (int) $e['workout_id'] . '@' . ($e['date'] ?? $e['session_date'] ?? '') . '|'
        . ($e['session_slot'] ?? 'unica');
    $fichero = array_map($clave, $res['data']['data']['entries'] ?? []);
    $copia = array_map($clave, $repo->entradas($u));
    sort($fichero);
    sort($copia);
    if (!$fichero && !$copia) {
        continue;
    }
    if ($fichero === $copia) {
        echo "u{$u}: ✓ " . count($fichero) . " entrenos\n";
    } else {
        $distintos++;
        echo "u{$u}: ✗ sólo en el fichero: " . implode(', ', array_diff($fichero, $copia))
            . " · sólo en MySQL: " . implode(', ', array_diff($copia, $fichero)) . "\n";
    }
}
echo $distintos ? "\n{$distintos} atleta(s) con copias distintas.\n" : "\nLas dos copias coinciden.\n";
exit($distintos ? 1 : 0);
