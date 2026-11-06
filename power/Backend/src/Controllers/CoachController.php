<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\ProfileService;
use App\Services\NotebookService;
use App\Services\CheckinService;
use App\Services\PlanService;
use App\Services\PythonBridgeService;
use App\Utils\JwtUtils;

class CoachController extends Controller
{
    /**
     * AI Coach chat. Read-only: the coach answers from the athlete's own data,
     * it does not change the plan. Proxies to the AiService bridge.
     *
     * Body: { message: string, history?: [{role, content}] }
     */
    #[Route('/coach/chat', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function chat()
    {
        $payload = JwtUtils::fromRequest();
        $userId = (int) $payload['sub'];

        // La puerta legal, tambien aqui: el chat le manda al modelo el plan, el
        // cuaderno y la ficha de salud del atleta. Ver PlanService::terminosAceptados.
        if (!(new \App\Repositories\UserRepository())->hasAcceptedTerms($userId, \App\Support\Legal::VERSION)) {
            $this->json([
                'status' => 'error',
                'code' => 'terms_required',
                'message' => 'Antes de hablar con tu coach tienes que aceptar las condiciones de uso. Recarga la pagina.',
            ], 403);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim((string) ($body['message'] ?? ''));
        if ($message === '') {
            $this->json(['status' => 'error', 'message' => 'Mensaje vacío'], 400);
        }

        $history = [];
        foreach ((array) ($body['history'] ?? []) as $turn) {
            $role = $turn['role'] ?? null;
            $content = trim((string) ($turn['content'] ?? ''));
            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $history[] = ['role' => $role, 'content' => $content];
            }
        }

        // Solo [a-z_], corto. No hace falta validar contra la lista aqui --
        // Python la tiene y cae al estilo por defecto si no la reconoce -- pero
        // si hace falta que lo que se reenvie no sea texto libre.
        $coachId = strtolower(trim((string) ($body['coach_id'] ?? '')));
        if (!preg_match('/^[a-z_]{1,32}$/', $coachId)) {
            $coachId = '';
        }

        $profile = (new ProfileService())->getForUser($userId);
        $notebook = (new NotebookService())->listForUser($userId)['entries'];
        $checkins = (new CheckinService())->historyForUser($userId);
        // El plan, desde MySQL.
        //
        // El puente ya tenía estos tres campos declarados en su esquema y
        // nadie los rellenaba nunca: el entrenador leía el plan de lo que la
        // última generación hubiera dejado en el disco del servicio. Funcionaba
        // por accidente, y dejaba de funcionar en cuanto ese directorio se
        // limpiaba o el atleta borraba y regeneraba. La fuente de la verdad es
        // MySQL, así que es MySQL quien tiene que mandarlo.
        $plan = (new PlanService())->getForUser($userId);

        $bridge = new PythonBridgeService();
        $result = $bridge->coachChat([
            'user_id' => $userId,
            'profile' => $profile,
            'message' => $message,
            'chat_history' => $history,
            'notebook' => $notebook,
            'checkins' => $checkins,
            'weekly_plan' => $plan['weekly_plan'] ?? null,
            'season_plan_md' => $plan['season_plan_md'] ?? null,
            'season_phases' => $plan['season_phases'] ?? null,
            // De aqui salen sus numeros reales (zonas por deporte, ritmos
            // umbral, bandas por intensidad) y su adherencia. Mismo caso que
            // los tres de arriba: estaba declarado y nadie lo rellenaba, asi
            // que el entrenador los leia de un resto en el disco del servicio.
            'summary' => $plan['summary'] ?? null,
            // El entrenador elegido en Configuracion. Es solo un
            // IDENTIFICADOR: el catalogo que decide que anade cada uno al
            // system prompt vive en Python, asi que por aqui no puede
            // colarse texto dentro del prompt. Vacio = estilo por defecto.
            'coach_id' => $coachId,
            // Lo que levantó en sus últimas sesiones (Database/026): sin esto el
            // entrenador no puede hablar de su fuerza con datos.
            'strength_logs' => (new \App\Repositories\StrengthLogRepository())->latest($userId, 6),
        ]);

        if (!$result['ok']) {
            error_log('coach chat bridge error: ' . ($result['error'] ?? 'unknown'));
            $msg = $result['data']['detail'] ?? $result['data']['message'] ?? 'El Agente IA no está disponible ahora.';
            $this->json(['status' => 'error', 'message' => $msg], 502);
        }

        $bridgeData = $result['data']['data'] ?? [];
        $reply = $bridgeData['reply'] ?? '';

        // The coach may have extracted durable notes from this turn — persist them.
        $notas = [];
        if (!empty($bridgeData['notebook_new'])) {
            $notas = (new NotebookService())->importarNotasDelChat($userId, $bridgeData['notebook_new']);
        }
        $notesAdded = count($notas);

        $this->json([
            'status' => 'success',
            'data' => [
                'reply' => $reply,
                'notes_added' => $notesAdded,
                // Cuáles, para enseñarlas en el chat con «Quitar» (Beta 3.0, F9).
                'notes' => $notas,
                'usage' => $bridgeData['usage_metadata'] ?? null,
            ],
        ]);
    }
}
