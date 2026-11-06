<?php
namespace App\Entities;

class CheckinEntity
{
    public function __construct(
        public ?int $id,
        public string $date,
        public ?int $sleep,
        public ?int $fatigue,
        public ?int $stress,
        public ?int $soreness,
        public ?int $motivation,
        public string $niggleLocation,
        public ?int $nigglePain,
        public ?string $lastWeekNote,
        public string $weekAhead,
        public ?int $hooperTotal,
        public ?string $recordedAt,
        public ?string $weekNote = null,
        public array $unavailableDates = [],
        public ?string $weekAsExpected = null,
        public array $openDates = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'sleep' => $this->sleep,
            'fatigue' => $this->fatigue,
            'stress' => $this->stress,
            'soreness' => $this->soreness,
            'motivation' => $this->motivation,
            'niggle_location' => $this->niggleLocation,
            'niggle_pain' => $this->nigglePain,
            'last_week_note' => $this->lastWeekNote,
            'week_note' => $this->weekNote,
            'unavailable_dates' => $this->unavailableDates,
            // Un objeto {fecha: [deportes]}; vacío sale como [] y el modal lo acepta.
            'open_dates' => $this->openDates,
            // Se guardaba (migración 016) y no salía de aquí: ni el cerebro ni
            // el panel lo leían. Auditoría de cables, 2026-09-15.
            'week_as_expected' => $this->weekAsExpected,
            'week_ahead' => $this->weekAhead,
            'hooper_total' => $this->hooperTotal,
            'recorded_at' => $this->recordedAt,
        ];
    }
}
