<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class FuelRecordRepository
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
                    fr.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre,
                    pr.nombre AS proyecto_nombre
                FROM fuel_records fr
                LEFT JOIN personnel p  ON p.id  = fr.piloto_id
                LEFT JOIN projects  pr ON pr.id = fr.proyecto_id
                ORDER BY fr.fecha DESC, fr.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fuel_records WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAllPlates(): array
    {
        $sql = "
            SELECT placa, 'Vehiculo' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM vehicles WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Transporte Pesado' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM heavy_transport WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Maquinaria' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM machinery WHERE placa IS NOT NULL AND placa != ''

            ORDER BY placa ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO fuel_records
                    (fecha, piloto_id, placa, tipo_unidad, proyecto_id,
                     cantidad_galones, monto, kilometraje, horometro)
                VALUES
                    (:fecha, :piloto_id, :placa, :tipo_unidad, :proyecto_id,
                     :cantidad_galones, :monto, :kilometraje, :horometro)";

        $this->pdo->prepare($sql)->execute([
            'fecha'           => $data['fecha'],
            'piloto_id'       => $data['piloto_id'] ?: null,
            'placa'           => $data['placa'],
            'tipo_unidad'     => $data['tipo_unidad'],
            'proyecto_id'     => $data['proyecto_id'] ?: null,
            'cantidad_galones'=> $data['cantidad_galones'],
            'monto'           => $data['monto'],
            'kilometraje'     => $data['kilometraje'] ?: null,
            'horometro'       => $data['horometro'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE fuel_records SET
                    fecha            = :fecha,
                    piloto_id        = :piloto_id,
                    placa            = :placa,
                    tipo_unidad      = :tipo_unidad,
                    proyecto_id      = :proyecto_id,
                    cantidad_galones = :cantidad_galones,
                    monto            = :monto,
                    kilometraje      = :kilometraje,
                    horometro        = :horometro
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'id'              => $id,
            'fecha'           => $data['fecha'],
            'piloto_id'       => $data['piloto_id'] ?: null,
            'placa'           => $data['placa'],
            'tipo_unidad'     => $data['tipo_unidad'],
            'proyecto_id'     => $data['proyecto_id'] ?: null,
            'cantidad_galones'=> $data['cantidad_galones'],
            'monto'           => $data['monto'],
            'kilometraje'     => $data['kilometraje'] ?: null,
            'horometro'       => $data['horometro'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_1', 'foto_2'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $this->pdo->prepare(
            "UPDATE fuel_records SET " . implode(', ', $updates) . " WHERE id = :id"
        )->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM fuel_records WHERE id = :id")->execute(['id' => $id]);
    }
}
