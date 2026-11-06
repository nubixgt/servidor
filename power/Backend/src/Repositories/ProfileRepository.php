<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ProfileEntity;
use App\Entities\CompetitionEntity;
use App\Entities\InjuryEntity;
use App\Support\ProfileFields;

class ProfileRepository
{
    private $pdo;

    private const COMPETITION_COLS = [
        'name', 'race_date', 'race_type', 'priority', 'target_time', 'distance', 'elevation_gain_m',
    ];

    // `status` y `notes` los aporta la migracion 014. Sin ellos aqui, una
    // lesion marcada "resuelta" se reinsertaba con el DEFAULT 'activa' en cada
    // guardado -- volvia sola a restringir el plan, que es el sesgo de lesion
    // que la v1.1 quiso quitar -- y las observaciones se perdian sin error.
    // Se filtran contra las columnas REALES (migraciones a mano). Auditoria
    // previa al merge, 2026-09-11.
    private const INJURY_COLS = [
        'location', 'laterality', 'onset', 'pain_scale', 'aggravating_factors', 'treatment',
        'status', 'notes',
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUserId(int $userId): ?ProfileEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM athlete_profiles WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return new ProfileEntity(
            $userId,
            $this->hydrateColumns($row),
            $this->findCompetitions($userId),
            $this->findInjuries($userId)
        );
    }

    /** @return CompetitionEntity[] */
    public function findCompetitions(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_competitions WHERE user_id = :uid ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map([$this, 'hydrateCompetition'], $stmt->fetchAll());
    }

    /** @return InjuryEntity[] */
    public function findInjuries(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_injuries WHERE user_id = :uid ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map([$this, 'hydrateInjury'], $stmt->fetchAll());
    }

    /**
     * @param array<string,mixed> $columns        bind-ready column => value (incl. completion_rate)
     * @param array<int,array>    $competitionRows each = CompetitionDTO::toRow()
     * @param array<int,array>    $injuryRows      each = InjuryDTO::toRow()
     */
    /**
     * La contraseña de Garmin, cifrada.
     *
     * Vive en su propia columna y NO en `ProfileFields::allColumns()`, así que
     * `hydrateColumns()` no la toca y no puede colarse en `toArray()` — que es
     * lo que el backend le manda al navegador. Una credencial que sale a la UI
     * por descuido es exactamente el fallo que esta separación impide, y
     * dejarla fuera de la lista lo hace imposible por construcción, no por
     * acordarse.
     *
     * Lo que se guarda aquí ya viene cifrado por el AiService, que es el único
     * que tiene la clave. Este backend no puede leerlo.
     */
    public function saveGarminPassword(int $userId, ?string $encrypted): void
    {
        // INSERT ... ON DUPLICATE KEY y no un UPDATE: en el primer inicio de sesión el atleta
        // importa de Garmin ANTES de pulsar Guardar, todavía no tiene fila, y el UPDATE no tocaba
        // nada sin dar error. La contraseña no se guardaba y al generar se la volvían a pedir
        // (lo reportó el responsable, 2026-09-24; reproducido con el usuario 1: `saved: false`).
        $this->pdo->prepare(
            "INSERT INTO athlete_profiles (user_id, garmin_password_enc) VALUES (:uid, :pw)
             ON DUPLICATE KEY UPDATE garmin_password_enc = VALUES(garmin_password_enc),
                                     updated_at = CURRENT_TIMESTAMP"
        )->execute(['pw' => $encrypted, 'uid' => $userId]);
    }

    /** El bloque cifrado tal cual, para devolvérselo al AiService. */
    public function findGarminPassword(int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT garmin_password_enc FROM athlete_profiles WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        $v = $stmt->fetchColumn();
        return ($v === false || $v === null || $v === '') ? null : (string) $v;
    }

