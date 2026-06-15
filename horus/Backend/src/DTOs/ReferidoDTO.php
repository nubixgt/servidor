<?php
namespace App\DTOs;

class ReferidoDTO
{
    public function __construct(
        public ?string $nombre,
        public ?string $dpi,
        public ?string $telefono,
        public ?string $direccion,
        public ?string $numeroCuenta,
        public ?string $banco,
        public ?string $tipoCuenta,
        public ?string $fotoPerfil = null,
        public ?string $dpiAnverso = null,
        public ?string $dpiReverso = null,
        public ?float $historialPagosMensual = null,
        public ?float $historialPagosAnual = null,
        public ?string $tipoClientesRefiere = null,
        public ?int $cantidadClientes = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['nombre'] ?? null,
            $data['dpi'] ?? null,
            $data['telefono'] ?? null,
            $data['direccion'] ?? null,
            $data['numero_cuenta'] ?? null,
            $data['banco'] ?? null,
            $data['tipo_cuenta'] ?? null,
            $data['foto_perfil'] ?? null,
            $data['dpi_anverso'] ?? null,
            $data['dpi_reverso'] ?? null,
            isset($data['historial_pagos_mensual']) ? (float)$data['historial_pagos_mensual'] : null,
            isset($data['historial_pagos_anual']) ? (float)$data['historial_pagos_anual'] : null,
            $data['tipo_clientes_refiere'] ?? null,
            isset($data['cantidad_clientes']) ? (int)$data['cantidad_clientes'] : null
        );
    }
}
