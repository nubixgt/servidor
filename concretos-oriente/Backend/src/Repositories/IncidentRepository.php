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
    }

    public function findAllWithPersonnel(): array
    {
        $sql = "SELECT
                    i.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS empleado_nombre
                FROM employee_incidents i
                JOIN personnel p ON p.id = i.personnel_id
                ORDER BY i.fecha DESC, i.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_incidents WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_incidents (personnel_id, texto, fecha, motivo)
                VALUES (:personnel_id, :texto, :fecha, :motivo)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'personnel_id' => $data['personnel_id'],
            'texto'        => $data['texto'],
            'fecha'        => $data['fecha'],
            'motivo'       => $data['motivo'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM employee_incidents WHERE id = :id")->execute(['id' => $id]);
    }
}
