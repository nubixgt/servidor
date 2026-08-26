<?php
namespace App\Repositories\Votaciones;

use App\Utils\Database;
use PDO;

class CongresistaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT c.*, 
                       COUNT(v.id) as total_votos,
                       SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as votos_favor,
                       SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as votos_contra,
                       SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausencias
                FROM votaciones_congresistas c
                LEFT JOIN votaciones_votos v ON c.id = v.congresista_id
                WHERE c.activo = 1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND c.nombre LIKE :search";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['bloque_id'])) {
            // Filtramos por congresistas que pertenezcan a ese bloque
            // Ojo, en esta versión simplificada asumiremos que buscamos el último voto o
            // simplemente que tengan votos en ese bloque
            $sql .= " AND c.id IN (SELECT DISTINCT congresista_id FROM votaciones_votos WHERE bloque_id = :bloque_id)";
            $params['bloque_id'] = $filters['bloque_id'];
        }

        $sql .= " GROUP BY c.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrCreate($nombre)
    {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $nombre)), 'UTF-8');
        
        $stmt = $this->db->prepare("SELECT id FROM votaciones_congresistas WHERE nombre_normalizado = ? LIMIT 1");
        $stmt->execute([$norm]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['id'];
        }

        $stmtIns = $this->db->prepare("INSERT INTO votaciones_congresistas (nombre, nombre_normalizado) VALUES (?, ?)");
        $stmtIns->execute([trim($nombre), $norm]);
        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM votaciones_congresistas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $data['nombre'])), 'UTF-8');
        $sql = "INSERT INTO votaciones_congresistas (nombre, nombre_normalizado, foto) 
                VALUES (:nombre, :nombre_normalizado, :foto)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => trim($data['nombre']),
            'nombre_normalizado' => $norm,
            'foto' => $data['foto'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $data['nombre'])), 'UTF-8');
        $sql = "UPDATE votaciones_congresistas 
                SET nombre = :nombre, nombre_normalizado = :nombre_normalizado, foto = :foto 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nombre' => trim($data['nombre']),
            'nombre_normalizado' => $norm,
            'foto' => $data['foto'] ?? null
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("UPDATE votaciones_congresistas SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                (SELECT COUNT(*) FROM votaciones_votos) as total_votos,
                (SELECT AVG(ausencias_count) FROM (
                    SELECT COUNT(*) as ausencias_count 
                    FROM votaciones_votos 
                    WHERE voto = 'AUSENTE' 
                    GROUP BY congresista_id
                ) as subquery) as promedio_ausencias
            FROM votaciones_congresistas WHERE activo = 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEstadisticas($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM votaciones_vista_estadisticas_congresista WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
