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
}
