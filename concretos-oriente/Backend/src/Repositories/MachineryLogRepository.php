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

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    ml.*,
                    CONCAT(m.marca, ' ', m.modelo, ' [', m.codigo_interno, ']') AS maquina_nombre,
                    pr.nombre AS proyecto_nombre,
                    CONCAT(p.nombres, ' ', p.apellidos) AS operador_nombre
                FROM machinery_log ml
                LEFT JOIN machinery  m  ON m.id  = ml.maquina_id
                LEFT JOIN projects   pr ON pr.id = ml.proyecto_id
                LEFT JOIN personnel  p  ON p.id  = ml.operador_id
                ORDER BY ml.fecha DESC, ml.id DESC";

        $stmt = $this->pdo->query($sql);
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
                     combustible_consumido, observaciones, operador_id)
                VALUES
                    (:maquina_id, :proyecto_id, :fecha, :horometro_inicial, :horometro_final,
                     :combustible_consumido, :observaciones, :operador_id)";

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
