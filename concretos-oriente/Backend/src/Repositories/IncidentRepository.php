<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class IncidentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureColumnsExist();
    }

    private function ensureColumnsExist(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM employee_incidents LIKE 'adjunto_path'");
            if ($stmt && $stmt->fetch() === false) {
                $this->pdo->exec("ALTER TABLE `employee_incidents` ADD COLUMN `adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `motivo`");
            }
        } catch (\Exception $e) {
            error_log('Error en auto-migración incidents: ' . $e->getMessage());
        }
    }

    public function findAllWithPersonnel(): array
    {
        $sql = "SELECT
                    i.*,
                    p.nombres,
                    p.apellidos,
                    p.puesto,
                    p.foto_path,
                    TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, ''))) AS empleado_nombre,
                    COALESCE(p.puesto, 'Colaborador') AS empleado_puesto,
                    p.foto_path AS empleado_foto
                FROM employee_incidents i
                LEFT JOIN personnel p ON p.id = i.personnel_id
                ORDER BY i.fecha DESC, i.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT
                    i.*,
                    p.nombres,
                    p.apellidos,
                    p.puesto,
                    p.foto_path,
                    TRIM(CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.apellidos, ''))) AS empleado_nombre,
                    COALESCE(p.puesto, 'Colaborador') AS empleado_puesto,
                    p.foto_path AS empleado_foto
                FROM employee_incidents i
                LEFT JOIN personnel p ON p.id = i.personnel_id
                WHERE i.id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_incidents (personnel_id, texto, fecha, motivo, adjunto_path)
                VALUES (:personnel_id, :texto, :fecha, :motivo, :adjunto_path)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'personnel_id' => $data['personnel_id'],
            'texto'        => $data['texto'],
            'fecha'        => $data['fecha'],
            'motivo'       => $data['motivo'],
            'adjunto_path' => $data['adjunto_path'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateAdjuntoPath(int $id, ?string $path): void
    {
        $this->pdo->prepare("UPDATE employee_incidents SET adjunto_path = :path WHERE id = :id")
             ->execute(['path' => $path, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM employee_incidents WHERE id = :id")->execute(['id' => $id]);
    }
}
