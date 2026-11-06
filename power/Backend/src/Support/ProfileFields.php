<?php
namespace App\Support;

/**
 * Single source of truth for the athlete_profiles column layout, shared by
 * ProfileDTO (input coercion), ProfileRepository (upsert + hydrate) and
 * ProfileEntity (output shape). Keeps the four files from drifting.
 *
 * Column names match PaginaLocal's UserProfileModel field names, except
 * `full_name` (PaginaLocal: `name`).
 */
final class ProfileFields
{
    /**
     * LO QUE SE RETIRO EN LA v1.2, y por que.
     *
     * `par_q_flags` (tamizaje de aptitud fisica, 7 preguntas), `injury_prone`
     * ("me considero propenso"), `doctor_performance_report` y
     * `doctor_chat_history` (el asistente de salud, retirado de la UI en la
     * v1.1), `last_medical_evaluation` y `medical_snapshot_hash`.
     *
     * Los cuatro primeros eran cuatro de los SEIS canales por los que se le
     * preguntaba al atleta por lesiones. Ese exceso -- y no los prompts -- era
     * la causa del sesgo que la beta reporto: lo que sentian era lo que ellos
     * mismos habian declarado, volviendoles amplificado.
     *
     * `health_disclaimer_accepted` y `health_disclaimer_accepted_at` los
     * sustituye la PUERTA LEGAL (`users.terms_accepted_at` + `terms_version`,
     * migracion 015), que lo hace mejor y en el sitio correcto: antes de
     * entrar, bloqueando, y con constancia de que version se acepto. Dos
     * mecanismos para lo mismo era uno de mas, y el viejo era opcional y
     * estaba en la octava pestaña.
     *
     * Las columnas las borra Database/017. Aqui dejan de leerse y de
     * escribirse, que es lo que el atleta nota.
     */
    /** Plain string columns (stored verbatim; '' allowed). */
    public const STRINGS = [
        'full_name', 'garmin_email', 'target_distance', 'triathlon_distance',
        'run_distance', 'bike_distance', 'limitations', 'planning_context',
        'analysis_context', 'nutrition_notes', 'menstrual_cycle_notes',
        'past_injuries_surgeries', 'chronic_conditions', 'medications',
        'preferred_rest_day', 'travel_frequency', 'ai_mode',
    ];

    /** ENUM columns => allowed values (anything else -> null). */
    public const ENUMS = [
        // El dia en que EMPIEZA la semana de entrenamiento del atleta. Hay
        // quien planifica de lunes a domingo y quien de sabado a viernes; sin
        // esto, la semana arrancaba el dia en que se generaba la primera y
        // ahi se quedaba para siempre. Ver Database/019.
        'week_start_day' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
        'sports_preference' => ['triathlon', 'run', 'swim', 'bike'],
        'training_age_level' => ['principiante', 'intermedio', 'avanzado', 'elite'],
        'pool_access' => ['ninguno', 'limitado', 'completo'],
        'bike_setup' => ['outdoor', 'rodillo', 'ambos'],
        'climate' => ['templado', 'calido_humedo', 'frio', 'variable'],
        'terrain' => ['llano', 'montanoso', 'mixto'],
        'occupation_type' => ['sedentario', 'activo_fisico', 'mixto'],
        'sleep_source' => ['garmin', 'manual'],
        'stress_level_self_report' => ['bajo', 'medio', 'alto'],
        'coaching_tone' => ['directo', 'empatico', 'tecnico'],
        'gender' => ['female', 'male', 'other'],
    ];

    /** Float columns. */
    public const FLOATS = [
        'lactate_threshold_hr', 'lactate_threshold_pace_sec_per_km',
        'ftp_watts', 'swim_threshold_pace_sec_per_100m', 'weight_kg', 'height_cm',
        'vo2max_running', 'vo2max_cycling', 'years_training',
        'current_weekly_volume_hours', 'longest_recent_session_hours', 'altitude_m',
        'avg_sleep_hours',
        // El ritmo de SU rodaje suave, cuando el atleta lo sabe (Database/022).
        // Lo pidió Fernando el 2026-09-14: "mi zona 2 es realmente a 6-6:20",
        // y su hoja le decía "Fácil 5:03-5:40" sacado de su predicción de
        // maratón. Lo declarado gana a lo derivado (prescription.py).
        'easy_run_pace_fast_sec_per_km', 'easy_run_pace_slow_sec_per_km',
    ];

    /** Integer columns. */
    // `pool_length_m`: 25, 50 u otro. Sin el, una serie de 8x100m son cuatro
    // largos o dos y el sistema no sabe cual, asi que el atleta los cuenta
    // de cabeza dentro del agua. Ver Database/014.
    public const INTS = ['resting_heart_rate', 'pool_length_m'];

    /** Boolean (TINYINT(1)) columns. */
    public const BOOLS = [
        'treadmill_access', 'menstrual_tracking_opt_in', 'cross_training_open',
        'ai_load_guardrail_enabled',
    ];

