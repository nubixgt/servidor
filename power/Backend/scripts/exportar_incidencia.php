<?php
/**
 * Exporta una consulta de «Pedir ayuda» a un JSON, para analizarla con calma (Beta 3.0, F3).
 *
 * Saca la fila de `athlete_feedback` (context = 'ayuda') con su diagnóstico, que ya va sin
 * contraseñas (lista blanca al guardarla). Lo siguiente es
 * `PaginaLocal/scripts/analizar_incidencia.py`, que la cruza con la traza archivada del
 * atleta y propone la prueba de regresión.
 *
 * **En producción, sólo con permiso del responsable para ESA incidencia** (AGENTS.md:
 * nunca correr scripts contra producción sin permiso explícito para esa acción).
 *
 * ## Uso
 *
 *   php Backend/scripts/exportar_incidencia.php 42                    # escribe incidencia_42.json
 *   php Backend/scripts/exportar_incidencia.php 42 --salida otra.json
 */

require_once __DIR__ . '/../autoload.php';

use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$id = (int) ($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Uso: php exportar_incidencia.php <id> [--salida archivo.json]\n");
    exit(2);
}
$i = array_search('--salida', $argv, true);
$salida = $i !== false && isset($argv[$i + 1]) ? $argv[$i + 1] : "incidencia_{$id}.json";

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare(
    "SELECT id, user_id, category, status, session_key, message, page, diagnostic, triage, reply, created_at
       FROM athlete_feedback WHERE id = :id AND context = 'ayuda'"
);
$stmt->execute(['id' => $id]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fila) {
    fwrite(STDERR, "No hay ninguna consulta de ayuda con id {$id}.\n");
    exit(1);
}
$fila['diagnostic'] = $fila['diagnostic'] !== null ? json_decode((string) $fila['diagnostic'], true) : null;
file_put_contents($salida, json_encode($fila, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Consulta {$id} (atleta {$fila['user_id']}, {$fila['category']}) exportada a {$salida}\n";
