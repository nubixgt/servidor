<?php
namespace App\DTOs;

class JugadorDTO
{
    public function __construct(
        public string $nombre_completo,
        public ?int $equipo_id,
        public ?int $dorsal,
        public ?string $dpi,
        public ?string $fecha_nacimiento,
        public ?string $nacionalidad,
        public ?string $posicion,
        public ?int $estatura_cm,
        public ?int $peso_kg,
        public string $estado,
        public ?float $ppg,
        public ?float $rpg,
        public ?float $apg,
        public ?float $tres_pct
    ) {
    }

    public static function fromRequest(array $d): self
    {
        $nullNum = fn ($v) => ($v === null || $v === '') ? null : $v;

        return new self(
            trim($d['nombre_completo'] ?? ''),
            $nullNum($d['equipo_id'] ?? null) !== null ? (int) $d['equipo_id'] : null,
            $nullNum($d['dorsal'] ?? null) !== null ? (int) $d['dorsal'] : null,
            isset($d['dpi']) && trim($d['dpi']) !== '' ? trim($d['dpi']) : null,
            isset($d['fecha_nacimiento']) && $d['fecha_nacimiento'] !== '' ? $d['fecha_nacimiento'] : null,
            isset($d['nacionalidad']) && trim($d['nacionalidad']) !== '' ? trim($d['nacionalidad']) : 'Guatemalteca',
            isset($d['posicion']) && $d['posicion'] !== '' ? $d['posicion'] : null,
            $nullNum($d['estatura_cm'] ?? null) !== null ? (int) $d['estatura_cm'] : null,
            $nullNum($d['peso_kg'] ?? null) !== null ? (int) $d['peso_kg'] : null,
            ($d['estado'] ?? 'Habilitado') ?: 'Habilitado',
            $nullNum($d['ppg'] ?? null) !== null ? (float) $d['ppg'] : null,
            $nullNum($d['rpg'] ?? null) !== null ? (float) $d['rpg'] : null,
            $nullNum($d['apg'] ?? null) !== null ? (float) $d['apg'] : null,
            $nullNum($d['tres_pct'] ?? null) !== null ? (float) $d['tres_pct'] : null
        );
    }
}