    /** DATE columns (Y-m-d). */
    public const DATES = ['birth_date'];

    /** DATETIME columns (Y-m-d H:i:s). */
    public const DATETIMES = [];

    /** JSON columns (stored as a JSON string, hydrated to an array). */
    public const JSON_COLS = [
        'available_days', 'hours_per_day', 'activities_per_day', 'day_time_blocks',
        // La rejilla deporte x dia con sus tres estados (fijo / posible /
        // ausente). Ver Database/013_add_sport_days.sql. `day_time_blocks` se
        // queda al lado: es de donde se deriva la rejilla de un perfil viejo.
        'sport_days',
        'recent_prs', 'garmin_autofilled_fields',
        // El test de umbral que el atleta hizo y decidió usar, por deporte, con
        // su fecha. Ver Database/023 y PaginaLocal/services/ai/test_de_umbral.py.
        'threshold_tests',
        // Cómo entrena la fuerza y su programa (Beta 2.1, Database/025). NULL =
        // no contestó y el plan sigue como hasta hoy. Ver
        // PaginaLocal/services/ai/perfil_de_fuerza.py.
        'strength_profile', 'strength_routine',
    ];

    /** Plausible [min, max] per column; out of range -> null (guards unit typos). */
    public const BOUNDS = [
        'weight_kg' => [20, 300],
        'height_cm' => [80, 260],
        'resting_heart_rate' => [25, 240],
        'lactate_threshold_hr' => [60, 240],
        'ftp_watts' => [30, 700],
        'lactate_threshold_pace_sec_per_km' => [120, 900],
        'swim_threshold_pace_sec_per_100m' => [40, 400],
        'easy_run_pace_fast_sec_per_km' => [180, 900],
        'easy_run_pace_slow_sec_per_km' => [180, 900],
        'avg_sleep_hours' => [0, 16],
        'altitude_m' => [0, 6000],
        'years_training' => [0, 80],
        'current_weekly_volume_hours' => [0, 60],
        'longest_recent_session_hours' => [0, 30],
        'vo2max_running' => [10, 100],
        'vo2max_cycling' => [10, 100],
    ];

    /** Columns that stay '' (NOT NULL) rather than becoming NULL when blank. */
    public const NOT_NULL_STRINGS = ['full_name', 'garmin_email'];

    /**
     * La barra de completitud.
     *
     * Hasta aqui eran 38 campos contados por igual, TODOS como obligatorios --
     * incluidos `menstrual_cycle_notes`, `medications`, `chronic_conditions` o
     * el FTP de quien no monta en bici. Un atleta sano que solo corre no podia
     * llegar al 100% ni rellenandolo todo, porque el resto no le aplicaba. Una
     * barra que nunca se llena deja de significar nada.
     *
     * Ahora hay tres grupos:
     *
     *  - BASE: lo que el plan necesita de cualquiera. Sin esto se adivina.
     *  - POR DEPORTE: solo cuenta si el atleta hace ese deporte. Pedirle el
     *    ritmo de 100m a un ciclista es pedirle un dato que no existe.
     *  - OPCIONAL: no se cuenta nunca. Notas libres y "solo si aplica" --
     *    lesiones, medicacion, ciclo menstrual, altitud, viajes. Rellenarlos
     *    mejora el plan, dejarlos vacios no es un perfil incompleto.
     */
    /**
     * v1.1: la barra mide LO MISMO QUE EXIGE LA PUERTA.
     *
     * Al partir el alta en basico y avanzado, el recorrido guiado dejo de pasar
     * por cinco pestañas -- y `PlanService::MINIMO_PERFIL` empezo a exigir un
     * 80% que ya no se podia alcanzar terminando el alta. Un atleta que
     * completaba el registro y conectaba su Garmin llegaba al 62% y se quedaba
     * fuera, con la app diciendole que terminara algo que acababa de terminar.
     *
     * La trampa la creo mezclar dos preguntas en un solo numero. Se separan:
     *
     *   BASE + POR DEPORTE = **puede un entrenador escribirle un plan de
     *   verdad a esta persona?** Es lo que la puerta comprueba, asi que es lo
     *   unico que la barra cuenta.
     *
     * Lo que MEJORA el plan pero no lo condiciona -- ocupacion, sueño, estres,
     * clima, terreno, dia de descanso preferido -- se fue a OPCIONAL. Se sigue
     * guardando y se sigue mandando al planificador; lo unico que cambia es que
     * no bloquea a nadie ni le baja la barra por no haberlo contestado.
     *
     * Efecto secundario bueno: los cinco atletas de la beta llevaban meses
     * entre el 95% y el 96% sin saber que campo les faltaba. Ahora llegan al
     * 100% con lo que ya tienen.
     */
    public const COMPLETION_BASE = [
        'full_name', 'sports_preference', 'garmin_email',
        // Los cinco los rellena la importacion de Garmin.
        'gender', 'birth_date', 'weight_kg', 'height_cm', 'resting_heart_rate',
        // El nivel y los años los escribe el; el volumen lo MIDE Garmin desde
        // la v1.1 (get_weekly_training_volume).
        'training_age_level', 'years_training', 'current_weekly_volume_hours',
    ];

