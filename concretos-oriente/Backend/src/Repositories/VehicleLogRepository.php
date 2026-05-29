<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;
use Exception;

class VehicleLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Inserción en vehicle_logs
            $sqlLog = "INSERT INTO vehicle_logs (vehiculo_id, piloto_id, estatus_vehiculo, envio_servicio, reportar_averia, observaciones) 
                       VALUES (:vehiculo_id, :piloto_id, :estatus_vehiculo, :envio_servicio, :reportar_averia, :observaciones)";
            
            $stmt = $this->pdo->prepare($sqlLog);
            $stmt->execute([
                ':vehiculo_id'      => $data['vehiculo_id'],
                ':piloto_id'        => $data['piloto_id'],
                ':estatus_vehiculo' => $data['estatus_vehiculo'],
                ':envio_servicio'   => $data['envio_servicio'],
                ':reportar_averia'  => $data['reportar_averia'],
                ':observaciones'    => $data['observaciones'],
            ]);
            
            $logId = $this->pdo->lastInsertId();

            // 2. Actualización automática en vehicles (estado y piloto)
            $sqlUpdate = "UPDATE vehicles SET estatus = :estatus_vehiculo, piloto_id = :piloto_id WHERE id = :vehiculo_id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':estatus_vehiculo' => $data['estatus_vehiculo'],
                ':piloto_id'        => $data['piloto_id'],
                ':vehiculo_id'      => $data['vehiculo_id']
            ]);

            $this->pdo->commit();
            return ['id' => $logId];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getLogsByVehicle(int $vehiculoId): array
    {
        $sql = "SELECT vl.*, p.nombres, p.apellidos 
                FROM vehicle_logs vl
                LEFT JOIN personnel p ON vl.piloto_id = p.id
                WHERE vl.vehiculo_id = :vehiculo_id
                ORDER BY vl.fecha_registro DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':vehiculo_id' => $vehiculoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
