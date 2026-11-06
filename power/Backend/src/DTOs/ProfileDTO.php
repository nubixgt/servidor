<?php
namespace App\DTOs;

use App\Support\ProfileFields;

/**
 * Coerces an incoming profile payload (PaginaLocal-shaped: snake_case field
 * names, `name` for the athlete name, `current_injuries` for the injury list)
 * into bind-ready athlete_profiles columns + child DTO lists.
 *
 * toColumns() is the single place FE/Python naming meets DB naming.
 */
class ProfileDTO
{
    /** @var array<string,mixed> DB column => bind-ready value */
    private array $columns = [];

    /** @var CompetitionDTO[] */
    public array $competitions = [];

    /** @var InjuryDTO[] */
    public array $injuries = [];

    public static function fromRequest(array $d): self
    {
        $dto = new self();
        $get = fn(string $k) => array_key_exists($k, $d) ? $d[$k] : null;

        // name -> full_name
        $dto->columns['full_name'] = self::cleanString($d['name'] ?? $d['full_name'] ?? null, true);

        foreach (ProfileFields::STRINGS as $col) {
            if ($col === 'full_name') {
                continue;
            }
            $notNull = in_array($col, ProfileFields::NOT_NULL_STRINGS, true);
            $dto->columns[$col] = self::cleanString($get($col), $notNull);
        }

        foreach (ProfileFields::ENUMS as $col => $allowed) {
            $val = self::cleanString($get($col), false);
            $dto->columns[$col] = ($val !== null && in_array($val, $allowed, true)) ? $val : null;
        }

        foreach (ProfileFields::FLOATS as $col) {
            $dto->columns[$col] = self::boundedNumber($col, self::cleanFloat($get($col)));
        }

        foreach (ProfileFields::INTS as $col) {
            $dto->columns[$col] = self::boundedNumber($col, self::cleanInt($get($col)));
        }

        foreach (ProfileFields::BOOLS as $col) {
            $dto->columns[$col] = self::cleanBool($get($col)) ? 1 : 0;
        }

        foreach (ProfileFields::DATES as $col) {
            $dto->columns[$col] = self::cleanDate($get($col), 'Y-m-d');
        }

        foreach (ProfileFields::DATETIMES as $col) {
            $dto->columns[$col] = self::cleanDate($get($col), 'Y-m-d H:i:s');
        }

        foreach (ProfileFields::JSON_COLS as $col) {
            $val = $get($col);
            $dto->columns[$col] = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : null;
        }

        // sports_preference is NOT NULL DEFAULT 'triathlon'
        if ($dto->columns['sports_preference'] === null) {
            $dto->columns['sports_preference'] = 'triathlon';
        }
        if ($dto->columns['ai_mode'] === null || $dto->columns['ai_mode'] === '') {
            $dto->columns['ai_mode'] = 'pro';
        }

        $comps = $d['competitions'] ?? [];
        if (is_array($comps)) {
            foreach ($comps as $c) {
                if (!is_array($c)) {
                    continue;
                }
                $comp = CompetitionDTO::fromRequest($c);
                if (!$comp->isEmpty()) {
                    $dto->competitions[] = $comp;
                }
            }
        }

        $injuries = $d['current_injuries'] ?? $d['injuries'] ?? [];
        if (is_array($injuries)) {
            foreach ($injuries as $i) {
                if (!is_array($i)) {
                    continue;
                }
                $inj = InjuryDTO::fromRequest($i);
                if (!$inj->isEmpty()) {
                    $dto->injuries[] = $inj;
                }
            }
        }

        return $dto;
    }

    /** @return array<string,mixed> DB column => bind-ready value (no completion_rate) */
    public function toColumns(): array
    {
        return $this->columns;
    }

    public function fullName(): string
    {
        return (string) ($this->columns['full_name'] ?? '');
    }

    public function setFullName(string $name): void
    {
        $this->columns['full_name'] = $name;
    }

    // --- coercion helpers ---

    private static function cleanString($v, bool $notNull): ?string
    {
        if ($v === null || is_array($v)) {
            return $notNull ? '' : null;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return $notNull ? '' : null;
        }
        return $s;
    }

    private static function cleanFloat($v): ?float
    {
        if ($v === null || $v === '' || is_array($v)) {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }

    private static function cleanInt($v): ?int
    {
        if ($v === null || $v === '' || is_array($v)) {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }
        return (int) $v;
    }

    private static function cleanBool($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v)) {
            return $v === 1;
        }
        if (is_string($v)) {
            return in_array(strtolower(trim($v)), ['1', 'true', 'on', 'yes', 'si', 'sí'], true);
        }
        return false;
    }

    private static function cleanDate($v, string $format): ?string
    {
        if ($v === null || !is_string($v) || trim($v) === '') {
            return null;
        }
        $ts = strtotime($v);
        return $ts ? date($format, $ts) : null;
    }

    private static function boundedNumber(string $col, $value)
    {
        if ($value === null) {
            return null;
        }
        if (isset(ProfileFields::BOUNDS[$col])) {
            [$min, $max] = ProfileFields::BOUNDS[$col];
            if ($value < $min || $value > $max) {
                return null;
            }
        }
        return $value;
    }
}
