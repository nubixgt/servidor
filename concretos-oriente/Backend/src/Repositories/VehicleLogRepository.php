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
        $this->ensureColumnsExist();
    }

    private function ensureColumnsExist(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `vehicle_logs`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $newColumns = [
                'fecha' => 'DATE NULL',
                'proyecto_id' => 'INT NULL',
                'kilometraje_inicial' => 'DECIMAL(10,2) DEFAULT 0',
                'kilometraje_final' => 'DECIMAL(10,2) DEFAULT 0',
                'combustible_consumido' => 'DECIMAL(10,2) DEFAULT 0',
                'precio_renta' => 'DECIMAL(10,2) DEFAULT 0',
                'created_by' => 'INT NULL'
            ];

            foreach ($newColumns as $colName => $colType) {
                if (!in_array($colName, $columns)) {
                    try { $this->pdo->exec("ALTER TABLE `vehicle_logs` ADD COLUMN `$colName` $colType"); } catch (\Throwable $e) {}
                }
            }
        } catch (\Throwable $e) {}
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
            $whereClause = "WHERE vl.proyecto_id IN ($inQuery)";
            $params = $proyectos;
        }

        $sql = "SELECT
                    vl.*,
                    CONCAT(v.marca, ' ', v.modelo, ' [', v.placa, ']') AS vehiculo_nombre,
                    pr.nombre AS proyecto_nombre,
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre,
                    u.nombre AS creado_por_nombre
                FROM vehicle_logs vl
                LEFT JOIN vehicles v ON v.id = vl.vehiculo_id
                LEFT JOIN projects pr ON pr.id = vl.proyecto_id
                LEFT JOIN personnel p ON p.id = vl.piloto_id
                LEFT JOIN users u ON u.id = vl.created_by
                $whereClause
                ORDER BY vl.fecha DESC, vl.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            $sqlLog = "INSERT INTO vehicle_logs (
                           vehiculo_id, piloto_id, estatus_vehiculo, envio_servicio, reportar_averia, observaciones,
                           fecha, proyecto_id, kilometraje_inicial, kilometraje_final, combustible_consumido, precio_renta, created_by
                       ) VALUES (
                           :vehiculo_id, :piloto_id, :estatus_vehiculo, :envio_servicio, :reportar_averia, :observaciones,
                           :fecha, :proyecto_id, :kilometraje_inicial, :kilometraje_final, :combustible_consumido, :precio_renta, :created_by
                       )";
            
            $stmt = $this->pdo->prepare($sqlLog);
            $stmt->execute([
                ':vehiculo_id'           => $data['vehiculo_id'],
                ':piloto_id'             => $data['piloto_id'] ?? null,
                ':estatus_vehiculo'      => $data['estatus_vehiculo'] ?? 'Activo',
                ':envio_servicio'        => $data['envio_servicio'] ?? '',
                ':reportar_averia'       => $data['reportar_averia'] ?? '',
                ':observaciones'         => $data['observaciones'] ?? '',
                ':fecha'                 => $data['fecha'] ?? null,
                ':proyecto_id'           => $data['proyecto_id'] ?? null,
                ':kilometraje_inicial'   => $data['kilometraje_inicial'] ?? 0,
                ':kilometraje_final'     => $data['kilometraje_final'] ?? 0,
                ':combustible_consumido' => $data['combustible_consumido'] ?? 0,
                ':precio_renta'          => $data['precio_renta'] ?? 0,
                ':created_by'            => $data['created_by'] ?? null,
            ]);
            
            $logId = $this->pdo->lastInsertId();

            if (!empty($data['estatus_vehiculo']) || !empty($data['piloto_id'])) {
                $sqlUpdate = "UPDATE vehicles SET estatus = COALESCE(:estatus_vehiculo, estatus), piloto_id = COALESCE(:piloto_id, piloto_id) WHERE id = :vehiculo_id";
                $stmtUpdate = $this->pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':estatus_vehiculo' => $data['estatus_vehiculo'] ?? null,
                    ':piloto_id'        => $data['piloto_id'] ?? null,
                    ':vehiculo_id'      => $data['vehiculo_id']
                ]);
            }

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
