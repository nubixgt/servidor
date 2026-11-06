<?php
namespace App\DTOs;

/**
 * One row of athlete_injuries. Mirrors PaginaLocal's InjuryModel.
 */
class InjuryDTO
{
    public function __construct(
        public string $location = '',
        public ?string $laterality = null,   // izquierda|derecha|bilateral
        public string $onset = '',
        public ?int $painScale = null,       // 0-10
        public ?string $aggravatingFactors = null,
        public ?string $treatment = null,
        // Una lesion curada se queda en la tabla cambiando de estado, no se
        // borra: el historial empieza a valer justo cuando deja de doler.
        public string $status = 'activa',
        // Lo que el atleta quiere que su entrenador sepa y no cabe en los otros
        // campos ("me pasa solo en bajada", "el fisio dice que es la cadera").
        public ?string $notes = null
    ) {
    }

    public static function fromRequest(array $d): self
    {
        $lat = trim((string) ($d['laterality'] ?? ''));
        $laterality = in_array($lat, ['izquierda', 'derecha', 'bilateral'], true) ? $lat : null;

        $painRaw = $d['pain_scale'] ?? $d['painScale'] ?? null;
        $painScale = null;
        if ($painRaw !== null && $painRaw !== '') {
            $painScale = max(0, min(10, (int) $painRaw));
        }

        $factors = trim((string) ($d['aggravating_factors'] ?? ''));
        $treatment = trim((string) ($d['treatment'] ?? ''));
        $notes = trim((string) ($d['notes'] ?? ''));

        // Por defecto ACTIVA: una lesion que se registra sin decir nada es una
        // que duele ahora. Marcarla resuelta es un acto deliberado.
        $statusRaw = trim((string) ($d['status'] ?? ''));
        $status = in_array($statusRaw, ['activa', 'resuelta'], true) ? $statusRaw : 'activa';

        return new self(
            location: trim((string) ($d['location'] ?? '')),
            laterality: $laterality,
            onset: trim((string) ($d['onset'] ?? '')),
            painScale: $painScale,
            aggravatingFactors: $factors === '' ? null : $factors,
            treatment: $treatment === '' ? null : $treatment,
            status: $status,
            notes: $notes === '' ? null : $notes
        );
    }

    /** @return array<string,mixed> column => value for athlete_injuries */
    public function toRow(): array
    {
        return [
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

    public function isEmpty(): bool
    {
        // `status` NO cuenta: siempre trae un valor por defecto, y contarlo
        // haria que una fila vacia pareciera llena y se guardara.
        return $this->location === '' && $this->onset === '' && $this->painScale === null
            && $this->aggravatingFactors === null && $this->treatment === null
            && $this->notes === null;
    }
}
