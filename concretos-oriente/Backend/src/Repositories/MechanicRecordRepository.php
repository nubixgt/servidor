<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class MechanicRecordRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->autoMigrate();
    }

    private function autoMigrate(): void
    {
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM mechanic_records")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('tipo_trabajo', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN tipo_trabajo VARCHAR(255) NULL AFTER tipo_unidad");
            }
            if (!in_array('mano_obra_proveedor_id', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN mano_obra_proveedor_id INT NULL");
            }
            if (!in_array('mano_obra_proveedor_nombre', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN mano_obra_proveedor_nombre VARCHAR(255) NULL");
            }
            if (!in_array('mano_obra_monto', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN mano_obra_monto DECIMAL(10,2) NULL DEFAULT 0");
            }
            if (!in_array('mano_obra_factura', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN mano_obra_factura VARCHAR(255) NULL");
            }
            if (!in_array('observaciones', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN observaciones TEXT NULL");
            }
            if (!in_array('proximo_servicio_km', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN proximo_servicio_km DECIMAL(10,2) NULL");
            }
            if (!in_array('proximo_servicio_hrs', $cols)) {
                $this->pdo->exec("ALTER TABLE mechanic_records ADD COLUMN proximo_servicio_hrs DECIMAL(10,2) NULL");
            }

            $itemCols = $this->pdo->query("SHOW COLUMNS FROM mechanic_record_items")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('proveedor_id', $itemCols)) {
                $this->pdo->exec("ALTER TABLE mechanic_record_items ADD COLUMN proveedor_id INT NULL");
            }
            if (!in_array('foto_factura', $itemCols)) {
                $this->pdo->exec("ALTER TABLE mechanic_record_items ADD COLUMN foto_factura VARCHAR(255) NULL");
            }
            if (!in_array('detalles_json', $itemCols)) {
                $this->pdo->exec("ALTER TABLE mechanic_record_items ADD COLUMN detalles_json TEXT NULL");
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    mr.*,
                    s.razon_social AS proveedor_nombre,
                    mo_s.razon_social AS mano_obra_proveedor_razon,
                    COALESCE(SUM(i.monto), 0) + COALESCE(mr.mano_obra_monto, 0) AS total_monto,
                    COALESCE(SUM(i.monto), 0) AS productos_monto,
                    COUNT(i.id) AS items_count
                FROM mechanic_records mr
                LEFT JOIN suppliers s ON s.id = mr.proveedor_id
                LEFT JOIN suppliers mo_s ON mo_s.id = mr.mano_obra_proveedor_id
                LEFT JOIN mechanic_record_items i ON i.mechanic_record_id = mr.id
                GROUP BY mr.id
                ORDER BY mr.fecha DESC, mr.id DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mechanic_records WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findItemsByRecordId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.*, s.razon_social AS proveedor_nombre
             FROM mechanic_record_items i
             LEFT JOIN suppliers s ON s.id = i.proveedor_id
             WHERE i.mechanic_record_id = :id
             ORDER BY i.id ASC"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPlates(): array
    {
        $sql = "
            SELECT placa, 'Vehiculo' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM vehicles WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Transporte Pesado' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM heavy_transport WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Maquinaria' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM machinery WHERE placa IS NOT NULL AND placa != ''

            ORDER BY placa ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO mechanic_records
                    (fecha, placa, tipo_unidad, tipo_trabajo, proveedor_id,
                     mano_obra_proveedor_id, mano_obra_proveedor_nombre, mano_obra_monto, mano_obra_factura,
                     observaciones, proximo_servicio_km, proximo_servicio_hrs)
                VALUES
                    (:fecha, :placa, :tipo_unidad, :tipo_trabajo, :proveedor_id,
                     :mano_obra_proveedor_id, :mano_obra_proveedor_nombre, :mano_obra_monto, :mano_obra_factura,
                     :observaciones, :proximo_servicio_km, :proximo_servicio_hrs)";

        $this->pdo->prepare($sql)->execute([
            'fecha'                      => $data['fecha'],
            'placa'                      => $data['placa'],
            'tipo_unidad'                => $data['tipo_unidad'],
            'tipo_trabajo'               => $data['tipo_trabajo'] ?? null,
            'proveedor_id'               => $data['proveedor_id'] ?: null,
            'mano_obra_proveedor_id'     => $data['mano_obra_proveedor_id'] ?: null,
            'mano_obra_proveedor_nombre' => $data['mano_obra_proveedor_nombre'] ?? null,
            'mano_obra_monto'            => $data['mano_obra_monto'] ?? 0,
            'mano_obra_factura'          => $data['mano_obra_factura'] ?? null,
            'observaciones'              => $data['observaciones'] ?? null,
            'proximo_servicio_km'        => $data['proximo_servicio_km'] ?: null,
            'proximo_servicio_hrs'       => $data['proximo_servicio_hrs'] ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createItem(int $recordId, string $producto, float $monto, ?int $proveedorId = null, ?string $fotoFactura = null, ?string $detallesJson = null): void
    {
        $this->pdo->prepare(
            "INSERT INTO mechanic_record_items (mechanic_record_id, producto, monto, proveedor_id, foto_factura, detalles_json)
             VALUES (:rid, :producto, :monto, :proveedor_id, :foto_factura, :detalles_json)"
        )->execute([
            'rid'           => $recordId,
            'producto'      => $producto,
            'monto'         => $monto,
            'proveedor_id'  => $proveedorId ?: null,
            'foto_factura'  => $fotoFactura ?: null,
            'detalles_json' => $detallesJson ?: null
        ]);
    }

    public function deleteItems(int $recordId): void
    {
        $this->pdo->prepare("DELETE FROM mechanic_record_items WHERE mechanic_record_id = :id")
                  ->execute(['id' => $recordId]);
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE mechanic_records SET
                    fecha                      = :fecha,
                    placa                      = :placa,
                    tipo_unidad                = :tipo_unidad,
                    tipo_trabajo               = :tipo_trabajo,
                    proveedor_id               = :proveedor_id,
                    mano_obra_proveedor_id     = :mano_obra_proveedor_id,
                    mano_obra_proveedor_nombre = :mano_obra_proveedor_nombre,
                    mano_obra_monto            = :mano_obra_monto,
                    mano_obra_factura          = :mano_obra_factura,
                    observaciones              = :observaciones,
                    proximo_servicio_km        = :proximo_servicio_km,
                    proximo_servicio_hrs       = :proximo_servicio_hrs
                WHERE id = :id";
        $this->pdo->prepare($sql)->execute([
            'id'                         => $id,
            'fecha'                      => $data['fecha'],
            'placa'                      => $data['placa'],
            'tipo_unidad'                => $data['tipo_unidad'],
            'tipo_trabajo'               => $data['tipo_trabajo'] ?? null,
            'proveedor_id'               => $data['proveedor_id'] ?: null,
            'mano_obra_proveedor_id'     => $data['mano_obra_proveedor_id'] ?: null,
            'mano_obra_proveedor_nombre' => $data['mano_obra_proveedor_nombre'] ?? null,
            'mano_obra_monto'            => $data['mano_obra_monto'] ?? 0,
            'mano_obra_factura'          => $data['mano_obra_factura'] ?? null,
            'observaciones'              => $data['observaciones'] ?? null,
            'proximo_servicio_km'        => $data['proximo_servicio_km'] ?: null,
            'proximo_servicio_hrs'       => $data['proximo_servicio_hrs'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];
        foreach (['foto_1','foto_2','foto_3','foto_4','foto_5','mano_obra_factura'] as $field) {
            if (isset($photos[$field])) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }
        if (empty($updates)) return;
        $this->pdo->prepare(
            "UPDATE mechanic_records SET " . implode(', ', $updates) . " WHERE id = :id"
        )->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM mechanic_records WHERE id = :id")->execute(['id' => $id]);
    }
}

