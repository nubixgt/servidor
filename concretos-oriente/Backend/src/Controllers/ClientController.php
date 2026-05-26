<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\ClientService;
use Exception;

class ClientController extends Controller
{
    private ClientService $clientService;

    public function __construct()
    {
        $this->clientService = new ClientService();
    }

    #[Route('/clients', 'GET')]
    public function index()
    {
        try {
            $data = $this->clientService->getAllClients();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clients', 'POST')]
    public function store()
    {
        try {
            $data = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'ruc'          => trim($_POST['ruc'] ?? '') ?: null,
                'status'       => trim($_POST['status'] ?? 'active'),
                'contact_name' => trim($_POST['contact_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? '') ?: null,
                'phone'        => trim($_POST['phone'] ?? '') ?: null,
                'address'      => trim($_POST['address'] ?? '') ?: null,
            ];

            $result = $this->clientService->createClient($data);

            $this->json([
                'status'  => 'success',
                'message' => 'Cliente registrado correctamente',
                'id'      => $result['id']
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/clients/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'ruc'          => trim($_POST['ruc'] ?? '') ?: null,
                'status'       => trim($_POST['status'] ?? 'active'),
                'contact_name' => trim($_POST['contact_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? '') ?: null,
                'phone'        => trim($_POST['phone'] ?? '') ?: null,
                'address'      => trim($_POST['address'] ?? '') ?: null,
            ];

            $this->clientService->updateClient((int)$id, $data);

            $this->json(['status' => 'success', 'message' => 'Cliente actualizado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/clients/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->clientService->deleteClient((int)$id);
            $this->json(['status' => 'success', 'message' => 'Cliente eliminado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
