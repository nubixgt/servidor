<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class PadronRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getGeneralStats()
    {
        $stats = [];
        
        // Total registros
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM padron_electoral");
        $stats['total_registros'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total aldeas únicas
        $stmt = $this->db->query("SELECT COUNT(DISTINCT aldea) as total FROM padron_electoral WHERE aldea != ''");
        $stats['total_aldeas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total municipios únicos
        $stmt = $this->db->query("SELECT COUNT(DISTINCT municipio) as total FROM padron_electoral WHERE municipio != ''");
        $stats['total_municipios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Top municipios (ranking)
        $stmt = $this->db->query("
            SELECT municipio as raw_name, COUNT(*) as count 
            FROM padron_electoral 
            WHERE municipio != '' 
            GROUP BY municipio 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $stats['top_municipios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Municipios array for dropdowns (if needed)
        $stmt = $this->db->query("SELECT DISTINCT municipio FROM padron_electoral WHERE municipio != '' ORDER BY municipio ASC");
        $stats['municipios'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $stats;
    }

    public function search($query, $municipio, $page, $limit)
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereConditions = [];

        if (!empty($municipio)) {
            $whereConditions[] = "municipio = :municipio";
            $params[':municipio'] = $municipio;
        }

        if (!empty($query)) {
            $whereConditions[] = "(dpi LIKE :query OR nombre_ciudadano LIKE :query OR aldea LIKE :query)";
            $params[':query'] = "%{$query}%";
        }

        $whereSql = "";
        if (!empty($whereConditions)) {
            $whereSql = "WHERE " . implode(" AND ", $whereConditions);
        }

        // Count total matching records
        $countSql = "SELECT COUNT(*) as total FROM padron_electoral " . $whereSql;
        $stmtCount = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $stmtCount->bindValue($key, $val);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Get records
        $dataSql = "SELECT * FROM padron_electoral " . $whereSql . " LIMIT :offset, :limit";
        $stmtData = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $stmtData->bindValue($key, $val);
        }
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtData->execute();
        $records = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "records" => $records
        ];
    }
}
