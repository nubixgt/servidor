<?php
namespace App\DTOs;

class FiltrosBeneficiariosDTO
{
    public const ESTADOS = ['con_seguimiento', 'sin_seguimiento', 'con_comentario', 'con_imagen'];
    public const RESALTADOS = ['rojo', 'ambar'];
    public const POR_PAGINA_MAX = 100;

    public function __construct(
        public string $q = '',
        public ?int $comunidadId = null,
        /** 0 = solo los que no tienen comunidad base */
        public ?int $comunidadBaseId = null,
        public ?string $estado = null,
        public ?string $resaltado = null,
        public int $pagina = 1,
        public int $porPagina = 50
    ) {
    }

    public static function fromQuery(array $q): self
    {
        $entero = fn($k) => isset($q[$k]) && ctype_digit((string) $q[$k]) ? (int) $q[$k] : null;
        $estado = $q['estado'] ?? null;
        $resaltado = $q['resaltado'] ?? null;

        return new self(
            mb_substr(trim((string) ($q['q'] ?? '')), 0, 80),
            $entero('comunidad_id'),
            $entero('comunidad_base_id'),
            in_array($estado, self::ESTADOS, true) ? $estado : null,
            in_array($resaltado, self::RESALTADOS, true) ? $resaltado : null,
            max(1, $entero('pagina') ?? 1),
            min(self::POR_PAGINA_MAX, max(1, $entero('por_pagina') ?? 50))
        );
    }
}
