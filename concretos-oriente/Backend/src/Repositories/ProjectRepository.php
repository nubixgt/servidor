<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ProjectRepository
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

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO projects 
                    (codigo, nombre, cliente_id, ubicacion, coordenadas, presupuesto, 
                     fecha_inicio, fecha_fin_estimada, fecha_fin_real, estado, 
                     numero_contrato, descripcion, contactos, gerente_id) 
                VALUES 
                    (:codigo, :nombre, :cliente_id, :ubicacion, :coordenadas, :presupuesto, 
                     :fecha_inicio, :fecha_fin_estimada, :fecha_fin_real, :estado, 
                     :numero_contrato, :descripcion, :contactos, :gerente_id)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':codigo'             => $data['codigo'],
            ':nombre'             => $data['nombre'],
            ':cliente_id'         => $data['cliente_id'],
            ':ubicacion'          => $data['ubicacion'],
            ':coordenadas'        => $data['coordenadas'],
            ':presupuesto'        => $data['presupuesto'],
            ':fecha_inicio'       => $data['fecha_inicio'],
            ':fecha_fin_estimada' => $data['fecha_fin_estimada'],
            ':fecha_fin_real'     => $data['fecha_fin_real'],
            ':estado'             => $data['estado'],
            ':numero_contrato'    => $data['numero_contrato'],
            ':descripcion'        => $data['descripcion'],
            ':contactos'          => $data['contactos'],
            ':gerente_id'         => $data['gerente_id']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE projects SET 
                    codigo = :codigo, nombre = :nombre, cliente_id = :cliente_id, 
                    ubicacion = :ubicacion, coordenadas = :coordenadas, 
                    presupuesto = :presupuesto, fecha_inicio = :fecha_inicio, 
                    fecha_fin_estimada = :fecha_fin_estimada, fecha_fin_real = :fecha_fin_real, 
                    estado = :estado, numero_contrato = :numero_contrato, 
                    descripcion = :descripcion, contactos = :contactos, gerente_id = :gerente_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $data['id'] = $id;
        
        // Re-map keys with colons for PDO named parameters
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->pdo->prepare($sql)->execute($params);
    }

    public function updatePhoto(int $id, string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE projects SET foto = :foto WHERE id = :id")
             ->execute([':foto' => $fotoPath, ':id' => $id]);
    }

    public function updateDocuments(int $id, string $docsJson): void
    {
        $this->pdo->prepare("UPDATE projects SET contratos_archivos = :contratos_archivos WHERE id = :id")
             ->execute([':contratos_archivos' => $docsJson, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM projects WHERE id = :id")->execute([':id' => $id]);
    }
}
