<?php
namespace App\Utils;

use App\Core\HttpException;

/**
 * Límite de peticiones por minuto, guardado en seg_limite (protege el login y las búsquedas masivas).
 */
class RateLimiter
{
    public static function hit(string $clave, int $porMinuto): void
    {
        $db = Database::getInstance();
        $ventana = intdiv(time(), 60);
        $db->execute('INSERT INTO seg_limite(clave, ventana, n) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE n = n + 1', [$clave, $ventana]);
        $n = (int) $db->scalar('SELECT n FROM seg_limite WHERE clave = ? AND ventana = ?', [$clave, $ventana]);
        if (random_int(1, 100) === 1) {
            $db->execute('DELETE FROM seg_limite WHERE ventana < ?', [$ventana - 5]);
        }
        if ($n > $porMinuto) {
            throw new HttpException('Demasiadas consultas seguidas. Espere un minuto e intente de nuevo.', 429);
        }
    }
}
