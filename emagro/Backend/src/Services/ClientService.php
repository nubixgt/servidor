<?php
namespace App\Services;

use App\Repositories\ClientRepository;

class ClientService
{
    private $clientRepo;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
    }

    public function getAllClients()
    {
        return $this->clientRepo->findAll();
    }

    public function createClient(array $data)
    {
        if (empty($data['nombre']) || empty($data['nit'])) {
            throw new \Exception("El nombre y NIT son campos requeridos.");
        }

        $client = new \App\Entities\Client();
        $client->nombre = $data['nombre'];
        $client->nit = $data['nit'];
        $client->telefono = $data['telefono'] ?? null;
        $client->departamento = $data['departamento'] ?? null;
        $client->municipio = $data['municipio'] ?? null;
        $client->direccion = $data['direccion'] ?? null;
        $client->email = $data['email'] ?? null;
        $client->bloquear_ventas = $data['bloquear_ventas'] ?? 'no';
        // Mocked usuario_id for web operations until JWT implementation parses ID
        $client->usuario_id = 1;

        $id = $this->clientRepo->create($client);
        return [
            "success" => true,
            "message" => "Cliente creado exitosamente.",
            "id" => $id
        ];
    }

    public function updateClient($id, array $data)
    {
        $client = $this->clientRepo->findById($id);
        if (!$client) {
            throw new \Exception("Cliente no encontrado.");
        }

        if (empty($data['nombre']) || empty($data['nit'])) {
            throw new \Exception("El nombre y NIT son campos requeridos.");
        }

        $client->nombre = $data['nombre'];
        $client->nit = $data['nit'];
        $client->telefono = $data['telefono'] ?? $client->telefono;
        $client->departamento = $data['departamento'] ?? $client->departamento;
        $client->municipio = $data['municipio'] ?? $client->municipio;
        $client->direccion = $data['direccion'] ?? $client->direccion;
        $client->email = $data['email'] ?? $client->email;
        $client->bloquear_ventas = $data['bloquear_ventas'] ?? 'no';

        $this->clientRepo->update($client);

        return [
            "success" => true,
            "message" => "Cliente actualizado exitosamente."
        ];
    }
}
