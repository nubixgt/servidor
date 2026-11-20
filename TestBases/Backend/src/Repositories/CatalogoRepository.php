<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class CatalogoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function comunidades(): array
    {
        return $this->pdo->query("SELECT id, nombre FROM comunidades ORDER BY nombre")->fetchAll();
    }

    public function comunidadesBase(): array
    {
        return $this->pdo->query("SELECT id, nombre FROM comunidades_base ORDER BY nombre")->fetchAll();
    }
}
