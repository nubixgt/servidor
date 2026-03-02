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
    #[Authorize(['Administrador', 'Vendedor'])]
    public function index()
    {
        $service = new ClientService();
        $clients = $service->getAllClients();
        Response::success($clients);
    }
}
