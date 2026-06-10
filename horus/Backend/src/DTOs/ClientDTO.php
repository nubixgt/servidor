<?php
namespace App\DTOs;

class ClientDTO
{
    public function __construct(
        public ?string $fecha,
        public ?string $cliente,
        public ?int $refiere,
        public ?float $capital,
        public ?string $plazo,
        public ?float $porcentaje,
        public ?float $interesPagar,
        public ?float $devolvioCapital,
        public ?float $pagoInteres,
        public ?string $observaciones,
        public ?string $documentacion = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['fecha'] ?? null,
            $data['cliente'] ?? null,
            isset($data['refiere']) && $data['refiere'] !== '' ? (int)$data['refiere'] : null,
            isset($data['capital']) ? (float)$data['capital'] : null,
            $data['plazo'] ?? null,
            isset($data['porcentaje']) ? (float)$data['porcentaje'] : null,
            isset($data['interes_pagar']) ? (float)$data['interes_pagar'] : null,
            isset($data['devolvio_capital']) ? (float)$data['devolvio_capital'] : null,
            isset($data['pago_interes']) ? (float)$data['pago_interes'] : null,
            $data['observaciones'] ?? null,
            $data['documentacion'] ?? null
        );
    }
}
