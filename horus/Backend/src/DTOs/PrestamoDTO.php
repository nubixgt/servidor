<?php
namespace App\DTOs;

class PrestamoDTO
{
    public function __construct(
        public ?int $clienteId,
        public ?float $montoPrincipal,
        public ?float $tasa,
        public ?string $tipoInteres,
        public ?int $plazo,
        public ?string $fechaDesembolso,
        public ?float $totalIntereses,
        public ?float $totalSeguro,
        public ?float $costoTotal,
        public ?string $estado = 'ACTIVO'
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            isset($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            isset($data['monto_principal']) ? (float)$data['monto_principal'] : null,
            isset($data['tasa']) ? (float)$data['tasa'] : null,
            $data['tipo_interes'] ?? null,
            isset($data['plazo']) ? (int)$data['plazo'] : null,
            $data['fecha_desembolso'] ?? null,
            isset($data['total_intereses']) ? (float)$data['total_intereses'] : null,
            isset($data['total_seguro']) ? (float)$data['total_seguro'] : null,
            isset($data['costo_total']) ? (float)$data['costo_total'] : null,
            $data['estado'] ?? 'ACTIVO'
        );
    }
}
