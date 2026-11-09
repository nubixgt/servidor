<?php
namespace App\Services;

use App\Repositories\ExampleRepository;
use App\DTOs\ExampleDTO;
use App\Entities\ExampleEntity;

class ExampleService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ExampleRepository();
    }

    public function getAllExamples()
    {
        return $this->repository->findAll();
    }

    public function createExample(ExampleDTO $dto)
    {
        // Business logic here (validation, transformation)
        if (empty($dto->name)) {
            throw new \Exception("Name cannot be empty");
        }

        $entity = new ExampleEntity(null, $dto->name, $dto->description);
        return $this->repository->create($entity);
    }
}
