<?php
namespace App\Services;

use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use App\DTOs\ProfileDTO;
use App\DTOs\CompetitionDTO;
use App\DTOs\InjuryDTO;
use App\Entities\ProfileEntity;
use App\Support\ProfileFields;

class ProfileService
{
    private ProfileRepository $repo;
    private UserRepository $users;

    public function __construct()
    {
        $this->repo = new ProfileRepository();
        $this->users = new UserRepository();
    }

    /** PaginaLocal-shaped profile for the authenticated user (empty skeleton if no row yet). */
    public function getForUser(int $userId): array
    {
        $entity = $this->repo->findByUserId($userId);
        if ($entity) {
            $perfil = $entity->toArray();

            // La columna `completion_rate` es una CACHE: se calcula al guardar.
            // Eso significa que cambiar qué campos se exigen no mueve la barra
            // de nadie hasta que ese atleta vuelva a pulsar Guardar -- y el
            // atleta al que se le acaba de dejar de exigir un campo sigue
            // viendo el 92% de ayer sin saber por qué.
            //
            // Se recalcula al leer y se persiste sólo si cambió. Persistirlo
            // importa porque el panel de administración cuenta perfiles
            // completos con SQL sobre esta columna, no con este cálculo: si
            // sólo se corrigiera la respuesta, la barra del atleta y el embudo
            // del admin dirían cosas distintas del mismo perfil.
            // `toArray()` renombra full_name -> name (así lo llama
            // PaginaLocal). Sin devolvérselo, el cálculo contaría el nombre
            // como vacío y todo el mundo perdería un punto al leer.
            $real = $this->computeCompletionRate(
                $perfil + ['full_name' => $perfil['name'] ?? '']
            );
            if ($real !== (int) ($perfil['completion_rate'] ?? 0)) {
                $this->repo->updateCompletionRate($userId, $real);
                $perfil['completion_rate'] = $real;
                $perfil['completionRate'] = $real;
            }
            // QUE le falta, no solo cuanto. Va con el perfil porque es donde el
            // frontend ya lo lee, y porque calcularlo alli seria mantener la
            // misma lista en dos idiomas.
            $perfil['missing_fields'] = self::missingFields(
                $perfil + ['full_name' => $perfil['name'] ?? '']
            );
            return $perfil;
        }

        $user = $this->users->findById($userId);
        return ProfileEntity::empty($userId, $user?->name)->toArray();
    }

    public function saveForUser(int $userId, ProfileDTO $dto): array
    {
        $columns = $dto->toColumns();

        // full_name is NOT NULL; seed it from the Google account name when blank.
        if (($columns['full_name'] ?? '') === '') {
            $columns['full_name'] = $this->users->findById($userId)?->name ?? '';
        }

        $columns['completion_rate'] = $this->computeCompletionRate($columns);

        $competitionRows = array_map(fn(CompetitionDTO $c) => $c->toRow(), $dto->competitions);
        $injuryRows = array_map(fn(InjuryDTO $i) => $i->toRow(), $dto->injuries);

        $this->repo->saveAll($userId, $columns, $competitionRows, $injuryRows);

        return $this->getForUser($userId);
    }

    public function resetForUser(int $userId): void
    {
        $this->repo->deleteByUserId($userId);
        // Y lo que el plan deja FUERA de MySQL: la traza de razonamiento (que
        // contiene sus datos de salud) y el resultado del ultimo trabajo
        // terminado. Sin esto, "borra mis datos" dejaba la traza en disco y el
        // siguiente sondeo -- la app hace uno en cada arranque -- volvia a
        // guardar el plan que se acababa de borrar, porque la fila con
        // `last_generation_job` ya no estaba para impedirlo. Auditoria previa
        // al merge, 2026-09-11. Nunca falla el borrado: si el puente no
        // responde, los datos de MySQL ya se fueron.
        try {
            (new PythonBridgeService())->planDeleted($userId, 'all');
        } catch (\Throwable $e) {
            error_log('resetForUser plan-deleted: ' . $e->getMessage());
        }
    }

