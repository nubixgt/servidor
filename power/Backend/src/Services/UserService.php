<?php
namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function listByStatus(string $status): array
    {
        return array_map(fn($u) => $u->toArray(), $this->repository->findAllByStatus($status));
    }

    public function approve(int $id): void
    {
        $this->repository->updateStatus($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->repository->updateStatus($id, 'rejected');
    }
}
