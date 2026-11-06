<?php
namespace App\DTOs;

/**
 * One row of athlete_competitions. Mirrors PaginaLocal's CompetitionModel
 * plus elevation_gain_m (which PaginaLocal's frontend sends).
 */
class CompetitionDTO
{
    public function __construct(
        public string $name = '',
        public ?string $raceDate = null,      // Y-m-d, from the incoming "date" key
        public string $raceType = '',
        public string $priority = 'B',        // A|B|C
        public string $targetTime = '',
        public string $distance = '',
        public ?int $elevationGainM = null
    ) {
    }

    public static function fromRequest(array $d): self
    {
        $rawDate = $d['date'] ?? $d['race_date'] ?? null;
        $raceDate = null;
        if (is_string($rawDate) && trim($rawDate) !== '') {
            $ts = strtotime($rawDate);
            $raceDate = $ts ? date('Y-m-d', $ts) : null;
        }

        $priority = strtoupper(trim((string) ($d['priority'] ?? 'B')));
        if (!in_array($priority, ['A', 'B', 'C'], true)) {
            $priority = 'B';
        }

        $elev = $d['elevation_gain_m'] ?? $d['elevationGainM'] ?? null;
        $elevationGainM = ($elev === null || $elev === '') ? null : max(0, (int) $elev);

        return new self(
            name: trim((string) ($d['name'] ?? '')),
            raceDate: $raceDate,
            // Sin 'Olympic' por defecto: convertia cada 5K en un triatlon para el
            // cerebro. El tipo de carrera sale de la distancia del catalogo
            // (race_distances.normalizar_competicion).
            raceType: trim((string) ($d['race_type'] ?? '')),
            priority: $priority,
            targetTime: trim((string) ($d['target_time'] ?? '')),
            distance: trim((string) ($d['distance'] ?? '')),
            elevationGainM: $elevationGainM
        );
    }

    /** @return array<string,mixed> column => value for athlete_competitions */
    public function toRow(): array
    {
        return [
            'name' => $this->name,
            'race_date' => $this->raceDate,
            'race_type' => $this->raceType,
            'priority' => $this->priority,
            'target_time' => $this->targetTime,
            'distance' => $this->distance,
            'elevation_gain_m' => $this->elevationGainM,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->name === '' && $this->raceDate === null && $this->targetTime === '' && $this->distance === '';
    }
}
