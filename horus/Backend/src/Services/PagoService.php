<?php
namespace App\Services;

use App\Repositories\PagoRepository;
use App\DTOs\PagoDTO;
use App\Entities\PagoEntity;

class PagoService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PagoRepository();
    }

    public function getAllPagos()
    {
        return $this->repository->findAll();
    }

    public function getPagoById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function createPago(PagoDTO $dto)
    {
        if (empty($dto->cliente_id) || empty($dto->monto_pagado) || empty($dto->fecha)) {
            throw new \Exception("Fecha, cliente y monto son obligatorios");
        }

        $entity = new PagoEntity(
            null,
            $dto->fecha,
            $dto->cliente_id,
            $dto->referencia,
            $dto->monto_pagado,
            null,
            $dto->interes,
            null,
            $dto->fecha_interes,
            $dto->capital,
            null,
            $dto->fecha_capital
        );

        $id = $this->repository->create($entity);

        $foto = $this->handleFileUpload('foto', $id, 'foto');
        $comprobante_interes = $this->handleFileUpload('comprobante_interes', $id, 'intereses');
        $comprobante_capital = $this->handleFileUpload('comprobante_capital', $id, 'capital');

        if ($foto || $comprobante_interes || $comprobante_capital) {
            $entity->id = $id;
            $entity->foto = $foto ?: $entity->foto;
            $entity->comprobante_interes = $comprobante_interes ?: $entity->comprobante_interes;
            $entity->comprobante_capital = $comprobante_capital ?: $entity->comprobante_capital;
            $this->repository->update($id, $entity);
        }

        return $this->repository->findById($id);
    }

    public function updatePago(int $id, PagoDTO $dto)
    {
        if (empty($dto->cliente_id) || empty($dto->monto_pagado) || empty($dto->fecha)) {
            throw new \Exception("Fecha, cliente y monto son obligatorios");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Pago no encontrado");
        }

        $entity = new PagoEntity(
            $id,
            $dto->fecha,
            $dto->cliente_id,
            $dto->referencia,
            $dto->monto_pagado,
            $existing->foto,
            $dto->interes,
            $existing->comprobante_interes,
            $dto->fecha_interes,
            $dto->capital,
            $existing->comprobante_capital,
            $dto->fecha_capital
        );

        $newFoto = $this->handleFileUpload('foto', $id, 'foto', true);
        if ($newFoto) $entity->foto = $newFoto;

        $newInteres = $this->handleFileUpload('comprobante_interes', $id, 'intereses', true);
        if ($newInteres) $entity->comprobante_interes = $newInteres;

        $newCapital = $this->handleFileUpload('comprobante_capital', $id, 'capital', true);
        if ($newCapital) $entity->comprobante_capital = $newCapital;

        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function deletePago(int $id)
    {
        $existing = $this->repository->findById($id);
        if ($existing) {
            $folders = ['foto', 'intereses', 'capital'];
            foreach ($folders as $folder) {
                $uploadDir = __DIR__ . '/../../uploads/pagos/' . $folder . '/' . $id . '/';
                if (is_dir($uploadDir)) {
                    $files = glob($uploadDir . '*');
                    foreach ($files as $file) {
                        if (is_file($file)) unlink($file);
                    }
                    rmdir($uploadDir);
                }
            }
        }
        return $this->repository->delete($id);
    }

    private function handleFileUpload(string $inputName, int $pagoId, string $folder, bool $isUpdate = false): ?string
    {
        if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['name'])) {
            return null;
        }

        if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/pagos/' . $folder . '/' . $pagoId . '/';
        
        if ($isUpdate && is_dir($uploadDir)) {
            $files = glob($uploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
        } elseif (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES[$inputName]['tmp_name'];
        $name = basename($_FILES[$inputName]['name']);
        $name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
        
        $targetFile = $uploadDir . $name;
        if (move_uploaded_file($tmpName, $targetFile)) {
            return 'uploads/pagos/' . $folder . '/' . $pagoId . '/' . $name;
        }

        return null;
    }
}
