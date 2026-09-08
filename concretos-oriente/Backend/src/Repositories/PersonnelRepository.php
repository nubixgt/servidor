<?php
namespace App\Repositories;

use App\Utils\Database;
use Exception;
use PDO;

class PersonnelRepository
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
            $existingColumns = [];
            $stmt = $this->pdo->query("SHOW COLUMNS FROM personnel");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $existingColumns[] = strtolower($row['Field']);
                }
            }

            if (!in_array('depto_nacimiento', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `depto_nacimiento` VARCHAR(100) DEFAULT NULL AFTER `fecha_nacimiento`");
            }
            if (!in_array('muni_nacimiento', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `muni_nacimiento` VARCHAR(100) DEFAULT NULL AFTER `depto_nacimiento`");
            }
            if (!in_array('estado_civil', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `estado_civil` VARCHAR(50) DEFAULT NULL AFTER `muni_nacimiento`");
            }
            if (!in_array('edades_hijos', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `edades_hijos` VARCHAR(255) DEFAULT NULL AFTER `cantidad_hijos`");
            }
            if (!in_array('dpi_adjunto_path', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `dpi_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `foto_path`");
            }
            if (!in_array('contrato_adjunto_path', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `contrato_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `dpi_adjunto_path`");
            }
            if (!in_array('licencia_adjunto_path', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE `personnel` ADD COLUMN `licencia_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `contrato_adjunto_path`");
            }
        } catch (Exception $e) {
            error_log('Error en auto-migración personnel: ' . $e->getMessage());
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithProjects(): array
    {
        $sql = "SELECT
                    p.*,
                    pr.nombre AS proyecto_nombre
                FROM personnel p
                LEFT JOIN projects pr ON pr.id = p.proyecto_id
                ORDER BY p.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM personnel WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO personnel
                    (tipo_empleado, nombres, apellidos, dpi, nit, telefono, direccion,
                     puesto, tipo_planilla, salario_base, tarifa_hora_extra,
                     diario_viaticos, contacto_nombres, contacto_numero,
                     cantidad_hijos, edades_hijos, nivel_academico, fecha_nacimiento, depto_nacimiento, muni_nacimiento, estado_civil,
                     igss, igss_numero,
                     fecha_contratacion, fecha_baja,
                     numero_cuenta, nombre_banco, proyecto_id)
                VALUES
                    (:tipo_empleado, :nombres, :apellidos, :dpi, :nit, :telefono, :direccion,
                     :puesto, :tipo_planilla, :salario_base, :tarifa_hora_extra,
                     :diario_viaticos, :contacto_nombres, :contacto_numero,
                     :cantidad_hijos, :edades_hijos, :nivel_academico, :fecha_nacimiento, :depto_nacimiento, :muni_nacimiento, :estado_civil,
                     :igss, :igss_numero,
                     :fecha_contratacion, :fecha_baja,
                     :numero_cuenta, :nombre_banco, :proyecto_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tipo_empleado'      => $data['tipo_empleado'],
            'nombres'            => $data['nombres'],
            'apellidos'          => $data['apellidos'],
            'dpi'                => $data['dpi'],
            'nit'                => $data['nit'] ?? null,
            'telefono'           => $data['telefono'] ?? null,
            'direccion'          => $data['direccion'] ?? null,
            'puesto'             => $data['puesto'],
            'tipo_planilla'      => $data['tipo_planilla'],
            'salario_base'       => $data['salario_base'],
            'tarifa_hora_extra'  => $data['tarifa_hora_extra'] ?? null,
            'diario_viaticos'    => $data['diario_viaticos'] ?? null,
            'contacto_nombres'   => $data['contacto_nombres'] ?? null,
            'contacto_numero'    => $data['contacto_numero'] ?? null,
            'cantidad_hijos'     => $data['cantidad_hijos'] ?? null,
            'edades_hijos'       => $data['edades_hijos'] ?? null,
            'nivel_academico'    => $data['nivel_academico'] ?? null,
            'fecha_nacimiento'   => $data['fecha_nacimiento'] ?? null,
            'depto_nacimiento'   => $data['depto_nacimiento'] ?? null,
            'muni_nacimiento'    => $data['muni_nacimiento'] ?? null,
            'estado_civil'       => $data['estado_civil'] ?? null,
            'igss'               => $data['igss'] ?? null,
            'igss_numero'        => $data['igss_numero'] ?? null,
            'fecha_contratacion' => $data['fecha_contratacion'],
            'fecha_baja'         => $data['fecha_baja'] ?? null,
            'numero_cuenta'      => $data['numero_cuenta'] ?? null,
            'nombre_banco'       => $data['nombre_banco'] ?? null,
            'proyecto_id'        => $data['proyecto_id'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE personnel SET
                    tipo_empleado      = :tipo_empleado,
                    nombres            = :nombres,
                    apellidos          = :apellidos,
                    dpi                = :dpi,
                    nit                = :nit,
                    telefono           = :telefono,
                    direccion          = :direccion,
                    puesto             = :puesto,
                    tipo_planilla      = :tipo_planilla,
                    salario_base       = :salario_base,
                    tarifa_hora_extra  = :tarifa_hora_extra,
                    diario_viaticos    = :diario_viaticos,
                    contacto_nombres   = :contacto_nombres,
                    contacto_numero    = :contacto_numero,
                    cantidad_hijos     = :cantidad_hijos,
                    edades_hijos       = :edades_hijos,
                    nivel_academico    = :nivel_academico,
                    fecha_nacimiento   = :fecha_nacimiento,
                    depto_nacimiento   = :depto_nacimiento,
                    muni_nacimiento    = :muni_nacimiento,
                    estado_civil       = :estado_civil,
                    igss               = :igss,
                    igss_numero        = :igss_numero,
                    fecha_contratacion = :fecha_contratacion,
                    fecha_baja         = :fecha_baja,
                    numero_cuenta      = :numero_cuenta,
                    nombre_banco       = :nombre_banco,
                    proyecto_id        = :proyecto_id
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tipo_empleado'      => $data['tipo_empleado'],
            'nombres'            => $data['nombres'],
            'apellidos'          => $data['apellidos'],
            'dpi'                => $data['dpi'],
            'nit'                => $data['nit'] ?? null,
            'telefono'           => $data['telefono'] ?? null,
            'direccion'          => $data['direccion'] ?? null,
            'puesto'             => $data['puesto'],
            'tipo_planilla'      => $data['tipo_planilla'],
            'salario_base'       => $data['salario_base'],
            'tarifa_hora_extra'  => $data['tarifa_hora_extra'] ?? null,
            'diario_viaticos'    => $data['diario_viaticos'] ?? null,
            'contacto_nombres'   => $data['contacto_nombres'] ?? null,
            'contacto_numero'    => $data['contacto_numero'] ?? null,
            'cantidad_hijos'     => $data['cantidad_hijos'] ?? null,
            'edades_hijos'       => $data['edades_hijos'] ?? null,
            'nivel_academico'    => $data['nivel_academico'] ?? null,
            'fecha_nacimiento'   => $data['fecha_nacimiento'] ?? null,
            'depto_nacimiento'   => $data['depto_nacimiento'] ?? null,
            'muni_nacimiento'    => $data['muni_nacimiento'] ?? null,
            'estado_civil'       => $data['estado_civil'] ?? null,
            'igss'               => $data['igss'] ?? null,
            'igss_numero'        => $data['igss_numero'] ?? null,
            'fecha_contratacion' => $data['fecha_contratacion'],
            'fecha_baja'         => $data['fecha_baja'] ?? null,
            'numero_cuenta'      => $data['numero_cuenta'] ?? null,
            'nombre_banco'       => $data['nombre_banco'] ?? null,
            'proyecto_id'        => $data['proyecto_id'] ?? null,
            'id'                 => $id,
        ]);
    }

    public function updatePhotoPath(int $id, ?string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE personnel SET foto_path = :fp WHERE id = :id")
             ->execute(['fp' => $fotoPath, 'id' => $id]);
    }

    public function updateDocumentPaths(int $id, array $paths): void
    {
        $sets = [];
        $params = ['id' => $id];
        foreach (['foto_path', 'dpi_adjunto_path', 'contrato_adjunto_path', 'licencia_adjunto_path'] as $field) {
            if (array_key_exists($field, $paths)) {
                $sets[] = "$field = :$field";
                $params[$field] = $paths[$field];
            }
        }
        if (!empty($sets)) {
            $sql = "UPDATE personnel SET " . implode(', ', $sets) . " WHERE id = :id";
            $this->pdo->prepare($sql)->execute($params);
        }
    }

    public function updateFechaBaja(int $id, ?string $fecha_baja): void
    {
        $stmt = $this->pdo->prepare("UPDATE personnel SET fecha_baja = :fecha_baja WHERE id = :id");
        $stmt->execute([
            'id'         => $id,
            'fecha_baja' => $fecha_baja
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM personnel WHERE id = :id")->execute(['id' => $id]);
    }
}
