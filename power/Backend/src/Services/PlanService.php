<?php
namespace App\Services;

use App\Repositories\GenerationRepository;
use App\Repositories\PlanRepository;
use App\Entities\PlanEntity;

class PlanService
{
    private PlanRepository $repo;

    public function __construct()
    {
        $this->repo = new PlanRepository();
    }

    public function getForUser(int $userId): array
    {
        $entity = $this->repo->findByUserId($userId) ?? PlanEntity::empty($userId);
        return $entity->toArray();
    }

    /**
     * La contraseña de Garmin que se va a usar: la que el atleta acaba de
     * escribir, o la que tenga guardada (cifrada).
     *
     * El AiService entiende las dos: `decrypt_secret` devuelve intacto lo que
     * no lleve el prefijo `enc:v1:`, así que un bloque cifrado y una contraseña
     * escrita a mano viajan por el mismo campo y el cambio es gradual — sin
     * migración que correr y sin romper a quien todavía no la haya guardado.
     *
     * Cadena vacía = no hay ninguna, y quien llama decide si eso es un error.
     */
    private function claveGarmin(int $userId, string $escrita): string
    {
        $escrita = trim($escrita);
        if ($escrita !== '') {
            return $escrita;
        }
        return (new ProfileService())->garminPasswordBlob($userId) ?? '';
    }

    /**
     * Minimo de perfil para generar. Devuelve null si puede pasar.
     *
     * ## Por que existe
     *
     * NO habia ninguna comprobacion de completitud en ninguna ruta: se podia
     * generar un plan con el perfil al 8%. El resultado no falla -- falla peor:
     * sale un plan verosimil construido sobre valores por defecto, y ni el
     * atleta ni nosotros sabemos cuanto de el es suyo.
     *
     * ## Por que 80 y no 100
     *
     * Porque el 100% exige el FTP y el ritmo umbral de natacion, que dependen
     * de tener potenciometro o de haberse hecho un test. Exigirlos dejaria
     * fuera a atletas que la app puede entrenar perfectamente. 80% es "faltan
     * dos o tres campos de veinticuatro", que es un plan bien fundado.
     *
     * Los cinco atletas de la beta estan entre el 95% y el 96%: esto no les
     * afecta. Afecta a quien se registre y quiera saltarse el alta.
     *
     * ## Por que aqui y no solo en el frontend
     *
     * El frontend ya lo dira antes, que es donde se lee mejor. Pero la ruta es
     * publica para cualquiera con un token, y una generacion cuesta dinero
     * real.
     */
    private const MINIMO_PERFIL = 80;

    /**
     * La puerta legal, tambien en el servidor.
     *
     * `LegalGate.vue` la enseña en el navegador, y hasta aqui era lo unico que
     * la exigia: una pestaña vieja, un bundle cacheado o una llamada directa
     * generaban igual -- y generar manda los datos de salud del atleta a
     * OpenAI, Anthropic y Google sin constancia de que lo aceptara. Es
     * justamente lo que ese documento dice que hace. Auditoria previa al
     * merge, 2026-09-11.
     *
     * @return array{ok:bool,code:string,message:string}|null
     */
    private function terminosAceptados(int $userId): ?array
    {
        $repo = new \App\Repositories\UserRepository();
        if ($repo->hasAcceptedTerms($userId, \App\Support\Legal::VERSION)) {
            return null;
        }
        return [
            'ok' => false,
            'code' => 'terms_required',
            'message' => 'Antes de generar tienes que aceptar las condiciones de uso. Recarga la pagina.',
        ];
    }

    /** @return array{ok:bool,message?:string}|null */
    private function perfilSuficiente(array $profile): ?array
    {
        $pct = (int) ($profile['completion_rate'] ?? $profile['completionRate'] ?? 0);
        if ($pct >= self::MINIMO_PERFIL) {
            return null;
        }
        $faltan = $profile['missing_fields'] ?? [];
        $cola = is_array($faltan) && $faltan
            ? ' Te faltan: ' . implode(', ', array_slice($faltan, 0, 6)) . '.'
            : '';
        return [
            'ok' => false,
            'code' => 'profile_incomplete',
            'completion_rate' => $pct,
            'missing_fields' => is_array($faltan) ? $faltan : [],
            'message' => "Tu perfil esta al {$pct}%. Tu entrenador necesita al menos el "
                . self::MINIMO_PERFIL . "% para escribirte un plan que sea tuyo y no una plantilla."
                . $cola,
        ];
    }

