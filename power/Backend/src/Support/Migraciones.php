<?php
namespace App\Support;

/**
 * Lo que deja cada migración de Database/ en la base, y qué falta.
 *
 * Las migraciones se corren a mano, también en producción, y no hay tabla que recuerde
 * cuáles corrieron (ver Backend/scripts/verificar_migraciones.php, que es quien la usa en
 * consola). Vive aquí desde la Beta 3.0 (F3) para que el diagnóstico de «Pedir ayuda»
 * diga qué migraciones faltan sin una segunda lista que se desincronice.
 * `tests/test_verificador_de_migraciones.py` falla si aparece un Database/0NN_*.sql que no
 * esté aquí.
 */
class Migraciones
{
    // Lo que deja cada migración. "tabla" debe existir; "tabla.columna" debe
    // existir; "!tabla.columna" NO debe existir (las que borran columnas).
    public const ESPERADO = [
        '001' => ['users'],
        '002' => [],  // semilla del primer administrador: datos, no estructura
        '003' => ['athlete_profiles', 'athlete_competitions', 'athlete_injuries'],
        '004' => ['athlete_notebook'],
        '005' => ['athlete_checkins'],
        '006' => ['athlete_plans'],
        '007' => ['athlete_plan_history', 'athlete_plans.last_generation_job'],
        '008' => ['athlete_plans.analysis_html', 'athlete_plans.planning_html',
                  'athlete_plan_history.analysis_html', 'athlete_plan_history.planning_html'],
        '009' => ['athlete_usage'],
        '010' => [],  // injury_prone: la retira la 017
        '011' => ['notifications', 'notification_dismissals'],
        '012' => ['app_events', 'users.last_login_at'],
        '013' => ['athlete_profiles.sport_days'],
        '014' => ['athlete_injuries.status', 'athlete_injuries.notes', 'athlete_profiles.pool_length_m'],
        '015' => ['users.terms_accepted_at', 'users.terms_version'],
        '016' => ['app_events.detail', 'athlete_checkins.week_as_expected'],
        '017' => ['!athlete_profiles.par_q_flags', '!athlete_profiles.injury_prone',
                  '!athlete_profiles.doctor_performance_report', '!athlete_profiles.doctor_chat_history',
                  '!athlete_profiles.health_disclaimer_accepted'],
        '018' => ['!athlete_profiles.available_hours', '!athlete_profiles.strength_training_preference',
                  '!athlete_profiles.brick_preference'],
        '019' => ['athlete_profiles.week_start_day'],
        '020' => ['athlete_feedback'],
        '021' => ['athlete_checkins.week_note'],
        '022' => ['athlete_checkins.unavailable_dates', 'athlete_profiles.easy_run_pace_fast_sec_per_km',
                  'athlete_profiles.easy_run_pace_slow_sec_per_km'],
        '023' => ['athlete_profiles.threshold_tests'],
        '024' => ['athlete_checkins.open_dates'],
        '025' => ['athlete_profiles.strength_profile', 'athlete_profiles.strength_routine'],
        '026' => ['athlete_strength_logs'],
        '027' => ['athlete_auto_weeks'],
        '028' => ['athlete_generations', 'notifications.user_id'],
        '029' => ['athlete_feedback.category', 'athlete_feedback.diagnostic', 'athlete_feedback.reply',
                  'notifications.feedback_id'],
        '030' => ['athlete_garmin_uploads'],
        '031' => ['athlete_plan_edits'],
        '032' => ['athlete_notebook.replaced_by'],
        '033' => ['users.referral_code', 'athlete_referrals'],
        '034' => ['athlete_track_record'],
        '035' => ['garmin_accounts_seen', 'athlete_free_weeks', 'athlete_referrals.garmin_repeated'],
        '036' => ['athlete_payments'],
        '037' => ['athlete_payments.voided_at'],
    ];

    /**
     * Las migraciones con algo que no está como deberían dejarlo.
     *
     * @return array<string,list<string>> numero => problemas (vacío si todo está)
     */
    public static function problemas(\PDO $pdo): array
    {
        $columnas = [];
        foreach ($pdo->query(
            "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
        )->fetchAll() as $fila) {
            $columnas[$fila['TABLE_NAME']][$fila['COLUMN_NAME']] = true;
        }
        $salida = [];
        foreach (self::ESPERADO as $numero => $objetos) {
            foreach ($objetos as $objeto) {
                $ausente = str_starts_with($objeto, '!');
                $nombre = ltrim($objeto, '!');
                [$tabla, $columna] = array_pad(explode('.', $nombre, 2), 2, null);
                $existe = $columna === null ? isset($columnas[$tabla]) : isset($columnas[$tabla][$columna]);
                if ($ausente && $existe) {
                    $salida[(string) $numero][] = "sigue existiendo $nombre";
                } elseif (!$ausente && !$existe) {
                    $salida[(string) $numero][] = "falta $nombre";
                }
            }
        }
        return $salida;
    }
}
