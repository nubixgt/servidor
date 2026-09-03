<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class PartidoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    private function baseSelect(): string
    {
        return "SELECT p.*,
                       el.nombre AS equipo_local, el.logo_ruta AS logo_local,
                       ev.nombre AS equipo_visitante, ev.logo_ruta AS logo_visitante
                FROM partidos p
                JOIN equipos el ON el.id = p.equipo_local_id
                JOIN equipos ev ON ev.id = p.equipo_visitante_id";
    }

    /** $filters: estado, jornada, fase, equipo_id */
    public function findAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[] = 'p.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (isset($filters['jornada']) && $filters['jornada'] !== '' && $filters['jornada'] !== null) {
            $where[] = 'p.jornada = :jornada';
            $params['jornada'] = (int) $filters['jornada'];
        }
        if (!empty($filters['fase'])) {
            $where[] = 'p.fase = :fase';
            $params['fase'] = $filters['fase'];
        }
        if (!empty($filters['equipo_id'])) {
            $where[] = '(p.equipo_local_id = :eq OR p.equipo_visitante_id = :eq2)';
            $params['eq'] = (int) $filters['equipo_id'];
            $params['eq2'] = (int) $filters['equipo_id'];
        }

        $sql = $this->baseSelect();
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY
                    FIELD(p.estado, 'En Vivo', 'Programado', 'Pospuesto', 'Finalizado'),
                    p.fecha ASC, p.hora ASC, p.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO partidos
                (jornada, fase, equipo_local_id, equipo_visitante_id, fecha, hora, sede,
                 estado, marcador_local, marcador_visitante, arbitro_principal, juez_mesa, acta_cerrada)
             VALUES
                (:jornada, :fase, :equipo_local_id, :equipo_visitante_id, :fecha, :hora, :sede,
                 :estado, :marcador_local, :marcador_visitante, :arbitro_principal, :juez_mesa, :acta_cerrada)"
        );
        $stmt->execute($this->bindings($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE partidos SET
                jornada = :jornada, fase = :fase, equipo_local_id = :equipo_local_id,
                equipo_visitante_id = :equipo_visitante_id, fecha = :fecha, hora = :hora, sede = :sede,
                estado = :estado, marcador_local = :marcador_local, marcador_visitante = :marcador_visitante,
                arbitro_principal = :arbitro_principal, juez_mesa = :juez_mesa, acta_cerrada = :acta_cerrada
             WHERE id = :id"
        );
        return $stmt->execute($this->bindings($d) + ['id' => $id]);
    }

    public function delete(int $id): bool
    {
        return $this->pdo->prepare("DELETE FROM partidos WHERE id = :id")->execute(['id' => $id]);
    }

    private function bindings(array $d): array
    {
        return [
            'jornada' => $d['jornada'] ?? null,
            'fase' => $d['fase'] ?? 'Regular',
            'equipo_local_id' => $d['equipo_local_id'],
            'equipo_visitante_id' => $d['equipo_visitante_id'],
            'fecha' => $d['fecha'] ?? null,
            'hora' => $d['hora'] ?? null,
            'sede' => $d['sede'] ?? null,
            'estado' => $d['estado'] ?? 'Programado',
            'marcador_local' => (int) ($d['marcador_local'] ?? 0),
            'marcador_visitante' => (int) ($d['marcador_visitante'] ?? 0),
            'arbitro_principal' => $d['arbitro_principal'] ?? null,
            'juez_mesa' => $d['juez_mesa'] ?? null,
            'acta_cerrada' => !empty($d['acta_cerrada']) ? 1 : 0,
        ];
    }
}
