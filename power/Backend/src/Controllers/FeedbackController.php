<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Repositories\FeedbackRepository;
use App\Services\HelpService;
use App\Utils\JwtUtils;

/**
 * Lo que el atleta nos escribe, desde la app y no por WhatsApp.
 *
 * El motivo cerrado de borrado suma pero no explica, y WhatsApp explica pero
 * no se puede leer junto, no dice de qué pantalla vino y muchos no le escriben
 * a alguien que conocen. Aquí queda cada mensaje con su contexto, y el
 * administrador los lee todos juntos en /admin/metricas.
 */
class FeedbackController extends Controller
{
    /** Dónde se escribió. Lista cerrada: el panel agrupa por esto. */
    // `pago`: el porqué de la cifra que dio en la puerta `pago_maximo`. El monto
    // va a la puerta (se agrega); la razón viene aquí (se lee).
    // `ayuda`: «Pedir ayuda» (Beta 3.0, F3), con categoría, sesión y diagnóstico.
    private const CONTEXTOS = ['borrar_semana', 'borrar_plan', 'general', 'pago', 'ayuda'];

    /** Lo que el atleta dice que es. */
    private const TIPOS = ['fallo', 'sugerencia', 'me_gusta'];

    /** Tope de un mensaje: dos páginas de texto. Más ya no es un comentario. */
    private const MAX_CARACTERES = 4000;

    #[Route('/feedback', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function send()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $mensaje = trim((string) ($body['message'] ?? ''));
        if ($mensaje === '') {
            $this->json(['status' => 'error', 'message' => 'Escribe algo antes de enviar.'], 422);
        }
        $mensaje = mb_substr($mensaje, 0, self::MAX_CARACTERES);

        $contexto = (string) ($body['context'] ?? 'general');
        if (!in_array($contexto, self::CONTEXTOS, true)) {
            $contexto = 'general';
        }
        $tipo = (string) ($body['kind'] ?? '');
        $tipo = in_array($tipo, self::TIPOS, true) ? $tipo : null;
        // El motivo cerrado del borrado, si lo hay: las mismas claves que
        // MOTIVOS_DE_BORRADO en PlanView. Se limpia como las puertas.
        $motivo = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($body['reason'] ?? '')));
        $pagina = trim((string) ($body['page'] ?? ''));

        // «Pedir ayuda»: con su diagnóstico. Sin la migración 029 llega como un «Opinar» más,
        // con la categoría delante del texto: nunca se pierde.
        if ($contexto === 'ayuda') {
            $ayuda = new HelpService();
            $categoria = (string) ($body['category'] ?? 'otra');
            if ($ayuda->disponible()) {
                $id = $ayuda->pedir(
                    (int) $payload['sub'], $categoria, $mensaje,
                    isset($body['session_key']) ? (string) $body['session_key'] : null,
                    $pagina !== '' ? $pagina : null,
                    is_array($body['client_errors'] ?? null) ? $body['client_errors'] : []
                );
                if ($id === null) {
                    $this->json(['status' => 'error', 'message' => 'No se pudo guardar tu consulta. Mándanoslo por WhatsApp, por favor.'], 503);
                }
                $this->json(['status' => 'success', 'data' => ['saved' => true, 'id' => $id]]);
            }
            $contexto = 'general';
            $tipo = 'fallo';
            $mensaje = mb_substr("[Ayuda · {$categoria}] {$mensaje}", 0, self::MAX_CARACTERES);
        }

        $ok = (new FeedbackRepository())->save(
            (int) $payload['sub'],
            $contexto,
            $mensaje,
            $tipo,
            $motivo !== '' ? substr($motivo, 0, 40) : null,
            $pagina !== '' ? $pagina : null
        );

        // Si no se pudo guardar se DICE: el atleta se tomó el tiempo de
        // escribir y tiene que saber que no llegó, para mandarlo por otro lado.
        if (!$ok) {
            $this->json(['status' => 'error', 'message' => 'No se pudo guardar tu mensaje. Mándanoslo por WhatsApp, por favor.'], 503);
        }
        $this->json(['status' => 'success', 'data' => ['saved' => true]]);
    }

    /** «Mis consultas»: lo que preguntó, su estado y la respuesta. Sin diagnóstico. */
    #[Route('/feedback/mine', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function mine()
    {
        $payload = JwtUtils::fromRequest();
        $ayuda = new HelpService();
        $this->json(['status' => 'success', 'data' => [
            'available' => $ayuda->disponible(),
            'items' => $ayuda->mias((int) $payload['sub']),
        ]]);
    }

    /**
     * Sin parámetros, los mensajes de siempre (sin las consultas de ayuda). Con
     * `context=ayuda`, la cola de consultas con su diagnóstico, filtrable por `status` y
     * `category`.
     */
    #[Route('/admin/feedback', 'GET')]
    #[Authorize(['admin'])]
    public function latest()
    {
        if (($_GET['context'] ?? '') === 'ayuda') {
            $ayuda = new HelpService();
            $this->json(['status' => 'success', 'data' => [
                'available' => $ayuda->disponible(),
                'items' => $ayuda->cola($_GET['status'] ?? null, $_GET['category'] ?? null),
            ]]);
        }
        $this->json(['status' => 'success', 'data' => (new FeedbackRepository())->latest()]);
    }

    /** Cambia el estado de una consulta: nuevo | investigando | resuelto. */
    #[Route('/admin/feedback/{id}', 'PATCH')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!(new HelpService())->cambiarEstado((int) $id, (string) ($body['status'] ?? ''))) {
            $this->json(['status' => 'error', 'message' => 'No existe esa consulta o el estado no vale.'], 404);
        }
        $this->json(['status' => 'success', 'data' => ['updated' => true]]);
    }

    /** Responde una consulta: se guarda y le llega al atleta como aviso a él solo. */
    #[Route('/admin/feedback/{id}/reply', 'POST')]
    #[Authorize(['admin'])]
    public function reply($id)
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $texto = mb_substr(trim((string) ($body['reply'] ?? '')), 0, self::MAX_CARACTERES);
        if ($texto === '') {
            $this->json(['status' => 'error', 'message' => 'Escribe la respuesta antes de enviarla.'], 422);
        }
        $estado = isset($body['status']) ? (string) $body['status'] : null;
        if (!(new HelpService())->responder((int) $id, (int) $payload['sub'], $texto, $estado)) {
            $this->json(['status' => 'error', 'message' => 'No existe esa consulta.'], 404);
        }
        $this->json(['status' => 'success', 'data' => ['replied' => true]]);
    }
}
