<?php
namespace App\Services;

use App\DTOs\PrestamoDTO;
use App\Entities\PrestamoEntity;
use App\Repositories\PrestamoRepository;

class PrestamoService
{
    private PrestamoRepository $repository;

    public function __construct()
    {
        $this->repository = new PrestamoRepository();
    }

    public function getAll()
    {
        return $this->repository->findAll();
    }

    public function getById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function create(PrestamoDTO $dto)
    {
        if (empty($dto->clienteId)) {
            throw new \Exception("El cliente es obligatorio para el préstamo");
        }

        $entity = new PrestamoEntity(
            null,
            $dto->clienteId,
            $dto->montoPrincipal,
            $dto->tasa,
            $dto->tipoInteres,
            $dto->plazo,
            $dto->fechaDesembolso,
            $dto->totalIntereses,
            $dto->totalSeguro,
            $dto->costoTotal,
            $dto->estado
        );
        
        $id = $this->repository->create($entity);
        return $this->repository->findById($id);
    }

    public function update(int $id, PrestamoDTO $dto)
    {
        if (empty($dto->clienteId)) {
            throw new \Exception("El cliente es obligatorio para el préstamo");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new \Exception("Préstamo no encontrado");
        }

        $entity = new PrestamoEntity(
            $id,
            $dto->clienteId,
            $dto->montoPrincipal,
            $dto->tasa,
            $dto->tipoInteres,
            $dto->plazo,
            $dto->fechaDesembolso,
            $dto->totalIntereses,
            $dto->totalSeguro,
            $dto->costoTotal,
            $dto->estado
        );
        
        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
