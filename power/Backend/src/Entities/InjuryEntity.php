<?php
namespace App\Entities;

class InjuryEntity
{
    public function __construct(
        public ?int $id,
        public string $location,
        public ?string $laterality,
        public string $onset,
        public ?int $painScale,
        public ?string $aggravatingFactors,
        public ?string $treatment,
        // Migracion 014: una lesion curada CAMBIA DE ESTADO, no se borra, y
        // una resuelta deja de ser una restriccion.
        public string $status = 'activa',
        public ?string $notes = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'laterality' => $this->laterality,
            'onset' => $this->onset,
            'pain_scale' => $this->painScale,
            'aggravating_factors' => $this->aggravatingFactors,
            'treatment' => $this->treatment,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
