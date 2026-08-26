<?php
namespace App\Repositories\ActividadesDespacho;

use App\Utils\Database;
use PDO;

class ActividadRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, t.nombre as tecnico, t.rol as tecnico_rol 
                FROM despacho_actividades a
                JOIN despacho_tecnicos t ON a.tecnico_id = t.id
                ORDER BY a.fecha_creacion DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // If table doesn't exist yet, return empty array instead of crashing
            return [];
        }
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM despacho_actividades WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO despacho_actividades (tecnico_id, titulo, descripcion, categoria, estado, prioridad, fecha_inicio, fecha_fin) 
                VALUES (:tecnico_id, :titulo, :descripcion, :categoria, :estado, :prioridad, :fecha_inicio, :fecha_fin)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE despacho_actividades 
                SET tecnico_id = :tecnico_id, titulo = :titulo, descripcion = :descripcion, 
                    categoria = :categoria, estado = :estado, prioridad = :prioridad, 
                    fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin 
                WHERE id = :id";
        
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM despacho_actividades WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTecnicos()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM despacho_tecnicos WHERE activo = 1 ORDER BY nombre ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function findTecnicoById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM despacho_tecnicos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createTecnico(array $data)
    {
        $sql = "INSERT INTO despacho_tecnicos (nombre, cargo, area, rol, activo) VALUES (:nombre, :cargo, :area, :rol, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function updateTecnico($id, array $data)
    {
        $sql = "UPDATE despacho_tecnicos SET nombre = :nombre, cargo = :cargo, area = :area, rol = :rol, activo = :activo WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function deleteTecnico($id)
    {
        $stmt = $this->db->prepare("UPDATE despacho_tecnicos SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- SEGUIMIENTO ---
    public function getSeguimiento($actividad_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM despacho_actividades_seguimiento WHERE actividad_id = ? ORDER BY fecha_registro DESC");
        $stmt->execute([$actividad_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSeguimiento($data)
    {
        $stmt = $this->db->prepare("INSERT INTO despacho_actividades_seguimiento (actividad_id, comentario) VALUES (?, ?)");
        return $stmt->execute([$data['actividad_id'], $data['comentario']]);
    }

    // --- ADJUNTOS ---
    public function getAdjuntos($actividad_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM despacho_actividades_adjuntos WHERE actividad_id = ? ORDER BY fecha_carga DESC");
        $stmt->execute([$actividad_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAdjunto($data)
    {
        $stmt = $this->db->prepare("INSERT INTO despacho_actividades_adjuntos (actividad_id, nombre_archivo, url_archivo, tipo_archivo) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['actividad_id'], $data['nombre_archivo'], $data['url_archivo'], $data['tipo_archivo']]);
    }

    // --- ESTADISTICAS ---
    public function getStatsByCategory()
    {
        $stmt = $this->db->prepare("SELECT categoria as name, COUNT(*) as count FROM despacho_actividades GROUP BY categoria");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatsByTecnico()
    {
        $sql = "SELECT t.nombre as name, t.rol as role,
                SUM(CASE WHEN a.estado = 'EN PROGRESO' THEN 1 ELSE 0 END) as enProgreso,
                SUM(CASE WHEN a.estado = 'COMPLETADA' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN a.estado = 'CRITICA' THEN 1 ELSE 0 END) as criticas,
                SUM(CASE WHEN a.prioridad = 'MEDIA' THEN 1 ELSE 0 END) as media
                FROM despacho_tecnicos t
                LEFT JOIN despacho_actividades a ON t.id = a.tecnico_id
                WHERE t.activo = 1
                GROUP BY t.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'CRITICA' THEN 1 ELSE 0 END) as criticas,
                SUM(CASE WHEN estado = 'EN PROGRESO' THEN 1 ELSE 0 END) as en_progreso,
                SUM(CASE WHEN estado = 'COMPLETADA' THEN 1 ELSE 0 END) as completadas
            FROM despacho_actividades
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
