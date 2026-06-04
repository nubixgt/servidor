<?php
namespace App\Services;

use App\Repositories\RecurrentRepository;
use Exception;

class RecurrentService
{
    private RecurrentRepository $repository;

    public function __construct()
    {
        $this->repository = new RecurrentRepository();
    }

    public function getAllByUser(string $username): array
    {
        if (empty($username)) {
            throw new Exception("Usuario no autenticado");
        }
        return $this->repository->findAllByUser($username);
    }

    public function create(array $data, string $username): int
    {
        if (empty($data['concepto'])) {
            throw new Exception("El concepto es obligatorio");
        }
        $data['creado_por'] = $username;
        return $this->repository->create($data);
    }

    public function update(int $id, array $data, string $username): void
    {
        if (empty($data['concepto'])) {
            throw new Exception("El concepto es obligatorio");
        }
        $data['creado_por'] = $username;
        $this->repository->update($id, $data);
    }

    public function delete(int $id, string $username): void
    {
        $this->repository->delete($id, $username);
    }
}