    public function saveAll(int $userId, array $columns, array $competitionRows, array $injuryRows): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->upsert($userId, $columns);
            $this->replaceChildren('athlete_competitions', self::COMPETITION_COLS, $userId, $competitionRows);
            $this->replaceChildren(
                'athlete_injuries',
                $this->columnasQueExisten('athlete_injuries', self::INJURY_COLS),
                $userId,
                $injuryRows
            );
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Borra el perfil del atleta Y lo que cuelga de él.
     *
     * ## El fallo que arregla
     *
     * Aquí ponía "Children cascade via FK ON DELETE CASCADE" y no era verdad:
     * TODAS las tablas hijas cascadean desde `users`, no desde
     * `athlete_profiles`. Borrar la fila del perfil no arrastraba nada.
     *
     * Y el diálogo que el atleta confirma dice, con todas las letras, "se
     * borrarán todos los datos del perfil del atleta (competiciones y lesiones
     * incluidas)". Se le prometía un borrado que no ocurría: sus competiciones
     * seguían ahí y reaparecían al volver a rellenar el perfil.
     *
     * ## Qué NO se borra, y por qué
     *
     * La cuenta (`users`) y con ella la constancia de que aceptó las
     * condiciones. Eso vive en `users` justamente para esto: el atleta puede
     * borrar sus datos de entrenamiento -- la app se lo permite a propósito --
     * y eso no puede borrar la prueba de que aceptó. Ver Database/015.
     */
    public function deleteByUserId(int $userId): void
    {
        // Lo que el diálogo promete, en una transacción: o se va todo o no se
        // va nada. Un borrado a medias deja al atleta con un perfil vacío y
        // competiciones de un plan que ya no existe.
        $tablas = [
            'athlete_competitions',
            'athlete_injuries',
            'athlete_checkins',
            'athlete_notebook',
            'athlete_plans',
            'athlete_plan_history',
            'athlete_profiles',
        ];
        $this->pdo->beginTransaction();
        try {
            foreach ($tablas as $tabla) {
                $this->pdo->prepare("DELETE FROM {$tabla} WHERE user_id = :uid")
                    ->execute(['uid' => $userId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Refresca la cache de completitud de una fila que YA existe.
     *
     * Sin INSERT: si el atleta no tiene fila todavía no hay barra que
     * corregir, y crearle una aquí le daría un perfil vacío por el simple
     * hecho de haber abierto la pantalla.
     */
    public function updateCompletionRate(int $userId, int $rate): void
    {
        $this->pdo
            ->prepare("UPDATE athlete_profiles SET completion_rate = :rate WHERE user_id = :uid")
            ->execute(['rate' => $rate, 'uid' => $userId]);
    }


    // --- internals ---

    /**
     * Las columnas que la tabla TIENE de verdad, una vez por peticion.
     *
     * Las migraciones se corren a mano, tambien en produccion. Sin este filtro,
     * un campo nuevo en ProfileFields desplegado antes que su migracion hace
     * que el INSERT reviente y el atleta no pueda guardar NINGUN campo de su
     * perfil. Con el, el campo nuevo simplemente no se guarda hasta que la
     * columna exista. Misma tolerancia que EventRepository::record().
     *
     * @return array<string,true>
     */
    private function existingColumns(): array
    {
        static $cache = null;
        if ($cache === null) {
            try {
                $cache = array_fill_keys(
                    $this->pdo->query('SHOW COLUMNS FROM athlete_profiles')->fetchAll(\PDO::FETCH_COLUMN, 0),
                    true
                );
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
        return $cache;
    }

    private function upsert(int $userId, array $columns): void
    {
        $allowed = ProfileFields::allColumns();
        $existing = $this->existingColumns();
        $cols = array_values(array_filter(
            $allowed,
            fn($c) => array_key_exists($c, $columns) && ($existing === [] || isset($existing[$c]))
        ));

        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $updates = array_map(fn($c) => "$c = VALUES($c)", $cols);

        $sql = "INSERT INTO athlete_profiles (user_id, " . implode(', ', $cols) . ")
                VALUES (:user_id, " . implode(', ', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP";

        $bind = ['user_id' => $userId];
        foreach ($cols as $c) {
            $bind[$c] = $columns[$c];
        }

        $this->pdo->prepare($sql)->execute($bind);
    }

    /**
     * @param string[]         $cols
     * @param array<int,array> $rows
     */
    /**
     * De `$cols`, las que la tabla tiene de verdad. Ver existingColumns().
     *
     * @param list<string> $cols
     * @return list<string>
     */
    private function columnasQueExisten(string $table, array $cols): array
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            try {
                $cache[$table] = array_fill_keys(
                    $this->pdo->query("SHOW COLUMNS FROM $table")->fetchAll(\PDO::FETCH_COLUMN, 0),
                    true
                );
            } catch (\Throwable $e) {
                $cache[$table] = [];
            }
        }
        $existen = $cache[$table];
        return $existen === [] ? $cols : array_values(array_filter($cols, fn($c) => isset($existen[$c])));
    }

    private function replaceChildren(string $table, array $cols, int $userId, array $rows): void
    {
        $del = $this->pdo->prepare("DELETE FROM $table WHERE user_id = :uid");
        $del->execute(['uid' => $userId]);

        if (!$rows) {
            return;
        }

        $colList = implode(', ', array_merge(['user_id'], $cols, ['sort_order']));
        $params = implode(', ', array_merge([':user_id'], array_map(fn($c) => ':' . $c, $cols), [':sort_order']));
        $stmt = $this->pdo->prepare("INSERT INTO $table ($colList) VALUES ($params)");

        foreach (array_values($rows) as $i => $row) {
            $bind = ['user_id' => $userId, 'sort_order' => $i];
            foreach ($cols as $c) {
                $bind[$c] = $row[$c] ?? null;
            }
            $stmt->execute($bind);
        }
    }

    private function hydrateColumns(array $row): array
    {
        $out = [];

        foreach (ProfileFields::STRINGS as $c) {
            $out[$c] = $this->str($row[$c] ?? null, in_array($c, ProfileFields::NOT_NULL_STRINGS, true));
        }
        foreach (array_keys(ProfileFields::ENUMS) as $c) {
            $out[$c] = $this->str($row[$c] ?? null, $c === 'sports_preference');
        }
        foreach (ProfileFields::FLOATS as $c) {
            $out[$c] = isset($row[$c]) && $row[$c] !== null ? (float) $row[$c] : null;
        }
        foreach (ProfileFields::INTS as $c) {
            $out[$c] = isset($row[$c]) && $row[$c] !== null ? (int) $row[$c] : null;
        }
        foreach (ProfileFields::BOOLS as $c) {
            $out[$c] = (bool) (int) ($row[$c] ?? 0);
        }
        foreach (array_merge(ProfileFields::DATES, ProfileFields::DATETIMES) as $c) {
            $out[$c] = isset($row[$c]) && $row[$c] !== null ? (string) $row[$c] : null;
        }
        foreach (ProfileFields::JSON_COLS as $c) {
            $decoded = isset($row[$c]) && $row[$c] !== null && $row[$c] !== ''
                ? json_decode((string) $row[$c], true)
                : null;
            $out[$c] = is_array($decoded) ? $decoded : null;
        }

        $out['completion_rate'] = (int) ($row['completion_rate'] ?? 0);

        return $out;
    }

    private function str($v, bool $notNull): ?string
    {
        if ($v === null) {
            return $notNull ? '' : null;
        }
        return (string) $v;
    }

    private function hydrateCompetition(array $row): CompetitionEntity
    {
        return new CompetitionEntity(
            id: (int) $row['id'],
            name: (string) $row['name'],
            raceDate: $row['race_date'] !== null ? (string) $row['race_date'] : null,
            raceType: (string) $row['race_type'],
            priority: (string) $row['priority'],
            targetTime: (string) $row['target_time'],
            distance: (string) $row['distance'],
            elevationGainM: $row['elevation_gain_m'] !== null ? (int) $row['elevation_gain_m'] : null,
        );
    }

    private function hydrateInjury(array $row): InjuryEntity
    {
        return new InjuryEntity(
            id: (int) $row['id'],
            location: (string) $row['location'],
            laterality: $row['laterality'] !== null ? (string) $row['laterality'] : null,
            onset: (string) $row['onset'],
            painScale: $row['pain_scale'] !== null ? (int) $row['pain_scale'] : null,
            aggravatingFactors: $row['aggravating_factors'] !== null ? (string) $row['aggravating_factors'] : null,
            treatment: $row['treatment'] !== null ? (string) $row['treatment'] : null,
            // Sin migracion 014 no existen: activa es lo que significaba la
            // fila antes de que la columna existiera.
            status: isset($row['status']) && $row['status'] !== null ? (string) $row['status'] : 'activa',
            notes: isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null,
        );
    }
}
