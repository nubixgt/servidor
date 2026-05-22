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
                     fecha_contratacion, fecha_baja,
                     numero_cuenta, nombre_banco, proyecto_id)
                VALUES
                    (:tipo_empleado, :nombres, :apellidos, :dpi, :nit, :telefono, :direccion,
                     :puesto, :tipo_planilla, :salario_base, :tarifa_hora_extra,
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
                    fecha_contratacion = :fecha_contratacion,
                    fecha_baja         = :fecha_baja,
                    numero_cuenta      = :numero_cuenta,
                    nombre_banco       = :nombre_banco,
                    proyecto_id        = :proyecto_id
                WHERE id = :id";

        $data['id'] = $id;
        $this->pdo->prepare($sql)->execute($data);
    }

    public function updatePhotoPath(int $id, ?string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE personnel SET foto_path = :fp WHERE id = :id")
             ->execute(['fp' => $fotoPath, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM personnel WHERE id = :id")->execute(['id' => $id]);
    }
}
