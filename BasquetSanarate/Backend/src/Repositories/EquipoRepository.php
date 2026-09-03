<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class EquipoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Lista equipos con datos de clasificación y conteo de jugadores.
     * $filters: conferencia, rama, search, soloActivos (bool)
     */
    public function findAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['soloActivos'])) {
            $where[] = 'e.activo = 1';
        }
        if (!empty($filters['conferencia'])) {
            $where[] = 'e.conferencia = :conferencia';
            $params['conferencia'] = $filters['conferencia'];
        }
        if (!empty($filters['rama'])) {
            $where[] = 'e.rama = :rama';
            $params['rama'] = $filters['rama'];
        }
        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', e.nombre, e.sede, e.director_tecnico) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT e.*, " . self::clasificacionSelect() . ",
                       (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.activo = 1) AS jugadores_count
                FROM equipos e
                LEFT JOIN clasificacion c ON c.equipo_id = e.id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.puntos_liga DESC, (c.pf - c.pc) DESC, e.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT e.*, " . self::clasificacionSelect() . ",
                       (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.activo = 1) AS jugadores_count
                FROM equipos e
                LEFT JOIN clasificacion c ON c.equipo_id = e.id
                WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO equipos (nombre, sede, conferencia, rama, director_tecnico, telefono_delegado, color_hex)
             VALUES (:nombre, :sede, :conferencia, :rama, :director_tecnico, :telefono_delegado, :color_hex)"
        );
        $stmt->execute([
            'nombre' => $data['nombre'],
            'sede' => $data['sede'],
            'conferencia' => $data['conferencia'],
            'rama' => $data['rama'],
            'director_tecnico' => $data['director_tecnico'],
            'telefono_delegado' => $data['telefono_delegado'],
            'color_hex' => $data['color_hex'],
        ]);
        $id = (int) $this->pdo->lastInsertId();

        // Fila de clasificación asociada
        $this->pdo->prepare("INSERT INTO clasificacion (equipo_id) VALUES (:id)")
            ->execute(['id' => $id]);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE equipos SET nombre = :nombre, sede = :sede, conferencia = :conferencia,
                    rama = :rama, director_tecnico = :director_tecnico,
                    telefono_delegado = :telefono_delegado, color_hex = :color_hex
             WHERE id = :id"
        );
        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'sede' => $data['sede'],
            'conferencia' => $data['conferencia'],
            'rama' => $data['rama'],
            'director_tecnico' => $data['director_tecnico'],
            'telefono_delegado' => $data['telefono_delegado'],
            'color_hex' => $data['color_hex'],
        ]);
    }

    public function setLogo(int $id, string $ruta): bool
    {
        return $this->pdo->prepare("UPDATE equipos SET logo_ruta = :ruta WHERE id = :id")
            ->execute(['ruta' => $ruta, 'id' => $id]);
    }

    public function softDelete(int $id): bool
    {
        return $this->pdo->prepare("UPDATE equipos SET activo = 0 WHERE id = :id")
            ->execute(['id' => $id]);
    }

    public function hardDelete(int $id): bool
    {
        return $this->pdo->prepare("DELETE FROM equipos WHERE id = :id")
            ->execute(['id' => $id]);
    }

    public function countPartidos(int $id): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM partidos WHERE equipo_local_id = :local OR equipo_visitante_id = :visita"
        );
        $stmt->execute(['local' => $id, 'visita' => $id]);
        return (int) $stmt->fetchColumn();
    }

    private static function clasificacionSelect(): string
    {
        return "COALESCE(c.pj,0) AS pj, COALESCE(c.pg,0) AS pg, COALESCE(c.pp,0) AS pp,
                COALESCE(c.pf,0) AS pf, COALESCE(c.pc,0) AS pc,
                c.racha AS racha, COALESCE(c.puntos_liga,0) AS puntos_liga, c.sancion AS sancion";
    }
}
