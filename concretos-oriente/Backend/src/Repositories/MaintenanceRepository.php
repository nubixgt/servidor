<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class MaintenanceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getMachineryList(): array
    {
        $stmt = $this->pdo->query("SELECT id, codigo_interno, marca, modelo, categoria, horometro_actual FROM machinery ORDER BY codigo_interno ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllLogsWithDetails(): array
    {
        $stmt = $this->pdo->query("
            SELECT ml.*, m.codigo_interno, m.marca, m.modelo, p.nombres, p.apellidos 
            FROM maintenance_logs ml
            JOIN machinery m ON ml.machinery_id = m.id
            LEFT JOIN personnel p ON ml.responsable_id = p.id
            ORDER BY ml.fecha_mantenimiento DESC, ml.created_at DESC
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtParts = $this->pdo->prepare("SELECT * FROM maintenance_parts WHERE maintenance_log_id = :log_id");
        
        foreach ($logs as &$log) {
            $stmtParts->execute(['log_id' => $log['id']]);
            $log['repuestos'] = $stmtParts->fetchAll(PDO::FETCH_ASSOC);
            $log['fotos'] = !empty($log['path_fotos']) ? json_decode($log['path_fotos'], true) : [];
        }
        
        return $logs;
    }

    public function createLog(array $data): int
    {
        $sql = "INSERT INTO maintenance_logs (
                    machinery_id, tipo_mantenimiento, fecha_mantenimiento, descripcion, 
                    costo_total, responsable_id, proximo_mantenimiento, horometro_servicio, observaciones, latitud, longitud
                ) VALUES (
                    :machinery_id, :tipo_mantenimiento, :fecha_mantenimiento, :descripcion, 
                    :costo_total, :responsable_id, :proximo_mantenimiento, :horometro_servicio, :observaciones, :latitud, :longitud
                )";
        
        $this->pdo->prepare($sql)->execute([
            'machinery_id'          => $data['machinery_id'],
            'tipo_mantenimiento'    => $data['tipo_mantenimiento'] ?? 'Preventivo',
            'fecha_mantenimiento'   => $data['fecha_mantenimiento'],
            'descripcion'           => $data['descripcion'],
            'costo_total'           => $data['costo_total'] ?? 0.0,
            'responsable_id'        => $data['responsable_id'] ?? null,
            'proximo_mantenimiento' => $data['proximo_mantenimiento'] ?? null,
            'horometro_servicio'    => $data['horometro_servicio'] ?? 0.0,
            'observaciones'         => $data['observaciones'] ?? null,
            'latitud'               => $data['latitud'] ?? null,
            'longitud'              => $data['longitud'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createPart(int $logId, array $part): void
    {
        $sql = "INSERT INTO maintenance_parts (maintenance_log_id, nombre_repuesto, cantidad, costo_unitario) 
                VALUES (:log_id, :nombre_repuesto, :cantidad, :costo_unitario)";
        $this->pdo->prepare($sql)->execute([
            'log_id'          => $logId,
            'nombre_repuesto' => $part['nombre'],
            'cantidad'        => $part['cantidad'],
            'costo_unitario'  => $part['costo_unitario']
        ]);
    }

    public function updatePhotos(int $logId, string $jsonPaths): void
    {
        $this->pdo->prepare("UPDATE maintenance_logs SET path_fotos = :fotos WHERE id = :id")
             ->execute(['fotos' => $jsonPaths, 'id' => $logId]);
    }

    public function updateMachineryHorometer(int $machineryId, float $horometer): void
    {
        $this->pdo->prepare("UPDATE machinery SET horometro_actual = GREATEST(horometro_actual, :horometro) WHERE id = :id")
             ->execute(['horometro' => $horometer, 'id' => $machineryId]);
    }
}
