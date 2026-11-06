<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\UserService;

class AdminUserController extends Controller
{
    #[Route('/admin/users/{status}', 'GET')]
    #[Authorize(['admin'])]
    public function listByStatus($status)
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $this->json(['status' => 'error', 'message' => 'Invalid status filter'], 400);
        }

        $service = new UserService();
        $usuarios = $service->listByStatus($status);
        if ($status === 'approved') {
            // Hasta cuándo puede generar cada uno: el administrador registra aquí los pagos.
            $acceso = new \App\Services\AccessService();
            foreach ($usuarios as &$u) {
                $id = (int) (is_array($u) ? ($u['id'] ?? 0) : ($u->id ?? 0));
                if (is_array($u)) {
                    $u['acceso'] = $acceso->estado($id);
                }
            }
            unset($u);
        }
        $this->json(['status' => 'success', 'data' => $usuarios]);
    }

    #[Route('/admin/users/{id}/approve', 'POST')]
    #[Authorize(['admin'])]
    public function approve($id)
    {
        (new UserService())->approve((int) $id);
        $this->json(['status' => 'success', 'message' => 'User approved']);
    }

    /**
     * Un pago de la suscripción, hecho FUERA de la app (Database/036): alarga su acceso `months` meses
     * desde donde terminaba. Body: {months, note, plan?}; `plan: 'anual'` son 12 meses a Q600 (2026-10-21).
     */
    #[Route('/admin/users/{id}/payment', 'POST')]
    #[Authorize(['admin'])]
    public function payment($id)
    {
        $payload = \App\Utils\JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $anual = ($body['plan'] ?? '') === 'anual';
        // `plan: 'cortesia'`: los meses que diga, a Q0 y con su propio aviso (los atletas de la beta, 2026-10-23).
        $cortesia = ($body['plan'] ?? '') === 'cortesia';
        $meses = $anual ? 12 : (int) ($body['months'] ?? 0);
        if ($meses < 1 || $meses > 24) {
            $this->json(['status' => 'error', 'message' => 'Entre 1 y 24 meses.'], 422);
        }
        try {
            $periodo = (new \App\Services\AccessService())->registrarPago(
                (int) $id, $meses, (int) ($payload['sub'] ?? 0), (string) ($body['note'] ?? ''), $anual, $cortesia);
        } catch (\Throwable $e) {
            error_log('payment: ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'No se pudo registrar: ¿falta la migración 036?'], 500);
        }
        $this->json(['status' => 'success', 'data' => $periodo]);
    }

    /** Los pagos de un atleta, con los anulados y cuál se puede anular (Database/037). */
    #[Route('/admin/users/{id}/payments', 'GET')]
    #[Authorize(['admin'])]
    public function payments($id)
    {
        try {
            $this->json(['status' => 'success', 'data' => (new \App\Services\AccessService())->pagosDe((int) $id)]);
        } catch (\Throwable $e) {
            error_log('payments: ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'No se pudieron leer los pagos: ¿falta la migración 036?'], 500);
        }
    }

    /** Anula un pago registrado por error; nunca lo borra. Body: {reason}. */
    #[Route('/admin/users/{id}/payments/{paymentId}/void', 'POST')]
    #[Authorize(['admin'])]
    public function voidPayment($id, $paymentId)
    {
        $payload = \App\Utils\JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $r = (new \App\Services\AccessService())->anularPago(
            (int) $id, (int) $paymentId, (int) ($payload['sub'] ?? 0), (string) ($body['reason'] ?? ''));
        if (!$r['ok']) {
            $this->json(['status' => 'error', 'message' => $r['message']], 422);
        }
        $this->json(['status' => 'success', 'data' => $r['acceso']]);
    }

    #[Route('/admin/users/{id}/reject', 'POST')]
    #[Authorize(['admin'])]
    public function reject($id)
    {
        (new UserService())->reject((int) $id);
        $this->json(['status' => 'success', 'message' => 'User rejected']);
    }
}
