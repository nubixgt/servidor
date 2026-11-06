<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\NotificationService;
use App\Utils\JwtUtils;

/**
 * Avisos del administrador a los atletas.
 *
 * Las dos rutas de borrado hacen cosas distintas a proposito, y por eso tienen
 * permisos distintos (ver Database/011_create_notifications.sql):
 *
 *   DELETE /notifications/{id}         -> el atleta lo quita de SU bandeja
 *   DELETE /admin/notifications/{id}   -> el administrador lo retira PARA TODOS
 */
class NotificationController extends Controller
{
    /** La bandeja del atleta que pregunta. */
    #[Route('/notifications', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function index()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new NotificationService())->inboxFor((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Lo quita de la bandeja de QUIEN LLAMA, sacando el usuario del token y
     * nunca del cuerpo de la peticion: si viniera del cuerpo, cualquiera
     * podria vaciarle la bandeja a otro.
     */
    #[Route('/notifications/{id}', 'DELETE')]
    #[Authorize(['admin', 'user'])]
    public function dismiss($id)
    {
        $payload = JwtUtils::fromRequest();
        (new NotificationService())->dismissFor((int) $payload['sub'], (int) $id);
        $this->json(['status' => 'success', 'data' => ['dismissed' => (int) $id]]);
    }

    /** Todo lo enviado, con cuantos lo han descartado ya. */
    #[Route('/admin/notifications', 'GET')]
    #[Authorize(['admin'])]
    public function adminIndex()
    {
        $this->json(['status' => 'success', 'data' => (new NotificationService())->allSent()]);
    }

    #[Route('/admin/notifications', 'POST')]
    #[Authorize(['admin'])]
    public function send()
    {
        $payload = JwtUtils::fromRequest();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = (new NotificationService())->send((int) $payload['sub'], $body);
        if (!$result['ok']) {
            $this->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $this->json(['status' => 'success', 'data' => $result['notification']]);
    }

    /** Lo retira para todos. Los descartes se van con el por CASCADE. */
    #[Route('/admin/notifications/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function destroy($id)
    {
        $borrado = (new NotificationService())->deleteForEveryone((int) $id);
        if (!$borrado) {
            $this->json(['status' => 'error', 'message' => 'Ese aviso ya no existe.'], 404);
        }
        $this->json(['status' => 'success', 'data' => ['deleted' => (int) $id]]);
    }
}
