<?php
namespace App\Entities;

class PlanEntity
{
    public function __construct(
        public int $userId,
        public ?string $seasonPlanMd,
        public ?array $seasonPhases,
        public ?array $weeklyPlan,
        public ?array $summary,
        public ?string $seasonGeneratedAt,
        public ?string $weekGeneratedAt,
        public ?string $lastGenerationJob = null,
        public ?string $analysisHtml = null,
        public ?string $planningHtml = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'season_plan_md' => $this->seasonPlanMd,
            'season_phases' => $this->seasonPhases,
            'weekly_plan' => $this->weeklyPlan,
            'summary' => $this->summary,
            'season_generated_at' => $this->seasonGeneratedAt,
            'week_generated_at' => $this->weekGeneratedAt,
            'has_season' => ($this->seasonPlanMd ?? '') !== '',
            'has_week' => !empty($this->weeklyPlan['weeks'] ?? []),
            'has_analysis_html' => ($this->analysisHtml ?? '') !== '',
            'has_planning_html' => ($this->planningHtml ?? '') !== '',
            // Para no pisar cambios (Beta 3.0, F6): quien edita una sesión manda la versión
            // que tenía delante, y si el plan cambió entre medias no se guarda.
            'plan_version' => self::version($this->weeklyPlan),
        ];
    }

    public static function version(?array $weeklyPlan): string
    {
        return $weeklyPlan === null ? '' : substr(sha1((string) json_encode($weeklyPlan, JSON_UNESCAPED_UNICODE)), 0, 16);
    }

    public static function empty(int $userId): self
    {
        return new self($userId, null, null, null, null, null, null);
    }
}
