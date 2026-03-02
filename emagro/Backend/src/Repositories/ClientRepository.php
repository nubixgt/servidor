<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Client;
use PDO;

class ClientRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM clients");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Client::class);
    }

    public function create(Client $client)
    {
        $stmt = $this->db->prepare("INSERT INTO clients (client_code, name, company_name, nit, phone, address, credit_limit, status, avatar_url) VALUES (:client_code, :name, :company_name, :nit, :phone, :address, :credit_limit, :status, :avatar_url)");
        $stmt->execute([
            'client_code' => $client->client_code,
            'name' => $client->name,
            'company_name' => $client->company_name,
            'nit' => $client->nit,
            'phone' => $client->phone,
            'address' => $client->address,
            'credit_limit' => $client->credit_limit,
            'status' => $client->status ?? 'Activo',
            'avatar_url' => $client->avatar_url
        ]);
        return $this->db->lastInsertId();
    }
}
