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

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM registros_vacunacion ORDER BY fecha DESC, id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $estado): bool
    {
        $sql = "UPDATE registros_vacunacion SET estado = :estado WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM registros_vacunacion WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function update(int $id, RegistroVacunacion $entity): bool
    {
        $sql = "UPDATE registros_vacunacion 
                SET fecha = :fecha, vacunador = :vacunador, cliente = :cliente, 
                    direccion = :direccion, servicio = :servicio, cantidad = :cantidad, 
                    costo_por_ave = :costo_por_ave, total = :total
                WHERE id = :id";

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
            'id' => $id
        ]);
    }
}
