<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class VoteRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function insert(string $optionId): void
    {
        $stmt = $this->db->prepare('INSERT INTO votes (option_id) VALUES (:option_id)');
        $stmt->execute([':option_id' => $optionId]);
    }

    public function getCountsByOption(): array
    {
        $stmt = $this->db->query('SELECT option_id, COUNT(*) as count FROM votes GROUP BY option_id');
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'option-a' => 0,
            'option-b' => 0,
        ];

        foreach ($results as $row) {
            $summary[$row['option_id']] = (int) $row['count'];
        }

        return $summary;
    }
}
