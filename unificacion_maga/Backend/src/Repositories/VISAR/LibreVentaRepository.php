<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;
use Exception;

class LibreVentaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRegistros($filters, $limit, $offset)
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(
                empresa LIKE ? OR 
                numero_documento LIKE ? OR 
                producto LIKE ?
            )";
            $searchParam = "%{$filters['search']}%";
            for ($i = 0; $i < 3; $i++) {
                $params[] = $searchParam;
            }
        }

        if (!empty($filters['categoria'])) {
            $whereConditions[] = "categoria_producto = ?";
            $params[] = $filters['categoria'];
        }

        if (!empty($filters['pais'])) {
            $whereConditions[] = "pais_destino = ?";
            $params[] = $filters['pais'];
        }

        if (!empty($filters['emisor'])) {
            $whereConditions[] = "emisor = ?";
            $params[] = $filters['emisor'];
        }

        if (!empty($filters['fechaDesde'])) {
            $whereConditions[] = "fecha_emision >= ?";
            $params[] = $filters['fechaDesde'];
        }

        if (!empty($filters['fechaHasta'])) {
            $whereConditions[] = "fecha_emision <= ?";
            $params[] = $filters['fechaHasta'];
        }

        $whereClause = '';
        if (count($whereConditions) > 0) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
        }

        $countSql = "SELECT COUNT(*) as total FROM visar_libre_venta" . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];

        $sql = "SELECT * FROM visar_libre_venta" . $whereClause . " ORDER BY fecha_emision DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'records' => $records,
            'total' => $totalRecords
        ];
    }
    
    public function getFilters()
    {
        $categorias = $this->db->query("SELECT DISTINCT categoria_producto FROM visar_libre_venta WHERE categoria_producto IS NOT NULL AND categoria_producto != '' ORDER BY categoria_producto")->fetchAll(PDO::FETCH_COLUMN);
        $paises = $this->db->query("SELECT DISTINCT pais_destino FROM visar_libre_venta WHERE pais_destino IS NOT NULL AND pais_destino != '' ORDER BY pais_destino")->fetchAll(PDO::FETCH_COLUMN);
        $emisores = $this->db->query("SELECT DISTINCT emisor FROM visar_libre_venta WHERE emisor IS NOT NULL AND emisor != '' ORDER BY emisor")->fetchAll(PDO::FETCH_COLUMN);
        
        return [
            'categorias' => $categorias,
            'paises' => $paises,
            'emisores' => $emisores
        ];
    }

    public function getStats()
    {
        $paisesSql = "SELECT pais_destino, COUNT(*) as total_exportaciones, SUM(peso_neto) as peso_total 
                      FROM visar_libre_venta 
                      WHERE pais_destino IS NOT NULL AND pais_destino != '' 
                      GROUP BY pais_destino ORDER BY total_exportaciones DESC LIMIT 10";
        $paises = $this->db->query($paisesSql)->fetchAll(PDO::FETCH_ASSOC);

        $categoriasSql = "SELECT categoria_producto, COUNT(*) as total_exportaciones 
                          FROM visar_libre_venta 
                          WHERE categoria_producto IS NOT NULL AND categoria_producto != '' 
                          GROUP BY categoria_producto ORDER BY total_exportaciones DESC LIMIT 10";
        $categorias = $this->db->query($categoriasSql)->fetchAll(PDO::FETCH_ASSOC);
        
        $mesesSql = "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COUNT(*) as total_registros 
                     FROM visar_libre_venta 
                     WHERE fecha_emision IS NOT NULL 
                     GROUP BY mes ORDER BY mes ASC LIMIT 12";
        $meses = $this->db->query($mesesSql)->fetchAll(PDO::FETCH_ASSOC);

        $empresasSql = "SELECT empresa, COUNT(*) as total_exportaciones, SUM(peso_neto) as peso_total, GROUP_CONCAT(DISTINCT pais_destino SEPARATOR ', ') as paises_destino 
                        FROM visar_libre_venta 
                        WHERE empresa IS NOT NULL AND empresa != '' 
                        GROUP BY empresa ORDER BY total_exportaciones DESC LIMIT 10";
        $empresas = $this->db->query($empresasSql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'top_paises' => $paises,
            'top_categorias' => $categorias,
            'tendencia_meses' => $meses,
            'top_empresas' => $empresas
        ];
    }

    public function createMany(array $records)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO visar_libre_venta (
                empresa, numero_documento, producto, categoria_producto, 
                pais_destino, emisor, fecha_emision
            ) VALUES (
                :empresa, :numero_documento, :producto, :categoria_producto, 
                :pais_destino, :emisor, :fecha_emision
            )");

            foreach ($records as $record) {
                $stmt->execute([
                    ':empresa' => $record['empresa'] ?? null,
                    ':numero_documento' => $record['numero_documento'] ?? null,
                    ':producto' => $record['producto'] ?? null,
                    ':categoria_producto' => $record['categoria_producto'] ?? null,
                    ':pais_destino' => $record['pais_destino'] ?? null,
                    ':emisor' => $record['emisor'] ?? null,
                    ':fecha_emision' => $record['fecha_emision'] ?? null
                ]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
