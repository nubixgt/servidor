<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class EstadisticaRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** Estadísticas denormalizadas de todos los jugadores activos. */
    public function jugadores(): array
    {
        return $this->pdo->query(
            "SELECT j.id, j.nombre_completo, j.dorsal, j.posicion, j.foto_ruta,
                    j.pj, j.ppg, j.rpg, j.apg, j.tres_pct, j.tl_pct, j.eff,
                    e.id AS equipo_id, e.nombre AS equipo_nombre
             FROM jugadores j
             LEFT JOIN equipos e ON e.id = j.equipo_id
             WHERE j.activo = 1
             ORDER BY j.ppg DESC, j.nombre_completo ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function jugadorExists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateJugadorStats(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE jugadores SET
                pj = :pj, ppg = :ppg, rpg = :rpg, apg = :apg,
                tres_pct = :tres_pct, tl_pct = :tl_pct, eff = :eff
             WHERE id = :id"
        );
        return $stmt->execute([
            'id' => $id,
            'pj' => (int) $d['pj'],
            'ppg' => (float) $d['ppg'],
            'rpg' => (float) $d['rpg'],
            'apg' => (float) $d['apg'],
            'tres_pct' => (float) $d['tres_pct'],
            'tl_pct' => (float) $d['tl_pct'],
            'eff' => (float) $d['eff'],
        ]);
    }

    /** Tabla de posiciones con nombre de equipo. */
    public function clasificacion(): array
    {
        return $this->pdo->query(
            "SELECT c.equipo_id, c.pj, c.pg, c.pp, c.pf, c.pc,
                    c.racha, c.puntos_liga, c.sancion,
                    e.nombre AS equipo_nombre
             FROM clasificacion c
             JOIN equipos e ON e.id = c.equipo_id
             WHERE e.activo = 1
             ORDER BY c.puntos_liga DESC, (c.pf - c.pc) DESC, e.nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clasificacionExists(int $equipoId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clasificacion WHERE equipo_id = :id");
        $stmt->execute(['id' => $equipoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateClasificacion(int $equipoId, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE clasificacion SET
                pj = :pj, pg = :pg, pp = :pp, pf = :pf, pc = :pc,
                racha = :racha, puntos_liga = :puntos_liga, sancion = :sancion
             WHERE equipo_id = :equipo_id"
        );
        return $stmt->execute([
            'equipo_id' => $equipoId,
            'pj' => (int) $d['pj'],
            'pg' => (int) $d['pg'],
            'pp' => (int) $d['pp'],
            'pf' => (int) $d['pf'],
            'pc' => (int) $d['pc'],
            'racha' => $d['racha'] !== '' ? $d['racha'] : null,
            'puntos_liga' => (int) $d['puntos_liga'],
            'sancion' => $d['sancion'] !== '' ? $d['sancion'] : null,
        ]);
    }

    /**
     * Recalcula pj/pg/pp/pf/pc de cada equipo a partir de los partidos
     * finalizados. No toca racha / puntos_liga / sancion (manuales).
     */
    public function recalcularClasificacion(): int
    {
        // Reset de columnas calculadas
        $this->pdo->exec("UPDATE clasificacion SET pj = 0, pg = 0, pp = 0, pf = 0, pc = 0");

        $rows = $this->pdo->query(
            "SELECT equipo_id,
                    COUNT(*) AS pj,
                    SUM(CASE WHEN pf > pc THEN 1 ELSE 0 END) AS pg,
                    SUM(CASE WHEN pf < pc THEN 1 ELSE 0 END) AS pp,
                    SUM(pf) AS pf, SUM(pc) AS pc
             FROM (
                SELECT equipo_local_id AS equipo_id, marcador_local AS pf, marcador_visitante AS pc
                FROM partidos WHERE estado = 'Finalizado'
                UNION ALL
                SELECT equipo_visitante_id AS equipo_id, marcador_visitante AS pf, marcador_local AS pc
                FROM partidos WHERE estado = 'Finalizado'
             ) t
             GROUP BY equipo_id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare(
            "UPDATE clasificacion SET pj = :pj, pg = :pg, pp = :pp, pf = :pf, pc = :pc
             WHERE equipo_id = :equipo_id"
        );
        foreach ($rows as $r) {
            $stmt->execute([
                'equipo_id' => $r['equipo_id'],
                'pj' => $r['pj'],
                'pg' => $r['pg'],
                'pp' => $r['pp'],
                'pf' => $r['pf'],
                'pc' => $r['pc'],
            ]);
        }

        return count($rows);
    }
}
