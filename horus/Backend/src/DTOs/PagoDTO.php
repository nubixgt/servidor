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
            !empty($data['fecha']) ? $data['fecha'] : null,
            !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            !empty($data['banco']) ? $data['banco'] : null,
            !empty($data['referencia']) ? $data['referencia'] : null,
            isset($data['monto_pagado']) && $data['monto_pagado'] !== '' ? (float)$data['monto_pagado'] : null,
            isset($data['interes']) && $data['interes'] !== '' ? (float)$data['interes'] : null,
            !empty($data['fecha_interes']) ? $data['fecha_interes'] : null,
            isset($data['capital']) && $data['capital'] !== '' ? (float)$data['capital'] : null,
            !empty($data['fecha_capital']) ? $data['fecha_capital'] : null
        );
    }
}
