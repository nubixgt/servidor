<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Repositories\UserRepository;
use App\Support\Legal;
use App\Utils\JwtUtils;

/**
 * Las condiciones de uso, y la constancia de que se aceptaron.
 *
 * ## Por qué necesita sesión para leerlas
 *
 * Porque no es una página pública de marketing: es el documento que este
 * atleta tiene que aceptar, y la respuesta le dice si YA lo hizo. Sin sesión no
 * hay a quién contestarle eso.
 *
 * ## Por qué NO hay una ruta que compruebe la aceptación
 *
 * Ya viaja con el usuario en `/auth/me` (`termsAcceptedAt`, `termsVersion`),
 * que el frontend pide al arrancar de todas formas. Una ruta aparte serían dos
 * sitios diciendo lo mismo y un salto de red más en cada carga.
 */
class LegalController extends Controller
{
    #[Route('/legal', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function show()
    {
        JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => [
            'version' => Legal::VERSION,
            'markdown' => Legal::markdown(),
        ]]);
    }

    #[Route('/legal/accept', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function accept()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // La versión la manda el cliente y se COMPRUEBA contra la vigente. Si
        // no coinciden, el atleta está aceptando un texto que ya no es el que
        // está en pie -- una pestaña abierta desde ayer, un despliegue en
        // medio -- y sellarlo dejaría constancia de una aceptación falsa.
        $version = trim((string) ($body['version'] ?? ''));
        if ($version !== Legal::VERSION) {
            $this->json([
                'status' => 'error',
                'code' => 'stale_version',
                'message' => 'Las condiciones cambiaron mientras las leías. Vuelve a cargar la página.',
                'version' => Legal::VERSION,
            ], 409);
        }

        // La casilla de 18 años y del aviso de salud (versión 2026-09-15) se
        // exige AQUÍ también, no sólo en LegalGate.vue: sellar la aceptación sin
        // ella dejaría constancia de algo que el atleta no confirmó. Que la
        // aceptación exista implica que la confirmó.
        if (($body['confirma'] ?? false) !== true) {
            $this->json([
                'status' => 'error',
                'code' => 'confirmation_required',
                'message' => 'Para continuar tienes que confirmar que eres mayor de 18 años y que leíste el aviso de salud.',
            ], 422);
        }

        $repo = new UserRepository();
        if (!$repo->supportsTerms()) {
            // No debería llegarse aquí: `/auth/me` no pide la aceptación si no
            // hay dónde sellarla. Pero si llega, se dice con claridad en vez de
            // reventar contra una columna ausente.
            $this->json([
                'status' => 'error',
                'code' => 'migration_pending',
                'message' => 'Falta correr la migración 015 en la base de datos.',
            ], 503);
        }
        $repo->acceptTerms((int) $payload['sub'], Legal::VERSION);
        $this->json(['status' => 'success', 'data' => [
            'version' => Legal::VERSION,
            'accepted' => true,
        ]]);
    }
}
