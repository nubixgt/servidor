<?php
namespace App\Entities;

class ClientEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $fecha = null,
        public ?string $cliente = null,
        public ?string $refiere = null,
        public ?float $capital = null,
        public ?string $plazo = null,
        public ?float $porcentaje = null,
        public ?float $interesPagar = null,
        public ?float $devolvioCapital = null,
        public ?float $pagoInteres = null,
        public ?string $observaciones = null,
        public ?string $documentacion = null
    ) {
    }
}
