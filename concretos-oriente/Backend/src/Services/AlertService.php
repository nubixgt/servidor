<?php
namespace App\Services;

use App\Repositories\AlertRepository;
use Exception;

class AlertService
{
    private AlertRepository $alertRepository;

    public function __construct()
    {
        $this->alertRepository = new AlertRepository();
    }

    public function getConfigs(): array
    {
        $data = $this->alertRepository->findAllConfigs();

        foreach ($data as &$row) {
            $row['canales'] = json_decode($row['canales'], true) ?: [];
            $row['destinatarios'] = json_decode($row['destinatarios'], true) ?: [];
        }

        return $data;
    }

    public function createConfig(array $data): array
    {
        if (empty($data['nombre']) || empty($data['tipo_evento'])) {
            throw new Exception('Faltan campos obligatorios', 400);
        }

        $data['canales'] = isset($data['canales']) ? json_encode($data['canales']) : '[]';
        $data['destinatarios'] = isset($data['destinatarios']) ? json_encode($data['destinatarios']) : '[]';
        $data['umbral'] = isset($data['umbral']) ? (float)$data['umbral'] : 0.0;
        $data['activa'] = isset($data['activa']) ? (int)$data['activa'] : 1;

        $newId = $this->alertRepository->createConfig($data);

        return ['id' => $newId];
    }

    public function deleteConfig(int $id): void
    {
        $this->alertRepository->deleteConfig($id);
    }

    public function getHistory(): array
    {
        return $this->alertRepository->findAllHistory();
    }
}
