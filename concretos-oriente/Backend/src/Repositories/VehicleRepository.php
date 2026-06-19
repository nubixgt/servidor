<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class VehicleRepository
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

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    v.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre
                FROM vehicles v
                LEFT JOIN personnel p ON p.id = v.piloto_id
                ORDER BY v.id DESC";
        $stmt = $this->pdo->query($sql);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $logStmt = $this->pdo->query("SELECT * FROM vehicle_logs ORDER BY fecha_registro DESC");
        $allLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vehicles as &$vehicle) {
            $vehicle['history'] = [];
            foreach ($allLogs as $log) {
                if ($log['vehiculo_id'] == $vehicle['id']) {
                    $type = "Movimiento / Bitácora";
                    $description = "";

                    if (!empty($log['reportar_averia'])) {
                        $type = "Avería Reportada";
                        $description = $log['reportar_averia'];
                    } elseif (!empty($log['envio_servicio'])) {
                        $type = "Servicio/Mantenimiento";
                        $description = $log['envio_servicio'];
                    } elseif (!empty($log['observaciones'])) {
                        $type = "Observación";
                        $description = $log['observaciones'];
                    } else {
                        $description = "Cambio de estado a " . strtoupper($log['estatus_vehiculo']);
                    }

                    $vehicle['history'][] = [
                        'date'        => explode(' ', $log['fecha_registro'])[0],
                        'type'        => $type,
                        'description' => $description
                    ];
                }
            }
        }

        return $vehicles;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO vehicles
                    (placa, tipo_vehiculo, tipo_seguro, ubicacion, precio,
                     kilometraje, marca, modelo, piloto_id, estatus)
                VALUES
                    (:placa, :tipo_vehiculo, :tipo_seguro, :ubicacion, :precio,
                     :kilometraje, :marca, :modelo, :piloto_id, :estatus)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'placa'         => $data['placa'],
            'tipo_vehiculo' => $data['tipo_vehiculo'],
            'tipo_seguro'   => $data['tipo_seguro'] ?: null,
            'ubicacion'     => $data['ubicacion'] ?: null,
            'precio'        => $data['precio'] ?: null,
            'kilometraje'   => $data['kilometraje'] ?? 0,
            'marca'         => $data['marca'],
            'modelo'        => $data['modelo'],
            'piloto_id'     => $data['piloto_id'] ?: null,
            'estatus'       => $data['estatus'] ?? 'Nuevo',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE vehicles SET
                    placa         = :placa,
                    tipo_vehiculo = :tipo_vehiculo,
                    tipo_seguro   = :tipo_seguro,
                    ubicacion     = :ubicacion,
                    precio        = :precio,
                    kilometraje   = :kilometraje,
                    marca         = :marca,
                    modelo        = :modelo,
                    piloto_id     = :piloto_id,
                    estatus       = :estatus
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id'            => $id,
            'placa'         => $data['placa'],
            'tipo_vehiculo' => $data['tipo_vehiculo'],
            'tipo_seguro'   => $data['tipo_seguro'] ?: null,
            'ubicacion'     => $data['ubicacion'] ?: null,
            'precio'        => $data['precio'] ?: null,
            'kilometraje'   => $data['kilometraje'] ?? 0,
            'marca'         => $data['marca'],
            'modelo'        => $data['modelo'],
            'piloto_id'     => $data['piloto_id'] ?: null,
            'estatus'       => $data['estatus'] ?? 'Nuevo',
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_delantera', 'foto_trasera', 'foto_lateral1', 'foto_lateral2'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]       = "{$field} = :{$field}";
                $params[$field]  = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $sql = "UPDATE vehicles SET " . implode(', ', $updates) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM vehicles WHERE id = :id")->execute(['id' => $id]);
    }
}