    /**
     * Guarda la contraseña de Garmin del atleta, cifrada.
     *
     * ## El reparto, y qué protege
     *
     * Este backend NO puede leerla. La cifra el AiService, que es el único con
     * `GARMIN_CREDENTIALS_KEY`, y aquí sólo se guarda el bloque opaco que
     * devuelve. Así no hay una segunda implementación de la criptografía en
     * PHP — que es donde estas cosas se rompen — y un volcado de MySQL, una
     * copia de seguridad o una inyección SQL no dan credenciales legibles.
     *
     * No protege contra alguien con acceso al servidor entero: la clave está en
     * el `.env` de la misma máquina. Blindar eso pide un gestor de secretos
     * aparte, que es otra conversación.
     *
     * ## Si el cifrado falla no se guarda nada
     *
     * Antes que guardar una contraseña en claro, no guardarla. El atleta
     * seguirá escribiéndola cada vez, que es lo que hacía hasta ahora y
     * funciona.
     *
     * @return array{ok:bool,message?:string}
     */
    public function saveGarminPassword(int $userId, string $plaintext): array
    {
        $plaintext = trim($plaintext);
        if ($plaintext === '') {
            $this->repo->saveGarminPassword($userId, null);
            return ['ok' => true];
        }

        $res = (new PythonBridgeService())->encryptCredential($plaintext);
        $cifrada = $res['ok'] ? ($res['data']['data']['value'] ?? '') : '';
        if (!is_string($cifrada) || $cifrada === '') {
            $msg = $res['data']['detail'] ?? $res['error']
                ?? 'No se pudo guardar la contraseña de forma segura.';
            error_log('encrypt-credential falló: ' . (string) $msg);
            return ['ok' => false, 'message' => (string) $msg];
        }

        $this->repo->saveGarminPassword($userId, $cifrada);
        return ['ok' => true];
    }

    /** Si este atleta tiene una contraseña de Garmin guardada. */
    public function hasGarminPassword(int $userId): bool
    {
        return $this->repo->findGarminPassword($userId) !== null;
    }

    /**
     * El bloque cifrado, para dárselo al AiService — el único que lo entiende.
     *
     * Nunca sale de aquí hacia el navegador: no está en
     * `ProfileFields::allColumns()`, así que `toArray()` no lo lleva.
     */
    public function garminPasswordBlob(int $userId): ?string
    {
        return $this->repo->findGarminPassword($userId);
    }


    /**
     * La barra de completitud, contando solo lo que a ESTE atleta le aplica.
     *
     * El denominador depende de su deporte: a quien solo corre no se le exige
     * el FTP ni el ritmo de 100m, y las notas libres ("medicacion",
     * "lesiones previas", "ciclo menstrual") no se exigen a nadie. Antes se
     * contaban los 38 campos por igual y el 100% era inalcanzable.
     *
     * @param array<string,mixed> $columns bind-ready column values
     */
    private function computeCompletionRate(array $columns): int
    {
        $total = count(ProfileFields::completionFieldsFor(
            isset($columns['sports_preference']) ? (string) $columns['sports_preference'] : ''
        ));
        if ($total === 0) {
            return 0;
        }
        // Se cuenta lo que FALTA y se resta, en vez de contar lo que hay: así
        // el porcentaje y la lista de campos que ve el atleta salen del mismo
        // recorrido y no pueden discrepar.
        return (int) round(($total - count(self::missingFields($columns))) / $total * 100);
    }

    /**
     * QUE campos faltan, no cuantos.
     *
     * El porcentaje solo decia "96%" y el atleta no tenia forma de saber cual
     * era el 4%. En la beta los cinco se quedaron entre el 95% y el 96% -- a
     * cada uno le faltaba exactamente un campo -- y ninguno sabia cual. Un
     * numero que no dice que hacer es un numero que frustra.
     *
     * @param array<string,mixed> $columns
     * @return list<string> nombres de columna, sin traducir: el frontend tiene
     *                      las etiquetas que ve el atleta.
     */
    public static function missingFields(array $columns): array
    {
        $sport = isset($columns['sports_preference']) ? (string) $columns['sports_preference'] : '';
        $faltan = [];
        foreach (ProfileFields::completionFieldsFor($sport) as $col) {
            $v = $columns[$col] ?? null;
            if ($v === null || trim((string) $v) === '') {
                $faltan[] = $col;
            }
        }
        return $faltan;
    }
}
