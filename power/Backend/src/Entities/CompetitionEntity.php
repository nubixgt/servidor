<?php
namespace App\Entities;

class CompetitionEntity
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $raceDate,
        public string $raceType,
        public string $priority,
        public string $targetTime,
        public string $distance,
        public ?int $elevationGainM
    ) {
    }

    /** PaginaLocal-shaped: race_date is exposed as `date`. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->raceDate,
            'race_type' => $this->raceType,
            'priority' => $this->priority,
            'target_time' => $this->targetTime,
            'distance' => $this->distance,
            'elevation_gain_m' => $this->elevationGainM,
        ];
    }
}
