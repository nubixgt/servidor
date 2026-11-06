<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\AutoWeekService;
use App\Services\EventService;
use App\Services\PlanService;
use App\Support\SinCostos;
use App\Utils\JwtUtils;

class PlanController extends Controller
{
    #[Route('/plan', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function show()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new PlanService())->getForUser((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => SinCostos::paraQuien($payload, $data)]);
    }

    #[Route('/plan/generate-season', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function generateSeason()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        // Vacía es válido: significa "usa la que tengo guardada". PlanService
        // resuelve cuál toca y avisa si al final no hay ninguna.
        $pw = (string) ($body['garmin_password'] ?? '');

        $result = (new PlanService())->startSeason(
            (int) $payload['sub'],
            $pw,
            trim((string) ($body['adjustment'] ?? '')),
            // El reloj del atleta. Ver PlanService::reloj().
            [
                'timezone' => (string) ($body['timezone'] ?? ''),
                'today' => (string) ($body['today'] ?? ''),
            ]
        );
        if (!$result['ok']) {
            $this->json($this->errorDeGeneracion($result), $this->codigoHttp($result));
        }
        $this->json(['status' => 'success', 'data' => ['started' => true, 'kind' => 'season']]);
    }

    #[Route('/plan/generate-week', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function generateWeek()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        // Vacía es válido: significa "usa la que tengo guardada". PlanService
        // resuelve cuál toca y avisa si al final no hay ninguna.
        $pw = (string) ($body['garmin_password'] ?? '');

        $result = (new PlanService())->startWeek(
            (int) $payload['sub'],
            $pw,
            (bool) ($body['redo'] ?? false),
            (bool) ($body['force'] ?? false),
            trim((string) ($body['adjustment'] ?? '')),
            // El reloj del atleta, y desde qué día quiere que arranque la
            // semana. Ver PlanService::reloj().
            [
                'timezone' => (string) ($body['timezone'] ?? ''),
                'today' => (string) ($body['today'] ?? ''),
                'start_date' => (string) ($body['start_date'] ?? ''),
            ],
            (bool) ($body['skip_checkin'] ?? false)
        );
        if (!$result['ok']) {
            // 409 para la ventana de antelación (se puede saltar a sabiendas: el cliente
            // ofrece "generar de todas formas") y para el tope de versiones (no se salta;
            // va con `code: cupo`). El resto con su código, como el macrociclo.
            $code = $result['code'] ?? '';
            if ($code === 'window' || $code === 'cupo') {
                $this->json([
                    'status' => 'error',
                    'code' => $code,
                    'message' => $result['message'],
                    'data' => $code === 'window' ? $result['window'] : $result['cupo'],
                ], 409);
            }
            $this->json($this->errorDeGeneracion($result), $this->codigoHttp($result));
        }
        $this->json(['status' => 'success', 'data' => ['started' => true, 'kind' => 'week']]);
    }

    /**
     * Las respuestas a las preguntas del coach. Body: { job_id, answers: [..] }.
     * Una respuesta vacia vale: es "prefiero no contestar", y el coach sigue
     * con los datos.
     */
    #[Route('/plan/coach-answers', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function coachAnswers()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $jobId = (string) ($body['job_id'] ?? '');
        $answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];
        $questions = is_array($body['questions'] ?? null) ? $body['questions'] : [];
        if ($jobId === '') {
            $this->json(['status' => 'error', 'message' => 'Falta el trabajo al que respondes.'], 400);
        }
        $res = (new PlanService())->answerCoach((int) $payload['sub'], $jobId, $answers, $questions);
        if (!$res['ok']) {
            $this->json(['status' => 'error', 'message' => $res['error']], 409);
        }
        $this->json(['status' => 'success', 'data' => ['received' => true]]);
    }

    /** Lo que le queda: versiones de su semana y cuándo otro macrociclo (D2, Beta 3.0 F2). */
    #[Route('/plan/generation-quota', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function generationQuota()
    {
        $payload = JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => (new PlanService())->cupo((int) $payload['sub'])]);
    }

    /**
     * Cambiar una sesión desde la app (Beta 3.0, F6-F7). Body: op (mover | editor | ajustar |
     * revertir), session_key, plan_version, today, dry_run; para mover to_date y modo
     * (intercambiar | doble); para ajustar `elementos`. 409 si hay algo en curso o el plan
     * cambió; 422 si no se puede (con el motivo para el atleta).
     */
    #[Route('/plan/session', 'PATCH')]
    #[Authorize(['admin', 'user'])]
    public function editSession()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $res = (new PlanService())->editSession((int) $payload['sub'], $body);
        if (!$res['ok']) {
            $http = ['busy' => 409, 'stale' => 409, 'not_allowed' => 422, 'no_plan' => 422][$res['code'] ?? ''] ?? 502;
            $this->json(['status' => 'error', 'code' => $res['code'] ?? '', 'message' => $res['message']], $http);
        }
        // Devuelve el plan entero: sin los costos si no es el administrador.
        $this->json(['status' => 'success', 'data' => SinCostos::paraQuien($payload, $res)]);
    }

    /** Qué lleva el reloj de cada sesión de su plan (Beta 3.0, F5). */
    #[Route('/plan/garmin-sessions', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function garminSessions()
    {
        $payload = JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => (new PlanService())->garminSessions((int) $payload['sub'])]);
    }

    /**
     * Cuándo sincronizó su reloj con Garmin Connect por última vez, para que el check-in le avise
     * antes de generar (José, 2026-10-19: lo que no está en Garmin cuenta como no hecho hasta la
     * semana siguiente). Sin la contraseña guardada no se puede saber, y no se avisa.
     */
    #[Route('/plan/garmin-sync', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function garminSync()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);
        $perfiles = new \App\Services\ProfileService();
        $email = trim((string) ($perfiles->getForUser($userId)['garmin_email'] ?? ''));
        $clave = $perfiles->garminPasswordBlob($userId) ?? '';
        if ($email === '' || $clave === '') {
            $this->json(['status' => 'success', 'data' => ['ultima_sincronizacion' => null, 'motivo' => 'sin_clave']]);
        }
        $res = (new \App\Services\PythonBridgeService())->garminSync($userId, $email, $clave);
        $ultima = $res['ok'] ? ($res['data']['data']['ultima_sincronizacion'] ?? null) : null;
        $this->json(['status' => 'success', 'data' => ['ultima_sincronizacion' => $ultima]]);
    }

    /**
     * ¿Ya hice mi sesión? Un ✓ o una ✗ por sesión, leído de su Garmin y sin IA (el responsable, 2026-10-21:
     * «la IA lo ve al generar la próxima semana, y eso crea ansiedad»). Sin la contraseña guardada no se puede
     * leer, y se dice. `?today=` es el día del atleta; `?force=1`, «Actualizar».
     */
    #[Route('/plan/session-status', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function sessionStatus()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) ($payload['sub'] ?? 0);
        $plan = (new \App\Repositories\PlanRepository())->findByUserId($userId);
        if (!$plan || empty($plan->weeklyPlan['weeks'] ?? [])) {
            $this->json(['status' => 'success', 'data' => ['sesiones' => []]]);
        }
        $perfiles = new \App\Services\ProfileService();
        $email = trim((string) ($perfiles->getForUser($userId)['garmin_email'] ?? ''));
        $clave = $perfiles->garminPasswordBlob($userId) ?? '';
        if ($email === '' || $clave === '') {
            $this->json(['status' => 'success', 'data' => ['sesiones' => [], 'motivo' => 'sin_clave']]);
        }
        $hoy = (string) ($_GET['today'] ?? '');
        $umbral = $plan->summary['prescription']['threshold_pace_sec_per_km'] ?? null;
        $res = (new \App\Services\PythonBridgeService())->sessionStatus([
            'user_id' => $userId, 'garmin_email' => $email, 'garmin_password' => $clave,
            'weekly_plan' => $plan->weeklyPlan,
            'today' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $hoy) ? $hoy : '',
            'threshold_pace_sec_per_km' => is_numeric($umbral) ? (float) $umbral : null,
            'force' => !empty($_GET['force']),
        ]);
        $datos = $res['ok'] ? ($res['data']['data'] ?? ['sesiones' => []]) : ['sesiones' => [], 'error' => 'El servicio no respondió.'];
        $this->json(['status' => 'success', 'data' => $datos]);
    }

    /** Hasta cuándo puede generar y el precio (Database/035-036). Ver `AccessService`. */
    #[Route('/plan/access', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function access()
    {
        $payload = JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => (new \App\Services\AccessService())->estado((int) $payload['sub'])]);
    }

    #[Route('/plan/generate-status', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function generateStatus()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new PlanService())->pollStatus((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => SinCostos::paraQuien($payload, $data)]);
    }

    /**
     * Borra el plan. Con `garmin_password`, lo saca además del reloj del atleta.
     *
     * La contraseña es opcional y no se guarda: igual que en la subida, el
     * atleta la escribe en ese momento. Sin ella el plan se borra aquí y las
     * sesiones se quedan en su Garmin — que es lo que pasaba siempre hasta
     * ahora, y sigue siendo una elección válida.
     */
    #[Route('/plan', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function destroy()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $pw = (string) ($body['garmin_password'] ?? '');
        // 'week' | 'season' | 'all'. Por defecto 'all', que es lo que hacía
        // antes de existir esta opción.
        $scope = (string) ($body['scope'] ?? 'all');
        // Explícito y no deducido de si la contraseña viene vacía: con una
        // guardada, vacía significa "usa la mía"; sin ella, "no toques mi
        // Garmin". Son dos cosas distintas.
        $retirar = array_key_exists('retire_from_garmin', $body)
            ? (bool) $body['retire_from_garmin']
            : ($pw !== '');

        $garmin = (new PlanService())->resetForUser((int) $payload['sub'], $pw, $scope, $retirar);

        // POR QUÉ lo borró. Es la pregunta más valiosa del plan Beta 1.1: hubo
        // 37 generaciones pagadas para 5 atletas y cero idea de por qué se
        // repetían. Se anota DESPUÉS del borrado y sin poder impedirlo: una
        // respuesta de encuesta no puede bloquear que el atleta borre su plan,
        // y el motivo es opcional -- quien no conteste, borra igual.
        $motivo = trim((string) ($body['reason'] ?? ''));
        if ($motivo !== '') {
            (new EventService())->recordDeleteReason(
                (int) $payload['sub'],
                $scope,
                $motivo,
                trim((string) ($body['reason_note'] ?? ''))
            );
        }

        $this->json([
            'status' => 'success',
            'message' => 'Plan eliminado',
            'data' => ['garmin' => $garmin, 'scope' => $scope],
        ]);
    }

    #[Route('/plan/garmin-upload', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function garminUpload()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        // Vacía es válido: significa "usa la que tengo guardada". PlanService
        // resuelve cuál toca y avisa si al final no hay ninguna.
        $pw = (string) ($body['garmin_password'] ?? '');
        $dates = is_array($body['dates'] ?? null) ? array_values($body['dates']) : null;

        $result = (new PlanService())->startGarminUpload((int) $payload['sub'], $pw, $dates);
        if (!$result['ok']) {
            $this->json($this->errorDeGeneracion($result), $this->codigoHttp($result));
        }
        $this->json(['status' => 'success', 'data' => ['started' => true]]);
    }

    #[Route('/plan/garmin-upload-status', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function garminUploadStatus()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new PlanService())->garminUploadStatus((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * «Generar mi semana automáticamente»: cómo está (Database/027). Ver AutoWeekService.
     */
    #[Route('/plan/auto-week', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function autoWeek()
    {
        $payload = JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => (new AutoWeekService())->estado((int) $payload['sub'])]);
    }

    /** Body: { enabled: bool, timezone: "America/Lima" } (la zona, de su navegador). */
    #[Route('/plan/auto-week', 'PUT')]
    #[Authorize(['admin', 'user'])]
    public function setAutoWeek()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $res = (new AutoWeekService())->configurar(
            (int) $payload['sub'],
            (bool) ($body['enabled'] ?? false),
            (string) ($body['timezone'] ?? '')
        );
        if (!$res['ok']) {
            $this->json(['status' => 'error', 'message' => $res['message']], 422);
        }
        $this->json(['status' => 'success', 'data' => $res['data']]);
    }

    #[Route('/plan/charts', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function charts()
    {
        $payload = JwtUtils::fromRequest();
        $result = (new PlanService())->charts((int) $payload['sub'], null);
        if (!$result['ok']) {
            $this->json($this->errorDeGeneracion($result), $this->codigoHttp($result));
        }
        $this->json(['status' => 'success', 'data' => $result['data']]);
    }

    #[Route('/plan/charts/refresh', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function chartsRefresh()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        // Vacía es válido: significa "usa la que tengo guardada". PlanService
        // resuelve cuál toca y avisa si al final no hay ninguna.
        $pw = (string) ($body['garmin_password'] ?? '');
        $result = (new PlanService())->charts((int) $payload['sub'], $pw);
        if (!$result['ok']) {
            $this->json($this->errorDeGeneracion($result), $this->codigoHttp($result));
        }
        $this->json(['status' => 'success', 'data' => $result['data']]);
    }

    #[Route('/plan/report/{type}', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function reportHtml($type)
    {
        // 'analysis'/'planning' salen de MySQL; 'plan'/'season' los renderiza el
        // servicio con sus plantillas. Ver PlanService::reportHtml. ('medical', el
        // informe del Doctor, se retiró en la Beta 3.0.)
        if (!in_array($type, ['analysis', 'planning', 'plan', 'season'], true)) {
            $this->json(['status' => 'error', 'message' => 'Informe inválido'], 400);
        }
        $payload = JwtUtils::fromRequest();
        $html = (new PlanService())->reportHtml((int) $payload['sub'], $type);
        if ($html === null) {
            $this->json(['status' => 'error', 'message' => 'Ese informe todavía no existe. Genera un plan primero.'], 404);
        }
        $this->json(['status' => 'success', 'data' => ['html' => $html]]);
    }

    #[Route('/plan/history', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function history()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new PlanService())->history((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => SinCostos::paraQuien($payload, $data)]);
    }

    #[Route('/plan/history/{id}', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function historyEntry($id)
    {
        $payload = JwtUtils::fromRequest();
        $entry = (new PlanService())->historyEntry((int) $payload['sub'], (int) $id);
        if (!$entry) {
            $this->json(['status' => 'error', 'message' => 'No se encontró ese plan en el historial.'], 404);
        }
        $this->json(['status' => 'success', 'data' => SinCostos::paraQuien($payload, $entry)]);
    }

    #[Route('/plan/history/{id}', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function historyDelete($id)
    {
        $payload = JwtUtils::fromRequest();
        $ok = (new PlanService())->deleteHistory((int) $payload['sub'], (int) $id);
        if (!$ok) {
            $this->json(['status' => 'error', 'message' => 'No se encontró ese plan en el historial.'], 404);
        }
        $this->json(['status' => 'success', 'message' => 'Plan eliminado del historial']);
    }

    /**
     * El cuerpo del error, con el CODIGO que el frontend necesita.
     *
     * `perfilSuficiente` y `terminosAceptados` devuelven `code` y
     * `missing_fields`, y este controlador los tiraba: el navegador recibia un
     * 502 con solo el mensaje, que ademas es mentira -- el puente no fallo, la
     * peticion no era valida. Auditoria previa al merge, 2026-09-11.
     *
     * @param array<string,mixed> $result
     * @return array<string,mixed>
     */
    private function errorDeGeneracion(array $result): array
    {
        $out = ['status' => 'error', 'message' => $result['message'] ?? 'No se pudo generar.'];
        foreach (['code', 'missing_fields', 'completion_rate', 'available_from', 'acceso'] as $extra) {
            if (isset($result[$extra])) {
                $out[$extra] = $result[$extra];
            }
        }
        return $out;
    }

    /**
     * 422 cuando el problema es del atleta (perfil, condiciones); 409 si ya hay algo en
     * curso o se acabó el cupo; 502 si falló el puente.
     */
    private function codigoHttp(array $result): int
    {
        $code = $result['code'] ?? '';
        if (in_array($code, ['busy', 'cupo'], true)) {
            return 409;
        }
        if ($code === 'paywall') {
            return 402;
        }
        return in_array($code, ['profile_incomplete', 'terms_required'], true) ? 422 : 502;
    }
}
