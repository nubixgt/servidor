<?php
namespace App\Repositories\Extension;

use App\Utils\Database;
use PDO;

class ExtensionRepository
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
            $where .= " AND (v.municipio LIKE :search OR v.comunidad LIKE :search OR v.codigo LIKE :search OR e.nombre LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['tipo'])) {
            $where .= " AND v.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        $sql = "
            SELECT v.*, e.nombre as extensionista, e.puesto as extensionista_puesto
            FROM extension_visitas v
            JOIN extension_extensionistas e ON v.extensionista_id = e.id
            $where
            ORDER BY v.fecha DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExtensionistas()
    {
        $stmt = $this->db->prepare("SELECT * FROM extension_extensionistas WHERE activo = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO extension_visitas (codigo, fecha, departamento, municipio, comunidad, tipo, extensionista_id, estado, beneficiarios, observaciones, latitud, longitud) 
                VALUES (:codigo, :fecha, :departamento, :municipio, :comunidad, :tipo, :extensionista_id, :estado, :beneficiarios, :observaciones, :latitud, :longitud)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE extension_visitas 
                SET codigo = :codigo, fecha = :fecha, departamento = :departamento, municipio = :municipio, 
                    comunidad = :comunidad, tipo = :tipo, extensionista_id = :extensionista_id, 
                    estado = :estado, beneficiarios = :beneficiarios, observaciones = :observaciones, 
                    latitud = :latitud, longitud = :longitud 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM extension_visitas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