    /** Campo => deportes que lo hacen relevante. */
    public const COMPLETION_BY_SPORT = [
        'lactate_threshold_pace_sec_per_km' => ['triathlon', 'run'],
        'vo2max_running'                    => ['triathlon', 'run'],
        // Sin 'ftp_watts' ni 'vo2max_cycling': los dos DEPENDEN DE TENER
        // POTENCIOMETRO, que es hardware que la mayoria de los triatletas
        // populares no lleva.
        //
        // Garmin solo estima el VO2max de ciclismo cuando hay potencia, y el
        // FTP ni siquiera se importa: el atleta tendria que teclear un numero
        // que no puede conocer sin medirlo. Un triatleta con el perfil entero
        // relleno se quedaba en el 92% para siempre por dos campos que no
        // dependen de el.
        //
        // Es la misma correccion que ya se hizo cuando eran 38 campos por
        // igual, un nivel mas abajo: la barra solo puede exigir lo que el
        // atleta PUEDE dar. Los dos se siguen guardando y se siguen mandando
        // al planificador cuando existen.
        // Estos dos SI condicionan el plan y no los da Garmin: sin saber si
        // tiene piscina no se puede prescribir natacion, y sin saber si su bici
        // es de rodillo o de calle las sesiones salen mal. Son dos
        // desplegables en Avanzado, y aparecen por su nombre en "Te falta".
        'bike_setup'                        => ['triathlon', 'bike'],
        'pool_access'                       => ['triathlon', 'swim'],
        // Sin 'swim_threshold_pace_sec_per_100m': exige un test que casi nadie
        // se ha hecho. Se sigue guardando y se sigue usando.
    ];

    /**
     * No cuentan para la barra. Se siguen guardando y se siguen mandando al
     * planificador: lo unico que cambia es que no se exigen.
     */
    public const COMPLETION_OPTIONAL = [
        'target_distance', 'limitations', 'lactate_threshold_hr',
        'longest_recent_session_hours', 'altitude_m', 'travel_frequency',
        'nutrition_notes', 'menstrual_cycle_notes', 'past_injuries_surgeries',
        'chronic_conditions', 'medications',
        // Requieren potenciometro. Ver el comentario en COMPLETION_BY_SPORT.
        'ftp_watts', 'vo2max_cycling',
        // v1.1: mejoran el plan, no lo condicionan. Salieron de COMPLETION_BASE
        // cuando la barra paso a medir lo mismo que exige la puerta.
        'occupation_type', 'avg_sleep_hours', 'stress_level_self_report',
        'climate', 'terrain', 'preferred_rest_day', 'week_start_day',
        'swim_threshold_pace_sec_per_100m',
        'pool_length_m', 'sport_days',
        'easy_run_pace_fast_sec_per_km', 'easy_run_pace_slow_sec_per_km',
    ];

    /**
     * Los campos que cuentan para ESTE atleta, segun su deporte.
     *
     * @return list<string>
     */
    public static function completionFieldsFor(?string $sport): array
    {
        $sport = $sport !== null && $sport !== '' ? $sport : 'triathlon';
        $fields = self::COMPLETION_BASE;
        foreach (self::COMPLETION_BY_SPORT as $col => $sports) {
            if (in_array($sport, $sports, true)) {
                $fields[] = $col;
            }
        }
        return $fields;
    }

    /** Every writable column (everything except user_id / timestamps / garmin_password_enc). */
    public static function allColumns(): array
    {
        return array_merge(
            self::STRINGS,
            array_keys(self::ENUMS),
            self::FLOATS,
            self::INTS,
            self::BOOLS,
            self::DATES,
            self::DATETIMES,
            self::JSON_COLS,
            ['completion_rate']
        );
    }

    /** Default values for a profile that has no row yet. */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::STRINGS as $c) {
            $out[$c] = in_array($c, self::NOT_NULL_STRINGS, true) ? '' : null;
        }
        foreach (array_keys(self::ENUMS) as $c) {
            $out[$c] = null;
        }
        foreach (self::FLOATS as $c) {
            $out[$c] = null;
        }
        foreach (self::INTS as $c) {
            $out[$c] = null;
        }
        foreach (self::BOOLS as $c) {
            $out[$c] = false;
        }
        foreach (self::DATES as $c) {
            $out[$c] = null;
        }
        foreach (self::DATETIMES as $c) {
            $out[$c] = null;
        }
        foreach (self::JSON_COLS as $c) {
            $out[$c] = null;
        }

        $out['sports_preference'] = 'triathlon';
        $out['cross_training_open'] = true;
        $out['ai_load_guardrail_enabled'] = true;
        $out['ai_mode'] = 'pro';
        $out['completion_rate'] = 0;

        return $out;
    }
}
