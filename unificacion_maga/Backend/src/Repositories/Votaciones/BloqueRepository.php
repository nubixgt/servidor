<?php
namespace App\Repositories\Votaciones;

use App\Utils\Database;
use PDO;

class BloqueRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $sql = "SELECT b.*, 
                       COUNT(DISTINCT c.id) as total_congresistas,
                       COUNT(v.id) as total_votos,
                       SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as votos_favor,
                       SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as votos_contra,
                       SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausencias
                FROM votaciones_bloques b 
                LEFT JOIN votaciones_votos v ON b.id = v.bloque_id
                LEFT JOIN votaciones_congresistas c ON v.congresista_id = c.id
                WHERE b.activo = 1
                GROUP BY b.id
                ORDER BY total_congresistas DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrCreate($nombre)
    {
        $nombreNormalizado = trim(preg_replace('/\s+/', ' ', $nombre));
        
        $stmt = $this->db->prepare("SELECT id FROM votaciones_bloques WHERE UPPER(nombre) = UPPER(?) LIMIT 1");
        $stmt->execute([$nombreNormalizado]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['id'];
        }

        $stmtIns = $this->db->prepare("INSERT INTO votaciones_bloques (nombre, nombre_corto) VALUES (?, ?)");
        $stmtIns->execute([$nombreNormalizado, substr($nombreNormalizado, 0, 100)]);
        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM votaciones_bloques WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO votaciones_bloques (nombre, nombre_corto, descripcion) 
                VALUES (:nombre, :nombre_corto, :descripcion)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => $data['nombre'],
            'nombre_corto' => $data['nombre_corto'] ?? null,
            'descripcion' => $data['descripcion'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE votaciones_bloques 
                SET nombre = :nombre, nombre_corto = :nombre_corto, descripcion = :descripcion 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'nombre_corto' => $data['nombre_corto'] ?? null,
            'descripcion' => $data['descripcion'] ?? null
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("UPDATE votaciones_bloques SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
