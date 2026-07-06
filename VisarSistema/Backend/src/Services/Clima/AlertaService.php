<?php
namespace App\Services\Clima;

use App\Repositories\Clima\AlertaRepository;
use App\DTOs\Clima\AlertaDTO;

class AlertaService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new AlertaRepository();
    }

    public function getAlertas()
    {
        $data = $this->repository->getAll();
        return array_map(function($item) {
            return AlertaDTO::fromArray($item);
        }, $data);
    }

    public function getAlerta($id)
    {
        $data = $this->repository->findById($id);
        return $data ? AlertaDTO::fromArray($data) : null;
    }

    public function createAlerta($data)
    {
        return $this->repository->create($data);
    }

    public function updateAlerta($id, $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteAlerta($id)
    {
        return $this->repository->delete($id);
    }
}
