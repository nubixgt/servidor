<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\RegistroMaquinaria;
use PDO;

class MaquinariaRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new registration and returns its ID
     */
    public function create(RegistroMaquinaria $registro): int|bool
    {
        $sql = "INSERT INTO registros_maquinaria 
                (operador, maquina_id, tipo_registro, valor_horometro, foto_horometro, latitud, longitud, usuario_id) 
                VALUES (:operador, :maquina_id, :tipo_registro, :valor_horometro, :foto_horometro, :latitud, :longitud, :usuario_id)";
        
        $stmt = $this->pdo->prepare($sql);
        
        $success = $stmt->execute([
            'operador' => $registro->operador,
            'maquina_id' => $registro->maquina_id,
            'tipo_registro' => $registro->tipo_registro,
            'valor_horometro' => $registro->valor_horometro,
            'foto_horometro' => $registro->foto_horometro,
            'latitud' => $registro->latitud,
            'longitud' => $registro->longitud,
            'usuario_id' => $registro->usuario_id
        ]);

        return $success ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Updates the photo path for a specific registration
     */
    public function updateFotoPath(int $id, string $path): bool
    {
        $sql = "UPDATE registros_maquinaria SET foto_horometro = :path WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['path' => $path, 'id' => $id]);
    }
}
