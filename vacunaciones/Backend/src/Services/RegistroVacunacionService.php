<?php
namespace App\Services;

use App\DTOs\RegistroVacunacionDTO;
use App\Entities\RegistroVacunacion;
use App\Repositories\RegistroVacunacionRepository;

class RegistroVacunacionService
{
    private RegistroVacunacionRepository $repository;

    public function __construct()
    {
        $this->repository = new RegistroVacunacionRepository();
    }

    public function createRegistro(RegistroVacunacionDTO $dto): bool
    {
        $entity = new RegistroVacunacion(
            $dto->fecha,
            $dto->vacunador,
            $dto->cliente,
            $dto->direccion,
            $dto->servicio,
            $dto->cantidad,
            $dto->costoPorAve,
            $dto->total,
            $dto->estado
        );

        return $this->repository->create($entity);
    }

    public function getAll()
    {
        return $this->repository->findAll();
    }

    public function updateStatus(int $id, string $estado): bool
    {
        return $this->repository->updateStatus($id, $estado);
    }

    public function deleteRegistro(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function updateRegistro(int $id, RegistroVacunacionDTO $dto): bool
    {
        $entity = new RegistroVacunacion(
            $dto->fecha,
            $dto->vacunador,
            $dto->cliente,
            $dto->direccion,
            $dto->servicio,
            $dto->cantidad,
            $dto->costoPorAve,
            $dto->total,
            $dto->estado
        );

        return $this->repository->update($id, $entity);
    }
}