    /** @return array{ok:bool,message?:string} */
    public function startSeason(int $userId, string $garminPassword, string $adjustment = '', array $reloj = []): array
    {
        $r = $this->reloj($reloj);
        $garminPassword = $this->claveGarmin($userId, $garminPassword);
        if ($garminPassword === '') {
            return ['ok' => false, 'message' => 'Falta la contraseña de Garmin. Escríbela o guárdala en tu perfil.'];
        }
        if ($sinAceptar = $this->terminosAceptados($userId)) {
            return $sinAceptar;
        }
        // Las semanas gratis o el mes pagado (Q60 / 8 USD). Sólo cierra lo que cuesta dinero.
        if ($muro = (new AccessService())->muro($userId)) {
            return $muro;
        }
        $profile = (new ProfileService())->getForUser($userId);
        if ($falta = $this->perfilSuficiente($profile)) {
            return $falta;
        }
        // Un macrociclo cada 7 días (D2, Beta 3.0 F2). Antes de gastar nada.
        $generaciones = new GenerationRepository();
        if ($desde = $generaciones->macrocicloDisponibleDesde($userId)) {
            return [
                'ok' => false,
                'code' => 'cupo',
                'available_from' => $desde,
                'message' => 'Ya generaste un macrociclo esta semana. Podrás volver a generarlo a partir del '
                    . $this->fechaParaElAtleta($desde) . '. Si algo no encaja, cuéntanoslo desde «Ayuda».',
            ];
        }
        $notebook = (new NotebookService())->listForUser($userId)['entries'];
        $checkins = (new CheckinService())->historyForUser($userId);
        // Puede no haber ninguno todavia: la primera temporada se genera sin
        // fila de plan. `?->` cubre ese caso.
        $plan = $this->repo->findByUserId($userId);

        $res = (new PythonBridgeService())->generateSeason([
            'user_id' => $userId,
            'profile' => $profile,
            'garmin_password' => $garminPassword,
            'notebook' => $notebook,
            'checkins' => $checkins,
            // El resumen acumulado de las generaciones anteriores.
            //
            // `summary.json` lleva claves de LAS DOS generaciones -- la
            // temporada aporta goal_realism y volume_baseline, la semana
            // aporta adherence y prescription -- y cada corrida MEZCLA lo suyo
            // sobre lo que ya hubiera. Sin mandarlo, el espejo se queda sin
            // fichero (MySQL es la fuente y no habia nada que reflejar) y la
            // mezcla parte de cero: generar una semana borraba el
            // `goal_realism` de la temporada, que es lo que el informe del
            // macrociclo imprime al pie.
            'summary' => $plan?->summary,
            // EL MACROCICLO QUE ESTE PLAN VA A REEMPLAZAR.
            //
            // Sin esto, el bloque "Macrociclo ANTERIOR" llegaba SIEMPRE VACIO:
            // el worker materializa el espejo antes de generar, y escribir el
            // plan de temporada con null BORRA los ficheros. Cuando el
            // servidor iba a leer el plan viejo para contarselo al
            // planificador, ya no estaba, asi que el macrociclo dos se escribia
            // sin saber en que fase iba el atleta ni que recomendo el anterior.
            //
            // La ruta de la SEMANA ya los mandaba desde siempre. Es la misma
            // forma de fallo que ya aparecio con weekly_plan, season_plan_md,
            // season_phases y summary en el chat: un dato que el consumidor
            // necesita, que otra ruta si envia, y que esta no.
            'season_plan_md' => $plan?->seasonPlanMd,
            'season_phases' => $plan?->seasonPhases,
            'adjustment' => $adjustment,
            'timezone' => $r['timezone'],
            'today' => $r['today'],
        ]);

        $resultado = $this->startResult($res);
        if ($resultado['ok']) {
            $generaciones->registrar($userId, 'season', null, $resultado['job_id'] ?? null, false);
        }
        return $resultado;
    }

    /**
     * El reloj del ATLETA, no el del servidor.
     *
     * El servidor de producción no está en Guatemala: a las 8 de la tarde allí
     * ya es el día siguiente en UTC, así que la app le escribía al atleta una
     * semana que empezaba mañana cuando él veía hoy. El navegador manda su
     * zona horaria y su fecha; el resto del sistema las usa.
     *
     * @param array{timezone?:string,today?:string,start_date?:string} $reloj
     * @return array{timezone:string,today:string,start_date:string}
     */
    private function reloj(array $reloj): array
    {
        // Una zona IANA se ve así: "America/Guatemala". Se valida antes de
        // mandarla porque acaba en la variable TZ de un proceso.
        $tz = (string) ($reloj['timezone'] ?? '');
        if (!preg_match('#^[A-Za-z0-9._+-]+(/[A-Za-z0-9._+-]+){0,2}$#', $tz) || strlen($tz) > 64) {
            $tz = '';
        }
        $fecha = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : '';
        return [
            'timezone' => $tz,
            'today' => $fecha($reloj['today'] ?? ''),
            'start_date' => $fecha($reloj['start_date'] ?? ''),
        ];
    }

    /** @return array{ok:bool,message?:string} */
    public function startWeek(int $userId, string $garminPassword, bool $redo, bool $force = false, string $adjustment = '', array $reloj = [], bool $sinCheckin = false, bool $automatica = false): array
    {
        $r = $this->reloj($reloj);
        $garminPassword = $this->claveGarmin($userId, $garminPassword);
        if ($garminPassword === '') {
            return ['ok' => false, 'message' => 'Falta la contraseña de Garmin. Escríbela o guárdala en tu perfil.'];
        }
        if ($sinAceptar = $this->terminosAceptados($userId)) {
            return $sinAceptar;
        }
        // Las semanas gratis o el mes pagado (Q60 / 8 USD). Sólo cierra lo que cuesta dinero.
        if ($muro = (new AccessService())->muro($userId)) {
            return $muro;
        }
        $plan = $this->repo->findByUserId($userId);
        if (!$plan || ($plan->seasonPlanMd ?? '') === '') {
            return ['ok' => false, 'message' => 'Genera primero el plan de temporada (macrociclo).'];
        }

        $profile = (new ProfileService())->getForUser($userId);
        // La semana ya exige un macrociclo, y el macrociclo ya exige el 80%.
        // Se vuelve a comprobar porque el perfil pudo vaciarse despues: el
        // atleta puede borrar sus datos, y esa ruta existe a proposito.
        if ($falta = $this->perfilSuficiente($profile)) {
            return $falta;
        }
        $notebook = (new NotebookService())->listForUser($userId)['entries'];
        $checkins = (new CheckinService())->historyForUser($userId);
        $generaciones = new GenerationRepository();
        // Lo que LEVANTÓ en el gimnasio (Database/026). Sin esto, el programa de
        // fuerza se queda congelado en lo que confirmó el primer día: la carga no
        // puede progresar contra algo que no se sabe.
        $registroDeFuerza = (new \App\Repositories\StrengthLogRepository())->latest($userId, 12);

        $res = (new PythonBridgeService())->generateWeek([
            'user_id' => $userId,
            'profile' => $profile,
            'garmin_password' => $garminPassword,
            'strength_logs' => $registroDeFuerza,
            'season_plan_md' => $plan->seasonPlanMd,
            'season_phases' => $plan->seasonPhases,
            'previous_weekly_plan' => $plan->weeklyPlan,
            'notebook' => $notebook,
            'checkins' => $checkins,
            // El resumen acumulado de las generaciones anteriores.
            //
            // `summary.json` lleva claves de LAS DOS generaciones -- la
            // temporada aporta goal_realism y volume_baseline, la semana
            // aporta adherence y prescription -- y cada corrida MEZCLA lo suyo
            // sobre lo que ya hubiera. Sin mandarlo, el espejo se queda sin
            // fichero (MySQL es la fuente y no habia nada que reflejar) y la
            // mezcla parte de cero: generar una semana borraba el
            // `goal_realism` de la temporada, que es lo que el informe del
            // macrociclo imprime al pie.
            'summary' => $plan?->summary,

            'redo' => $redo,
            'force' => $force,
            'adjustment' => $adjustment,
            'timezone' => $r['timezone'],
            'today' => $r['today'],
            'start_date' => $r['start_date'],
            // "Saltar" en el check-in: sin esto la semana usaba el último de los
            // dos últimos días, con su nota y sus días, aunque no se contestara.
            'skip_checkin' => $sinCheckin,
            // La genera el script de las semanas automáticas, sin nadie delante: sin
            // preguntas del coach, y el cerebro sabe que el atleta no dijo nada de esta
            // semana. Ver AutoWeekService.
            'automatic' => $automatica,
            // El tope de versiones de una misma semana (D2, Beta 3.0 F2). Qué semana se
            // va a escribir lo sabe el cerebro: aquí sólo las que ya se generaron. La
            // automática no cuenta; sin la migración 028 va null y no hay tope.
            'versiones_previas' => $automatica ? null : $generaciones->semanasQueCuentan($userId),
        ]);

        $resultado = $this->startResult($res);
        if ($resultado['ok']) {
            $generaciones->registrar($userId, 'week', $res['data']['data']['target_week'] ?? null,
                                     $resultado['job_id'] ?? null, $automatica);
        }
        return $resultado;
    }

