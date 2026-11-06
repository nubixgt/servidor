<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\EventService;
use App\Utils\JwtUtils;

/**
 * Las puertas falsas: entradas visibles a cosas que todavía no existen.
 *
 * ## Para qué
 *
 * Compran información sobre demanda al precio de un botón, en vez de al precio
 * de una función. La beta dejó una lista de peticiones -- app nativa, otros
 * relojes, vídeos, nutrición -- y construirlas todas para descubrir cuál
 * importaba costaría meses. Una puerta lo pregunta en un día.
 *
 * Con cinco atletas ninguna dará significancia estadística. Dará algo más útil
 * a esta escala: **a quién le importó lo bastante como para tocarla**, para
 * poder preguntarle a esa persona en concreto.
 *
 * ## La puerta más valiosa es la de invitar
 *
 * Las nueve cuentas de la beta se repartieron a mano. No hay motor de
 * crecimiento -- ni viral, ni pagado -- y eso significa que se puede validar
 * que el producto sirve y no tener por dónde crecer. `invitar` es la única
 * puerta que prueba esa hipótesis, y construirla de verdad es barato.
 *
 * ## Lo que este endpoint NO hace, a propósito
 *
 * No cobra, no pide datos de pago, y no devuelve nada que parezca que la cosa
 * existe. Sólo anota el toque. El texto honesto ("todavía no existe, te
 * avisamos") lo enseña el cliente, y es parte del trato: son cinco personas que
 * conocemos por WhatsApp, y una puerta que se siente a engaño cuesta más que
 * cualquier función.
 */
class ExperimentController extends Controller
{
    /**
     * Las puertas que existen. Cerrada a propósito.
     *
     * Sin esta lista, `detail` acabaría siendo texto libre del cliente y el
     * panel agruparía por cadenas que no coinciden -- que es exactamente cómo
     * una medición deja de poder sumarse. Añadir una puerta es añadir una línea
     * aquí y usar el componente en el frontend.
     */
    private const PUERTAS = [
        'invitar',      // ¿existe motor viral?
        // ¿Cuánto pagarían, como máximo? Sustituye a `precio` (2026-09-13), que
        // enseñaba nuestros costes de IA a todos los atletas -- contra la regla
        // de que los costes son sólo del administrador -- y además ANCLABA la
        // respuesta: con "$9" delante, la gente contesta alrededor de $9. Las
        // filas viejas de `precio` se siguen leyendo en el panel; lo que ya no
        // se puede es escribir una nueva.
        'pago_maximo',
        'app_movil',
        'otros_relojes',
        'video_ejercicio',
        'aguas_abiertas',
        'nutricion',
        // Ritmos por tramo y avituallamiento para cada competicion: lo mas
        // valorado de TriDot (RaceX). En el panel de inicio, en la tarjeta de
        // la proxima competicion.
        'plan_carrera',
    ];

    #[Route('/puerta', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function knock()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $puerta = trim((string) ($body['door'] ?? ''));
        if (!in_array($puerta, self::PUERTAS, true)) {
            $this->json(['status' => 'error', 'message' => 'Puerta desconocida'], 400);
        }

        // Una respuesta opcional, para las puertas que además preguntan algo
        // (la de precio ofrece tres). Se recorta y se limpia: va a parar a una
        // columna que el panel agrupa, no a un campo de texto libre.
        $respuesta = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($body['answer'] ?? '')));

        // El monto tiene forma fija, "25_usd": número entero y moneda de una
        // lista cerrada. Es lo que permite sacar una mediana por moneda en el
        // panel; un "25dolares" o un "veinticinco" no se podrían sumar. Una
        // respuesta con otra forma se anota como toque sin respuesta, no como
        // un monto inventado. El cero vale: "no pagaría nada" es la respuesta
        // que más importa oír.
        if ($puerta === 'pago_maximo' && !preg_match('/^\d{1,5}_(usd|gtq|mxn|eur)$/', $respuesta)) {
            $respuesta = '';
        }

        // La etapa: la vio, abrio el dialogo, o dijo que si. Sin etapa es una
        // confirmacion, que es lo unico que se anotaba antes de 2026-09-11 --
        // asi los clientes viejos siguen contando igual. Lista cerrada.
        $etapa = (string) ($body['stage'] ?? 'confirmada');
        if (!in_array($etapa, ['vista', 'toque', 'confirmada'], true)) {
            $etapa = 'confirmada';
        }

        (new EventService())->recordDoorKnock(
            (int) $payload['sub'],
            $puerta,
            substr((string) $respuesta, 0, 40),
            $etapa
        );

        // Siempre 200 y siempre lo mismo. El cliente no necesita saber si la
        // anotación funcionó: lo que le enseña al atleta no depende de eso.
        $this->json(['status' => 'success', 'data' => ['registered' => true]]);
    }

    /**
     * Las pantallas que se miden: los `name` de `Frontend/src/router/index.js`.
     *
     * Beta 3.0, F0: antes de quitar estadísticas o de cambiar una pantalla hay que
     * saber cuáles abren los atletas. `app_events` sólo anotaba acciones de
     * escritura, así que "nadie mira Analytics" era una suposición. Lista cerrada
     * por lo mismo que las puertas: un nombre libre no se puede sumar.
     */
    private const PANTALLAS = [
        'AdminDashboard', 'AdminProfile', 'AdminPlan', 'AdminHistorial', 'AdminCuaderno',
        'AdminAnalytics', 'AdminReports', 'AdminWelcome',
    ];

    /** Compartió su progreso (Beta 3.0, F8): para medir si se comparte. Nunca falla hacia el atleta. */
    #[Route('/telemetry/share', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function share()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $formato = in_array($body['format'] ?? '', ['publicacion', 'historia'], true) ? $body['format'] : 'otro';
        $como = in_array($body['how'] ?? '', ['compartida', 'descargada'], true) ? $body['how'] : 'otro';
        $ok = (new \App\Repositories\EventRepository())->record((int) $payload['sub'], 'logro_compartido',
                                                                '/telemetry/share', "{$formato}:{$como}");
        $this->json(['status' => 'success', 'data' => ['registered' => $ok]]);
    }

    /** Un error de la pantalla (Beta 3.0, F3). Nunca falla hacia el atleta. */
    #[Route('/telemetry/client-error', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function clientError()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $guardado = (new EventService())->recordClientError(
            (int) $payload['sub'],
            mb_substr((string) ($body['message'] ?? ''), 0, 300),
            mb_substr((string) ($body['page'] ?? ''), 0, 120)
        );
        $this->json(['status' => 'success', 'data' => ['registered' => $guardado]]);
    }

    #[Route('/telemetry/screen', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function screen()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $pantalla = trim((string) ($body['screen'] ?? ''));
        if (!in_array($pantalla, self::PANTALLAS, true)) {
            $this->json(['status' => 'error', 'message' => 'Pantalla desconocida'], 400);
        }
        (new EventService())->recordScreenView((int) $payload['sub'], $pantalla);
        $this->json(['status' => 'success', 'data' => ['registered' => true]]);
    }
}
