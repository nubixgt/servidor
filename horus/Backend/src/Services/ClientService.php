<?php
namespace App\Services;

use App\Repositories\ClientRepository;
use App\DTOs\ClientDTO;
use App\Entities\ClientEntity;

class ClientService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ClientRepository();
    }

    public function getAllClients()
    {
        return $this->repository->findAll();
    }

    public function getClientById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function createClient(ClientDTO $dto)
    {
        if (empty($dto->cliente)) {
            throw new \Exception("El nombre del cliente es obligatorio");
        }

        $entity = new ClientEntity(
            null,
            $dto->fecha,
            $dto->cliente,
            $dto->refiere,
            $dto->capital,
            $dto->plazo,
            $dto->porcentaje,
            $dto->interesPagar,
            $dto->devolvioCapital,
            $dto->pagoInteres,
            $dto->observaciones
        );
        
        $id = $this->repository->create($entity);
        return $this->repository->findById($id);
    }

    public function updateClient(int $id, ClientDTO $dto)
    {
        if (empty($dto->cliente)) {
            throw new \Exception("El nombre del cliente es obligatorio");
        }

        $entity = new ClientEntity(
            $id,
            $dto->fecha,
            $dto->cliente,
            $dto->refiere,
            $dto->capital,
            $dto->plazo,
            $dto->porcentaje,
            $dto->interesPagar,
            $dto->devolvioCapital,
            $dto->pagoInteres,
            $dto->observaciones
        );
        
        $this->repository->update($id, $entity);
        return $this->repository->findById($id);
    }

    public function deleteClient(int $id)
    {
        return $this->repository->delete($id);
    }
}

