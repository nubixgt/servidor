<?php
namespace App\DTOs;

class PartidoDTO
{
    public function __construct(
        public int $equipo_local_id,
        public int $equipo_visitante_id,
        public ?int $jornada,
        public string $fase,
        public ?string $fecha,
        public ?string $hora,
        public ?string $sede,
        public string $estado,
        public int $marcador_local,
        public int $marcador_visitante,
        public ?string $arbitro_principal,
        public ?string $juez_mesa,
        public bool $acta_cerrada
    ) {
    }

    public static function fromRequest(array $d): self
    {
        $str = fn ($k) => isset($d[$k]) && trim((string) $d[$k]) !== '' ? trim((string) $d[$k]) : null;

        return new self(
            (int) ($d['equipo_local_id'] ?? 0),
            (int) ($d['equipo_visitante_id'] ?? 0),
            isset($d['jornada']) && $d['jornada'] !== '' ? (int) $d['jornada'] : null,
            $str('fase') ?? 'Regular',
            $str('fecha'),
            $str('hora'),
            $str('sede'),
            $str('estado') ?? 'Programado',
            (int) ($d['marcador_local'] ?? 0),
            (int) ($d['marcador_visitante'] ?? 0),
            $str('arbitro_principal'),
            $str('juez_mesa'),
            !empty($d['acta_cerrada'])
        );
    }
}
