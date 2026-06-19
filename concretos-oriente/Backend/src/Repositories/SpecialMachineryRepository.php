<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SpecialMachineryRepository
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
                    sm.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS responsable_nombre
                FROM special_machinery sm
                LEFT JOIN personnel p ON p.id = sm.responsable_id
                ORDER BY sm.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM special_machinery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO special_machinery
                    (nombre, tipo_maquinaria, subtipo, marca, modelo, anio,
                     estado, ubicacion, responsable_id)
                VALUES
                    (:nombre, :tipo_maquinaria, :subtipo, :marca, :modelo, :anio,
                     :estado, :ubicacion, :responsable_id)";

        $this->pdo->prepare($sql)->execute([
            'nombre'          => $data['nombre'],
            'tipo_maquinaria' => $data['tipo_maquinaria'],
            'subtipo'         => $data['subtipo'],
            'marca'           => $data['marca'] ?: null,
            'modelo'          => $data['modelo'] ?: null,
            'anio'            => $data['anio'] ?: null,
            'estado'          => $data['estado'] ?? 'En Funcionamiento',
            'ubicacion'       => $data['ubicacion'] ?: null,
            'responsable_id'  => $data['responsable_id'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE special_machinery SET
                    nombre          = :nombre,
                    tipo_maquinaria = :tipo_maquinaria,
                    subtipo         = :subtipo,
                    marca           = :marca,
                    modelo          = :modelo,
                    anio            = :anio,
                    estado          = :estado,
                    ubicacion       = :ubicacion,
                    responsable_id  = :responsable_id
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'id'              => $id,
            'nombre'          => $data['nombre'],
            'tipo_maquinaria' => $data['tipo_maquinaria'],
            'subtipo'         => $data['subtipo'],
            'marca'           => $data['marca'] ?: null,
            'modelo'          => $data['modelo'] ?: null,
            'anio'            => $data['anio'] ?: null,
            'estado'          => $data['estado'] ?? 'En Funcionamiento',
            'ubicacion'       => $data['ubicacion'] ?: null,
            'responsable_id'  => $data['responsable_id'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_1', 'foto_2', 'foto_3', 'foto_4', 'foto_5'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $this->pdo->prepare(
            "UPDATE special_machinery SET " . implode(', ', $updates) . " WHERE id = :id"
        )->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM special_machinery WHERE id = :id")->execute(['id' => $id]);
    }
}
