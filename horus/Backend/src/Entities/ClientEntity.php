<?php
namespace App\Entities;

class ClientEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $fecha = null,
        public ?string $cliente = null,
        public ?int $refiere = null,
        public ?float $capital = null,
        public ?string $plazo = null,
        public ?float $porcentaje = null,
        public ?float $porcentajeReferido = null,
        public ?float $interesPagar = null,
        public ?string $observaciones = null,
        public ?string $documentacion = null,
        public ?string $refiere_nombre = null
    ) {
    }
}
