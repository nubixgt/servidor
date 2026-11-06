<?php
/**
 * ¿Coinciden las dos copias del expediente? (Database/034)
 *
 * El de verdad es `track_record.jsonl` (y `track_record.descartadas.jsonl`) del AiService;
 * `athlete_track_record` es su copia en MySQL, línea a línea y con el texto exacto, escrita al
 * terminar cada generación y al borrar un plan. Antes de que la copia pase a mandar tiene que
 * haber coincidido en la beta: esto compara, atleta a atleta, las líneas de cada fichero.
 *
 * Con `--copiar` hace además la copia de quien no coincida (la primera vez, para los atletas
 * que ya existían antes de la 034). Sólo lee del AiService; en MySQL sólo escribe la copia.
 * En producción, con permiso del responsable.
 *
 *   php Backend/scripts/comparar_expediente.php            # sale con 1 si difieren
 *   php Backend/scripts/comparar_expediente.php --copiar   # y copia lo que no coincida
 */

require_once __DIR__ . '/../autoload.php';

use App\Repositories\TrackRecordRepository;
use App\Services\PythonBridgeService;
use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$copiar = in_array('--copiar', $argv, true);
$repo = new TrackRecordRepository();
if (!$repo->disponible()) {
    echo "Falta Database/034_copia_del_expediente.sql: no hay copia que comparar.\n";
    exit(0);
}
$puente = new PythonBridgeService();
$usuarios = array_map('intval', Database::getInstance()->getConnection()
    ->query('SELECT id FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
$distintos = 0;
foreach ($usuarios as $u) {
    $res = $puente->trackRecord($u);
    if (!$res['ok']) {
        echo "u{$u}: el AiService no respondió\n";
        $distintos++;
        continue;
    }
    $fichero = array_merge(['expediente' => [], 'descartadas' => []], (array) ($res['data']['data'] ?? []));
    $copia = $repo->lineas($u);
    if (!$fichero['expediente'] && !$fichero['descartadas'] && !$copia['expediente'] && !$copia['descartadas']) {
        continue;
    }
    $iguales = $fichero['expediente'] === $copia['expediente'] && $fichero['descartadas'] === $copia['descartadas'];
    if ($iguales) {
        echo "u{$u}: ✓ " . count($fichero['expediente']) . " semanas, " . count($fichero['descartadas']) . " descartadas\n";
        continue;
    }
    $distintos++;
    echo "u{$u}: ✗ fichero " . count($fichero['expediente']) . "+" . count($fichero['descartadas'])
        . " líneas · MySQL " . count($copia['expediente']) . "+" . count($copia['descartadas']);
    if ($copiar) {
        echo $repo->reemplazar($u, $fichero) ? " → copiado" : " → no se pudo copiar";
    }
    echo "\n";
}
echo $distintos ? "\n{$distintos} atleta(s) con copias distintas.\n" : "\nLas dos copias coinciden.\n";
exit($distintos && !$copiar ? 1 : 0);
