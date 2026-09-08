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
        $this->ensureColumnsExist();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    private function ensureColumnsExist(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM machinery");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = strtolower($row['Field']);
            }

            $alters = [];
            if (!in_array('clasificacion_tipo', $columns)) {
                $alters[] = "ADD COLUMN clasificacion_tipo VARCHAR(50) DEFAULT 'Pesada'";
            }
            if (!in_array('no_factura', $columns)) {
                $alters[] = "ADD COLUMN no_factura VARCHAR(100) NULL";
            }
            if (!in_array('fecha_servicio', $columns)) {
                $alters[] = "ADD COLUMN fecha_servicio DATE NULL";
            }
            if (!in_array('seguro_contacto_nombre', $columns)) {
                $alters[] = "ADD COLUMN seguro_contacto_nombre VARCHAR(255) NULL";
            }
            if (!in_array('seguro_contacto_telefono', $columns)) {
                $alters[] = "ADD COLUMN seguro_contacto_telefono VARCHAR(50) NULL";
            }
            if (!in_array('seguro_aseguradora', $columns)) {
                $alters[] = "ADD COLUMN seguro_aseguradora VARCHAR(255) NULL";
            }
            if (!in_array('seguro_contrato_adjunto_path', $columns)) {
                $alters[] = "ADD COLUMN seguro_contrato_adjunto_path VARCHAR(255) NULL";
            }

            if (!empty($alters)) {
                $this->pdo->exec("ALTER TABLE machinery " . implode(', ', $alters));
            }
        } catch (\Exception $e) {
            error_log("Error in MachineryRepository::ensureColumnsExist: " . $e->getMessage());
        }
    }

    public function findAllWithDetails(?array $user = null): array
    {
        $whereClause = "";
        $params = [];

        if ($user && $user['role'] !== 'admin') {
            $proyectos = $user['proyectos'] ?? [];
            if (empty($proyectos)) {
                return [];
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
                    (clasificacion_tipo, categoria, codigo_interno, no_factura, marca, modelo, numero_serie, anio_fabricacion,
                     placa, horometro_actual, fecha_servicio, operador_id, proyecto_id, estado,
                     seguro_contacto_nombre, seguro_contacto_telefono, seguro_aseguradora,
                     costo_adquisicion, fecha_adquisicion, created_by)
                VALUES
                    (:clasificacion_tipo, :categoria, :codigo_interno, :no_factura, :marca, :modelo, :numero_serie, :anio_fabricacion,
                     :placa, :horometro_actual, :fecha_servicio, :operador_id, :proyecto_id, :estado,
                     :seguro_contacto_nombre, :seguro_contacto_telefono, :seguro_aseguradora,
                     :costo_adquisicion, :fecha_adquisicion, :created_by)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clasificacion_tipo'       => $data['clasificacion_tipo'] ?? 'Pesada',
            'categoria'                => $data['categoria'],
            'codigo_interno'           => $data['codigo_interno'],
            'no_factura'               => $data['no_factura'] ?? null,
            'marca'                    => $data['marca'],
            'modelo'                   => $data['modelo'],
            'numero_serie'             => $data['numero_serie'] ?? null,
            'anio_fabricacion'         => $data['anio_fabricacion'] ?? null,
            'placa'                    => $data['placa'] ?? null,
            'horometro_actual'         => $data['horometro_actual'] ?? 0,
            'fecha_servicio'           => $data['fecha_servicio'] ?? null,
            'operador_id'              => $data['operador_id'] ?? null,
            'proyecto_id'              => $data['proyecto_id'] ?? null,
            'estado'                   => $data['estado'] ?? 'Activo',
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?? null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?? null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?? null,
            'costo_adquisicion'        => $data['costo_adquisicion'] ?? null,
            'fecha_adquisicion'        => $data['fecha_adquisicion'] ?? null,
            'created_by'               => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE machinery SET
                    clasificacion_tipo       = :clasificacion_tipo,
                    categoria                = :categoria,
                    codigo_interno           = :codigo_interno,
                    no_factura               = :no_factura,
                    marca                    = :marca,
                    modelo                   = :modelo,
                    numero_serie             = :numero_serie,
                    anio_fabricacion         = :anio_fabricacion,
                    placa                    = :placa,
                    horometro_actual         = :horometro_actual,
                    fecha_servicio           = :fecha_servicio,
                    operador_id              = :operador_id,
                    proyecto_id              = :proyecto_id,
                    estado                   = :estado,
                    seguro_contacto_nombre   = :seguro_contacto_nombre,
                    seguro_contacto_telefono = :seguro_contacto_telefono,
                    seguro_aseguradora       = :seguro_aseguradora,
                    costo_adquisicion        = :costo_adquisicion,
                    fecha_adquisicion        = :fecha_adquisicion
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id'                       => $id,
            'clasificacion_tipo'       => $data['clasificacion_tipo'] ?? 'Pesada',
            'categoria'                => $data['categoria'],
            'codigo_interno'           => $data['codigo_interno'],
            'no_factura'               => $data['no_factura'] ?? null,
            'marca'                    => $data['marca'],
            'modelo'                   => $data['modelo'],
            'numero_serie'             => $data['numero_serie'] ?? null,
            'anio_fabricacion'         => $data['anio_fabricacion'] ?? null,
            'placa'                    => $data['placa'] ?? null,
            'horometro_actual'         => $data['horometro_actual'] ?? 0,
            'fecha_servicio'           => $data['fecha_servicio'] ?? null,
            'operador_id'              => $data['operador_id'] ?? null,
            'proyecto_id'              => $data['proyecto_id'] ?? null,
            'estado'                   => $data['estado'] ?? 'Activo',
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?? null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?? null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?? null,
            'costo_adquisicion'        => $data['costo_adquisicion'] ?? null,
            'fecha_adquisicion'        => $data['fecha_adquisicion'] ?? null,
        ]);
    }

    public function updatePhotoPath(int $id, ?string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE machinery SET foto_path = :fp WHERE id = :id")
             ->execute(['fp' => $fotoPath, 'id' => $id]);
    }

    public function updateDocumentPaths(int $id, array $paths): void
    {
        $sets = [];
        $params = ['id' => $id];
        foreach (['foto_path', 'seguro_contrato_adjunto_path'] as $field) {
            if (array_key_exists($field, $paths)) {
                $sets[] = "$field = :$field";
                $params[$field] = $paths[$field];
            }
        }
        if (!empty($sets)) {
            $sql = "UPDATE machinery SET " . implode(', ', $sets) . " WHERE id = :id";
            $this->pdo->prepare($sql)->execute($params);
        }
    }

    public function updateHorometroIfGreater(int $id, int $newHorometro): void
    {
        $this->pdo->prepare(
            "UPDATE machinery SET horometro_actual = :h1 WHERE id = :id AND horometro_actual < :h2"
        )->execute(['h1' => $newHorometro, 'h2' => $newHorometro, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM machinery WHERE id = :id")->execute(['id' => $id]);
    }
}
