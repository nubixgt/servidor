<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\PadreEntity;
use PDO;

class PadreRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, nombre_completo, telefono, correo, direccion, departamento, fecha_registro
             FROM registros_padres
             ORDER BY fecha_registro DESC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => new PadreEntity(
            (int) $row['id'],
            $row['nombre_completo'],
            $row['telefono'],
            $row['correo'],
            $row['direccion'],
            $row['departamento'],
            $row['fecha_registro']
        ), $rows);
    }

    public function findById(int $id): ?PadreEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre_completo, telefono, correo, direccion, departamento, fecha_registro
             FROM registros_padres WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        return new PadreEntity(
            (int) $row['id'],
            $row['nombre_completo'],
            $row['telefono'],
            $row['correo'],
            $row['direccion'],
            $row['departamento'],
            $row['fecha_registro']
        );
    }

    public function findByCorreo(string $correo): ?PadreEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre_completo, telefono, correo, direccion, departamento, fecha_registro
             FROM registros_padres
             WHERE correo = :correo
             LIMIT 1"
        );
        $stmt->execute(['correo' => $correo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new PadreEntity(
            (int) $row['id'],
            $row['nombre_completo'],
            $row['telefono'],
            $row['correo'],
            $row['direccion'],
            $row['departamento'],
            $row['fecha_registro']
        );
    }

    public function create(PadreEntity $entity): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO registros_padres (nombre_completo, telefono, correo, direccion, departamento)
             VALUES (:nombre_completo, :telefono, :correo, :direccion, :departamento)"
        );

        $stmt->execute([
            'nombre_completo' => $entity->nombreCompleto,
            'telefono'        => $entity->telefono,
            'correo'          => $entity->correo,
            'direccion'       => $entity->direccion,
            'departamento'    => $entity->departamento,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(PadreEntity $entity): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE registros_padres
             SET nombre_completo = :nombre_completo,
                 telefono        = :telefono,
                 correo          = :correo,
                 direccion       = :direccion,
                 departamento    = :departamento
             WHERE id = :id"
        );

        return $stmt->execute([
            'id'              => $entity->id,
            'nombre_completo' => $entity->nombreCompleto,
            'telefono'        => $entity->telefono,
            'correo'          => $entity->correo,
            'direccion'       => $entity->direccion,
            'departamento'    => $entity->departamento,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM registros_padres WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
