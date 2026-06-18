<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ConcreteTripRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT ct.*, 
                       p.nombre as proyecto_nombre, 
                       v.placa as placa, 
                       CONCAT(per.nombres, ' ', per.apellidos) as piloto_nombre 
                FROM concrete_trips ct
                LEFT JOIN projects p ON ct.proyecto_id = p.id
                LEFT JOIN vehicles v ON ct.vehiculo_id = v.id
                LEFT JOIN personnel per ON ct.piloto_id = per.id
                ORDER BY ct.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM concrete_trips WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO concrete_trips (
                    proyecto_id, vehiculo_id, piloto_id, created_by,
                    m3_arena, m3_piedrin, m3_cemento,
                    hora_planta, lat_planta, lng_planta, estado
                ) VALUES (
                    :proyecto_id, :vehiculo_id, :piloto_id, :created_by,
                    :m3_arena, :m3_piedrin, :m3_cemento,
                    :hora_planta, :lat_planta, :lng_planta, 2
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'proyecto_id' => $data['proyecto_id'],
            'vehiculo_id' => $data['vehiculo_id'],
            'piloto_id'   => $data['piloto_id'],
            'created_by'  => $data['created_by'],
            'm3_arena'    => !empty($data['m3_arena'])   ? (float)$data['m3_arena']   : null,
            'm3_piedrin'  => !empty($data['m3_piedrin']) ? (float)$data['m3_piedrin'] : null,
            'm3_cemento'  => !empty($data['m3_cemento']) ? (float)$data['m3_cemento'] : null,
            'hora_planta' => date('H:i:s'),
            'lat_planta'  => $data['lat_planta'] ?? null,
            'lng_planta'  => $data['lng_planta'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePilotStep(int $id, array $data): void
    {
        $sql = "UPDATE concrete_trips SET 
                    hora_salida = :hora_salida,
                    hora_llegada = :hora_llegada,
                    lat_piloto = :lat_piloto,
                    lng_piloto = :lng_piloto,
                    estado = 3
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'hora_salida' => $data['hora_salida'],
            'hora_llegada' => $data['hora_llegada'],
            'lat_piloto' => $data['lat_piloto'] ?? null,
            'lng_piloto' => $data['lng_piloto'] ?? null
        ]);
    }

    public function updatePlacementStep(int $id, array $data): void
    {
        $sql = "UPDATE concrete_trips SET 
                    tipo_concreto = :tipo_concreto,
                    hora_llegada_obra = :hora_llegada_obra,
                    lat_colocacion = :lat_colocacion,
                    lng_colocacion = :lng_colocacion,
                    estado = 4
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'tipo_concreto' => $data['tipo_concreto'],
            'hora_llegada_obra' => $data['hora_llegada_obra'],
            'lat_colocacion' => $data['lat_colocacion'] ?? null,
            'lng_colocacion' => $data['lng_colocacion'] ?? null
        ]);
    }
}
