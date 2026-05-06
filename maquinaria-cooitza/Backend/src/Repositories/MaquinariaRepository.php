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

    public function create(RegistroMaquinaria $registro): bool
    {
        $sql = "INSERT INTO registros_maquinaria 
                (operador, maquina_id, tipo_registro, valor_horometro, foto_horometro, latitud, longitud) 
                VALUES (:operador, :maquina_id, :tipo_registro, :valor_horometro, :foto_horometro, :latitud, :longitud)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            'operador' => $registro->operador,
            'maquina_id' => $registro->maquina_id,
            'tipo_registro' => $registro->tipo_registro,
            'valor_horometro' => $registro->valor_horometro,
            'foto_horometro' => $registro->foto_horometro,
            'latitud' => $registro->latitud,
            'longitud' => $registro->longitud
        ]);
    }
}
