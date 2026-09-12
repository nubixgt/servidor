<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ProjectRepository
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
            $cols = $this->pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
            $newCols = [
                'snip'                      => 'VARCHAR(100) NULL',
                'nog'                       => 'VARCHAR(100) NULL',
                'monto_cocode'              => 'DECIMAL(14,2) NULL DEFAULT 0.00',
                'monto_muni'                => 'DECIMAL(14,2) NULL DEFAULT 0.00',
                'monto_comunidad'           => 'DECIMAL(14,2) NULL DEFAULT 0.00',
                'tipo_inversion'            => 'VARCHAR(100) NULL',
                'foto_contrato'             => 'VARCHAR(255) NULL',
                'excel_presupuesto'         => 'VARCHAR(255) NULL',
                'especificaciones_tecnicas' => 'VARCHAR(255) NULL',
                'convenios_archivos'        => 'TEXT NULL',
            ];
            foreach ($newCols as $col => $type) {
                if (!in_array($col, $cols)) {
                    $this->pdo->exec("ALTER TABLE projects ADD COLUMN $col $type");
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO projects 
                    (codigo, nombre, cliente_id, ubicacion, coordenadas, presupuesto, 
                     fecha_inicio, fecha_fin_estimada, fecha_fin_real, estado, 
                     numero_contrato, descripcion, contactos, gerente_id,
                     snip, nog, monto_cocode, monto_muni, monto_comunidad, tipo_inversion) 
                VALUES 
                    (:codigo, :nombre, :cliente_id, :ubicacion, :coordenadas, :presupuesto, 
                     :fecha_inicio, :fecha_fin_estimada, :fecha_fin_real, :estado, 
                     :numero_contrato, :descripcion, :contactos, :gerente_id,
                     :snip, :nog, :monto_cocode, :monto_muni, :monto_comunidad, :tipo_inversion)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':codigo'             => $data['codigo'],
            ':nombre'             => $data['nombre'],
            ':cliente_id'         => $data['cliente_id'],
            ':ubicacion'          => $data['ubicacion'],
            ':coordenadas'        => $data['coordenadas'],
            ':presupuesto'        => $data['presupuesto'],
            ':fecha_inicio'       => $data['fecha_inicio'],
            ':fecha_fin_estimada' => $data['fecha_fin_estimada'],
            ':fecha_fin_real'     => $data['fecha_fin_real'],
            ':estado'             => $data['estado'],
            ':numero_contrato'    => $data['numero_contrato'],
            ':descripcion'        => $data['descripcion'],
            ':contactos'          => $data['contactos'],
            ':gerente_id'         => $data['gerente_id'],
            ':snip'               => $data['snip'] ?? null,
            ':nog'                => $data['nog'] ?? null,
            ':monto_cocode'       => $data['monto_cocode'] ?? 0,
            ':monto_muni'         => $data['monto_muni'] ?? 0,
            ':monto_comunidad'    => $data['monto_comunidad'] ?? 0,
            ':tipo_inversion'     => $data['tipo_inversion'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE projects SET 
                    codigo = :codigo, nombre = :nombre, cliente_id = :cliente_id, 
                    ubicacion = :ubicacion, coordenadas = :coordenadas, 
                    presupuesto = :presupuesto, fecha_inicio = :fecha_inicio, 
                    fecha_fin_estimada = :fecha_fin_estimada, fecha_fin_real = :fecha_fin_real, 
                    estado = :estado, numero_contrato = :numero_contrato, 
                    descripcion = :descripcion, contactos = :contactos, gerente_id = :gerente_id,
                    snip = :snip, nog = :nog, monto_cocode = :monto_cocode,
                    monto_muni = :monto_muni, monto_comunidad = :monto_comunidad,
                    tipo_inversion = :tipo_inversion,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $data['id'] = $id;
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->pdo->prepare($sql)->execute($params);
    }

    public function updatePhoto(int $id, string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE projects SET foto = :foto WHERE id = :id")
             ->execute([':foto' => $fotoPath, ':id' => $id]);
    }

    public function updateFotoContrato(int $id, string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE projects SET foto_contrato = :foto WHERE id = :id")
             ->execute([':foto' => $fotoPath, ':id' => $id]);
    }

    public function updateExcelPresupuesto(int $id, string $filePath): void
    {
        $this->pdo->prepare("UPDATE projects SET excel_presupuesto = :path WHERE id = :id")
             ->execute([':path' => $filePath, ':id' => $id]);
    }

    public function updateEspecificacionesTecnicas(int $id, string $filePath): void
    {
        $this->pdo->prepare("UPDATE projects SET especificaciones_tecnicas = :path WHERE id = :id")
             ->execute([':path' => $filePath, ':id' => $id]);
    }

    public function updateConvenios(int $id, string $conveniosJson): void
    {
        $this->pdo->prepare("UPDATE projects SET convenios_archivos = :convenios_archivos WHERE id = :id")
             ->execute([':convenios_archivos' => $conveniosJson, ':id' => $id]);
    }

    public function updateDocuments(int $id, string $docsJson): void
    {
        $this->pdo->prepare("UPDATE projects SET contratos_archivos = :contratos_archivos WHERE id = :id")
             ->execute([':contratos_archivos' => $docsJson, ':id' => $id]);
    }

    public function removeConvenio(int $id, string $filePath): void
    {
        $project = $this->findById($id);
        if (!$project || !$project['convenios_archivos']) return;
        $files = json_decode($project['convenios_archivos'], true) ?: [];
        $files = array_values(array_filter($files, fn($f) => $f !== $filePath));
        
        $newJson = count($files) > 0 ? json_encode($files) : null;
        $this->pdo->prepare("UPDATE projects SET convenios_archivos = :json WHERE id = :id")
             ->execute(['json' => $newJson, 'id' => $id]);
    }

    public function getHistory(int $projectId): array
    {
        // Global Incomes (general income logged against the project)
        $sqlIncomes = "SELECT 'Ingreso' as type, fecha_ingreso as date, monto as amount, descripcion as detail, numero_cheque as reference, pagador as entity
                       FROM incomes WHERE proyecto_id = :project_id";
        
        // Global Expenses (general expenses logged against the project)
        $sqlExpenses = "SELECT 'Egreso' as type, fecha_egreso as date, monto as amount, descripcion as detail, numero_cheque as reference, beneficiario as entity
                        FROM expenses WHERE proyecto_id = :project_id";
                        
        // Project Incomes (estimations from project sources)
        $sqlProjectIncomes = "SELECT 'Cobro de Estimación' as type, s.fecha_cobro as date, s.monto_aportado as amount, 
                                     CONCAT('Estimación ', i.numero_estimacion, ' - Fuente: ', s.fuente) as detail, s.numero_documento as reference, s.fuente as entity
                              FROM project_income_sources s
                              JOIN project_incomes i ON s.project_income_id = i.id
                              WHERE i.project_id = :project_id AND s.estado = 'Recibido'";

        $stmtIncomes = $this->pdo->prepare($sqlIncomes);
        $stmtIncomes->execute(['project_id' => $projectId]);
        $incomes = $stmtIncomes->fetchAll(PDO::FETCH_ASSOC);

        $stmtExpenses = $this->pdo->prepare($sqlExpenses);
        $stmtExpenses->execute(['project_id' => $projectId]);
        $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtProjIncomes = $this->pdo->prepare($sqlProjectIncomes);
        $stmtProjIncomes->execute(['project_id' => $projectId]);
        $projIncomes = $stmtProjIncomes->fetchAll(PDO::FETCH_ASSOC);

        $history = array_merge($incomes, $expenses, $projIncomes);
        
        usort($history, fn($a, $b) => strtotime($b['date'] ?: '1970-01-01') - strtotime($a['date'] ?: '1970-01-01'));
        
        return $history;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM projects WHERE id = :id")->execute([':id' => $id]);
    }
}
