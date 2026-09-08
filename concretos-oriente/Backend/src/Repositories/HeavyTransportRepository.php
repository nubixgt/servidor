<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class HeavyTransportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureColumnsExist();
    }

    private function ensureColumnsExist(): void
    {
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM heavy_transport")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('kilometraje', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN kilometraje INT DEFAULT 0 AFTER precio");
            }
            if (!in_array('seguro_aseguradora', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN seguro_aseguradora VARCHAR(150) NULL");
            }
            if (!in_array('seguro_contacto_nombre', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN seguro_contacto_nombre VARCHAR(150) NULL");
            }
            if (!in_array('seguro_contacto_telefono', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN seguro_contacto_telefono VARCHAR(50) NULL");
            }
            if (!in_array('seguro_poliza', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN seguro_poliza VARCHAR(100) NULL");
            }
            if (!in_array('seguro_contrato_adjunto_path', $cols)) {
                $this->pdo->exec("ALTER TABLE heavy_transport ADD COLUMN seguro_contrato_adjunto_path VARCHAR(255) NULL");
            }
        } catch (\Exception $e) {
            // Table might not exist yet or permission issues
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    ht.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre
                FROM heavy_transport ht
                LEFT JOIN personnel p ON p.id = ht.piloto_id
                ORDER BY ht.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM heavy_transport WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO heavy_transport
                    (placa, tipo_transporte, tipo_seguro, ubicacion, estado,
                     precio, kilometraje, marca, modelo, piloto_id,
                     seguro_aseguradora, seguro_contacto_nombre, seguro_contacto_telefono, seguro_poliza)
                VALUES
                    (:placa, :tipo_transporte, :tipo_seguro, :ubicacion, :estado,
                     :precio, :kilometraje, :marca, :modelo, :piloto_id,
                     :seguro_aseguradora, :seguro_contacto_nombre, :seguro_contacto_telefono, :seguro_poliza)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'placa'                    => $data['placa'],
            'tipo_transporte'          => $data['tipo_transporte'],
            'tipo_seguro'              => $data['tipo_seguro'] ?: null,
            'ubicacion'                => $data['ubicacion'] ?: null,
            'estado'                   => $data['estado'] ?? 'Nuevo',
            'precio'                   => $data['precio'] ?: null,
            'kilometraje'              => $data['kilometraje'] ?? 0,
            'marca'                    => $data['marca'],
            'modelo'                   => $data['modelo'],
            'piloto_id'                => $data['piloto_id'] ?: null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?? null,
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?? null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?? null,
            'seguro_poliza'            => $data['seguro_poliza'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE heavy_transport SET
                    placa                    = :placa,
                    tipo_transporte          = :tipo_transporte,
                    tipo_seguro              = :tipo_seguro,
                    ubicacion                = :ubicacion,
                    estado                   = :estado,
                    precio                   = :precio,
                    kilometraje              = :kilometraje,
                    marca                    = :marca,
                    modelo                   = :modelo,
                    piloto_id                = :piloto_id,
                    seguro_aseguradora       = :seguro_aseguradora,
                    seguro_contacto_nombre   = :seguro_contacto_nombre,
                    seguro_contacto_telefono = :seguro_contacto_telefono,
                    seguro_poliza            = :seguro_poliza
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'id'                       => $id,
            'placa'                    => $data['placa'],
            'tipo_transporte'          => $data['tipo_transporte'],
            'tipo_seguro'              => $data['tipo_seguro'] ?: null,
            'ubicacion'                => $data['ubicacion'] ?: null,
            'estado'                   => $data['estado'] ?? 'Nuevo',
            'precio'                   => $data['precio'] ?: null,
            'kilometraje'              => $data['kilometraje'] ?? 0,
            'marca'                    => $data['marca'],
            'modelo'                   => $data['modelo'],
            'piloto_id'                => $data['piloto_id'] ?: null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?? null,
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?? null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?? null,
            'seguro_poliza'            => $data['seguro_poliza'] ?? null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_delantera', 'foto_trasera', 'foto_lateral1', 'foto_lateral2'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $sql = "UPDATE heavy_transport SET " . implode(', ', $updates) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function updateDocumentPaths(int $id, array $paths): void
    {
        if (empty($paths)) return;

        $sets = [];
        $params = ['id' => $id];
        foreach ($paths as $col => $val) {
            $sets[] = "{$col} = :{$col}";
            $params[$col] = $val;
        }

        $sql = "UPDATE heavy_transport SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM heavy_transport WHERE id = :id")->execute(['id' => $id]);
    }
}
