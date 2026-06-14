<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\RegistroVacunacion;
use PDO;

class RegistroVacunacionRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(RegistroVacunacion $entity): bool
    {
        $sql = "INSERT INTO registros_vacunacion 
                (fecha, vacunador, cliente, direccion, servicio, cantidad, costo_por_ave, total, estado) 
                VALUES 
                (:fecha, :vacunador, :cliente, :direccion, :servicio, :cantidad, :costo_por_ave, :total, :estado)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'fecha' => $entity->fecha,
            'vacunador' => $entity->vacunador,
            'cliente' => $entity->cliente,
            'direccion' => $entity->direccion,
            'servicio' => $entity->servicio,
            'cantidad' => $entity->cantidad,
            'costo_por_ave' => $entity->costoPorAve,
            'total' => $entity->total,
            'estado' => $entity->estado
        ]);
    }
}
