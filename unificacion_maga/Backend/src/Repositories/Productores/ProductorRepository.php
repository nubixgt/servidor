<?php
namespace App\Repositories\Productores;

use App\Utils\Database;
use PDO;

class ProductorRepository
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
            $where .= " AND (nombre LIKE :search OR apellido LIKE :search OR dpi LIKE :search OR finca LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['tipo'])) {
            $where .= " AND tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        if (!empty($filters['estado'])) {
            $where .= " AND estado = :estado";
            $params['estado'] = $filters['estado'];
        }

        $sql = "SELECT * FROM agro_productores $where ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM agro_productores WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByDpi($dpi)
    {
        $stmt = $this->db->prepare("SELECT * FROM agro_productores WHERE dpi = ?");
        $stmt->execute([$dpi]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO agro_productores (dpi, nombre, apellido, finca, departamento, municipio, tipo, estado, telefono, email, direccion, fecha_registro) 
                VALUES (:dpi, :nombre, :apellido, :finca, :departamento, :municipio, :tipo, :estado, :telefono, :email, :direccion, :fecha_registro)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE agro_productores 
                SET dpi = :dpi, nombre = :nombre, apellido = :apellido, finca = :finca, 
                    departamento = :departamento, municipio = :municipio, tipo = :tipo, 
                    estado = :estado, telefono = :telefono, email = :email, 
                    direccion = :direccion, fecha_registro = :fecha_registro 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM agro_productores WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
