<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;
use Exception;

class ProjectIncomeRepository
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

    public function getIncomesByProject(int $projectId): array
    {
        $sql = "SELECT * FROM project_incomes WHERE project_id = :project_id ORDER BY created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['project_id' => $projectId]);
        $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch sources for each income
        foreach ($incomes as &$income) {
            $sqlSources = "SELECT * FROM project_income_sources WHERE project_income_id = :income_id";
            $stmtSources = $this->pdo->prepare($sqlSources);
            $stmtSources->execute(['income_id' => $income['id']]);
            $income['sources'] = $stmtSources->fetchAll(PDO::FETCH_ASSOC);
        }

        return $incomes;
    }

    public function getSummaryByProject(int $projectId): array
    {
        $sql = "
            SELECT 
                s.fuente, 
                SUM(s.monto_aportado) as monto_total_esperado,
                SUM(CASE WHEN s.estado = 'Recibido' THEN s.monto_aportado ELSE 0 END) as monto_cobrado,
                SUM(CASE WHEN s.estado = 'Pendiente' THEN s.monto_aportado ELSE 0 END) as monto_pendiente
            FROM project_income_sources s
            JOIN project_incomes i ON s.project_income_id = i.id
            WHERE i.project_id = :project_id
            GROUP BY s.fuente
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createIncome(array $data, array $sourcesData): int
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO project_incomes 
                    (project_id, tipo_cobro, numero_estimacion, monto_total, porcentaje_contrato, fecha_registro, periodo_avance, observaciones) 
                    VALUES 
                    (:project_id, :tipo_cobro, :numero_estimacion, :monto_total, :porcentaje_contrato, :fecha_registro, :periodo_avance, :observaciones)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':project_id' => $data['project_id'],
                ':tipo_cobro' => $data['tipo_cobro'],
                ':numero_estimacion' => $data['numero_estimacion'] ?? null,
                ':monto_total' => $data['monto_total'],
                ':porcentaje_contrato' => $data['porcentaje_contrato'],
                ':fecha_registro' => $data['fecha_registro'],
                ':periodo_avance' => $data['periodo_avance'] ?? null,
                ':observaciones' => $data['observaciones'] ?? null
            ]);

            $incomeId = (int) $this->pdo->lastInsertId();

            $sqlSource = "INSERT INTO project_income_sources 
                          (project_income_id, fuente, porcentaje_aporte, monto_aportado, fecha_cobro, numero_documento, bank_account_id, estado, comprobante_path)
                          VALUES 
                          (:project_income_id, :fuente, :porcentaje_aporte, :monto_aportado, :fecha_cobro, :numero_documento, :bank_account_id, :estado, :comprobante_path)";
            $stmtSource = $this->pdo->prepare($sqlSource);

            foreach ($sourcesData as $source) {
                $stmtSource->execute([
                    ':project_income_id' => $incomeId,
                    ':fuente' => $source['fuente'],
                    ':porcentaje_aporte' => $source['porcentaje_aporte'],
                    ':monto_aportado' => $source['monto_aportado'],
                    ':fecha_cobro' => $source['fecha_cobro'] ?? null,
                    ':numero_documento' => $source['numero_documento'] ?? null,
                    ':bank_account_id' => !empty($source['bank_account_id']) ? $source['bank_account_id'] : null,
                    ':estado' => $source['estado'] ?? 'Pendiente',
                    ':comprobante_path' => $source['comprobante_path'] ?? null
                ]);
            }

            $this->pdo->commit();
            return $incomeId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateSource(int $sourceId, array $data): void
    {
        $sql = "UPDATE project_income_sources SET 
                fecha_cobro = :fecha_cobro, 
                numero_documento = :numero_documento, 
                bank_account_id = :bank_account_id, 
                estado = :estado,
                comprobante_path = COALESCE(:comprobante_path, comprobante_path),
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fecha_cobro' => $data['fecha_cobro'] ?? null,
            ':numero_documento' => $data['numero_documento'] ?? null,
            ':bank_account_id' => !empty($data['bank_account_id']) ? $data['bank_account_id'] : null,
            ':estado' => $data['estado'],
            ':comprobante_path' => $data['comprobante_path'] ?? null,
            ':id' => $sourceId
        ]);
    }
    
    public function getTotalsByProject(int $projectId): array
    {
        $sql = "SELECT SUM(monto_total) as total_cobrado, MAX(numero_estimacion) as ultima_estimacion,
                       COUNT(CASE WHEN tipo_cobro = 'Anticipo' THEN 1 END) as tiene_anticipo,
                       COUNT(CASE WHEN tipo_cobro = 'Pago Final' THEN 1 END) as tiene_pago_final
                FROM project_incomes WHERE project_id = :project_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
