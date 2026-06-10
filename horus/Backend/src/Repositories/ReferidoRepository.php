<?php
namespace App\Repositories;

use App\Entities\ReferidoEntity;
use App\Utils\Database;
use PDO;

class ReferidoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM referidos ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    public function findById(int $id): ?ReferidoEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM referidos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        return $this->mapRowToEntity($row);
    }

    public function create(ReferidoEntity $entity): int
    {
        $sql = "INSERT INTO referidos (
            nombre, dpi, telefono, direccion, numero_cuenta, banco, tipo_cuenta, 
            foto_perfil, dpi_anverso, dpi_reverso
        ) VALUES (
            :nombre, :dpi, :telefono, :direccion, :numero_cuenta, :banco, :tipo_cuenta,
            :foto_perfil, :dpi_anverso, :dpi_reverso
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $entity->nombre,
            'dpi' => $entity->dpi,
            'telefono' => $entity->telefono,
            'direccion' => $entity->direccion,
            'numero_cuenta' => $entity->numero_cuenta,
            'banco' => $entity->banco,
            'tipo_cuenta' => $entity->tipo_cuenta,
            'foto_perfil' => $entity->foto_perfil,
            'dpi_anverso' => $entity->dpi_anverso,
            'dpi_reverso' => $entity->dpi_reverso
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, ReferidoEntity $entity): void
    {
        $sql = "UPDATE referidos SET 
            nombre = :nombre, 
            dpi = :dpi, 
            telefono = :telefono, 
            direccion = :direccion, 
            numero_cuenta = :numero_cuenta, 
            banco = :banco, 
            tipo_cuenta = :tipo_cuenta,
            foto_perfil = :foto_perfil,
            dpi_anverso = :dpi_anverso,
            dpi_reverso = :dpi_reverso
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'nombre' => $entity->nombre,
            'dpi' => $entity->dpi,
            'telefono' => $entity->telefono,
            'direccion' => $entity->direccion,
            'numero_cuenta' => $entity->numero_cuenta,
            'banco' => $entity->banco,
            'tipo_cuenta' => $entity->tipo_cuenta,
            'foto_perfil' => $entity->foto_perfil,
            'dpi_anverso' => $entity->dpi_anverso,
            'dpi_reverso' => $entity->dpi_reverso
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM referidos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function mapRowToEntity(array $row): ReferidoEntity
    {
        return new ReferidoEntity(
            $row['id'],
            $row['nombre'],
            $row['dpi'],
            $row['telefono'],
            $row['direccion'],
            $row['numero_cuenta'],
            $row['banco'],
            $row['tipo_cuenta'],
            $row['foto_perfil'] ?? null,
            $row['dpi_anverso'] ?? null,
            $row['dpi_reverso'] ?? null
        );
    }
}
