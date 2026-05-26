<?php
namespace App\Services;

use App\Repositories\ClientRepository;
use Exception;

class ClientService
{
    private ClientRepository $clientRepo;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
    }

    public function getAllClients(): array
    {
        return $this->clientRepo->findAll();
    }

    public function createClient(array $data): array
    {
        if (empty($data['company_name']) || empty($data['contact_name'])) {
            throw new Exception("Los campos Nombre de la Empresa y Contacto Principal son obligatorios.", 400);
        }

        $id = $this->clientRepo->create($data);
        return ['id' => $id];
    }

    public function updateClient(int $id, array $data): void
    {
        $existing = $this->clientRepo->findById($id);
        if (!$existing) {
            throw new Exception("Cliente no encontrado.", 404);
        }

        if (empty($data['company_name']) || empty($data['contact_name'])) {
            throw new Exception("Los campos Nombre de la Empresa y Contacto Principal son obligatorios.", 400);
        }

        $this->clientRepo->update($id, $data);
    }

    public function deleteClient(int $id): void
    {
        $existing = $this->clientRepo->findById($id);
        if (!$existing) {
            throw new Exception("Cliente no encontrado.", 404);
        }

        $this->clientRepo->delete($id);
    }
}
