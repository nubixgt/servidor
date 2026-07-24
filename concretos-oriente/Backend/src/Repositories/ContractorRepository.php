<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ContractorRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM contractors ORDER BY nombre ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contractors WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO contractors (nombre, telefono, correo_electronico)
                VALUES (:nombre, :telefono, :correo_electronico)";

        $this->pdo->prepare($sql)->execute([
            'nombre'             => $data['nombre'],
            'telefono'           => $data['telefono'] ?? null,
            'correo_electronico' => $data['correo_electronico'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE contractors SET
                    nombre = :nombre,
                    telefono = :telefono,
                    correo_electronico = :correo_electronico
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'nombre'             => $data['nombre'],
            'telefono'           => $data['telefono'] ?? null,
            'correo_electronico' => $data['correo_electronico'] ?? null,
            'id'                 => $id
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM contractors WHERE id = :id")->execute(['id' => $id]);
    }

    public function getProjectAssignments(int $contractorId): array
    {
        $sql = "SELECT pc.*, p.nombre as proyecto_nombre
                FROM project_contractors pc
                JOIN projects p ON pc.project_id = p.id
                WHERE pc.contractor_id = :contractor_id
                ORDER BY pc.fecha_asignacion ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['contractor_id' => $contractorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentsByContractor(int $contractorId): array
    {
        $sql = "SELECT e.id, e.proyecto_id, p.nombre as proyecto_nombre, e.fecha_egreso,
                       e.numero_cheque, e.cuenta_origen, e.monto, e.descripcion
                FROM expenses e
                LEFT JOIN projects p ON e.proyecto_id = p.id
                WHERE e.contratista_id = :contractor_id AND e.tipo_egreso = 'Contratista'
                ORDER BY e.fecha_egreso ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['contractor_id' => $contractorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignProject(int $contractorId, int $projectId, float $montoContratado, string $fechaAsignacion, ?string $observaciones): void
    {
        $sql = "INSERT INTO project_contractors (project_id, contractor_id, monto_contratado, fecha_asignacion, observaciones)
                VALUES (:project_id, :contractor_id, :monto_contratado, :fecha_asignacion, :observaciones)
                ON DUPLICATE KEY UPDATE
                    monto_contratado = VALUES(monto_contratado),
                    fecha_asignacion = VALUES(fecha_asignacion),
                    observaciones = VALUES(observaciones),
                    updated_at = CURRENT_TIMESTAMP";

        $this->pdo->prepare($sql)->execute([
            'project_id'       => $projectId,
            'contractor_id'    => $contractorId,
            'monto_contratado' => $montoContratado,
            'fecha_asignacion' => $fechaAsignacion,
            'observaciones'    => $observaciones
        ]);
    }

    public function removeAssignment(int $contractorId, int $projectId): void
    {
        $sql = "DELETE FROM project_contractors WHERE contractor_id = :contractor_id AND project_id = :project_id";
        $this->pdo->prepare($sql)->execute([
            'contractor_id' => $contractorId,
            'project_id'    => $projectId
        ]);
    }
}
