<?php
namespace App\Services;

use App\Repositories\AutoWeekRepository;
use App\Repositories\FeedbackRepository;
use App\Repositories\GenerationRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PlanRepository;
use App\Support\Migraciones;
use App\Utils\Database;

/**
 * «Pedir ayuda» con una persona en el circuito (Beta 3.0, F3).
 *
 * Hasta aquí un atleta reportaba por WhatsApp o con «Opinar», y lo que llegaba no traía
 * nada para mirarlo: ni qué sesión, ni qué decidió la IA, ni qué llevaba el reloj. Una
 * consulta de ayuda es una fila de `athlete_feedback` (`context = 'ayuda'`, Database/029)
 * con un DIAGNÓSTICO que arma el servidor en el momento:
 *
 * - aquí, de MySQL y con lista blanca: el perfil (cuánto está completo, si hay rejilla), la
 *   contraseña de Garmin SÓLO como sí/no, las carreras, las migraciones que faltan, sus
 *   últimas generaciones y su semana automática;
 * - en el AiService (`/bridge/support-bundle`, sin IA): la sesión decidida contra la
 *   codificada y la del reloj, sus trabajos, las redes que le corrigieron algo, la última
 *   semana de su expediente y el triaje.
 *
 * Nunca contraseñas ni el perfil entero. Lo que no cabe en 64 KB se recorta y se dice.
 * El administrador responde y la respuesta le llega al atleta como aviso a él solo.
 */
class HelpService
{
    public const CATEGORIAS = ['plan', 'reloj', 'app', 'datos', 'otra'];
    public const ESTADOS = ['nuevo', 'investigando', 'resuelto'];
    public const MAX_DIAGNOSTICO = 65536;

    private FeedbackRepository $repo;

    public function __construct()
    {
        $this->repo = new FeedbackRepository();
    }

    public function disponible(): bool
    {
        return $this->repo->admiteAyuda();
    }

