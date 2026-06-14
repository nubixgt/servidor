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
}
