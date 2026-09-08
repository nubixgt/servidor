<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SpecialMachineryRepository
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
            $cols = $this->pdo->query("SHOW COLUMNS FROM special_machinery")->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('codigo', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN codigo VARCHAR(50) NULL AFTER id");
            }
            if (!in_array('valor', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN valor DECIMAL(12,2) NULL AFTER estado");
            }
            if (!in_array('seguro_aseguradora', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN seguro_aseguradora VARCHAR(150) NULL");
            }
            if (!in_array('seguro_contacto_nombre', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN seguro_contacto_nombre VARCHAR(150) NULL");
            }
            if (!in_array('seguro_contacto_telefono', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN seguro_contacto_telefono VARCHAR(50) NULL");
            }
            if (!in_array('seguro_poliza', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN seguro_poliza VARCHAR(100) NULL");
            }
            if (!in_array('seguro_contrato_adjunto_path', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery ADD COLUMN seguro_contrato_adjunto_path VARCHAR(255) NULL");
            }

            // Make subtipo nullable if it exists
            if (in_array('subtipo', $cols)) {
                $this->pdo->exec("ALTER TABLE special_machinery MODIFY COLUMN subtipo VARCHAR(100) NULL");
            }
        } catch (\Exception $e) {
            // Table might not exist or permission restriction
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    sm.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS responsable_nombre
                FROM special_machinery sm
                LEFT JOIN personnel p ON p.id = sm.responsable_id
                ORDER BY sm.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM special_machinery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO special_machinery
                    (codigo, nombre, tipo_maquinaria, subtipo, marca, modelo, anio,
                     estado, valor, ubicacion, responsable_id,
                     seguro_aseguradora, seguro_contacto_nombre, seguro_contacto_telefono, seguro_poliza)
                VALUES
                    (:codigo, :nombre, :tipo_maquinaria, :subtipo, :marca, :modelo, :anio,
                     :estado, :valor, :ubicacion, :responsable_id,
                     :seguro_aseguradora, :seguro_contacto_nombre, :seguro_contacto_telefono, :seguro_poliza)";

        $this->pdo->prepare($sql)->execute([
            'codigo'                   => !empty($data['codigo']) ? $data['codigo'] : null,
            'nombre'                   => $data['nombre'],
            'tipo_maquinaria'          => $data['tipo_maquinaria'],
            'subtipo'                  => $data['subtipo'] ?? null,
            'marca'                    => $data['marca'] ?: null,
            'modelo'                   => $data['modelo'] ?: null,
            'anio'                     => $data['anio'] ?: null,
            'estado'                   => $data['estado'] ?? 'Activo',
            'valor'                    => !empty($data['valor']) ? $data['valor'] : null,
            'ubicacion'                => $data['ubicacion'] ?: null,
            'responsable_id'           => $data['responsable_id'] ?: null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?: null,
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?: null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?: null,
            'seguro_poliza'            => $data['seguro_poliza'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE special_machinery SET
                    codigo                   = :codigo,
                    nombre                   = :nombre,
                    tipo_maquinaria          = :tipo_maquinaria,
                    subtipo                  = :subtipo,
                    marca                    = :marca,
                    modelo                   = :modelo,
                    anio                     = :anio,
                    estado                   = :estado,
                    valor                    = :valor,
                    ubicacion                = :ubicacion,
                    responsable_id           = :responsable_id,
                    seguro_aseguradora       = :seguro_aseguradora,
                    seguro_contacto_nombre   = :seguro_contacto_nombre,
                    seguro_contacto_telefono = :seguro_contacto_telefono,
                    seguro_poliza            = :seguro_poliza
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'id'                       => $id,
            'codigo'                   => !empty($data['codigo']) ? $data['codigo'] : null,
            'nombre'                   => $data['nombre'],
            'tipo_maquinaria'          => $data['tipo_maquinaria'],
            'subtipo'                  => $data['subtipo'] ?? null,
            'marca'                    => $data['marca'] ?: null,
            'modelo'                   => $data['modelo'] ?: null,
            'anio'                     => $data['anio'] ?: null,
            'estado'                   => $data['estado'] ?? 'Activo',
            'valor'                    => !empty($data['valor']) ? $data['valor'] : null,
            'ubicacion'                => $data['ubicacion'] ?: null,
            'responsable_id'           => $data['responsable_id'] ?: null,
            'seguro_aseguradora'       => $data['seguro_aseguradora'] ?: null,
            'seguro_contacto_nombre'   => $data['seguro_contacto_nombre'] ?: null,
            'seguro_contacto_telefono' => $data['seguro_contacto_telefono'] ?: null,
            'seguro_poliza'            => $data['seguro_poliza'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_1', 'foto_2', 'foto_3', 'foto_4', 'foto_5'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $this->pdo->prepare(
            "UPDATE special_machinery SET " . implode(', ', $updates) . " WHERE id = :id"
        )->execute($params);
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

        $sql = "UPDATE special_machinery SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM special_machinery WHERE id = :id")->execute(['id' => $id]);
    }
}
