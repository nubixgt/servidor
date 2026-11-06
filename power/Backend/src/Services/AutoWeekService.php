<?php
namespace App\Services;

use App\Repositories\AutoWeekRepository;
use App\Repositories\PlanRepository;

/**
 * «Generar mi semana automáticamente» (decisión del responsable, 2026-09-24).
 *
 * Con el interruptor encendido, la VÍSPERA del primer día de su siguiente semana, a
 * las HORA de SU zona horaria, se genera esa semana y se sube a su reloj. Lo corre
 * `Backend/scripts/semanas_automaticas.php`, que el servidor lanza cada hora.
 *
 * Lo que la hace distinta de la que genera el atleta, y por qué:
 * - **Sin preguntas del coach y sin check-in.** Nadie está delante para contestar, y
 *   el check-in sólo se rellena al generar (PlanView). Además es más barata: el paso
 *   de las preguntas no corre.
 * - **No supone nada que no dijo** (`automatica` llega al cerebro, que se lo dice al
 *   modelo): sin check-in, lo que se sabe de esa semana es lo que mide Garmin.
 * - **Se sube sola al reloj**, como la subida de «Subir a Garmin»: reemplaza lo que
 *   ya estaba y retira lo que sobra y lo caducado (decisión del responsable).
 * - **Sólo continúa un plan**: la primera semana y la que se quedó atrás se generan a
 *   mano. Nunca escribe más de la semana en curso y la siguiente
 *   (`ventana_de_generacion`), y como mucho TRES intentos por semana: una generación
 *   que falla puede haber costado dinero.
 */
class AutoWeekService
{
    /** La hora local a la que se lanza, la víspera. Tarde, con margen para reintentar. */
    public const HORA = 21;
    /** Intentos por semana, uno por hora como mucho. */
    public const INTENTOS = 3;
    /**
     * Lo que se espera a un trabajo cuyo estado NO se conoce (el AiService no contesta o no lo
     * encuentra) antes de darlo por perdido. Uno en cola o en marcha se espera más: ver
     * ESPERA_EN_COLA_MAX_MIN.
     */
    private const ESPERA_MAX_MIN = 60;
    /**
     * Lo que se espera a uno que el AiService dice que está en cola o en marcha. Con tres
     * generaciones a la vez (~6 min cada una), cien atletas con la misma víspera salen en ~3 h;
     * el AiService ya corta él una generación colgada a los 40-45 min. Antes se daba por perdida
     * a los 60 min aunque siguiera en cola, y la vuelta siguiente lanzaba OTRA generación pagada
     * mientras la primera terminaba sin guardarse (auditoría del 2026-09-24).
     */
    private const ESPERA_EN_COLA_MAX_MIN = 6 * 60;

    private AutoWeekRepository $repo;

    public function __construct()
    {
        $this->repo = new AutoWeekRepository();
    }

    /**
     * Cuándo toca respecto al momento de lanzar (la víspera de `$inicio` a las `$hora`).
     *
     * 'antes' todavía no; 'ahora' desde ese momento hasta que acaba el primer día de la
     * semana (si el servidor estuvo caído a las 21:00, se genera igual por la mañana);
     * 'tarde' si la semana ya empezó ayer o antes sin plan: eso se retoma a mano.
     */
    public static function cuandoToca(string $inicio, \DateTimeImmutable $ahoraLocal, int $hora = self::HORA): string
    {
        $tz = $ahoraLocal->getTimezone();
        $dia = \DateTimeImmutable::createFromFormat('!Y-m-d', $inicio, $tz);
        if (!$dia) {
            return 'antes';
        }
        $lanzar = $dia->modify('-1 day')->setTime($hora, 0);
        if ($ahoraLocal < $lanzar) {
            return 'antes';
        }
        return $ahoraLocal->format('Y-m-d') <= $inicio ? 'ahora' : 'tarde';
    }

