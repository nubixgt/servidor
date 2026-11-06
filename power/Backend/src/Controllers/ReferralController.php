<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\ReferralService;
use App\Utils\JwtUtils;

/** Invitaciones (Beta 3.0, Database/033). Ver `ReferralService`. */
class ReferralController extends Controller
{
    /** Su código, a quién invitó y las semanas gratis que lleva. */
    #[Route('/referrals/mine', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function mine()
    {
        $payload = JwtUtils::fromRequest();
        $this->json(['status' => 'success', 'data' => (new ReferralService())->mio((int) $payload['sub'])]);
    }

    /** Escribir el código de quien le invitó, hasta conectar su Garmin (2026-10-21). Body: {code}. */
    #[Route('/referrals/claim', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function claim()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $servicio = new ReferralService();
        $r = $servicio->reclamar((int) $payload['sub'], (string) ($body['code'] ?? ''));
        if (!$r['ok']) {
            $this->json(['status' => 'error', 'message' => $r['message']], 422);
        }
        $this->json(['status' => 'success', 'data' => $servicio->mio((int) $payload['sub'])]);
    }

    /** Para Métricas: cuántos entraron invitados, cuántos cuentan y quién invita. */
    #[Route('/admin/metrics/referrals', 'GET')]
    #[Authorize(['admin'])]
    public function resumen()
    {
        $this->json(['status' => 'success', 'data' => (new ReferralService())->resumenParaElAdministrador()]);
    }
}
