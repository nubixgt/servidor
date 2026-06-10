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

    public function insert(string $optionId, ?string $singer = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO votes (option_id, singer) VALUES (:option_id, :singer)');
        $stmt->execute([
            ':option_id' => $optionId,
            ':singer' => $singer
        ]);
    }

    public function getCountsByOption(): array
    {
        $stmt = $this->db->query('SELECT option_id, COUNT(*) as count FROM votes GROUP BY option_id');
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'option-a' => 0,
            'option-b' => 0,
            'singers' => [
                'Eddy Herrera' => 0,
                'Wilfredo Vargas' => 0
            ]
        ];

        foreach ($results as $row) {
            if (isset($summary[$row['option_id']])) {
                $summary[$row['option_id']] = (int) $row['count'];
            }
        }

        $singerStmt = $this->db->query("SELECT singer, COUNT(*) as count FROM votes WHERE option_id = 'option-a' AND singer IS NOT NULL GROUP BY singer");
        $singerResults = $singerStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($singerResults as $row) {
            $summary['singers'][$row['singer']] = (int) $row['count'];
        }

        return $summary;
    }
}