    /** El día después del último que ya tiene escrito, o null si no tiene ninguna semana. */
    public static function siguienteInicio(?array $weeklyPlan): ?string
    {
        $ultimo = null;
        foreach (($weeklyPlan['weeks'] ?? []) as $semana) {
            foreach (($semana['days'] ?? []) as $dia) {
                $f = substr((string) ($dia['date'] ?? ''), 0, 10);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && ($ultimo === null || $f > $ultimo)) {
                    $ultimo = $f;
                }
            }
        }
        return $ultimo === null ? null : (new \DateTimeImmutable($ultimo))->modify('+1 day')->format('Y-m-d');
    }

    /**
     * "el lunes 5 de octubre", para lo que lee el atleta; con el año si no es el de hoy (2026-10-21: un
     * pago anual hasta el 2027-10-28 salía «jueves 28 de octubre», que se lee como la semana que viene).
     */
    public static function fechaLarga(string $iso, ?string $hoy = null): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
                  'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $f = new \DateTimeImmutable($iso);
        $anio = substr($hoy ?? (new \DateTimeImmutable('now', new \DateTimeZone(AccessService::ZONA)))->format('Y-m-d'), 0, 4);
        return $dias[(int) $f->format('w')] . ' ' . (int) $f->format('j') . ' de ' . $meses[(int) $f->format('n') - 1]
            . ($f->format('Y') !== $anio ? ' de ' . $f->format('Y') : '');
    }

    private static function zonaValida(string $tz): bool
    {
        if ($tz === '' || strlen($tz) > 64) {
            return false;
        }
        try {
            new \DateTimeZone($tz);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Lo que enseña la pantalla de Plan. */
    public function estado(int $userId): array
    {
        if (!$this->repo->disponible()) {
            return ['available' => false];
        }
        $fila = $this->repo->find($userId) ?? [];
        $plan = (new PlanRepository())->findByUserId($userId);
        $siguiente = self::siguienteInicio($plan?->weeklyPlan);
        return [
            'available' => true,
            'enabled' => (bool) ($fila['enabled'] ?? false),
            'timezone' => $fila['timezone'] ?? null,
            'hour' => self::HORA,
            'next_start' => $siguiente,
            'launch_date' => $siguiente ? (new \DateTimeImmutable($siguiente))->modify('-1 day')->format('Y-m-d') : null,
            'week_start' => $fila['week_start'] ?? null,
            'stage' => $fila['stage'] ?? null,
            'message' => $fila['message'] ?? null,
            'garmin_password_saved' => (new ProfileService())->garminPasswordBlob($userId) !== null,
            'has_season' => ($plan?->seasonPlanMd ?? '') !== '',
        ];
    }

    /** @return array{ok:bool,message?:string,data?:array} */
    public function configurar(int $userId, bool $enabled, string $timezone): array
    {
        if (!$this->repo->disponible()) {
            return ['ok' => false, 'message' => 'Esta opción todavía no está disponible.'];
        }
        $timezone = trim($timezone);
        if ($enabled) {
            if (!self::zonaValida($timezone)) {
                return ['ok' => false, 'message' => 'No pudimos leer tu zona horaria. Recarga la página e inténtalo de nuevo.'];
            }
            if ((new ProfileService())->garminPasswordBlob($userId) === null) {
                return ['ok' => false, 'message' => 'Para que tu semana se genere sola, guarda tu contraseña de Garmin en tu perfil: sin ella no podemos leer tus datos ni subirla a tu reloj.'];
            }
            $plan = (new PlanRepository())->findByUserId($userId);
            if (($plan?->seasonPlanMd ?? '') === '') {
                return ['ok' => false, 'message' => 'Genera primero tu plan de temporada y tu primera semana.'];
            }
        }
        $this->repo->setEnabled($userId, $enabled, self::zonaValida($timezone) ? $timezone : '');
        return ['ok' => true, 'data' => $this->estado($userId)];
    }

    /**
     * Un paso para UN atleta: sigue lo que esté en marcha o, si toca, lo lanza.
     *
     * Devuelve true si queda algo en marcha (el script vuelve a preguntar en unos
     * segundos) y false si con este atleta ya no hay nada que hacer en esta vuelta.
     * `$log` recibe una línea por cada cosa que pasa.
     */
    public function paso(array $fila, callable $log, ?\DateTimeImmutable $ahora = null): bool
    {
        $userId = (int) $fila['user_id'];
        $tz = (string) ($fila['timezone'] ?? '');
        if (!self::zonaValida($tz)) {
            $log("u{$userId}: sin zona horaria válida; no se puede saber cuándo es su víspera");
            return false;
        }
        $local = ($ahora ?? new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone($tz));
        $semana = (string) ($fila['week_start'] ?? '');
        $stage = (string) ($fila['stage'] ?? '');
        $planes = new PlanService();

        // 1) Lo que ya está en marcha.
        if ($stage === 'generando' || $stage === 'subiendo') {
            $desde = !empty($fila['stage_at']) ? new \DateTimeImmutable($fila['stage_at'], new \DateTimeZone('UTC')) : null;
            $minutos = $desde ? intdiv((new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp()
                - $desde->getTimestamp(), 60) : 0;
            return $stage === 'generando'
                ? $this->seguirGeneracion($userId, $semana, (string) ($fila['job_id'] ?? ''), $minutos, $planes, $log)
                : $this->seguirSubida($userId, $semana, $minutos, $planes, $log);
        }

        // 2) ¿Toca lanzar? Antes, se recoge cualquier generación suya ya terminada: guardarla
        // (pollStatus) es gratis, y relanzar sin mirar pagaba dos veces la misma semana.
        $planes->pollStatus($userId);
        $plan = (new PlanRepository())->findByUserId($userId);
        if (($plan?->seasonPlanMd ?? '') === '') {
            return false;
        }
        $siguiente = self::siguienteInicio($plan->weeklyPlan);
        if ($siguiente === null) {
            return false;   // la primera semana se genera a mano
        }
        $cuando = self::cuandoToca($siguiente, $local);
        if ($cuando === 'antes') {
            return false;
        }
        $mismaSemana = $semana === $siguiente;
        if ($cuando === 'tarde') {
            // «está esperando…» (condiciones o acceso) prometía generarse sola: pasado su primer día ya no.
            $esperaba = str_contains((string) ($fila['message'] ?? ''), 'está esperando');
            if (!$mismaSemana || $stage !== 'error' || $esperaba) {
                $this->repo->setStage($userId, $siguiente, 'error',
                    'Tu semana del ' . self::fechaLarga($siguiente) . ' no se generó a tiempo. Genérala a mano y la siguiente volverá a generarse sola.');
                $log("u{$userId}: la semana del {$siguiente} ya empezó sin plan; se retoma a mano");
            }
            return false;
        }
        if ($mismaSemana && ($stage === 'hecha' || (int) ($fila['attempts'] ?? 0) >= self::INTENTOS)) {
            return false;
        }

        $res = $planes->startWeek($userId, '', false, false, '', [
            'timezone' => $tz,
            'today' => $local->format('Y-m-d'),
        ], true, true);
        if ($res['ok']) {
            $this->repo->setStage($userId, $siguiente, 'generando',
                'Generando tu semana del ' . self::fechaLarga($siguiente) . '…', true, $res['job_id'] ?? null);
            $log("u{$userId}: lanzada la semana del {$siguiente} (trabajo " . ($res['job_id'] ?? '?') . ')');
            // Una semana lanzada sin clic no pasa por el router (EventService::EVENTOS), así que
            // la tabla de Métricas no la contaba como plan. Su costo sí está en el libro.
            (new \App\Repositories\EventRepository())->record($userId, 'plan_semana_automatica', '/auto-week', $siguiente);
            return true;
        }
        if (($res['code'] ?? '') === 'busy') {
            // Otra generación o subida suya en curso (quizá la está haciendo él): no es un
            // intento fallido, se vuelve a mirar en la próxima hora.
            $log("u{$userId}: ocupado ({$res['message']}); se reintenta en la próxima vuelta");
            return false;
        }
        if (in_array($res['code'] ?? '', ['terms_required', 'paywall'], true)) {
            // Le falta aceptar las condiciones nuevas o no tiene acceso (auditoría del 2026-10-23: al subir la
            // versión del Legal, quien no abriera la app antes del domingo a las 21:00 gastaba sus 3 intentos y se
            // quedaba sin semana). No es un intento fallido: se dice qué falta y se vuelve a mirar cada hora
            // mientras toque (hasta el final de su primer día), así que se genera en cuanto lo resuelva.
            $falta = ($res['code'] ?? '') === 'terms_required'
                ? 'acepta las nuevas condiciones de uso al abrir la app'
                : 'necesitas acceso para generar semanas nuevas (mira «Precios» en la tuerca)';
            $this->repo->setStage($userId, $siguiente, 'error',
                'Tu semana del ' . self::fechaLarga($siguiente) . " está esperando: {$falta}, y se generará sola en la hora siguiente.");
            $log("u{$userId}: esperando ({$res['code']}); se reintenta en la próxima vuelta");
            return false;
        }
        $this->repo->setStage($userId, $siguiente, 'error',
            'No se pudo generar tu semana del ' . self::fechaLarga($siguiente) . ': ' . $res['message'], true);
        $log("u{$userId}: no se pudo lanzar la semana del {$siguiente}: {$res['message']}");
        return false;
    }

    private function seguirGeneracion(int $userId, string $semana, string $jobId, int $minutos,
                                      PlanService $planes, callable $log): bool
    {
        // pollStatus es lo mismo que pregunta la pantalla, y GUARDA la semana en MySQL al
        // terminar: sin nadie delante, sin esto la semana se quedaba en el AiService.
        $st = $planes->pollStatus($userId);
        $enMarcha = !empty($st['running']) || !empty($st['queued']);
        // La señal de que salió bien es que la semana ESTÁ en su plan (la guardó esta
        // pregunta, o la pantalla si él la tenía abierta), no el estado del trabajo.
        $plan = (new PlanRepository())->findByUserId($userId);
        if (!$enMarcha && self::siguienteInicio($plan?->weeklyPlan) > $semana) {
            return $this->empezarSubida($userId, $semana, $planes, $log);
        }
        // En cola o en marcha: se espera (el AiService corta él las colgadas). Estado
        // desconocido (no contesta, o no lo encuentra): se espera menos.
        $caducado = $enMarcha ? $minutos > self::ESPERA_EN_COLA_MAX_MIN : $minutos > self::ESPERA_MAX_MIN;
        if (!$caducado && ($enMarcha || empty($st['done']))) {
            return true;
        }
        $fecha = self::fechaLarga($semana);
        $motivo = $st['error'] ?? ($caducado ? 'tardó demasiado' : 'terminó sin tu semana');
        $this->repo->setStage($userId, $semana, 'error', "No se pudo generar tu semana del {$fecha}: {$motivo}.");
        $log("u{$userId}: la generación de la semana del {$semana} (trabajo {$jobId}) falló: {$motivo}");
        return false;
    }

    private function empezarSubida(int $userId, string $semana, PlanService $planes, callable $log): bool
    {
        $fecha = self::fechaLarga($semana);
        $res = $planes->startGarminUpload($userId, '', null);
        if ($res['ok']) {
            $this->repo->setStage($userId, $semana, 'subiendo', "Tu semana del {$fecha} está lista; subiéndola a tu reloj…");
            $log("u{$userId}: semana del {$semana} guardada; subiendo a Garmin");
            return true;
        }
        $this->repo->setStage($userId, $semana, 'error',
            "Tu semana del {$fecha} está en la app, pero no se pudo subir a tu reloj: {$res['message']} Súbela con «Subir a Garmin».");
        $log("u{$userId}: no se pudo empezar la subida: {$res['message']}");
        return false;
    }

    private function seguirSubida(int $userId, string $semana, int $minutos, PlanService $planes, callable $log): bool
    {
        $fecha = self::fechaLarga($semana);
        $st = $planes->garminUploadStatus($userId);
        // Una subida no hace cola detrás de las generaciones y tarda un minuto.
        $caducado = $minutos > self::ESPERA_MAX_MIN;
        if (!empty($st['running']) && !$caducado) {
            return true;
        }
        $error = $st['error'] ?? null;
        if (!$error && ($st['status'] ?? '') !== 'failed' && is_array($st['result'] ?? null)) {
            $r = $st['result'];
            $this->repo->setStage($userId, $semana, 'hecha',
                "Tu semana del {$fecha} se generó sola y está en tu reloj ({$r['uploaded']} de {$r['total']} sesiones).");
            $log("u{$userId}: semana del {$semana} subida ({$r['uploaded']}/{$r['total']}, retirados " . ($r['pruned'] ?? 0) . ')');
            return false;
        }
        if (!$error && !$caducado) {
            return true;   // en cola todavía
        }
        $motivo = $error ?: 'tardó demasiado';
        $this->repo->setStage($userId, $semana, 'error',
            "Tu semana del {$fecha} está en la app, pero no llegó a tu reloj: {$motivo}. Súbela con «Subir a Garmin».");
        $log("u{$userId}: la subida de la semana del {$semana} falló: {$motivo}");
        return false;
    }
}
