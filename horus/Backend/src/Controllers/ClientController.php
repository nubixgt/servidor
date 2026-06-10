<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\ClientDTO;
use App\Services\ClientService;

class ClientController extends Controller
{
    #[Route('/clientes', 'GET')]
    // #[Authorize(['admin', 'user'])] // Uncomment if auth is needed
    public function index()
    {
        $service = new ClientService();
        $data = $service->getAllClients();

        $this->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    #[Route('/clientes/{id}', 'GET')]
    // #[Authorize(['admin', 'user'])]
    public function show(int $id)
    {
        $service = new ClientService();
        $client = $service->getClientById($id);

        if (!$client) {
            $this->json(['error' => 'Cliente no encontrado'], 404);
            return;
        }

        $this->json([
            'status' => 'success',
            'data' => $client
        ]);
    }

    #[Route('/clientes', 'POST')]
    // #[Authorize(['admin', 'user'])]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = ClientDTO::fromRequest($data);
        $service = new ClientService();

        try {
            $client = $service->createClient($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Cliente creado exitosamente',
                'data' => $client
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
