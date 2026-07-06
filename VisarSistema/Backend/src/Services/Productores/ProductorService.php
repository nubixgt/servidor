<?php
namespace App\Services\Productores;

use App\Repositories\Productores\ProductorRepository;
use App\DTOs\Productores\ProductorDTO;

class ProductorService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ProductorRepository();
    }

    public function getProductores($filters = [])
    {
        $data = $this->repository->getAll($filters);
        return ProductorDTO::mapCollection($data);
    }

    public function getProductor($id)
    {
        $data = $this->repository->findById($id);
        return $data ? ProductorDTO::map($data) : null;
    }

    public function createProductor($data)
    {
        // Validation: Check if DPI already exists
        if ($this->repository->findByDpi($data['dpi'])) {
            throw new \Exception("Ya existe un productor registrado con el DPI " . $data['dpi']);
        }
        return $this->repository->create($data);
    }

    public function updateProductor($id, $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteProductor($id)
    {
        return $this->repository->delete($id);
    }
}
