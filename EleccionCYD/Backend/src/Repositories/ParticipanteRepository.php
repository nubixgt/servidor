<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ParticipanteEntity;
use PDO;

class ParticipanteRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return ParticipanteEntity[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM participantes WHERE activo = 1 ORDER BY categoria, codigo");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => new ParticipanteEntity(
            (int) $row['id'],
            $row['codigo'],
            $row['nombre'],
            $row['categoria'],
            (bool) $row['activo']
        ), $rows);
    }
}
