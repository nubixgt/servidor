<?php
namespace App\Services;

use App\Repositories\ContractorRepository;
use Exception;

class ContractorService
{
    private ContractorRepository $contractorRepository;

    public function __construct()
    {
        $this->contractorRepository = new ContractorRepository();
    }

    public function getAllContractors(): array
    {
        return $this->contractorRepository->findAll();
    }

    public function createContractor(array $data): void
    {
        $this->validateContractorData($data);
        $this->contractorRepository->create($data);
    }

    public function updateContractor(int $id, array $data): void
    {
        $this->validateContractorData($data);

        if (!$this->contractorRepository->findById($id)) {
            throw new Exception('Contratista no encontrado.', 404);
        }

        $this->contractorRepository->update($id, $data);
    }

    public function deleteContractor(int $id): void
    {
        if (!$this->contractorRepository->findById($id)) {
            throw new Exception('Contratista no encontrado.', 404);
        }

        $this->contractorRepository->delete($id);
    }

    public function getSummary(int $contractorId): array
    {
        $contractor = $this->contractorRepository->findById($contractorId);
        if (!$contractor) {
            throw new Exception('Contratista no encontrado.', 404);
        }

        $assignments = $this->contractorRepository->getProjectAssignments($contractorId);
        $payments = $this->contractorRepository->getPaymentsByContractor($contractorId);

        $paymentsByProject = [];
        foreach ($payments as $payment) {
            if ($payment['proyecto_id'] === null) {
                continue;
            }
            $paymentsByProject[$payment['proyecto_id']][] = $payment;
        }

        $projects = [];
        foreach ($assignments as $assignment) {
            $projectId = (int) $assignment['project_id'];
            $projectPayments = $paymentsByProject[$projectId] ?? [];
            unset($paymentsByProject[$projectId]);

            $projects[] = $this->buildProjectSummary($projectId, $assignment['proyecto_nombre'], (float) $assignment['monto_contratado'], $projectPayments, $assignment['id']);
        }

        // Proyectos con pagos registrados pero sin monto contratado asignado todavía
        foreach ($paymentsByProject as $projectId => $projectPayments) {
            $projects[] = $this->buildProjectSummary((int) $projectId, $projectPayments[0]['proyecto_nombre'], 0.0, $projectPayments, null);
        }

        return [
            'contractor' => $contractor,
            'projects'   => $projects
        ];
    }

    public function assignProject(int $contractorId, array $data): void
    {
        if (!$this->contractorRepository->findById($contractorId)) {
            throw new Exception('Contratista no encontrado.', 404);
        }

        if (empty($data['project_id'])) {
            throw new Exception('Debe seleccionar un proyecto.', 400);
        }

        $this->contractorRepository->assignProject(
            $contractorId,
            (int) $data['project_id'],
            (float) ($data['monto_contratado'] ?? 0),
            !empty($data['fecha_asignacion']) ? $data['fecha_asignacion'] : date('Y-m-d'),
            $data['observaciones'] ?? null
        );
    }

    public function removeProjectAssignment(int $contractorId, int $projectId): void
    {
        $this->contractorRepository->removeAssignment($contractorId, $projectId);
    }

    private function buildProjectSummary(int $projectId, string $proyectoNombre, float $montoContratado, array $pagos, ?int $projectContractorId): array
    {
        $totalPagado = array_reduce($pagos, fn($carry, $p) => $carry + (float) $p['monto'], 0.0);
        $porcentaje = $montoContratado > 0 ? ($totalPagado / $montoContratado) * 100 : 0;

        return [
            'project_id'            => $projectId,
            'project_contractor_id' => $projectContractorId,
            'proyecto_nombre'       => $proyectoNombre,
            'monto_contratado'      => $montoContratado,
            'total_pagado'          => $totalPagado,
            'por_pagar'             => $montoContratado - $totalPagado,
            'porcentaje'            => $porcentaje,
            'pagos'                 => $pagos
        ];
    }

    private function validateContractorData(array $data): void
    {
        if (empty($data['nombre'])) {
            throw new Exception('El nombre del contratista es requerido.', 400);
        }
    }
}