    /**
     * Poll the in-flight generation. When it finishes, persist the result and
     * return the fresh plan alongside the status.
     */
    /**
     * Entrega las respuestas al job que las espera. Y nada mas.
     *
     * Hasta el 2026-09-11 tambien las anotaba en el cuaderno, para que el coach
     * no preguntara dos veces lo mismo. Pero cada generacion pregunta, y el
     * cuaderno es para hechos duraderos: se habria llenado de "como te sentiste
     * tras las series del 9-sep", y un macrociclo de dentro de un ano seguiria
     * leyendo "vas a tope al 5K Monterrico". Ahora las respuestas de la
     * temporada las escribe el propio macrociclo al final de su informe
     * (PaginaLocal/services/ai/preguntas_del_coach.py): la semana las lee de
     * ahi, y se van con el macro cuando se borra o se rehace.
     *
     * @param list<string> $questions se acepta por compatibilidad con el frontend; ya no se usa
     * @return array{ok:bool, error:?string}
     */
    public function answerCoach(int $userId, string $jobId, array $answers, array $questions = []): array
    {
        $res = (new PythonBridgeService())->coachAnswers($userId, $jobId, $answers);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['data']['message'] ?? 'No se pudieron enviar tus respuestas.'];
        }
        return ['ok' => true, 'error' => null];
    }

    public function pollStatus(int $userId): array
    {
        $res = (new PythonBridgeService())->generateStatus($userId);
        if (!$res['ok']) {
            return ['running' => false, 'done' => false, 'error' => 'No se pudo contactar el servicio de generación.'];
        }

        $d = $res['data']['data'] ?? [];
        $out = [
            'kind' => $d['kind'] ?? null,
            'running' => (bool) ($d['running'] ?? false),
            'progress' => (int) ($d['progress'] ?? 0),
            'status_text' => $d['status_text'] ?? '',
            'done' => (bool) ($d['done'] ?? false),
            'error' => $d['error'] ?? null,
            // El coach le esta preguntando algo al atleta a mitad de generacion.
            // Este array FILTRA lo que llega al frontend: sin estas tres claves
            // las preguntas se quedaban en el AiService y la generacion esperaba
            // diez minutos a alguien que nunca las vio.
            'job_id' => $d['job_id'] ?? null,
            'awaiting_answers' => (bool) ($d['awaiting_answers'] ?? false),
            'questions' => $d['questions'] ?? null,
            // Si ya paso el momento de las preguntas. Hasta entonces la pantalla
            // pide no cerrar: cerrar a mitad dejaba las preguntas sin ver.
            'questions_done' => (bool) ($d['questions_done'] ?? false),
            // En cola todavia no corre: el frontend no descuenta ese tiempo de
            // su limite de espera. Sin esto, un atleta con cola larga veia
            // "tardando demasiado" y encolaba otra generacion pagada.
            'queued' => (bool) ($d['queued'] ?? false),
        ];

        // La fila del tope se cierra al terminar: un fallo no gasta cupo (D2). Sólo
        // cierra una fila abierta, así que preguntar dos veces no la reescribe.
        $jobTerminado = (string) ($d['job_id'] ?? '');
        if ($out['done'] && $jobTerminado !== '' && in_array($d['kind'] ?? '', ['season', 'week'], true)) {
            $semana = ($d['kind'] ?? '') === 'week' && empty($out['error'])
                ? $this->inicioDeLaUltimaSemana($d['result']['weekly_plan'] ?? null) : null;
            (new GenerationRepository())->cerrar($userId, $jobTerminado, empty($out['error']),
                                                 is_string($out['error']) ? $out['error'] : null, $semana);
            // Una generación cierra semanas en el expediente: se copia (Database/034).
            $this->copiarExpediente($userId);
        }

        // Un trabajo que termino con error NO se guarda, aunque traiga algo en
        // `result`: seria el plan anterior leido del espejo, guardado como nuevo.
        if ($out['done'] && empty($out['error']) && !empty($d['result'])) {
            $jobId = (string) ($d['job_id'] ?? '');
            $existing = $this->repo->findByUserId($userId);
            $alreadySaved = $existing && $jobId !== '' && $existing->lastGenerationJob === $jobId;

            if (!$alreadySaved) {
                $r = $d['result'];
                $kind = $d['kind'] ?? '';
                if (in_array($kind, ['season', 'week'], true)) {
                    // Archive the plan we're about to replace (mirrors PaginaLocal).
                    $this->repo->archiveCurrent($userId);
                }
                if ($kind === 'season') {
                    $this->repo->saveSeason($userId, $r['season_plan_md'] ?? null, $r['season_phases'] ?? null, $r['summary'] ?? null, $r['analysis_html'] ?? null);
                } elseif ($kind === 'week') {
                    $this->repo->saveWeek($userId, $r['weekly_plan'] ?? null, $r['summary'] ?? null, $r['planning_html'] ?? null);
                }
                if ($jobId !== '') {
                    $this->repo->setLastGenerationJob($userId, $jobId);
                }
            }
            $out['plan'] = $this->getForUser($userId);
        }

        return $out;
    }

    /**
     * Borra el plan del atleta y, si da su contraseña, lo saca también de su Garmin.
     *
     * Borrar la semana aquí y dejarla en el reloj es el desajuste que hay que
     * evitar: la app dice que ese plan ya no existe y la muñeca sigue pitando
     * para hacerlo. PaginaLocal ya sabía hacerlo desde v7 (sólo toca los ids
     * que están en su registro de subidas, nunca los entrenos que el atleta se
     * creó él); lo que faltaba era la llamada.
     *
     * **El borrado nunca falla por culpa de Garmin.** El plan es del atleta y
     * ya dijo que lo quería fuera: si el reloj no coopera se borra igual y se
     * devuelve cuántas sesiones se quedaron allí, para poder decírselo.
     *
     * Sin contraseña no se toca Garmin — es lo que pasa hoy, y sigue siendo
     * una opción legítima: el atleta puede querer conservar la semana en el
     * reloj.
     *
     * `$scope` dice QUÉ se borra: 'week' sólo la semana, 'season' sólo el
     * macrociclo, 'all' las dos cosas. Sin esto lo único posible era borrarlo
     * todo, y deshacerse de siete días que no gustaron obligaba a regenerar un
     * plan de 24 semanas — la llamada más cara del producto.
     *
     * Los entrenos sólo se retiran de Garmin cuando se va la semana: el
     * macrociclo nunca estuvo en el reloj.
     *
     * @return array{retirados:int,pendientes:int,error:?string}
     */
    public function resetForUser(int $userId, string $garminPassword = '', string $scope = 'all', ?bool $retirarDeGarmin = null): array
    {
        $scope = in_array($scope, ['week', 'season', 'all'], true) ? $scope : 'all';
        if ($scope === 'season') {
            $this->repo->deleteSeasonByUserId($userId);
            $this->olvidarRazonamiento($userId, $scope);
            return ['retirados' => 0, 'pendientes' => 0, 'error' => null];
        }
        $retirar = $retirarDeGarmin ?? (trim($garminPassword) !== '');
        $garmin = $this->borrarSemanaYQuizaLaTemporada($userId, $garminPassword, $scope, $retirar);
        // Retirar del reloj cambia el registro de subidas: se copia (Beta 3.0, F5).
        $this->copiarRegistroDeSubidas($userId);
        // Y borrar una semana descarta del expediente las líneas que no llegaron a medirse.
        $this->copiarExpediente($userId);
        if ($scope === 'all') {
            // Plan y semana: se va tambien el historial de versiones. Ver
            // PlanRepository::deleteHistoryByUserId.
            $this->repo->deleteHistoryByUserId($userId);
        }
        $this->olvidarRazonamiento($userId, $scope);
        return $garmin;
    }

    /**
     * Que el servicio tire la traza del plan que se acaba de borrar.
     *
     * No puede hacer fallar el borrado: el plan ya se fue de MySQL, que es la
     * fuente, y lo que queda al otro lado es un directorio de mas. Se registra
     * y se sigue.
     */
    private function olvidarRazonamiento(int $userId, string $scope): void
    {
        $res = (new PythonBridgeService())->planDeleted($userId, $scope);
        if (!$res['ok']) {
            error_log('plan-deleted bridge error: ' . ($res['error'] ?? ('HTTP ' . $res['status'])));
        }
    }

    /** @return array{retirados:int,pendientes:int,error:?string} */
    private function borrarSemanaYQuizaLaTemporada(int $userId, string $garminPassword, string $scope, bool $retirar): array
    {
        $garmin = ['retirados' => 0, 'pendientes' => 0, 'error' => null];

        $garminPassword = $this->claveGarmin($userId, $garminPassword);
        if ($retirar && $garminPassword !== '') {
            // ANTES de borrar: el puente necesita las fechas del plan para
            // saber qué sesiones son suyas, y en cuanto se borre la fila ya no
            // hay de dónde sacarlas.
            $plan = $this->repo->findByUserId($userId);
            // Con alcance "semana" se retira SOLO la ultima: es la que se borra.
            // Mandar el plan entero sacaba del reloj sesiones de semanas que
            // siguen en el calendario del atleta.
            $paraRetirar = $plan?->weeklyPlan;
            if ($scope === 'week' && is_array($paraRetirar['weeks'] ?? null) && count($paraRetirar['weeks']) > 1) {
                $paraRetirar['weeks'] = [end($paraRetirar['weeks'])];
            }
            $res = (new PythonBridgeService())->garminRetire([
                'user_id' => $userId,
                'profile' => (new ProfileService())->getForUser($userId),
                'garmin_password' => $garminPassword,
                'weekly_plan' => $paraRetirar,
            ]);
            $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
            if (is_array($datos)) {
                $garmin = [
                    'retirados' => (int) ($datos['retirados'] ?? 0),
                    'pendientes' => (int) ($datos['pendientes'] ?? 0),
                    'error' => $datos['error'] ?? null,
                ];
            } else {
                $garmin['error'] = $res['error'] ?? 'El servicio de IA no respondió.';
                error_log('garmin-retire bridge error: ' . ($garmin['error'] ?? 'unknown'));
            }
        }

        if ($scope === 'week') {
            $this->repo->deleteWeekByUserId($userId);
        } else {
            $this->repo->deleteByUserId($userId);
        }
        return $garmin;
    }

    public function history(int $userId): array
    {
        $entries = $this->repo->listHistory($userId);
        // Cómo salió la semana que añadió cada versión, del expediente (a ±1 día: la primera semana puede
        // empezar el día siguiente a la fecha con la que se archivó).
        $resultados = (new \App\Repositories\TrackRecordRepository())->resultados($userId);
        foreach ($entries as &$e) {
            $desde = $e['ultima_desde'] ?? null;
            if (($e['kind'] ?? '') !== 'week' || !$desde) {
                continue;
            }
            foreach ([0, 1, -1] as $dias) {
                $f = date('Y-m-d', strtotime($desde . " {$dias} day"));
                if (isset($resultados[$f])) {
                    $e['resultado'] = $resultados[$f];
                    break;
                }
            }
        }
        unset($e);
        return ['entries' => $entries];
    }

    public function historyEntry(int $userId, int $id): ?array
    {
        return $this->repo->findHistoryEntry($userId, $id);
    }

    public function deleteHistory(int $userId, int $id): bool
    {
        return $this->repo->deleteHistoryEntry($userId, $id);
    }

    /**
     * El HTML de un informe.
     *
     * Dos origenes, y la diferencia importa:
     *
     *   'analysis'  lo ESCRIBE el pipeline durante la generacion y se guarda
     *               como HTML en MySQL. Se sirve tal cual.
     *   los demas   se RENDERIZAN al pedirlos, con las plantillas del
     *               servicio (plan semanal, macrociclo con su calendario de
     *               semanas, informe de salud). No cuestan una llamada de IA
     *               y siempre reflejan el estado de AHORA.
     *
     * Hasta aqui solo existian los dos HTML guardados, y la vista de Informes
     * rehacia a mano en Vue las otras tres -- peor, y sin el calendario.
     */
    public function reportHtml(int $userId, string $type): ?string
    {
        if ($type === 'planning') {
            return $this->repo->reportHtml($userId, $type);
        }

        $plan = $this->repo->findByUserId($userId);
        if ($type === 'analysis') {
            // Sin macrociclo no hay analisis que enseñar, aunque quede una
            // traza en disco: borrar la traza puede fallar (en Windows falla a
            // medias) y re-renderizarla le enseñaria el analisis de un plan
            // que el atleta ya borro.
            if (!$plan || !$plan->seasonPlanMd) {
                return null;
            }
            // Primero con la plantilla de hoy, desde la sintesis archivada; si
            // el servicio no la tiene (traza podada, otra maquina), el HTML que
            // se guardo al generar. Sin esto un rediseño de la hoja no llegaba
            // al atleta hasta su siguiente macrociclo.
            $res = (new PythonBridgeService())->report([
                'user_id' => $userId,
                'kind' => 'analysis',
                'profile' => (new ProfileService())->getForUser($userId),
                'summary' => $plan?->summary,
                'generated_at' => $plan?->seasonGeneratedAt,
            ]);
            $html = $res['ok'] ? ($res['data']['data']['html'] ?? null) : null;
            return is_string($html) && $html !== '' ? $html : $this->repo->reportHtml($userId, 'analysis');
        }
        $res = (new PythonBridgeService())->report([
            'user_id' => $userId,
            'kind' => $type,
            'profile' => (new ProfileService())->getForUser($userId),
            'weekly_plan' => $plan?->weeklyPlan,
            'season_plan_md' => $plan?->seasonPlanMd,
            'season_phases' => $plan?->seasonPhases,
            'summary' => $plan?->summary,
            'generated_at' => $type === 'season' ? $plan?->seasonGeneratedAt : $plan?->weekGeneratedAt,
        ]);
        if (!$res['ok']) {
            error_log('report bridge error: ' . ($res['error'] ?? ('HTTP ' . $res['status'])));
            return null;
        }
        $html = $res['data']['data']['html'] ?? null;
        return is_string($html) && $html !== '' ? $html : null;
    }

    /** @return array{ok:bool,message?:string} */
    public function startGarminUpload(int $userId, string $garminPassword, ?array $dates): array
    {
        $garminPassword = $this->claveGarmin($userId, $garminPassword);
        if ($garminPassword === '') {
            return ['ok' => false, 'message' => 'Falta la contraseña de Garmin. Escríbela o guárdala en tu perfil.'];
        }
        $plan = $this->repo->findByUserId($userId);
        if (!$plan || empty($plan->weeklyPlan['weeks'] ?? [])) {
            return ['ok' => false, 'message' => 'No hay una semana generada para subir.'];
        }

        $res = (new PythonBridgeService())->garminUpload([
            'user_id' => $userId,
            'profile' => (new ProfileService())->getForUser($userId),
            'garmin_password' => $garminPassword,
            'weekly_plan' => $plan->weeklyPlan,
            'summary' => $plan->summary,
            'dates' => $dates,
        ]);

        if ($res['ok']) {
            return ['ok' => true];
        }
        if (($res['status'] ?? 0) === 409) {
            return ['ok' => false, 'message' => 'Ya hay una subida a Garmin en curso.'];
        }
        return ['ok' => false, 'message' => $res['data']['detail'] ?? $res['data']['message'] ?? 'No se pudo iniciar la subida a Garmin.'];
    }

    public function garminUploadStatus(int $userId): array
    {
        $res = (new PythonBridgeService())->garminUploadStatus($userId);
        if (!$res['ok']) {
            return ['running' => false, 'status' => 'error', 'error' => 'No se pudo contactar el servicio.'];
        }
        $estado = $res['data']['data'] ?? [];
        // Terminó (bien o a medias): el registro de subidas cambió y se copia (Beta 3.0, F5).
        // Se sabe que terminó por `result` o `error`, no por `status`: al acabar, `status` sigue
        // siendo el texto de progreso («Subiendo entrenamientos...»). Mirando `status` la copia
        // no se escribía nunca al subir (prueba en el reloj real del 2026-10-11: la copia se quedó
        // con los ids de la primera de cuatro subidas, escritos sólo al mover una sesión).
        $termino = empty($estado['running']) && (!empty($estado['result']) || !empty($estado['error']));
        if ($termino) {
            $this->copiarRegistroDeSubidas($userId);
        }
        return $estado;
    }

    /**
     * Mover una sesión dentro de su semana, sin IA (Beta 3.0, F6).
     *
     * El cálculo es del cerebro (`services/ai/edicion_de_sesion.py`, por `/bridge/plan-edit`):
     * las reglas (de hoy en adelante, su semana, nunca el día de carrera) y los avisos que no
     * impiden. Aquí: no editar con una generación o subida en marcha (409), no pisar un plan
     * que cambió desde que el atleta lo abrió (`plan_version`), guardar, dejar constancia y
     * llevar los ids del reloj a sus claves nuevas. El reloj NO se toca: la sesión queda
     * «cambios sin enviar» hasta la siguiente subida, que reemplaza el entreno.
     *
     * `dry_run` devuelve lo que pasaría (el resumen y los avisos) sin guardar nada.
     *
     * @return array{ok:bool,code?:string,message?:string,preview?:array,plan?:array}
     */
    public function editSession(int $userId, array $body): array
    {
        $plan = $this->repo->findByUserId($userId);
        if (!$plan || empty($plan->weeklyPlan['weeks'] ?? [])) {
            return ['ok' => false, 'code' => 'no_plan', 'message' => 'No tienes una semana generada.'];
        }
        $puente = new PythonBridgeService();
        $gen = $puente->generateStatus($userId);
        $sub = $puente->garminUploadStatus($userId);
        if (!empty($gen['data']['data']['running']) || !empty($sub['data']['data']['running'])) {
            return ['ok' => false, 'code' => 'busy',
                    'message' => 'Tienes una generación o una subida en curso. Espera a que termine y vuelve a intentarlo.'];
        }
        $version = (string) ($body['plan_version'] ?? '');
        if ($version !== \App\Entities\PlanEntity::version($plan->weeklyPlan)) {
            return ['ok' => false, 'code' => 'stale',
                    'message' => 'Tu plan cambió desde que lo abriste. Recarga la pantalla y vuelve a moverla.'];
        }
        $perfil = (new ProfileService())->getForUser($userId);
        $r = $this->reloj(['today' => $body['today'] ?? '']);
        // 2026-10-23: `nuevo` (con qué empieza un entreno que añade el atleta), `anadir` y `quitar` (sólo los suyos).
        $op = in_array($body['op'] ?? 'mover', ['mover', 'editor', 'ajustar', 'revertir', 'nuevo', 'anadir', 'quitar'], true)
            ? ($body['op'] ?? 'mover') : 'mover';
        $clave = (string) ($body['session_key'] ?? '');
        $ediciones = new \App\Repositories\PlanEditRepository();
        $prescripcion = $plan->summary['prescription'] ?? null;
        $res = $puente->planEdit([
            'user_id' => $userId,
            'weekly_plan' => $plan->weeklyPlan,
            'op' => $op,
            'session_key' => $clave,
            'to_date' => (string) ($body['to_date'] ?? ''),
            'modo' => ($body['modo'] ?? '') === 'doble' ? 'doble' : 'intercambiar',
            'today' => $r['today'],
            // Vacía va como null: `[]` llegaría al puente como lista y no como rejilla.
            'sport_days' => !empty($perfil['sport_days']) && is_array($perfil['sport_days']) ? $perfil['sport_days'] : null,
            // F7: los elementos del editor, sus bandas y la versión del coach.
            'elementos' => is_array($body['elementos'] ?? null) ? $body['elementos'] : null,
            'prescripcion' => is_array($prescripcion) && $prescripcion ? $prescripcion : null,
            'original' => in_array($op, ['ajustar', 'revertir'], true) ? $ediciones->original($userId, $clave) : null,
            // La fuerza de la semana (los grupos de su editor) y el entreno que añade el atleta.
            'grupos' => is_array($body['grupos'] ?? null) ? $body['grupos'] : null,
            'deporte' => (string) ($body['deporte'] ?? ''),
            'titulo' => mb_substr((string) ($body['titulo'] ?? ''), 0, 60),
        ]);
        if (!$res['ok']) {
            $motivo = $res['data']['detail'] ?? null;
            return ['ok' => false, 'code' => ($res['status'] ?? 0) === 422 ? 'not_allowed' : 'bridge',
                    'message' => is_string($motivo) ? $motivo : 'No se pudo calcular el cambio. Inténtalo en un momento.'];
        }
        $d = $res['data']['data'] ?? [];
        // El editor sólo pide lo que necesita la pantalla: nada se guarda.
        if ($op === 'editor' || $op === 'nuevo') {
            return ['ok' => true, 'editor' => $d];
        }
        $preview = [
            'resumen' => (string) ($d['resumen'] ?? ''),
            'avisos' => is_array($d['avisos'] ?? null) ? $d['avisos'] : [],
            'dias_tocados' => $d['dias_tocados'] ?? [],
        ];
        if (!empty($body['dry_run'])) {
            return ['ok' => true, 'preview' => $preview];
        }
        $nuevo = is_array($d['plan'] ?? null) ? $d['plan'] : null;
        if ($nuevo === null || !$this->repo->replaceWeeklyPlan($userId, $version, $nuevo)) {
            return ['ok' => false, 'code' => 'stale',
                    'message' => 'Tu plan cambió mientras lo movías. Recarga la pantalla y vuelve a intentarlo.'];
        }

        // Constancia de cada sesión tocada, con la versión del coach la primera vez.
        $cambios = is_array($d['cambios_registro'] ?? null) ? $d['cambios_registro'] : [];
        $diaDe = function (?array $semanas, string $clave): ?array {
            [$f, $s] = array_pad(explode('|', $clave, 2), 2, 'unica');
            foreach ($semanas['weeks'] ?? [] as $w) {
                foreach ($w['days'] ?? [] as $dia) {
                    if (($dia['date'] ?? '') === $f && ($dia['session_slot'] ?? 'unica') === $s && empty($dia['is_rest'])) {
                        return $dia;
                    }
                }
            }
            return null;
        };
        foreach ($cambios as $antes => $despues) {
            $diaAntes = $diaDe($plan->weeklyPlan, (string) $antes);
            $ediciones->registrar($userId, 'mover', (string) $antes, (string) $despues,
                $ediciones->original($userId, (string) $antes) ?? $diaAntes,
                $diaAntes, $diaDe($nuevo, (string) $despues), $preview['resumen']);
        }
        if ($op === 'ajustar') {
            $diaAntes = $diaDe($plan->weeklyPlan, $clave);
            $ediciones->registrar($userId, 'ajustar', $clave, $clave, $ediciones->original($userId, $clave) ?? $diaAntes,
                $diaAntes, $diaDe($nuevo, $clave), $preview['resumen']);
        } elseif ($op === 'revertir') {
            $ediciones->registrar($userId, 'revertir', $clave, $clave, null, $diaDe($plan->weeklyPlan, $clave),
                $diaDe($nuevo, $clave), $preview['resumen']);
            $ediciones->marcarRevertidas($userId, $clave);
        } elseif ($op === 'anadir') {
            $nueva = (string) ($d['clave'] ?? '');
            $ediciones->registrar($userId, 'anadir', $nueva, $nueva, null, null, $diaDe($nuevo, $nueva), $preview['resumen']);
        } elseif ($op === 'quitar') {
            $ediciones->registrar($userId, 'quitar', $clave, $clave, null, $diaDe($plan->weeklyPlan, $clave), null,
                $preview['resumen']);
        }

        // El registro de fuerza de una sesión movida se va con ella.
        $this->moverRegistroDeFuerza($userId, $cambios);

        // Los ids del reloj, a sus claves nuevas. Si falla, se dice: subir la semana entera
        // lo arregla igual (los huérfanos se retiran y lo que falta se crea).
        $reloj = 'pendiente';
        if ($cambios) {
            $mov = $puente->garminLedgerMove($userId, $cambios);
            if (!$mov['ok']) {
                error_log("PlanService::editSession: no se movieron los ids del reloj (u{$userId}): " . json_encode($mov['data']));
                $reloj = 'revisar';
            }
            $this->copiarRegistroDeSubidas($userId);
        }
        return ['ok' => true, 'preview' => $preview, 'reloj' => $reloj, 'plan' => $this->getForUser($userId)];
    }

    /** Lleva el registro de fuerza de cada sesión movida a su fecha y franja nuevas. */
    private function moverRegistroDeFuerza(int $userId, array $cambios): void
    {
        if (!$cambios || !(new \App\Repositories\StrengthLogRepository())->disponible()) {
            return;
        }
        try {
            $pdo = \App\Utils\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                'UPDATE athlete_strength_logs SET fecha = :nf, session_slot = :ns
                  WHERE user_id = :u AND fecha = :vf AND session_slot = :vs'
            );
            foreach ($cambios as $antes => $despues) {
                [$vf, $vs] = array_pad(explode('|', (string) $antes, 2), 2, 'unica');
                [$nf, $ns] = array_pad(explode('|', (string) $despues, 2), 2, 'unica');
                $stmt->execute(['nf' => $nf, 'ns' => $ns, 'u' => $userId, 'vf' => $vf, 'vs' => $vs]);
            }
        } catch (\Throwable $e) {
            error_log('PlanService::moverRegistroDeFuerza: ' . $e->getMessage());
        }
    }

    /**
     * Qué lleva el reloj de cada sesión de su plan: `en_el_reloj`, `cambios_sin_enviar` o
     * `sin_subir` (Beta 3.0, F5). Antes la pantalla sólo sabía lo que decía el sondeo de
     * ESA subida, y se perdía al recargar.
     */
    public function garminSessions(int $userId): array
    {
        $plan = $this->repo->findByUserId($userId);
        if (!$plan || empty($plan->weeklyPlan['weeks'] ?? [])) {
            return ['sessions' => []];
        }
        $res = (new PythonBridgeService())->garminSessions($userId, $plan->weeklyPlan);
        return $res['ok'] ? ($res['data']['data'] ?? ['sessions' => []]) : ['sessions' => [], 'error' => 'sin respuesta'];
    }

    /**
     * Copia el registro de subidas del AiService en MySQL (Database/030). Es una copia: el
     * fichero sigue mandando y nada se borra del reloj con ella. Nunca falla hacia fuera.
     */
    /**
     * La copia del expediente en MySQL (Database/034): `track_record.jsonl` es una de las dos cosas
     * del espejo que no se reconstruyen. El fichero sigue mandando; esto la deja en los respaldos.
     */
    private function copiarExpediente(int $userId): void
    {
        $repo = new \App\Repositories\TrackRecordRepository();
        if (!$repo->disponible()) {
            return;
        }
        $res = (new PythonBridgeService())->trackRecord($userId);
        $lineas = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (is_array($lineas)) {
            $repo->reemplazar($userId, $lineas);
        } else {
            error_log("PlanService::copiarExpediente: sin respuesta del AiService para el atleta {$userId}");
        }
    }

    private function copiarRegistroDeSubidas(int $userId): void
    {
        $repo = new \App\Repositories\GarminUploadRepository();
        if (!$repo->disponible()) {
            return;
        }
        $res = (new PythonBridgeService())->garminLedger($userId);
        $entradas = $res['ok'] ? ($res['data']['data']['entries'] ?? null) : null;
        if (is_array($entradas)) {
            $repo->reemplazar($userId, $entradas);
        } else {
            error_log("PlanService::copiarRegistroDeSubidas: sin respuesta del AiService para el atleta {$userId}");
        }
    }

    /** Analytics chart series. $garminPassword null = read from disk only. */
    public function charts(int $userId, ?string $garminPassword): array
    {
        $garminPassword = $garminPassword === null ? null : $this->claveGarmin($userId, $garminPassword);
        $plan = $this->repo->findByUserId($userId);
        $payload = [
            'user_id' => $userId,
            'profile' => (new ProfileService())->getForUser($userId),
            'weekly_plan' => $plan?->weeklyPlan,
            // Para el progreso (Beta 3.0, F8): una molestia congela la racha; la fuerza da medallas.
            'checkins' => (new CheckinService())->historyForUser($userId),
            'strength_logs' => (new \App\Repositories\StrengthLogRepository())->latest($userId, 200),
        ];
        if ($garminPassword !== null && $garminPassword !== '') {
            $payload['garmin_password'] = $garminPassword;
        }

        $res = (new PythonBridgeService())->charts($payload);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => $res['data']['detail'] ?? 'No se pudieron cargar las gráficas.'];
        }
        return ['ok' => true, 'data' => $res['data']['data'] ?? []];
    }

    /**
     * Lo que le queda al atleta (D2, Beta 3.0 F2), para decírselo ANTES de que pulse: cuántas
     * versiones de su semana actual y desde cuándo otro macrociclo. Nunca en dinero.
     *
     * La semana es la ÚLTIMA del plan, la que se vuelve a generar al borrarla. La cuenta de
     * ±1 día es la de `cupo_de_generaciones.versiones_de` (la decisión al generar la toma el
     * cerebro; esto sólo informa).
     *
     * @return array{available:bool,week:?array,season:?array}
     */
    public function cupo(int $userId): array
    {
        $repo = new GenerationRepository();
        if (!$repo->disponible()) {
            return ['available' => false, 'week' => null, 'season' => null];
        }
        $plan = $this->repo->findByUserId($userId);
        $semana = $this->inicioDeLaUltimaSemana($plan?->weeklyPlan);
        $week = null;
        if ($semana !== null) {
            $t = strtotime($semana);
            $usadas = count(array_filter(
                $repo->semanasQueCuentan($userId) ?? [],
                fn($w) => abs((strtotime($w) - $t) / 86400) <= 1
            ));
            $week = [
                'target_week' => $semana,
                'usadas' => $usadas,
                'quedan' => max(GenerationRepository::TOPE_POR_SEMANA - $usadas, 0),
                'tope' => GenerationRepository::TOPE_POR_SEMANA,
            ];
        }
        $desde = $repo->macrocicloDisponibleDesde($userId);
        return [
            'available' => true,
            'week' => $week,
            'season' => ['available_from' => $desde, 'dias' => GenerationRepository::DIAS_ENTRE_MACROCICLOS],
        ];
    }

    /** El primer día de la última semana de un weekly_plan (array o JSON), o null. */
    private function inicioDeLaUltimaSemana($weeklyPlan): ?string
    {
        if (is_string($weeklyPlan)) {
            $weeklyPlan = json_decode($weeklyPlan, true);
        }
        $semanas = is_array($weeklyPlan) ? ($weeklyPlan['weeks'] ?? []) : [];
        $ultima = is_array($semanas) && $semanas ? end($semanas) : null;
        $fechas = [];
        foreach ((is_array($ultima) ? ($ultima['days'] ?? []) : []) as $dia) {
            $f = (string) ($dia['date'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
                $fechas[] = $f;
            }
        }
        return $fechas ? min($fechas) : null;
    }

    /** "lunes 12 de octubre", como lo lee el atleta. */
    private function fechaParaElAtleta(string $fecha): string
    {
        $t = strtotime($fecha);
        if ($t === false) {
            return $fecha;
        }
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
                  'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $dias[(int) date('w', $t)] . ' ' . (int) date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1];
    }

    private function startResult(array $res): array
    {
        if ($res['ok']) {
            return ['ok' => true, 'job_id' => $res['data']['data']['job_id'] ?? null];
        }
        $detail = $res['data']['detail'] ?? null;

        // Dos cosas distintas llegan como 409 y no se pueden tratar igual: la
        // ventana de antelación viene como OBJETO (con su propio motivo, y se
        // puede saltar con "generar de todas formas"), y el candado de "ya
        // tienes algo en curso" viene como TEXTO, y ahí sólo cabe esperar.
        if (($res['status'] ?? 0) === 409) {
            // El tope de versiones de esta semana (Beta 3.0, F2): no se salta.
            if (is_array($detail) && !empty($detail['cupo'])) {
                return [
                    'ok' => false,
                    'code' => 'cupo',
                    'cupo' => $detail,
                    'message' => $detail['reason'] ?? 'Ya generaste todas las versiones de esta semana.',
                ];
            }
            if (is_array($detail) && isset($detail['allowed_from'])) {
                return [
                    'ok' => false,
                    'code' => 'window',
                    'window' => $detail,
                    'message' => $detail['reason'] ?? 'Todavía no toca generar la siguiente semana.',
                ];
            }
            return [
                'ok' => false,
                'code' => 'busy',
                'message' => is_string($detail) && $detail !== ''
                    ? $detail
                    : 'Ya hay una generación en curso. Espera a que termine.',
            ];
        }
        $msg = $res['data']['message'] ?? (is_string($detail) ? $detail : null) ?? 'No se pudo iniciar la generación.';
        return ['ok' => false, 'message' => $msg];
    }
}
