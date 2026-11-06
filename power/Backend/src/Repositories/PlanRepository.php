<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\PlanEntity;

class PlanRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUserId(int $userId): ?PlanEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM athlete_plans WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Copy the current active plan into history before it gets overwritten
     * (mirrors PaginaLocal's archive_previous_generation). No-op if there is
     * no active plan yet.
     */
    public function archiveCurrent(int $userId): void
    {
        $cur = $this->findByUserId($userId);
        if (!$cur || (($cur->seasonPlanMd ?? '') === '' && empty($cur->weeklyPlan['weeks'] ?? []))) {
            return;
        }

        $weekAt = $cur->weekGeneratedAt;
        $seasonAt = $cur->seasonGeneratedAt;
        $kind = ($weekAt !== null && ($seasonAt === null || $weekAt >= $seasonAt)) ? 'week' : 'season';
        $generatedAt = $kind === 'week' ? ($weekAt ?? $seasonAt) : ($seasonAt ?? $weekAt);

        $stmt = $this->pdo->prepare(
            "INSERT INTO athlete_plan_history
                (user_id, kind, season_plan_md, season_phases, weekly_plan, summary, generated_at)
             VALUES (:uid, :kind, :md, :phases, :wp, :summary, :gen)"
        );
        $stmt->execute([
            'uid' => $userId,
            'kind' => $kind,
            'md' => $cur->seasonPlanMd,
            'phases' => $cur->seasonPhases !== null ? json_encode($cur->seasonPhases, JSON_UNESCAPED_UNICODE) : null,
            'wp' => $cur->weeklyPlan !== null ? json_encode($cur->weeklyPlan, JSON_UNESCAPED_UNICODE) : null,
            'summary' => $cur->summary !== null ? json_encode($cur->summary, JSON_UNESCAPED_UNICODE) : null,
            'gen' => $generatedAt,
        ]);
    }

    /** @return array<int,array> newest first, list-view shape (no full weekly_plan). */
    public function listHistory(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, kind, weekly_plan, summary, generated_at, archived_at
             FROM athlete_plan_history WHERE user_id = :uid ORDER BY archived_at DESC, id DESC"
        );
        $stmt->execute(['uid' => $userId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $wp = ($row['weekly_plan'] !== null && $row['weekly_plan'] !== '')
                ? (json_decode((string) $row['weekly_plan'], true) ?: [])
                : [];
            $summary = ($row['summary'] !== null && $row['summary'] !== '')
                ? (json_decode((string) $row['summary'], true) ?: [])
                : [];
            $weeks = $wp['weeks'] ?? [];
            $sessions = 0;
            // Qué días cubría (2026-10-21): la lista sólo decía cuándo se escribió, y nueve tarjetas «Semana» con
            // la hora al minuto no le decían al atleta cuál abrir.
            $fechas = [];
            foreach ($weeks as $w) {
                foreach ($w['days'] ?? [] as $d) {
                    if (empty($d['is_rest'])) {
                        $sessions++;
                    }
                    if (!empty($d['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d['date'])) {
                        $fechas[] = (string) $d['date'];
                    }
                }
            }
            sort($fechas);
            // La ÚLTIMA semana de esa versión: cada versión guarda las anteriores, y tres tarjetas que empezaban el
            // 26-sep parecían duplicados (revisión del 2026-10-21).
            $ultima = [];
            foreach (($weeks ? $weeks[count($weeks) - 1]['days'] ?? [] : []) as $d) {
                if (!empty($d['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d['date'])) {
                    $ultima[] = (string) $d['date'];
                }
            }
            sort($ultima);
            $out[] = [
                'id' => (int) $row['id'],
                'kind' => $row['kind'],
                'weeks' => count($weeks),
                'sessions' => $sessions,
                'desde' => $fechas[0] ?? null,
                'hasta' => $fechas ? $fechas[count($fechas) - 1] : null,
                'ultima_desde' => $ultima[0] ?? null,
                'ultima_hasta' => $ultima ? $ultima[count($ultima) - 1] : null,
                'total_cost_usd' => $summary['total_cost_usd'] ?? null,
                'generated_at' => $row['generated_at'],
                'archived_at' => $row['archived_at'],
            ];
        }
        return $out;
    }

    public function findHistoryEntry(int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_plan_history WHERE user_id = :uid AND id = :id"
        );
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $json = fn($v) => ($v !== null && $v !== '') ? (json_decode((string) $v, true) ?: null) : null;
        return [
            'id' => (int) $row['id'],
            'kind' => $row['kind'],
            'season_plan_md' => $row['season_plan_md'],
            'season_phases' => $json($row['season_phases']),
            'weekly_plan' => $json($row['weekly_plan']),
            'summary' => $json($row['summary']),
            'generated_at' => $row['generated_at'],
            'archived_at' => $row['archived_at'],
        ];
    }

    public function deleteHistoryEntry(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM athlete_plan_history WHERE user_id = :uid AND id = :id"
        );
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Persist a season generation result. */
    public function saveSeason(int $userId, ?string $md, ?array $phases, ?array $summary, ?string $analysisHtml = null): void
    {
        $sql = "INSERT INTO athlete_plans (user_id, season_plan_md, season_phases, summary, analysis_html, season_generated_at)
                VALUES (:uid, :md, :phases, :summary, :ah, NOW())
                ON DUPLICATE KEY UPDATE
                    season_plan_md = VALUES(season_plan_md),
                    season_phases = VALUES(season_phases),
                    summary = VALUES(summary),
                    analysis_html = VALUES(analysis_html),
                    season_generated_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP";
        $this->pdo->prepare($sql)->execute([
            'uid' => $userId,
            'md' => $md,
            'phases' => $phases !== null ? json_encode($phases, JSON_UNESCAPED_UNICODE) : null,
            'summary' => $summary !== null ? json_encode($summary, JSON_UNESCAPED_UNICODE) : null,
            'ah' => ($analysisHtml ?? '') !== '' ? $analysisHtml : null,
        ]);
    }

    /** Persist a week generation result (season fields untouched). */
    public function saveWeek(int $userId, ?array $weeklyPlan, ?array $summary, ?string $planningHtml = null): void
    {
        $sql = "INSERT INTO athlete_plans (user_id, weekly_plan, summary, planning_html, week_generated_at)
                VALUES (:uid, :wp, :summary, :ph, NOW())
                ON DUPLICATE KEY UPDATE
                    weekly_plan = VALUES(weekly_plan),
                    summary = VALUES(summary),
                    planning_html = VALUES(planning_html),
                    week_generated_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP";
        $this->pdo->prepare($sql)->execute([
            'uid' => $userId,
            'wp' => $weeklyPlan !== null ? json_encode($weeklyPlan, JSON_UNESCAPED_UNICODE) : null,
            'summary' => $summary !== null ? json_encode($summary, JSON_UNESCAPED_UNICODE) : null,
            'ph' => ($planningHtml ?? '') !== '' ? $planningHtml : null,
        ]);
    }

    /**
     * Reemplaza la semana guardada SÓLO si sigue siendo la versión que el atleta tenía
     * delante (Beta 3.0, F6). Leer, comparar y escribir van en una transacción con la fila
     * bloqueada: dos cambios seguidos desde dos pestañas no se pisan. No cambia
     * `week_generated_at`: mover una sesión no es generar una semana.
     */
    public function replaceWeeklyPlan(int $userId, string $version, array $weeklyPlan): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('SELECT weekly_plan FROM athlete_plans WHERE user_id = :u FOR UPDATE');
            $stmt->execute(['u' => $userId]);
            $fila = $stmt->fetch();
            $actual = $fila && $fila['weekly_plan'] !== null ? json_decode((string) $fila['weekly_plan'], true) : null;
            if (!$fila || \App\Entities\PlanEntity::version(is_array($actual) ? $actual : null) !== $version) {
                $this->pdo->rollBack();
                return false;
            }
            $this->pdo->prepare('UPDATE athlete_plans SET weekly_plan = :wp, updated_at = CURRENT_TIMESTAMP WHERE user_id = :u')
                ->execute(['wp' => json_encode($weeklyPlan, JSON_UNESCAPED_UNICODE), 'u' => $userId]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('PlanRepository::replaceWeeklyPlan: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteByUserId(int $userId): void
    {
        $this->pdo->prepare("DELETE FROM athlete_plans WHERE user_id = :uid")->execute(['uid' => $userId]);
    }

    /**
     * Vacia el historial de planes del atleta.
     *
     * Cada regeneracion archiva el plan anterior aqui (`archiveCurrent`), asi
     * que tras un par de rehacer el historial es sobre todo versiones
     * descartadas. "Eliminar plan y semana" solo borraba el plan activo y el
     * atleta se encontraba despues con todo lo que acababa de tirar esperando
     * en Historial -- lo reporto asi: "eliminar no borra el historial".
     *
     * El expediente de lo ENTRENADO (`track_record.jsonl` en el AiService) no
     * vive aqui y no se toca: eso es su historia, no la de nuestros planes.
     */
    public function deleteHistoryByUserId(int $userId): void
    {
        $this->pdo->prepare("DELETE FROM athlete_plan_history WHERE user_id = :uid")->execute(['uid' => $userId]);
    }

    /**
     * Borra SÓLO la semana y deja el macrociclo en pie.
     *
     * Hasta aquí lo único que había era borrarlo todo, y eso obligaba a
     * regenerar un plan de 24 semanas -- una llamada cara, y a un modelo con
     * búsqueda web -- para deshacerse de siete días que no gustaron. El
     * macrociclo y la semana son cosas de escalas distintas y se deciden por
     * separado, así que se borran por separado.
     *
     * `summary` es de los dos a la vez (lo escriben las dos generaciones), así
     * que no se toca: la mitad de temporada sigue siendo válida, y la de
     * semana la reescribe la siguiente. `planning_html` sí, que es el informe
     * imprimible de ESTA semana y sin semana no describe nada.
     */
    public function deleteWeekByUserId(int $userId): void
    {
        // SOLO LA ULTIMA SEMANA, no el calendario entero.
        //
        // `weekly_plan` acumula hasta 12 semanas (MAX_WEEKS_KEPT). Ponerlo a
        // NULL borraba tambien las ya entrenadas: la numeracion volvia a 1, el
        // dialogo de "desde que dia empiezas" reaparecia como en el primer uso
        // y la continuidad se perdia. El atleta pidio quitar ESTA semana.
        // Auditoria previa al merge, 2026-09-11.
        $plan = $this->findByUserId($userId);
        $semanas = $plan?->weeklyPlan['weeks'] ?? null;

        if (is_array($semanas) && count($semanas) > 1) {
            $restantes = $plan->weeklyPlan;
            array_pop($restantes['weeks']);
            $this->pdo->prepare(
                "UPDATE athlete_plans
                    SET weekly_plan = :wp, planning_html = NULL, week_generated_at = NULL,
                        updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = :uid"
            )->execute(['uid' => $userId, 'wp' => json_encode($restantes, JSON_UNESCAPED_UNICODE)]);
            return;
        }

        $this->pdo->prepare(
            "UPDATE athlete_plans
                SET weekly_plan = NULL, planning_html = NULL, week_generated_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
              WHERE user_id = :uid"
        )->execute(['uid' => $userId]);
    }

    /** Borra sólo el macrociclo y deja la semana en curso. */
    public function deleteSeasonByUserId(int $userId): void
    {
        $this->pdo->prepare(
            "UPDATE athlete_plans
                SET season_plan_md = NULL, season_phases = NULL, analysis_html = NULL,
                    season_generated_at = NULL, updated_at = CURRENT_TIMESTAMP
              WHERE user_id = :uid"
        )->execute(['uid' => $userId]);
    }

    private function hydrate(array $row): PlanEntity
    {
        $json = fn($v) => ($v !== null && $v !== '') ? (json_decode((string) $v, true) ?: null) : null;
        return new PlanEntity(
            userId: (int) $row['user_id'],
            seasonPlanMd: $row['season_plan_md'] !== null ? (string) $row['season_plan_md'] : null,
            seasonPhases: $json($row['season_phases']),
            weeklyPlan: $json($row['weekly_plan']),
            summary: $json($row['summary']),
            seasonGeneratedAt: $row['season_generated_at'] !== null ? (string) $row['season_generated_at'] : null,
            weekGeneratedAt: $row['week_generated_at'] !== null ? (string) $row['week_generated_at'] : null,
            lastGenerationJob: isset($row['last_generation_job']) && $row['last_generation_job'] !== null
                ? (string) $row['last_generation_job'] : null,
            analysisHtml: isset($row['analysis_html']) && $row['analysis_html'] !== null ? (string) $row['analysis_html'] : null,
            planningHtml: isset($row['planning_html']) && $row['planning_html'] !== null ? (string) $row['planning_html'] : null,
        );
    }

    /** @return ?string the stored HTML report ('analysis' | 'planning'), or null. */
    public function reportHtml(int $userId, string $type): ?string
    {
        $col = $type === 'analysis' ? 'analysis_html' : 'planning_html';
        $stmt = $this->pdo->prepare("SELECT $col AS html FROM athlete_plans WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return ($row && $row['html'] !== null && $row['html'] !== '') ? (string) $row['html'] : null;
    }

    public function setLastGenerationJob(int $userId, string $jobId): void
    {
        $this->pdo->prepare(
            "UPDATE athlete_plans SET last_generation_job = :j WHERE user_id = :uid"
        )->execute(['j' => $jobId, 'uid' => $userId]);
    }
}
