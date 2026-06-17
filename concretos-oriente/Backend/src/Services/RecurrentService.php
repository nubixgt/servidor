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

    public function getAllByUser(int $userId): array
    {
        return $this->repository->findAllByUser($userId);
    }

    public function create(array $data, int $userId): int
    {
        if (empty($data['concepto'])) {
            throw new Exception("El concepto es obligatorio");
        }
        $data['created_by'] = $userId;
        return $this->repository->create($data);
    }

    public function update(int $id, array $data, int $userId): void
    {
        if (empty($data['concepto'])) {
            throw new Exception("El concepto es obligatorio");
        }
        $data['created_by'] = $userId;
        $this->repository->update($id, $data);
    }

    public function delete(int $id, int $userId): void
    {
        $this->repository->delete($id, $userId);
    }
}
