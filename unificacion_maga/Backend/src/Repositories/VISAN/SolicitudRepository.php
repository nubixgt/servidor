<?php
namespace App\Repositories\VISAN;

use App\Utils\Database;
use PDO;

class SolicitudRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (s.id_solicitud LIKE :search OR p.nombre LIKE :search OR p.dpi LIKE :search OR s.comunidad LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['programa'])) {
            $where .= " AND s.programa = :programa";
            $params['programa'] = $filters['programa'];
        }

        $sql = "
            SELECT s.*, p.nombre as beneficiario_nombre, p.dpi as beneficiario_dpi, p.departamento, p.municipio
            FROM visan_solicitudes s
            LEFT JOIN productores p ON s.productor_id = p.id
            $where
            ORDER BY s.fecha DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO visan_solicitudes (id_solicitud, fecha, productor_id, comunidad, programa, estado, observaciones) 
                VALUES (:id_solicitud, :fecha, :productor_id, :comunidad, :programa, :estado, :observaciones)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }
}