    /**
     * Guarda una consulta con su diagnóstico. Devuelve su id, o null si no se pudo (y
     * entonces quien llama lo dice: el atleta escribió y tiene que saber que no llegó).
     *
     * @param list<mixed> $erroresDelNavegador los últimos errores de su pantalla
     */
    public function pedir(int $userId, string $categoria, string $mensaje, ?string $sessionKey,
                          ?string $pagina, array $erroresDelNavegador): ?int
    {
        $categoria = in_array($categoria, self::CATEGORIAS, true) ? $categoria : 'otra';
        $sessionKey = $sessionKey !== null && preg_match('/^\d{4}-\d{2}-\d{2}(\|(unica|am|pm))?$/', $sessionKey)
            ? $sessionKey : null;
        $php = $this->diagnosticoPhp($userId);
        $plan = (new PlanRepository())->findByUserId($userId);

        $res = (new PythonBridgeService())->supportBundle([
            'user_id' => $userId,
            'categoria' => $categoria,
            'session_key' => $sessionKey,
            'php' => $php,
            'client_errors' => array_slice($erroresDelNavegador, -20),
            'weekly_plan' => $plan?->weeklyPlan,
            'summary' => $plan?->summary,
        ]);
        $diag = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($diag)) {
            // Sin el AiService la consulta llega igual, con lo que sabe PHP y dicho.
            $diag = [
                'version' => 1, 'categoria' => $categoria, 'php' => $php,
                'aviso' => 'El AiService no respondió al preguntar: el diagnóstico sólo lleva lo de PHP.',
                'triaje' => ['origen' => 'desconocido', 'motivos' => []],
            ];
        }
        $triaje = (string) ($diag['triaje']['origen'] ?? 'desconocido');
        return $this->repo->saveAyuda($userId, $categoria, $mensaje, $sessionKey, $pagina,
                                      $this->recortar($diag), substr($triaje, 0, 20));
    }

    /** Lo que el atleta ve de sus consultas: sin diagnóstico ni triaje. */
    public function mias(int $userId): array
    {
        return $this->repo->ayudasDe($userId);
    }

    public function cola(?string $estado, ?string $categoria): array
    {
        $estado = in_array($estado, self::ESTADOS, true) ? $estado : null;
        $categoria = in_array($categoria, self::CATEGORIAS, true) ? $categoria : null;
        return $this->repo->ayudas($estado, $categoria);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return in_array($estado, self::ESTADOS, true) && $this->repo->cambiarEstado($id, $estado);
    }

    /**
     * La respuesta del administrador: se guarda en la consulta y le llega al atleta como
     * aviso a él solo. Devuelve false si la consulta no existe.
     */
    public function responder(int $id, int $adminId, string $texto, ?string $estado): bool
    {
        $userId = $this->repo->responder($id, $adminId, $texto,
                                         in_array($estado, self::ESTADOS, true) ? $estado : null);
        if ($userId === null) {
            return false;
        }
        (new NotificationRepository())->createFor($userId, $adminId, 'Respuesta a tu consulta',
                                                  mb_substr($texto, 0, 1000), false, $id);
        return true;
    }

    /**
     * Lo que PHP sabe y el AiService no, con lista blanca. La contraseña de Garmin va como
     * sí/no y nunca su valor; del perfil, sólo lo que dice si le deja a la IA hacerlo bien.
     *
     * @return array<string,mixed>
     */
    public function diagnosticoPhp(int $userId): array
    {
        $perfilService = new ProfileService();
        $perfil = $perfilService->getForUser($userId);
        $rejilla = is_array($perfil['sport_days'] ?? null) ? $perfil['sport_days'] : [];
        $diasConDeporte = [];
        foreach ($rejilla as $dias) {
            foreach ((array) $dias as $dia => $estado) {
                if ($estado) {
                    $diasConDeporte[$dia] = true;
                }
            }
        }
        $nombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $carreras = [];
        foreach (($perfil['competitions'] ?? []) as $c) {
            $fecha = (string) ($c['date'] ?? '');
            $t = strtotime($fecha);
            if ($t === false || $fecha < date('Y-m-d') || !$diasConDeporte) {
                continue;
            }
            $dia = $nombres[(int) date('w', $t)];
            if (!isset($diasConDeporte[$dia])) {
                // Información, no un fallo: el día de una carrera está disponible para ella
                // (Hugo, 2026-10-09). El triaje no lo cuenta como señal.
                $carreras[] = mb_substr((string) ($c['name'] ?? 'Carrera'), 0, 60) . " ({$dia} {$fecha})";
            }
        }
        $plan = (new PlanRepository())->findByUserId($userId);
        $semanas = [];
        foreach (($plan?->weeklyPlan['weeks'] ?? []) as $w) {
            $fechas = array_filter(array_map(fn($d) => (string) ($d['date'] ?? ''), $w['days'] ?? []));
            if ($fechas) {
                $semanas[] = min($fechas) . ' → ' . max($fechas);
            }
        }
        $auto = (new AutoWeekRepository())->find($userId);
        try {
            $faltan = array_keys(Migraciones::problemas(Database::getInstance()->getConnection()));
        } catch (\Throwable $e) {
            $faltan = ['no se pudo comprobar'];
        }
        return [
            'perfil_pct' => (int) ($perfil['completion_rate'] ?? 0),
            'contrasena_garmin_guardada' => $perfilService->garminPasswordBlob($userId) !== null,
            'rejilla_vacia' => !$diasConDeporte,
            'carreras_en_dia_de_descanso' => $carreras,
            'migraciones_que_faltan' => $faltan,
            'ultimas_generaciones' => array_map(fn($g) => [
                'kind' => $g['kind'], 'target_week' => $g['target_week'], 'status' => $g['status'],
                'automatic' => (bool) $g['automatic'], 'created_at' => $g['created_at'],
                'error' => $g['error'] !== null ? mb_substr((string) $g['error'], 0, 200) : null,
            ], (new GenerationRepository())->ultimas($userId, 3)),
            'semana_automatica' => $auto ? [
                'enabled' => (bool) $auto['enabled'], 'stage' => $auto['stage'], 'week_start' => $auto['week_start'],
                'attempts' => (int) $auto['attempts'], 'message' => $auto['message'],
            ] : null,
            'tiene_macrociclo' => ($plan?->seasonPlanMd ?? '') !== '',
            'semanas_del_plan' => $semanas,
            'zona_horaria' => $auto['timezone'] ?? null,
            // Lo que el atleta movió o ajustó desde la app (Beta 3.0, F6).
            'cambios_del_atleta' => (new \App\Repositories\PlanEditRepository())->ultimos($userId, 5),
        ];
    }

    /**
     * Cabe en 64 KB o se recorta lo más grande, diciéndolo dentro (`_recortado`). Un
     * diagnóstico mudo sobre lo que falta se lee como completo.
     */
    public function recortar(array $diag): string
    {
        $json = json_encode($diag, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        foreach (['errores_del_navegador', 'redes_que_le_corrigieron', 'trabajos', 'sesion', 'php'] as $clave) {
            if (strlen((string) $json) <= self::MAX_DIAGNOSTICO) {
                break;
            }
            if (!array_key_exists($clave, $diag)) {
                continue;
            }
            $tam = strlen((string) json_encode($diag[$clave], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
            $diag[$clave] = "[recortado: {$tam} bytes]";
            $diag['_recortado'][] = "{$clave} ({$tam} bytes) no cabía en " . self::MAX_DIAGNOSTICO . ' bytes';
            $json = json_encode($diag, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        if (strlen((string) $json) > self::MAX_DIAGNOSTICO) {
            $json = json_encode(['_recortado' => ['el diagnóstico entero no cabía en ' . self::MAX_DIAGNOSTICO . ' bytes'],
                                 'triaje' => $diag['triaje'] ?? null], JSON_UNESCAPED_UNICODE);
        }
        return (string) $json;
    }
}
