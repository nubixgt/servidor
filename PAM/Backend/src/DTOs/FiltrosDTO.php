<?php
namespace App\DTOs;

/**
 * Filtros del tablero y de los listados (mismos códigos que el cubo):
 *   dep departamento (1-22)     mun municipio (código INE, p. ej. 1601)    anio año de solicitud (0 = sin fecha)
 *   e   estado (orden 0-8)      r   familia de la última resolución (0-8; 9 = sin resolución)
 *   n   situación de nómina (0 nunca, 1 activo, 2 egresado)               v   última visita (0 sin, 1 positiva, 2 negativa)
 *   s   con suspensión (0/1)    f   con fallecimiento (0/1)
 *   a   banda de edad (0 = 65-69 … 5 = 90+)    am  banda mínima          t   antigüedad del trámite (1-6)
 *   regla regla de alerta / calidad            q   texto libre (nombre, DPI, teléfono, expediente)
 */
class FiltrosDTO
{
    public const ENTEROS = ['dep', 'mun', 'anio', 'e', 'r', 'n', 'v', 's', 'f', 'a', 'am', 't'];

    public function __construct(
        public ?int $dep = null,
        public ?int $mun = null,
        public ?int $anio = null,
        public ?int $e = null,
        public ?int $r = null,
        public ?int $n = null,
        public ?int $v = null,
        public ?int $s = null,
        public ?int $f = null,
        public ?int $a = null,
        public ?int $am = null,
        public ?int $t = null,
        public ?string $regla = null,
        public ?string $q = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        foreach (self::ENTEROS as $campo) {
            $valor = $data[$campo] ?? null;
            if ($valor !== null && $valor !== '' && filter_var($valor, FILTER_VALIDATE_INT) !== false) {
                $dto->$campo = (int) $valor;
            }
        }
        $dto->regla = isset($data['regla']) && $data['regla'] !== '' ? (string) $data['regla'] : null;
        $dto->q = isset($data['q']) && trim((string) $data['q']) !== '' ? mb_substr(trim((string) $data['q']), 0, 200) : null;
        return $dto;
    }

    /** Solo los filtros con valor (para la bitácora). */
    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn($v) => $v !== null);
    }
}
