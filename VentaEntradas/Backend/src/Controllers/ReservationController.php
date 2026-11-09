<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\ReservationDTO;
use App\Services\ReservationService;
use App\Exceptions\ReservationException;
use App\Utils\Auth;

// Requiere sesión (JWT). Contrato base (ver Diseno/LEEME.md):
//   GET    /reservations                      -> [{ tableId, bookingId, name, createdAt }]
//   POST   /reservations  { tableIds, name }  -> 201 { bookingId, tableIds, createdAt } | 400/409 { error }
//   DELETE /reservations  { bookingId }       -> 200 { ok: true } | 400/404 { error }
class ReservationController extends Controller
{
    #[Route('/reservations', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function index()
    {
        header('Cache-Control: no-store');
        try {
            $this->json((new ReservationService())->getAll(Auth::user()));
        } catch (ReservationException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/reservations', 'POST')]
    #[Authorize(['admin', 'vendedor'])]
    public function create()
    {
        header('Cache-Control: no-store');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['error' => 'Solicitud inválida.'], 400);
        }

        try {
            $result = (new ReservationService())->create(ReservationDTO::fromRequest($data), Auth::user());
            $this->json($result, 201);
        } catch (ReservationException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/reservations', 'DELETE')]
    #[Authorize(['admin', 'vendedor'])]
    public function cancel()
    {
        header('Cache-Control: no-store');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['error' => 'Solicitud inválida.'], 400);
        }

        try {
            (new ReservationService())->cancel($data['bookingId'] ?? null, Auth::user());
            $this->json(['ok' => true]);
        } catch (ReservationException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
