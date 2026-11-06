<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\CheckinDTO;
use App\Services\CheckinService;
use App\Utils\JwtUtils;

class CheckinController extends Controller
{
    #[Route('/checkin', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function submit()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $dto = CheckinDTO::fromRequest($body);
        if (!$dto->hasContent()) {
            $this->json(['status' => 'error', 'message' => 'El check-in está vacío'], 400);
        }

        // `today` es la fecha LOCAL del navegador; ver CheckinService::hoy().
        $result = (new CheckinService())->submitForUser(
            (int) $payload['sub'],
            $dto,
            (string) ($body['today'] ?? '')
        );
        $this->json(['status' => 'success', 'data' => $result]);
    }

    #[Route('/checkin/latest', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function latest()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new CheckinService())->latestForUser((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }
}
