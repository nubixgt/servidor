<?php
namespace App\Repositories;

use App\Utils\Database;
use Exception;
use PDO;

class MachineryLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAllWithDetails(?array $user = null): array
    {
        $whereClause = "";
        $params = [];

        if ($user && $user['role'] !== 'admin') {
            $proyectos = $user['proyectos'] ?? [];
            if (empty($proyectos)) {
                return [];
            }
            $inQuery = implode(',', array_fill(0, count($proyectos), '?'));
            $whereClause = "WHERE m.proyecto_id IN ($inQuery)";
            $params = $proyectos;
        }

        $sql = "SELECT
                    ml.*,
                    CONCAT(m.marca, ' ', m.modelo, ' [', m.codigo_interno, ']') AS maquina_nombre,
                    pr.nombre AS proyecto_nombre,
                    CONCAT(p.nombres, ' ', p.apellidos) AS operador_nombre,
                    u.nombre AS creado_por_nombre
                FROM machinery_log ml
                LEFT JOIN machinery  m  ON m.id  = ml.maquina_id
                LEFT JOIN projects   pr ON pr.id = ml.proyecto_id
                LEFT JOIN personnel  p  ON p.id  = ml.operador_id
                LEFT JOIN users u ON u.id = ml.created_by
                $whereClause
                ORDER BY ml.fecha DESC, ml.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM machinery_log WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO machinery_log
                    (maquina_id, proyecto_id, fecha, horometro_inicial, horometro_final,
                     combustible_consumido, observaciones, operador_id, created_by)
                VALUES
                    (:maquina_id, :proyecto_id, :fecha, :horometro_inicial, :horometro_final,
                     :combustible_consumido, :observaciones, :operador_id, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'maquina_id'           => $data['maquina_id'],
            'proyecto_id'          => $data['proyecto_id'] ?? null,
            'fecha'                => $data['fecha'],
            'horometro_inicial'    => $data['horometro_inicial'],
            'horometro_final'      => $data['horometro_final'],
            'combustible_consumido'=> $data['combustible_consumido'] ?? null,
            'observaciones'        => $data['observaciones'] ?? null,
            'operador_id'          => $data['operador_id'] ?? null,
            'created_by'           => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteByMachineryId(int $machineryId): void
    {
        $this->pdo->prepare("DELETE FROM machinery_log WHERE maquina_id = :id")->execute(['id' => $machineryId]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM machinery_log WHERE id = :id")->execute(['id' => $id]);
    }
}
