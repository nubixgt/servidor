<?php

namespace App\Repositories;

use App\Utils\Database;
use App\DTOs\PresupuestoDTO;
use PDO;

class PresupuestoRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function upsert(PresupuestoDTO $dto): bool {
        // As we only store one main budget for now, we use id = 1
        $query = "
            INSERT INTO presupuestos_sicoin (id, datos_json, fecha_actualizacion) 
            VALUES (1, :datos_json, NOW())
            ON DUPLICATE KEY UPDATE 
                datos_json = VALUES(datos_json),
                fecha_actualizacion = NOW()
        ";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':datos_json' => $dto->datosJson
        ]);
    }

    public function get(): ?string {
        $query = "SELECT datos_json FROM presupuestos_sicoin WHERE id = 1 LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['datos_json'] : null;
    }
}
