<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ExampleEntity;
use PDO;

class ExampleRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        // Example Query
        // $stmt = $this->pdo->query("SELECT * FROM examples");
        // $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mock data for template purposes
        return [
            new ExampleEntity(1, 'Item 1', 'Description 1'),
            new ExampleEntity(2, 'Item 2', 'Description 2'),
        ];
    }

    public function create(ExampleEntity $entity): bool
    {
        // Example Insert
        // $stmt = $this->pdo->prepare("INSERT INTO examples (name, description) VALUES (:name, :desc)");
        // return $stmt->execute(['name' => $entity->name, 'desc' => $entity->description]);

        return true;
    }
}
