<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class JugadorRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * $filters: equipo_id, posicion, estado, search, soloActivos (bool), page, perPage
     * Devuelve ['items' => [...], 'total' => int]
     */
    public function findAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['soloActivos'])) {
            $where[] = 'j.activo = 1';
        }
        if (!empty($filters['equipo_id'])) {
            $where[] = 'j.equipo_id = :equipo_id';
            $params['equipo_id'] = (int) $filters['equipo_id'];
        }
        if (!empty($filters['posicion'])) {
            $where[] = 'j.posicion = :posicion';
            $params['posicion'] = $filters['posicion'];
        }
        if (!empty($filters['estado'])) {
            $where[] = 'j.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(CONCAT_WS(' ', j.nombre_completo, j.dpi) LIKE :search OR CAST(j.dorsal AS CHAR) = :dorsal)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['dorsal'] = $filters['search'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->countWhere($whereSql, $params);

        $sql = "SELECT j.*, e.nombre AS equipo_nombre
                FROM jugadores j
                LEFT JOIN equipos e ON e.id = j.equipo_id
                $whereSql
                ORDER BY j.nombre_completo ASC";

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = (int) ($filters['perPage'] ?? 0);
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT $perPage OFFSET $offset";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }

    private function countWhere(string $whereSql, array $params): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM jugadores j LEFT JOIN equipos e ON e.id = j.equipo_id $whereSql");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT j.*, e.nombre AS equipo_nombre
             FROM jugadores j LEFT JOIN equipos e ON e.id = j.equipo_id
             WHERE j.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function dpiExists(string $dpi, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM jugadores WHERE dpi = :dpi";
        $params = ['dpi' => $dpi];
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO jugadores
                (equipo_id, nombre_completo, dorsal, dpi, fecha_nacimiento, nacionalidad,
                 posicion, estatura_cm, peso_kg, estado, ppg, rpg, apg, tres_pct)
             VALUES
                (:equipo_id, :nombre_completo, :dorsal, :dpi, :fecha_nacimiento, :nacionalidad,
                 :posicion, :estatura_cm, :peso_kg, :estado, :ppg, :rpg, :apg, :tres_pct)"
        );
        $stmt->execute($this->bindings($d));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE jugadores SET
                equipo_id = :equipo_id, nombre_completo = :nombre_completo, dorsal = :dorsal,
                dpi = :dpi, fecha_nacimiento = :fecha_nacimiento, nacionalidad = :nacionalidad,
                posicion = :posicion, estatura_cm = :estatura_cm, peso_kg = :peso_kg,
                estado = :estado, ppg = :ppg, rpg = :rpg, apg = :apg, tres_pct = :tres_pct
             WHERE id = :id"
        );
        return $stmt->execute($this->bindings($d) + ['id' => $id]);
    }

    public function setFoto(int $id, string $ruta): bool
    {
        return $this->pdo->prepare("UPDATE jugadores SET foto_ruta = :ruta WHERE id = :id")
            ->execute(['ruta' => $ruta, 'id' => $id]);
    }

    public function softDelete(int $id): bool
    {
        return $this->pdo->prepare("UPDATE jugadores SET activo = 0 WHERE id = :id")
            ->execute(['id' => $id]);
    }

    private function bindings(array $d): array
    {
        return [
            'equipo_id' => $d['equipo_id'] ?? null,
            'nombre_completo' => $d['nombre_completo'],
            'dorsal' => $d['dorsal'] ?? null,
            'dpi' => $d['dpi'] ?? null,
            'fecha_nacimiento' => $d['fecha_nacimiento'] ?? null,
            'nacionalidad' => $d['nacionalidad'] ?? 'Guatemalteca',
            'posicion' => $d['posicion'] ?? null,
            'estatura_cm' => $d['estatura_cm'] ?? null,
            'peso_kg' => $d['peso_kg'] ?? null,
            'estado' => $d['estado'] ?? 'Habilitado',
            'ppg' => $d['ppg'] ?? 0,
            'rpg' => $d['rpg'] ?? 0,
            'apg' => $d['apg'] ?? 0,
            'tres_pct' => $d['tres_pct'] ?? 0,
        ];
    }
}
