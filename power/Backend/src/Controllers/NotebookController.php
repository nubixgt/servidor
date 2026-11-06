<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\NotebookEntryDTO;
use App\Services\NotebookService;
use App\Utils\JwtUtils;

class NotebookController extends Controller
{
    #[Route('/notebook', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function index()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new NotebookService())->listForUser((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/notebook', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function create()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = (new NotebookService())->addForUser((int) $payload['sub'], NotebookEntryDTO::fromRequest($body));
        if (!$result['ok']) {
            $this->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $this->json(['status' => 'success', 'data' => $result['entry']]);
    }

    /** «Reordenar cuaderno» (Beta 3.0, F9): la propuesta, sin guardar nada. */
    #[Route('/notebook/reorganize', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function reorganize()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $respuestas = is_array($body['respuestas'] ?? null) ? $body['respuestas'] : [];
        $res = (new NotebookService())->proponerReorden((int) $payload['sub'], $respuestas);
        if (!$res['ok']) {
            $this->json(['status' => 'error', 'message' => $res['message']], 502);
        }
        $this->json(['status' => 'success', 'data' => $res['propuesta']]);
    }

    /** Acepta UNA acción de la propuesta. Nada se borra: se retira con su motivo. */
    #[Route('/notebook/reorganize/apply', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function applyReorganize()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $res = (new NotebookService())->aplicarReorden((int) $payload['sub'], is_array($body) ? $body : []);
        if (!$res['ok']) {
            $this->json(['status' => 'error', 'message' => $res['message']], 409);
        }
        $this->json(['status' => 'success', 'data' => $res]);
    }

    #[Route('/notebook/{id}', 'PATCH')]
    #[Authorize(['admin', 'user'])]
    public function update($id)
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = (new NotebookService())->patchForUser(
            (int) $payload['sub'],
            (int) $id,
            NotebookEntryDTO::fromRequest($body)
        );

        if (!$result['ok']) {
            $this->json(['status' => 'error', 'message' => $result['message']], $result['code'] ?? 400);
        }

        $this->json(['status' => 'success', 'data' => $result['entry']]);
    }

    #[Route('/notebook/{id}', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function destroy($id)
    {
        $payload = JwtUtils::fromRequest();
        // Borrar retira; `?para_siempre=1` sobre una ya retirada la borra de verdad (Beta 3.0).
        $paraSiempre = ($_GET['para_siempre'] ?? '') === '1';
        $resultado = (new NotebookService())->deleteForUser((int) $payload['sub'], (int) $id, $paraSiempre);

        if ($resultado === null) {
            $this->json(['status' => 'error', 'message' => 'Esa anotación no existe.'], 404);
        }

        $this->json([
            'status' => 'success',
            'data' => ['resultado' => $resultado],
            'message' => $resultado === 'borrada' ? 'Anotación borrada' : 'Anotación retirada',
        ]);
    }
}
