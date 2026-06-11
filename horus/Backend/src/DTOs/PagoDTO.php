<?php
namespace App\DTOs;

class PagoDTO
{
    public function __construct(
        public ?string $fecha,
        public ?int $cliente_id,
        public ?string $banco,
        public ?string $referencia,
        public ?float $monto_pagado,
        public ?float $interes,
        public ?string $fecha_interes,
        public ?float $capital,
        public ?string $fecha_capital
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            isset($data['cliente_id']) && $data['cliente_id'] !== '' ? (int)$data['cliente_id'] : null,
            $data['banco'] ?? null,
            $data['referencia'] ?? null,
            isset($data['monto_pagado']) && $data['monto_pagado'] !== '' ? (float)$data['monto_pagado'] : null,
            isset($data['interes']) && $data['interes'] !== '' ? (float)$data['interes'] : null,
            $data['fecha_interes'] ?? null,
            isset($data['capital']) && $data['capital'] !== '' ? (float)$data['capital'] : null,
            $data['fecha_capital'] ?? null
        );
    }
}
