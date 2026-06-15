<?php

namespace App\Services;

use App\Repositories\GastoRecurrenteRepository;
use App\DTOs\GastoRecurrenteDTO;
use App\Entities\GastoRecurrenteEntity;

class GastoRecurrenteService {
    private GastoRecurrenteRepository $repository;

    public function __construct() {
        $this->repository = new GastoRecurrenteRepository();
    }

    public function getAllGastos(): array {
        $gastos = $this->repository->getAll();
        return array_map(function($gasto) {
            return $gasto->toArray();
        }, $gastos);
    }

    public function getGastoById(int $id): ?array {
        $gasto = $this->repository->getById($id);
        return $gasto ? $gasto->toArray() : null;
    }

    public function createGasto(GastoRecurrenteDTO $dto): array {
        $gasto = new GastoRecurrenteEntity(
            null,
            $dto->concepto,
            $dto->descripcion,
            $dto->monto,
            $dto->dia_pago,
            $dto->estado
        );

        $id = $this->repository->save($gasto);
        return $this->getGastoById($id);
    }

    public function updateGasto(int $id, GastoRecurrenteDTO $dto): ?array {
        $existing = $this->repository->getById($id);
        if (!$existing) {
            throw new \Exception("Gasto recurrente no encontrado.");
        }

        $gasto = new GastoRecurrenteEntity(
            $id,
            $dto->concepto,
            $dto->descripcion,
            $dto->monto,
            $dto->dia_pago,
            $dto->estado,
            $existing->getCreatedAt()
        );

        $this->repository->update($gasto);
        return $this->getGastoById($id);
    }

    public function deleteGasto(int $id): bool {
        $existing = $this->repository->getById($id);
        if (!$existing) {
            throw new \Exception("Gasto recurrente no encontrado.");
        }
        return $this->repository->delete($id);
    }
}
