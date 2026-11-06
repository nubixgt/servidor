<?php
namespace App\Services;

use App\Utils\Env;
use App\Utils\HttpClient;

/**
 * Talks to the AiService bridge (AiService/main.py), which imports PaginaLocal
 * and runs its AI agents. Config from Backend/.env:
 *   PYTHON_SERVICE_URL    (e.g. http://127.0.0.1:8600)
 *   PYTHON_SERVICE_TOKEN  (must equal BRIDGE_SERVICE_TOKEN in the repo-root .env)
 */
class PythonBridgeService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        // Defaults locales (rama pruebas-locales): el AiService se levanta con
        // AiService/run.sh en 127.0.0.1:8600 y el token debe igualar
        // BRIDGE_SERVICE_TOKEN del .env de la raiz del repo.
        //
        // SIN token por defecto en el código (2026-09-15): estaba escrito aquí
        // y quedó en el historial de git. En producción viene de
        // backend/.env; en local, del .env de la raíz. Sin ninguno de los dos,
        // isConfigured() dice que no y el puente no se llama.
        $this->baseUrl = rtrim((string) Env::get('PYTHON_SERVICE_URL', 'http://127.0.0.1:8600'), '/');
        $this->token = (string) (Env::get('PYTHON_SERVICE_TOKEN') ?? Env::raiz('BRIDGE_SERVICE_TOKEN', ''));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->token !== '';
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function coachChat(array $payload): array
    {
        return $this->post('/bridge/coach-chat', $payload, 120);
    }

    /**
     * Ordena la rutina de gimnasio pegada (Beta 2.1, S4). No guarda nada.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function strengthRoutine(array $payload): array
    {
        return $this->post('/bridge/strength-routine', $payload, 90);
    }

    /**
     * El programa de gimnasio que escribe el coach (Beta 2.1, S6). No guarda nada.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function strengthProgram(array $payload): array
    {
        return $this->post('/bridge/strength-program', $payload, 180);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function garminUpload(array $payload): array
    {
        return $this->post('/bridge/garmin-upload', $payload, 30);
    }

    /**
     * Saca del reloj del atleta los entrenos que esta app le subió.
     *
     * Se llama al borrar el plan. Nunca debe impedir el borrado: si Garmin no
     * contesta, el plan se borra igual y se le dice al atleta cuántas sesiones
     * se quedaron en su reloj.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function garminRetire(array $payload): array
    {
        return $this->post('/bridge/garmin-retire', $payload, 90);
    }

    /**
     * Avisa al servicio de que un plan se borro, para que limpie su traza.
     *
     * Aparte de garminRetire a proposito: retirar del reloj solo ocurre si el
     * atleta lo pidio y hay contrasena, mientras que la traza sobra siempre
     * que el plan se fue. Colgarlo del mismo sitio la habria dejado en disco
     * justo en el caso mas comun -- borrar sin tocar Garmin.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function planDeleted(int $userId, string $scope): array
    {
        return $this->post('/bridge/plan-deleted', ['user_id' => $userId, 'scope' => $scope], 15);
    }

    /**
     * Un informe imprimible, renderizado con las plantillas del servicio.
     *
     * Son plantillas Jinja sobre datos que van en la peticion -- sin IA y sin
     * Garmin -- asi que 20s sobra. El informe de ANALISIS no pasa por aqui:
     * ese lo escribe el pipeline y se guarda como HTML en MySQL.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function report(array $payload): array
    {
        return $this->post('/bridge/report', $payload, 20);
    }

    /** Propone cómo ordenar el cuaderno (Beta 3.0, F9). Una llamada barata a un modelo. */
    public function notebookReorganize(array $payload): array
    {
        return $this->post('/bridge/notebook-reorganize', $payload, 90);
    }

    /** Calcula un cambio de sesión, sin IA (Beta 3.0, F6). No escribe nada. */
    public function planEdit(array $payload): array
    {
        return $this->post('/bridge/plan-edit', $payload, 15);
    }

    /** Lleva los ids del registro de subidas a las claves nuevas (Beta 3.0, F6). */
    public function garminLedgerMove(int $userId, array $cambios): array
    {
        return $this->post('/bridge/garmin-ledger-move', ['user_id' => $userId, 'cambios' => (object) $cambios], 15);
    }

    /** Qué lleva el reloj de cada sesión del plan (Beta 3.0, F5). */
    public function garminSessions(int $userId, ?array $weeklyPlan): array
    {
        return $this->post('/bridge/garmin-sessions', ['user_id' => $userId, 'weekly_plan' => $weeklyPlan], 15);
    }

    /** El registro de subidas de un atleta, para su copia en MySQL (Beta 3.0, F5). */
    public function garminLedger(int $userId): array
    {
        return $this->getJson('/bridge/garmin-ledger?user_id=' . $userId, 10);
    }

    /** El expediente de un atleta, línea a línea, para su copia en MySQL (Database/034). */
    public function trackRecord(int $userId): array
    {
        return $this->getJson('/bridge/track-record?user_id=' . $userId, 10);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function garminUploadStatus(int $userId): array
    {
        $ch = curl_init($this->baseUrl . '/bridge/garmin-upload-status?user_id=' . $userId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    /**
     * Lo que esta cuenta ha costado en APIs.
     *
     * La fuente de la verdad es el libro del AiService: sólo-añadir, fuera del
     * árbol que se borra, con una línea por corrida y sus tokens. MySQL guarda
     * un espejo agregado por mes para poder consultarlo y facturarlo sin que el
     * servicio de Python tenga que estar arriba.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function usage(int $userId): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado'];
        }
        $ch = curl_init($this->baseUrl . '/bridge/usage?user_id=' . $userId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    /**
     * El gasto de TODA la instalacion, por herramienta y por cuenta.
     *
     * Distinto de usage(): aquella responde "cuanto ha costado ESTE atleta" y
     * esta responde "en que se va el dinero". Solo la pide el panel de
     * administracion.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function usageGlobal(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado'];
        }
        $ch = curl_init($this->baseUrl . '/bridge/usage-global');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    /**
     * Semanas prescritas contra semanas ENTRENADAS, de todo el servicio.
     *
     * Es la metrica de valor del producto y la unica que no puede salir de
     * MySQL: el expediente vive en el disco del AiService. Un plan generado es
     * curiosidad; un plan entrenado es el producto funcionando.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function adherenceGlobal(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado'];
        }
        $ch = curl_init($this->baseUrl . '/bridge/adherence-global');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    /**
     * En cual de los cinco pasos del bucle se cae cada atleta.
     *
     * Mismo origen que la adherencia -- el expediente en el disco del AiService
     * mas el registro de subidas a Garmin -- y por el mismo motivo no puede
     * salir de MySQL. Instrumenta los tres pasos del medio (subio, entreno,
     * continuo) que el panel no medía, que es donde estaria la explicacion de
     * las 37 generaciones con 0 semanas cerradas.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function loopFunnel(): array
    {
        return $this->getJson('/bridge/loop-funnel');
    }

    // --- La salud de la app (Beta 3.0, F1). Timeouts cortos: es un panel que se
    // refresca solo, y un AiService colgado no puede colgar también la pantalla.

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function adminJobs(int $limit = 60): array
    {
        return $this->getJson('/bridge/admin/jobs?limit=' . max(1, min(200, $limit)), 10);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function adminJobLog(string $jobId, int $lines = 200): array
    {
        return $this->getJson('/bridge/admin/job-log?job_id=' . rawurlencode($jobId) . '&lines=' . max(20, min(2000, $lines)), 10);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function adminHealth(): array
    {
        return $this->getJson('/bridge/admin/health', 8);
    }

    /** Los atletas que gastaron más de `$umbral` USD en un día (Beta 3.0, F2). */
    public function usageAlerts(float $umbral = 1.0, int $dias = 7, string $hoy = ''): array
    {
        $q = http_build_query(['threshold' => $umbral, 'days' => max(1, min(90, $dias)), 'today' => $hoy]);
        return $this->getJson('/bridge/usage-alerts?' . $q, 10);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function adminNets(int $days = 30): array
    {
        return $this->getJson('/bridge/admin/nets?days=' . max(1, min(365, $days)), 10);
    }

    /**
     * Un GET al puente, con su tolerancia a fallos.
     *
     * El panel del administrador se compone de tres fuentes y cada una tiene su
     * propio camino a proposito: la caida de una no puede llevarse el panel
     * entero. Por eso esto devuelve un sobre con `ok` en vez de lanzar.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    private function getJson(string $ruta, int $timeout = 20): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado'];
        }
        $ch = curl_init($this->baseUrl . $ruta);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function charts(array $payload): array
    {
        // The Garmin-refresh path does 3 network calls; give it room.
        return $this->post('/bridge/charts', $payload, empty($payload['garmin_password']) ? 25 : 90);
    }

    /**
     * Cifra un secreto para poder guardarlo sin poder leerlo.
     *
     * La clave vive sólo en el AiService, así que la contraseña de Garmin viaja
     * en claro UNA vez —cuando el atleta la escribe— y lo que llega a MySQL es
     * un bloque opaco. Aquí no hay criptografía escrita a mano, que es donde
     * estas cosas se rompen.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function encryptCredential(string $secret): array
    {
        return $this->post('/bridge/encrypt-credential', ['secret' => $secret], 15);
    }

    /** @return array{ok:bool,status:int,data:array|null,error:?string} */
    public function garminImport(string $email, string $password): array
    {
        return $this->post('/bridge/garmin-import', [
            'garmin_email' => $email,
            'garmin_password' => $password,
        ], 60);
    }

    /**
     * El test de umbral: `subir` lo programa en el reloj del atleta para `fecha`;
     * `analizar` lee la actividad que hizo con ESE entreno (por su workoutId).
     * Sin IA.
     *
     * @return array{ok:bool,status:int,data:array|null,error:?string}
     */
    public function thresholdTest(int $userId, string $email, string $password, string $sport,
                                  string $action, string $fecha = ''): array
    {
        return $this->post('/bridge/threshold-test', [
            'user_id' => $userId,
            'garmin_email' => $email,
            'garmin_password' => $password,
            'sport' => $sport,
            'action' => $action,
            'fecha' => $fecha,
        ], 90);
    }

    /** Cuándo sincronizó su reloj por última vez (el aviso del check-in). Sin IA. */
    public function garminSync(int $userId, string $email, string $password): array
    {
        return $this->post('/bridge/garmin-sync', [
            'user_id' => $userId,
            'garmin_email' => $email,
            'garmin_password' => $password,
        ], 30);
    }

    /** ✓/✗ de cada sesión reciente, desde Garmin y sin IA (2026-10-21). Lee Garmin: hasta ~20 s. */
    public function sessionStatus(array $payload): array
    {
        return $this->post('/bridge/session-status', $payload, 60);
    }

    /** Starts a background season generation. Returns quickly with {started:true}. */
    public function generateSeason(array $payload): array
    {
        return $this->post('/bridge/generate-season', $payload, 30);
    }

    /** El diagnóstico de una consulta de ayuda, con su triaje (Beta 3.0, F3). Sin IA. */
    public function supportBundle(array $payload): array
    {
        return $this->post('/bridge/support-bundle', $payload, 20);
    }

    /** Starts a background week generation. Returns quickly with {started:true}. */
    public function generateWeek(array $payload): array
    {
        return $this->post('/bridge/generate-week', $payload, 30);
    }

    /**
     * Poll the in-flight generation FOR ONE USER.
     *
     * The user id is not optional: the status carries the generated plan in
     * `result`, and PlanService writes that result into the polling user's row.
     * Without the filter the bridge answered with the newest job of whoever
     * happened to be generating.
     */
    /**
     * Las respuestas del atleta a las preguntas que su coach le hizo a mitad de
     * generacion. El AiService las deja en la carpeta del job que las espera.
     */
    public function coachAnswers(int $userId, string $jobId, array $answers): array
    {
        return $this->post('/bridge/coach-answers', [
            'user_id' => $userId,
            'job_id' => $jobId,
            'answers' => array_values(array_map('strval', $answers)),
        ], 15);
    }

    public function generateStatus(int $userId): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado'];
        }
        $ch = curl_init($this->baseUrl . '/bridge/generate-status?user_id=' . $userId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Service-Token: ' . $this->token]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $data = is_string($resp) && $resp !== '' ? json_decode($resp, true) : null;
        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $err];
        }
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => is_array($data) ? $data : null, 'error' => null];
    }

    private function post(string $path, array $payload, int $timeout): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'AiService no configurado (PYTHON_SERVICE_URL / PYTHON_SERVICE_TOKEN)'];
        }

        return HttpClient::postJson(
            $this->baseUrl . $path,
            $payload,
            ['X-Service-Token: ' . $this->token],
            $timeout
        );
    }
}
