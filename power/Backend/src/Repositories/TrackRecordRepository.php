<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * La copia en MySQL del expediente de un atleta (Database/034).
 *
 * El de verdad sigue siendo `track_record.jsonl` del AiService (y sus descartadas). Esto es su
 * copia línea a línea, con el texto exacto, para que entre en los respaldos de la base. Se
 * reemplaza entera en cada copia. Tolera que la migración no esté.
 */
class TrackRecordRepository
{
    private $pdo;
    private static ?bool $existe = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function disponible(): bool
    {
        if (self::$existe === null) {
            try {
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_track_record'")->fetch();
            } catch (\Throwable $e) {
                self::$existe = false;
            }
        }
        return self::$existe;
    }

    /**
     * Deja la copia del atleta igual que los dos ficheros. `$lineas` = ['expediente' => [texto…],
     * 'descartadas' => [texto…]], cada texto tal cual. En una transacción; nunca lanza.
     */
    public function reemplazar(int $userId, array $lineas): bool
    {
        if (!$this->disponible()) {
            return false;
        }
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare('DELETE FROM athlete_track_record WHERE user_id = :u')->execute(['u' => $userId]);
            $ins = $this->pdo->prepare(
                'INSERT INTO athlete_track_record (user_id, fichero, orden, week_from, week_to, linea)
                 VALUES (:u, :f, :o, :wf, :wt, :l)'
            );
            $fecha = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
            foreach (['expediente', 'descartadas'] as $fichero) {
                foreach (array_values((array) ($lineas[$fichero] ?? [])) as $i => $texto) {
                    if (!is_string($texto) || trim($texto) === '') {
                        continue;
                    }
                    $d = json_decode($texto, true);
                    $ins->execute([
                        'u' => $userId, 'f' => $fichero, 'o' => $i + 1,
                        'wf' => $fecha(is_array($d) ? ($d['from'] ?? null) : null),
                        'wt' => $fecha(is_array($d) ? ($d['to'] ?? null) : null),
                        'l' => $texto,
                    ]);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('TrackRecordRepository::reemplazar: ' . $e->getMessage());
            return false;
        }
    }

    /** Las líneas de la copia, en su orden: ['expediente' => [...], 'descartadas' => [...]]. */
    public function lineas(int $userId): array
    {
        $salida = ['expediente' => [], 'descartadas' => []];
        if (!$this->disponible()) {
            return $salida;
        }
        $q = $this->pdo->prepare('SELECT fichero, linea FROM athlete_track_record WHERE user_id = :u ORDER BY fichero, orden');
        $q->execute(['u' => $userId]);
        foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $f) {
            $salida[$f['fichero']][] = $f['linea'];
        }
        return $salida;
    }

    /**
     * Cómo salió cada semana CERRADA, por su primer día: sesiones hechas de las evaluadas (hechas, cambiadas o
     * sin la intensidad cuentan como hechas, igual que la racha de `progreso.py`), entrenos clave y horas.
     * Para el Historial (2026-10-21): enseñaba sólo lo planeado y un historial es también lo que hiciste.
     * Sin IA ni Garmin: es lo que el expediente ya guardó al cerrar la semana. Una semana abierta no sale.
     */
    public function resultados(int $userId): array
    {
        $salida = [];
        foreach ($this->lineas($userId)['expediente'] as $texto) {
            $w = json_decode($texto, true);
            if (!is_array($w) || empty($w['from'])) {
                continue;
            }
            $cerrada = false;
            foreach (['completed_sessions', 'modified_sessions', 'missed_sessions'] as $k) {
                if (isset($w[$k])) {
                    $cerrada = true;
                }
            }
            if (!$cerrada) {
                continue;
            }
            $hechas = (int) ($w['completed_sessions'] ?? 0) + (int) ($w['modified_sessions'] ?? 0)
                + (int) ($w['under_intensity_sessions'] ?? 0);
            // La última línea de una semana manda (una semana se vuelve a medir al completarla).
            $salida[(string) $w['from']] = [
                'hechas' => $hechas,
                'evaluadas' => $hechas + (int) ($w['missed_sessions'] ?? 0),
                'clave_hechas' => (int) ($w['quality_done'] ?? 0),
                'clave' => (int) ($w['quality_prescribed'] ?? 0),
                'horas' => round((float) ($w['executed_hours'] ?? 0), 1),
                'horas_plan' => round((float) ($w['prescribed_hours'] ?? 0), 1),
                // Una semana que todavía no termina se midió a mitad (al generar la siguiente) y se vuelve a
                // medir al acabar: no es un resultado (José, 2026-10-22: «3 de 3 hechas», en verde, el jueves).
                'en_curso' => !empty($w['to']) && (string) $w['to'] >= date('Y-m-d'),
            ];
        }
        return $salida;
    }
}
