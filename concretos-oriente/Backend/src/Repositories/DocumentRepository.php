<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class DocumentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT d.*, p.nombre as proyecto_nombre 
            FROM digital_documents d
            LEFT JOIN projects p ON d.project_id = p.id
            ORDER BY d.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO digital_documents (
                    tipo_documento, project_id, modulo_relacionado, nombre_documento, 
                    etiquetas, archivo_path, tipo_archivo, peso_archivo
                ) VALUES (
                    :tipo_documento, :project_id, :modulo_relacionado, :nombre_documento, 
                    :etiquetas, :archivo_path, :tipo_archivo, :peso_archivo
                )";
        
        $this->pdo->prepare($sql)->execute([
            'tipo_documento'     => $data['tipo_documento'],
            'project_id'         => $data['project_id'] ?? null,
            'modulo_relacionado' => $data['modulo_relacionado'] ?? null,
            'nombre_documento'   => $data['nombre_documento'],
            'etiquetas'          => $data['etiquetas'] ?? null,
            'archivo_path'       => $data['archivo_path'],
            'tipo_archivo'       => $data['tipo_archivo'],
            'peso_archivo'       => $data['peso_archivo']
        ]);
    }
}
