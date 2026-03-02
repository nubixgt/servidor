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
        $stmt = $this->db->query("SELECT * FROM clientes");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Client::class);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, Client::class);
        return $stmt->fetch();
    }

    public function create(Client $client)
    {
        $stmt = $this->db->prepare("INSERT INTO clientes (nombre, nit, telefono, departamento, municipio, direccion, email, bloquear_ventas, usuario_id) VALUES (:nombre, :nit, :telefono, :departamento, :municipio, :direccion, :email, :bloquear_ventas, :usuario_id)");
        $stmt->execute([
            'nombre' => $client->nombre,
            'nit' => $client->nit,
            'telefono' => $client->telefono,
            'departamento' => $client->departamento,
            'municipio' => $client->municipio,
            'direccion' => $client->direccion,
            'email' => $client->email,
            'bloquear_ventas' => $client->bloquear_ventas ?? 'no',
            'usuario_id' => $client->usuario_id
        ]);
        return $this->db->lastInsertId();
    }

    public function update(Client $client)
    {
        $stmt = $this->db->prepare("UPDATE clientes SET 
            nombre = :nombre, 
            nit = :nit, 
            telefono = :telefono, 
            departamento = :departamento, 
            municipio = :municipio, 
            direccion = :direccion, 
            email = :email, 
            bloquear_ventas = :bloquear_ventas 
            WHERE id = :id");

        return $stmt->execute([
            'id' => $client->id,
            'nombre' => $client->nombre,
            'nit' => $client->nit,
            'telefono' => $client->telefono,
            'departamento' => $client->departamento,
            'municipio' => $client->municipio,
            'direccion' => $client->direccion,
            'email' => $client->email,
            'bloquear_ventas' => $client->bloquear_ventas ?? 'no'
        ]);
    }
}
