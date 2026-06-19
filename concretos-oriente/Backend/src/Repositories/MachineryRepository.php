<?php
namespace App\Repositories;

use App\Utils\Database;
use Exception;
use PDO;

class MachineryRepository
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

    public function findAllWithDetails(?array $user = null): array
    {
        $whereClause = "";
        $params = [];

        if ($user && $user['role'] !== 'admin') {
            $proyectos = $user['proyectos'] ?? [];
            if (empty($proyectos)) {
                return []; // Si no tiene proyectos asignados, no ve nada
            }
            $inQuery = implode(',', array_fill(0, count($proyectos), '?'));
            $whereClause = "WHERE m.proyecto_id IN ($inQuery)";
            $params = $proyectos;
        }

        $sql = "SELECT
                    m.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS operador_nombre,
                    pr.nombre AS proyecto_nombre,
                    u.nombre AS creado_por_nombre
                FROM machinery m
                LEFT JOIN personnel p  ON p.id  = m.operador_id
                LEFT JOIN projects  pr ON pr.id = m.proyecto_id
                LEFT JOIN users u ON u.id = m.created_by
                $whereClause
                ORDER BY m.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM machinery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO machinery
                    (categoria, codigo_interno, marca, modelo, numero_serie, anio_fabricacion,
                     placa, horometro_actual, operador_id, proyecto_id, estado,
                     costo_adquisicion, fecha_adquisicion, created_by)
                VALUES
                    (:categoria, :codigo_interno, :marca, :modelo, :numero_serie, :anio_fabricacion,
                     :placa, :horometro_actual, :operador_id, :proyecto_id, :estado,
                     :costo_adquisicion, :fecha_adquisicion, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'categoria'        => $data['categoria'],
            'codigo_interno'   => $data['codigo_interno'],
            'marca'            => $data['marca'],
            'modelo'           => $data['modelo'],
            'numero_serie'     => $data['numero_serie'] ?? null,
            'anio_fabricacion' => $data['anio_fabricacion'] ?? null,
            'placa'            => $data['placa'] ?? null,
            'horometro_actual' => $data['horometro_actual'],
            'operador_id'      => $data['operador_id'] ?? null,
            'proyecto_id'      => $data['proyecto_id'] ?? null,
            'estado'           => $data['estado'],
            'costo_adquisicion'=> $data['costo_adquisicion'] ?? null,
            'fecha_adquisicion'=> $data['fecha_adquisicion'] ?? null,
            'created_by'       => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE machinery SET
                    categoria         = :categoria,
                    codigo_interno    = :codigo_interno,
                    marca             = :marca,
                    modelo            = :modelo,
                    numero_serie      = :numero_serie,
                    anio_fabricacion  = :anio_fabricacion,
                    placa             = :placa,
                    horometro_actual  = :horometro_actual,
                    operador_id       = :operador_id,
                    proyecto_id       = :proyecto_id,
                    estado            = :estado,
                    costo_adquisicion = :costo_adquisicion,
                    fecha_adquisicion = :fecha_adquisicion
                WHERE id = :id";

        $data['id'] = $id;
        $this->pdo->prepare($sql)->execute($data);
    }

    public function updatePhotoPath(int $id, ?string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE machinery SET foto_path = :fp WHERE id = :id")
             ->execute(['fp' => $fotoPath, 'id' => $id]);
    }

    public function updateHorometroIfGreater(int $id, int $newHorometro): void
    {
        $this->pdo->prepare(
            "UPDATE machinery SET horometro_actual = :h1 WHERE id = :id AND horometro_actual < :h2"
        )->execute(['h1' => $newHorometro, 'h2' => $newHorometro, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        // El controlador anterior eliminaba las bitácoras relacionadas primero. 
        // Aunque esto se puede hacer en el servicio usando MachineryLogRepository, 
        // también es válido hacerlo como una transacción en el servicio.
        $this->pdo->prepare("DELETE FROM machinery WHERE id = :id")->execute(['id' => $id]);
    }
}
