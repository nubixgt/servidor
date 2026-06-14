<?php
namespace App\DTOs;

class RegistroVacunacionDTO
{
    public string $fecha;
    public string $vacunador;
    public string $cliente;
    public string $direccion;
    public string $servicio;
    public int $cantidad;
    public float $costoPorAve;
    public float $total;
    public string $estado;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->fecha = $data['fecha'] ?? '';
        $dto->vacunador = $data['vacunador'] ?? '';
        $dto->cliente = $data['cliente'] ?? '';
        $dto->direccion = $data['direccion'] ?? '';
        $dto->servicio = $data['servicio'] ?? '';
        $dto->cantidad = isset($data['cantidad']) ? (int)$data['cantidad'] : 0;
        $dto->costoPorAve = isset($data['costoPorAve']) ? (float)$data['costoPorAve'] : 0.0;
        $dto->total = isset($data['total']) ? (float)$data['total'] : 0.0;
        $dto->estado = $data['estado'] ?? 'Completado';

        self::validate($dto);
        return $dto;
    }

    private static function validate(self $dto): void
    {
        if (empty($dto->fecha) || empty($dto->vacunador) || empty($dto->cliente) || empty($dto->servicio) || $dto->cantidad <= 0) {
            throw new \InvalidArgumentException('Faltan datos obligatorios para crear el registro.');
        }
    }
}
