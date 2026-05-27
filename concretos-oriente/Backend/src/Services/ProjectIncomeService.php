<?php
namespace App\Services;

use App\Repositories\ProjectIncomeRepository;
use App\Repositories\ProjectRepository;
use App\Utils\Uploader;
use Exception;

class ProjectIncomeService
{
    private ProjectIncomeRepository $incomeRepo;
    private ProjectRepository $projectRepo;

    public function __construct()
    {
        $this->incomeRepo = new ProjectIncomeRepository();
        $this->projectRepo = new ProjectRepository();
    }

    public function getProjectIncomes(int $projectId): array
    {
        return $this->incomeRepo->getIncomesByProject($projectId);
    }

    public function getProjectIncomeSummary(int $projectId): array
    {
        return $this->incomeRepo->getSummaryByProject($projectId);
    }
    
    public function getProjectTotals(int $projectId): array
    {
        $totals = $this->incomeRepo->getTotalsByProject($projectId);
        $project = $this->projectRepo->findById($projectId);
        
        if (!$project) {
            throw new Exception("Proyecto no encontrado.");
        }
        
        return [
            'presupuesto' => $project['presupuesto'],
            'total_cobrado' => $totals['total_cobrado'] ?? 0,
            'ultima_estimacion' => $totals['ultima_estimacion'] ?? 0,
            'tiene_anticipo' => $totals['tiene_anticipo'] > 0,
            'tiene_pago_final' => $totals['tiene_pago_final'] > 0,
            'restante' => $project['presupuesto'] - ($totals['total_cobrado'] ?? 0)
        ];
    }

    public function registerIncome(array $data, array $sourcesData): int
    {
        $projectId = (int)$data['project_id'];
        $project = $this->projectRepo->findById($projectId);

        if (!$project || $project['presupuesto'] <= 0) {
            throw new Exception("El proyecto no existe o no tiene un presupuesto definido.");
        }

        $presupuesto = (float)$project['presupuesto'];
        $tipoCobro = $data['tipo_cobro'];

        // Validar que las fuentes sumen 100%
        $sumaPorcentajes = 0;
        foreach ($sourcesData as $source) {
            $sumaPorcentajes += (float)$source['porcentaje_aporte'];
        }
        if (abs($sumaPorcentajes - 100) > 0.01) {
            throw new Exception("La suma de los porcentajes de las fuentes debe ser exactamente 100%.");
        }

        $totals = $this->incomeRepo->getTotalsByProject($projectId);
        $totalCobradoPrevio = (float)($totals['total_cobrado'] ?? 0);
        $tieneAnticipo = $totals['tiene_anticipo'] > 0;
        $tienePagoFinal = $totals['tiene_pago_final'] > 0;
        $ultimaEstimacion = (int)($totals['ultima_estimacion'] ?? 0);

        if ($tienePagoFinal) {
            throw new Exception("El proyecto ya tiene registrado un Pago Final.");
        }

        $montoTotal = 0;
        $numeroEstimacion = null;

        if ($tipoCobro === 'Anticipo') {
            if ($tieneAnticipo) {
                throw new Exception("Este proyecto ya tiene un Anticipo registrado.");
            }
            // 20% exacto
            $montoTotal = $presupuesto * 0.20;
        } elseif ($tipoCobro === 'Estimacion') {
            if (!$tieneAnticipo) {
                throw new Exception("Debe registrar el Anticipo antes de registrar Estimaciones.");
            }
            if ($ultimaEstimacion >= 8) {
                throw new Exception("Se ha alcanzado el límite máximo de 8 estimaciones.");
            }
            
            $numeroEstimacion = $ultimaEstimacion + 1;
            $montoTotal = (float)$data['monto_total'];
            
            if ($montoTotal <= 0) {
                throw new Exception("El monto de la estimación debe ser mayor a cero.");
            }
        } elseif ($tipoCobro === 'Pago Final') {
            if (!$tieneAnticipo || $ultimaEstimacion === 0) {
                throw new Exception("Debe registrar al menos el Anticipo y 1 Estimación antes del Pago Final.");
            }
            $montoTotal = $presupuesto - $totalCobradoPrevio;
            if ($montoTotal <= 0) {
                throw new Exception("El presupuesto del proyecto ya ha sido cubierto al 100%.");
            }
        }

        // Validar que no se exceda del 100%
        if (round($totalCobradoPrevio + $montoTotal, 2) > round($presupuesto, 2)) {
            throw new Exception("El monto total a cobrar supera el presupuesto del contrato.");
        }

        $porcentajeContrato = ($montoTotal / $presupuesto) * 100;

        $incomeData = [
            'project_id' => $projectId,
            'tipo_cobro' => $tipoCobro,
            'numero_estimacion' => $numeroEstimacion,
            'monto_total' => $montoTotal,
            'porcentaje_contrato' => $porcentajeContrato,
            'fecha_registro' => $data['fecha_registro'] ?? date('Y-m-d'),
            'periodo_avance' => $data['periodo_avance'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];

        // Ajustar montos por fuente según porcentaje
        $finalSources = [];
        foreach ($sourcesData as $source) {
            $montoAportado = $montoTotal * ((float)$source['porcentaje_aporte'] / 100);
            
            if ($source['estado'] === 'Recibido') {
                if (empty($source['fecha_cobro']) || empty($source['bank_account_id'])) {
                    throw new Exception("Para marcar una fuente como Recibido, debe especificar Fecha de Cobro y Cuenta Bancaria.");
                }
            }

            $finalSources[] = [
                'fuente' => $source['fuente'],
                'porcentaje_aporte' => $source['porcentaje_aporte'],
                'monto_aportado' => $montoAportado,
                'fecha_cobro' => $source['fecha_cobro'] ?? null,
                'numero_documento' => $source['numero_documento'] ?? null,
                'bank_account_id' => $source['bank_account_id'] ?? null,
                'estado' => $source['estado'] ?? 'Pendiente'
            ];
        }

        return $this->incomeRepo->createIncome($incomeData, $finalSources);
    }

    public function updateSource(int $sourceId, array $data, $file = null): void
    {
        // Validaciones de recibido
        if (isset($data['estado']) && $data['estado'] === 'Recibido') {
            if (empty($data['fecha_cobro']) || empty($data['bank_account_id'])) {
                throw new Exception("Para marcar como Recibido, debe proporcionar la Fecha de Cobro y la Cuenta Bancaria.");
            }
        }

        $comprobantePath = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploader = new Uploader('Uploads/ProjectIncomes');
            $comprobantePath = $uploader->upload($file, "comprobante_{$sourceId}");
            $data['comprobante_path'] = $comprobantePath;
        }

        $this->incomeRepo->updateSource($sourceId, $data);
    }
}
