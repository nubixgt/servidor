<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

/**
 * Repositorio genérico para las 3 tablas de calificaciones (fashion show / coreografía / gala).
 * La tabla y los rubros los decide App\Utils\RondaConfig -- nunca vienen directo del cliente,
 * así que interpolar el nombre de tabla/columna aquí es seguro (siempre viene de la whitelist).
 */
class CalificacionRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function upsert(string $tabla, array $rubricKeys, int $participanteId, int $juradoId, array $rubros, float $total): void
    {
        $columns = array_merge(['participante_id', 'jurado_id'], $rubricKeys, ['total']);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        $updates = array_map(fn($c) => "$c = VALUES($c)", array_merge($rubricKeys, ['total']));

        $sql = "INSERT INTO {$tabla} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updates);

        $params = [
            'participante_id' => $participanteId,
            'jurado_id' => $juradoId,
            'total' => $total,
        ];
        foreach ($rubricKeys as $key) {
            $params[$key] = $rubros[$key];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /** Todas las calificaciones que ha metido UN jurado en una ronda, indexadas por participante_id. */
    public function findByJurado(string $tabla, int $juradoId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$tabla} WHERE jurado_id = :jurado_id");
        $stmt->execute(['jurado_id' => $juradoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Suma de "total" entre todos los jurados, agrupado por participante. */
    public function sumByParticipante(string $tabla): array
    {
        $stmt = $this->pdo->query(
            "SELECT participante_id, SUM(total) AS suma, COUNT(*) AS jurados FROM {$tabla} GROUP BY participante_id"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['participante_id']] = [
                'suma' => (float) $row['suma'],
                'jurados' => (int) $row['jurados'],
            ];
        }
        return $result;
    }
}
