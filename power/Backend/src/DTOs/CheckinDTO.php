<?php
namespace App\DTOs;

/**
 * One submitted pre-week check-in, normalized (ported from PaginaLocal's
 * services/ai/checkin.py::normalize_checkin).
 *
 * Every field is optional. hooper_total is only set when all four Hooper
 * items were answered -- a partial sum is not comparable to a full one.
 */
class CheckinDTO
{
    public const WEEK_AHEAD = ['normal', 'viaje', 'carga_laboral', 'poco_sueno'];
    /**
     * Si la semana que se cierra salio como el atleta esperaba.
     *
     * Tres opciones y no una escala: con cuatro atletas en beta, "a medias en
     * tres de cuatro semanas" es un dato que se puede sumar; un 4 sobre 7 no
     * dice si sobro volumen o falto tiempo. La linea de texto que lo explica
     * ya existe al lado (`last_week_note`).
     */
    public const AS_EXPECTED = ['si', 'a_medias', 'no'];
    private const HOOPER = ['sleep', 'fatigue', 'stress', 'soreness'];

    public ?int $sleep = null;
    public ?int $fatigue = null;
    public ?int $stress = null;
    public ?int $soreness = null;
    public ?int $motivation = null;
    public string $niggleLocation = '';
    public ?int $nigglePain = null;
    public string $lastWeekNote = '';
    /** Lo que el atleta quiere que se tenga en cuenta ESTA semana. Ver Database/021. */
    public string $weekNote = '';
    /** Fechas (YYYY-MM-DD) de ESTA semana en las que el atleta no puede entrenar. Ver Database/022. */
    public array $unavailableDates = [];
    /**
     * Días que su tabla no le da y ESTA semana sí puede, con qué deportes:
     * {"2026-09-19": ["Correr"]}. Ver Database/024.
     */
    public array $openDates = [];
    public string $weekAhead = '';

    /** Los deportes de la rejilla del perfil, en su orden (web_app/server.py::DEPORTES_REJILLA). */
    public const DEPORTES_DE_LA_REJILLA = ['Correr', 'Trail', 'Bici', 'Nadar', 'Fuerza'];
    public string $weekAsExpected = '';
    public ?int $hooperTotal = null;

    public static function fromRequest(array $d): self
    {
        $dto = new self();

        $dto->sleep = self::clamp($d['sleep'] ?? null, 1, 7);
        $dto->fatigue = self::clamp($d['fatigue'] ?? null, 1, 7);
        $dto->stress = self::clamp($d['stress'] ?? null, 1, 7);
        $dto->soreness = self::clamp($d['soreness'] ?? null, 1, 7);
        $dto->motivation = self::clamp($d['motivation'] ?? null, 1, 7);
        $dto->nigglePain = self::clamp($d['niggle_pain'] ?? $d['nigglePain'] ?? null, 0, 10);

        $dto->niggleLocation = trim((string) ($d['niggle_location'] ?? $d['niggleLocation'] ?? ''));
        $dto->lastWeekNote = trim((string) ($d['last_week_note'] ?? $d['lastWeekNote'] ?? ''));
        $dto->weekNote = trim((string) ($d['week_note'] ?? $d['weekNote'] ?? ''));
        $dto->unavailableDates = self::fechas($d['unavailable_dates'] ?? $d['unavailableDates'] ?? []);
        $dto->openDates = self::fechasAbiertas($d['open_dates'] ?? $d['openDates'] ?? [], $dto->unavailableDates);

        $week = trim((string) ($d['week_ahead'] ?? $d['weekAhead'] ?? ''));
        $dto->weekAhead = in_array($week, self::WEEK_AHEAD, true) ? $week : '';

        $esperada = trim((string) ($d['week_as_expected'] ?? $d['weekAsExpected'] ?? ''));
        $dto->weekAsExpected = in_array($esperada, self::AS_EXPECTED, true) ? $esperada : '';

        $scores = [$dto->sleep, $dto->fatigue, $dto->stress, $dto->soreness];
        $dto->hooperTotal = in_array(null, $scores, true) ? null : array_sum($scores);

        return $dto;
    }

    public function hasContent(): bool
    {
        return $this->sleep !== null || $this->fatigue !== null || $this->stress !== null
            || $this->soreness !== null || $this->motivation !== null
            || $this->niggleLocation !== '' || $this->lastWeekNote !== '' || $this->weekAhead !== ''
            || $this->weekAsExpected !== '' || $this->weekNote !== '' || $this->unavailableDates !== []
            || $this->openDates !== [];
    }

    /** @return array<string,mixed> column => value for athlete_checkins */
    public function toColumns(): array
    {
        return [
            'sleep' => $this->sleep,
            'fatigue' => $this->fatigue,
            'stress' => $this->stress,
            'soreness' => $this->soreness,
            'motivation' => $this->motivation,
            'niggle_location' => $this->niggleLocation,
            'niggle_pain' => $this->nigglePain,
            'last_week_note' => $this->lastWeekNote !== '' ? $this->lastWeekNote : null,
            'week_note' => $this->weekNote !== '' ? $this->weekNote : null,
            'unavailable_dates' => $this->unavailableDates !== [] ? json_encode($this->unavailableDates) : null,
            'open_dates' => $this->openDates !== [] ? json_encode($this->openDates, JSON_UNESCAPED_UNICODE) : null,
            'week_ahead' => $this->weekAhead,
            'week_as_expected' => $this->weekAsExpected !== '' ? $this->weekAsExpected : null,
            'hooper_total' => $this->hooperTotal,
        ];
    }

    /** Fechas ISO válidas, sin repetir, ordenadas; como mucho 14. Cualquier otra cosa se descarta. */
    private static function fechas($valor): array
    {
        if (!is_array($valor)) {
            return [];
        }
        $vistas = [];
        foreach ($valor as $v) {
            $v = substr(trim((string) $v), 0, 10);
            $fecha = \DateTime::createFromFormat('Y-m-d', $v);
            if ($fecha && $fecha->format('Y-m-d') === $v) {
                $vistas[$v] = true;
            }
        }
        $fechas = array_keys($vistas);
        sort($fechas);
        return array_slice($fechas, 0, 14);
    }

    /**
     * {fecha: [deportes]} con fechas válidas y deportes de la rejilla, como mucho 14.
     * Un día sin deportes no se abre, y uno que también marcó como que no puede
     * tampoco: gana el no. Misma lectura que services/ai/checkin.py::dias_abiertos.
     */
    private static function fechasAbiertas($valor, array $noPuede): array
    {
        if (!is_array($valor)) {
            return [];
        }
        $salida = [];
        foreach ($valor as $clave => $deportes) {
            $f = substr(trim((string) $clave), 0, 10);
            $fecha = \DateTime::createFromFormat('Y-m-d', $f);
            if (!$fecha || $fecha->format('Y-m-d') !== $f || in_array($f, $noPuede, true) || !is_array($deportes)) {
                continue;
            }
            $elegidos = array_values(array_filter(
                self::DEPORTES_DE_LA_REJILLA,
                fn($d) => in_array($d, $deportes, true)
            ));
            if ($elegidos !== []) {
                $salida[$f] = $elegidos;
            }
        }
        ksort($salida);
        return array_slice($salida, 0, 14, true);
    }

    private static function clamp($value, int $low, int $high): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $n = (int) $value;
        return ($n >= $low && $n <= $high) ? $n : null;
    }
}
