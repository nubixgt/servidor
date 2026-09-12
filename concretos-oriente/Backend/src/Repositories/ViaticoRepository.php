<?php
namespace App\Repositories;

use App\Utils\Database;
use Exception;
use PDO;

class ViaticoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `viaticos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `personnel_id` int(10) UNSIGNED NOT NULL,
              `fecha_solicitud` date NOT NULL,
              `fecha_inicio` date DEFAULT NULL,
              `fecha_fin` date DEFAULT NULL,
              `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
              `motivo` varchar(255) NOT NULL,
              `estado` varchar(50) DEFAULT 'Pendiente',
              `observaciones` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `fk_viatico_personnel` (`personnel_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->pdo->exec($sql);
        } catch (Exception $e) {
            error_log('Error en auto-migración viaticos: ' . $e->getMessage());
        }
    }

    public function findAll(): array
    {
        $sql = "SELECT
                    v.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS empleado_nombre,
                    p.puesto AS empleado_puesto
                FROM viaticos v
                JOIN personnel p ON p.id = v.personnel_id
                ORDER BY v.fecha_solicitud DESC, v.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM viaticos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO viaticos
                    (personnel_id, fecha_solicitud, fecha_inicio, fecha_fin, monto, motivo, estado, observaciones)
                VALUES
                    (:personnel_id, :fecha_solicitud, :fecha_inicio, :fecha_fin, :monto, :motivo, :estado, :observaciones)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'personnel_id'    => $data['personnel_id'],
            'fecha_solicitud' => $data['fecha_solicitud'],
            'fecha_inicio'    => $data['fecha_inicio'] ?: null,
            'fecha_fin'       => $data['fecha_fin'] ?: null,
            'monto'           => $data['monto'],
            'motivo'          => $data['motivo'],
            'estado'          => $data['estado'] ?? 'Pendiente',
            'observaciones'   => $data['observaciones'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE viaticos SET
                    personnel_id = :personnel_id,
                    fecha_solicitud = :fecha_solicitud,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    monto = :monto,
                    motivo = :motivo,
                    estado = :estado,
                    observaciones = :observaciones
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'personnel_id'    => $data['personnel_id'],
            'fecha_solicitud' => $data['fecha_solicitud'],
            'fecha_inicio'    => $data['fecha_inicio'] ?: null,
            'fecha_fin'       => $data['fecha_fin'] ?: null,
            'monto'           => $data['monto'],
            'motivo'          => $data['motivo'],
            'estado'          => $data['estado'] ?? 'Pendiente',
            'observaciones'   => $data['observaciones'] ?? null,
            'id'              => $id
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM viaticos WHERE id = :id")->execute(['id' => $id]);
    }
}
