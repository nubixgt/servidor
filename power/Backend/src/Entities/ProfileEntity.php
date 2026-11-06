<?php
namespace App\Entities;

use App\Support\ProfileFields;

/**
 * Athlete profile aggregate. Holds hydrated column values (typed PHP: bool,
 * array, float, string, null) plus the child lists. toArray() emits the
 * PaginaLocal-shaped payload the frontend and (later) the Python service expect.
 */
class ProfileEntity
{
    /**
     * @param array<string,mixed>   $columns      hydrated column => value
     * @param CompetitionEntity[]   $competitions
     * @param InjuryEntity[]        $injuries
     */
    public function __construct(
        public int $userId,
        public array $columns,
        public array $competitions = [],
        public array $injuries = []
    ) {
    }

    /** A profile with no row yet: PaginaLocal defaults + athlete name seeded from the users row. */
    public static function empty(int $userId, ?string $fallbackName): self
    {
        $columns = ProfileFields::defaults();
        $columns['full_name'] = $fallbackName ?? '';
        return new self($userId, $columns, [], []);
    }

    public function toArray(): array
    {
        $out = $this->columns;

        // PaginaLocal calls the athlete name `name`.
        $out['name'] = $out['full_name'] ?? '';
        unset($out['full_name']);

        $out['completion_rate'] = (int) ($out['completion_rate'] ?? 0);
        $out['completionRate'] = $out['completion_rate'];

        $out['competitions'] = array_map(fn(CompetitionEntity $c) => $c->toArray(), $this->competitions);
        $out['current_injuries'] = array_map(fn(InjuryEntity $i) => $i->toArray(), $this->injuries);

        return $out;
    }
}
