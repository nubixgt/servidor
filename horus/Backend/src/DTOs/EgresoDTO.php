<?php
namespace App\DTOs;

class EgresoDTO
{
    public function __construct(
        public ?int $recurrente_id = null,
        public ?string $tipo_egreso = null,
        public ?string $fecha = null,
        public ?string $referencia = null,
        public ?string $pagador = null,
        public ?string $descripcion_concepto = null,
        public array $registros = []
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $registros = [];
        if (isset($data['registros'])) {
            if (is_string($data['registros'])) {
                $registros = json_decode($data['registros'], true) ?? [];
            } else if (is_array($data['registros'])) {
                $registros = $data['registros'];
            }
        }

        return new self(
            recurrente_id: !empty($data['recurrente_id']) ? (int)$data['recurrente_id'] : null,
            tipo_egreso: $data['tipo_egreso'] ?? null,
            fecha: $data['fecha'] ?? null,
            referencia: $data['referencia'] ?? null,
            pagador: $data['pagador'] ?? null,
            descripcion_concepto: $data['descripcion_concepto'] ?? null,
            registros: $registros
        );
    }
}
