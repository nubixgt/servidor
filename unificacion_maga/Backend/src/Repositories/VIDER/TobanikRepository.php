<?php
namespace App\Repositories\VIDER;

use App\Utils\Database;
use PDO;

class TobanikRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getSummary($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];
        if (!empty($filters['departamento'])) {
            $where .= " AND t.departamento = :depto";
            $params['depto'] = $filters['departamento'];
        }

        // Totales
        $sqlTotales = "
            SELECT 
                COUNT(DISTINCT t.nombre_cooperativa) as total_cooperativas,
                SUM(t.productores) as total_productores,
                SUM(t.monto_colocado) as total_monto_colocado,
                SUM(t.monto_otorgado) as total_monto_otorgado
            FROM vider_tobanik t
            $where
        ";
        $stmt = $this->db->prepare($sqlTotales);
        $stmt->execute($params);
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);

        // Top 10 Cooperativas
        $sqlTop = "
            SELECT t.nombre_cooperativa, t.monto_colocado 
            FROM vider_tobanik t
            $where 
            ORDER BY t.monto_colocado DESC 
            LIMIT 10
        ";
        $stmt = $this->db->prepare($sqlTop);
        $stmt->execute($params);
        $top = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'totales' => $totales,
            'top_cooperativas' => $top
        ];
    }

    public function create(array $data) {
        $sql = "INSERT INTO vider_tobanik (departamento, nombre_cooperativa, productores, monto_colocado, monto_otorgado, fecha_registro) 
                VALUES (:departamento, :nombre_cooperativa, :productores, :monto_colocado, :monto_otorgado, :fecha_registro)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE vider_tobanik 
                SET departamento = :departamento, nombre_cooperativa = :nombre_cooperativa, 
                    productores = :productores, monto_colocado = :monto_colocado, 
                    monto_otorgado = :monto_otorgado, fecha_registro = :fecha_registro 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM vider_tobanik WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
