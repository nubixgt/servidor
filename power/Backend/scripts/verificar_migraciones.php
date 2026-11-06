<?php
/**
 * Comprueba contra la base que TODAS las migraciones de Database/ están aplicadas.
 *
 * ## Por qué existe
 *
 * Las migraciones se corren a mano, también en producción, y no hay tabla que
 * recuerde cuáles corrieron. El 2026-09-14, revisando la base local una por una,
 * la 018 no estaba aplicada: seguían tres columnas que el código ya no nombra, y
 * nada lo decía. El código aguanta que falten (SHOW COLUMNS, COLS_OPCIONALES),
 * así que una migración olvidada no rompe nada visible: se queda olvidada.
 *
 * Esto mira lo que cada migración deja en la base, no un registro que alguien
 * tenga que acordarse de escribir. Una prueba de PaginaLocal
 * (tests/test_verificador_de_migraciones.py) falla si aparece un
 * Database/0NN_*.sql que no esté en ESPERADO.
 *
 * ## Uso
 *
 *   php Backend/scripts/verificar_migraciones.php     # sale con 1 si falta algo
 */

require_once __DIR__ . '/../autoload.php';

use App\Utils\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Lo que deja cada migración vive en App\Support\Migraciones::ESPERADO (desde la Beta 3.0,
// F3: el diagnóstico de «Pedir ayuda» también la usa).
use App\Support\Migraciones;

$pdo = Database::getInstance()->getConnection();
$problemas = Migraciones::problemas($pdo);
$faltan = 0;
foreach (array_keys(Migraciones::ESPERADO) as $numero) {
    $numero = (string) $numero;
    if (!empty($problemas[$numero])) {
        $faltan++;
        echo "✗ $numero: " . implode('; ', $problemas[$numero]) . "\n";
    } else {
        echo "✓ $numero\n";
    }
}

echo $faltan ? "\n$faltan migración(es) sin aplicar.\n" : "\nTodas las migraciones están aplicadas.\n";
exit($faltan ? 1 : 0);
