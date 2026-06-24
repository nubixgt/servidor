<?php
namespace App\Services;

use App\Repositories\PadreRepository;
use App\DTOs\PadreDTO;
use App\Entities\PadreEntity;

class PadreService
{
    private const VALID_DIRECTIONS = [
        'SANIDAD ANIMAL', 'SANIDAD VEGETAL', 'INOCUIDAD', 'FITOZOOGENÉTICA',
        'DIPESCA', 'UDAFA', 'RRHH', 'VICEDESPACHO',
    ];

    private PadreRepository $repository;

    public function __construct()
    {
        $this->repository = new PadreRepository();
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function register(PadreDTO $dto): int
    {
        $this->validate($dto);

        if ($this->repository->findByCorreo($dto->correo) !== null) {
            throw new \RuntimeException('Ya existe un registro con ese correo electrónico.');
        }

        $entity = new PadreEntity(
            null,
            $dto->nombreCompleto,
            $dto->telefono,
            $dto->correo,
            $dto->direccion,
            $dto->departamento
        );

        return $this->repository->create($entity);
    }

    public function update(int $id, PadreDTO $dto): void
    {
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new \RuntimeException('Registro no encontrado.');
        }

        $this->validate($dto);

        $duplicate = $this->repository->findByCorreo($dto->correo);
        if ($duplicate !== null && $duplicate->id !== $id) {
            throw new \RuntimeException('Ya existe otro registro con ese correo electrónico.');
        }

        $entity = new PadreEntity(
            $id,
            $dto->nombreCompleto,
            $dto->telefono,
            $dto->correo,
            $dto->direccion,
            $dto->departamento
        );

        $this->repository->update($entity);
    }

    public function delete(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new \RuntimeException('Registro no encontrado.');
        }

        $this->repository->delete($id);
    }

    private function validate(PadreDTO $dto): void
    {
        if (strlen($dto->nombreCompleto) < 4) {
            throw new \InvalidArgumentException('El nombre debe tener al menos 4 caracteres.');
        }

        $cleanPhone = preg_replace('/\D/', '', $dto->telefono);
        if (strlen($cleanPhone) !== 8) {
            throw new \InvalidArgumentException('El teléfono debe contener 8 dígitos.');
        }

        if (!filter_var($dto->correo, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El formato del correo no es válido.');
        }

        if (!in_array($dto->direccion, self::VALID_DIRECTIONS, true)) {
            throw new \InvalidArgumentException('La dirección seleccionada no es válida.');
        }

        if (empty($dto->departamento)) {
            throw new \InvalidArgumentException('El departamento es obligatorio.');
        }
    }
}
