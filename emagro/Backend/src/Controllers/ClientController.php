<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\ClientService;
use App\Utils\Response;

class ClientController extends Controller
{
    #[Route('/clients', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function index()
    {
        $service = new ClientService();
        $clients = $service->getAllClients();
        Response::success($clients);
    }

    #[Route('/clients', 'POST')]
    #[Authorize(['admin', 'vendedor'])]
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $service = new ClientService();
        try {
            $result = $service->createClient($data);
            Response::success($result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/clients/{id}', 'PUT')]
    #[Authorize(['admin', 'vendedor'])]
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $service = new ClientService();
        try {
            $result = $service->updateClient($id, $data);
            Response::success($result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
