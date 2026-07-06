<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;
use Exception;

class ExportacionesRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getExportaciones($filters, $limit, $offset)
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(
                nombre_empresa LIKE ? OR 
                pais_destino LIKE ? OR 
                producto LIKE ? OR 
                certificado LIKE ? OR
                categoria_producto LIKE ? OR
                destinatario LIKE ? OR
                aduana LIKE ? OR
                emisor LIKE ?
            )";
            $searchParam = "%{$filters['search']}%";
            for ($i = 0; $i < 8; $i++) {
                $params[] = $searchParam;
            }
        }

        if (!empty($filters['pais'])) {
            $whereConditions[] = "pais_destino = ?";
            $params[] = $filters['pais'];
        }

        if (!empty($filters['categoria'])) {
            $whereConditions[] = "categoria_producto = ?";
            $params[] = $filters['categoria'];
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

        $countSql = "SELECT COUNT(*) as total FROM visar_exportaciones" . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];

        $sql = "SELECT * FROM visar_exportaciones" . $whereClause . " ORDER BY fecha_emision DESC LIMIT ? OFFSET ?";
        
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
        $paises = $this->db->query("SELECT DISTINCT pais_destino FROM visar_exportaciones WHERE pais_destino IS NOT NULL AND pais_destino != '' ORDER BY pais_destino")->fetchAll(PDO::FETCH_COLUMN);
        $categorias = $this->db->query("SELECT DISTINCT categoria_producto FROM visar_exportaciones WHERE categoria_producto IS NOT NULL AND categoria_producto != '' ORDER BY categoria_producto")->fetchAll(PDO::FETCH_COLUMN);
        $emisores = $this->db->query("SELECT DISTINCT emisor FROM visar_exportaciones WHERE emisor IS NOT NULL AND emisor != '' ORDER BY emisor")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'paises' => $paises,
            'categorias' => $categorias,
            'emisores' => $emisores
        ];
    }

    public function getStats($filtros = [])
    {
        $whereClause = "1=1";
        $params = [];
        if (!empty($filtros['fechaDesde'])) {
            $whereClause .= " AND fecha_emision >= :fechaDesde";
            $params[':fechaDesde'] = $filtros['fechaDesde'];
        }
        if (!empty($filtros['fechaHasta'])) {
            $whereClause .= " AND fecha_emision <= :fechaHasta";
            $params[':fechaHasta'] = $filtros['fechaHasta'];
        }

        // 1. Exportaciones por mes
        $mesesSql = "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, SUM(valor_fob) as total_fob, COUNT(*) as cantidad 
                     FROM visar_exportaciones 
                     WHERE fecha_emision IS NOT NULL AND $whereClause
                     GROUP BY mes ORDER BY mes ASC LIMIT 12";
        $stmt = $this->db->prepare($mesesSql);
        $stmt->execute($params);
        $meses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Top 10 Países
        $paisesSql = "SELECT pais_destino, SUM(valor_fob) as total_fob, COUNT(*) as cantidad 
                      FROM visar_exportaciones 
                      WHERE pais_destino IS NOT NULL AND pais_destino != '' AND $whereClause
                      GROUP BY pais_destino ORDER BY total_fob DESC LIMIT 10";
        $stmt = $this->db->prepare($paisesSql);
        $stmt->execute($params);
        $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Top 10 Categorías
        $categoriasSql = "SELECT categoria_producto, SUM(valor_fob) as total_fob, COUNT(*) as cantidad, SUM(peso_neto) as total_peso
                          FROM visar_exportaciones 
                          WHERE categoria_producto IS NOT NULL AND categoria_producto != '' AND $whereClause
                          GROUP BY categoria_producto ORDER BY total_fob DESC LIMIT 10";
        $stmt = $this->db->prepare($categoriasSql);
        $stmt->execute($params);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Top 10 Empresas Exportadoras
        $empresasSql = "SELECT nombre_empresa, COUNT(*) as cantidad, SUM(valor_fob) as total_fob, SUM(peso_neto) as total_peso
                        FROM visar_exportaciones
                        WHERE nombre_empresa IS NOT NULL AND nombre_empresa != '' AND $whereClause
                        GROUP BY nombre_empresa ORDER BY total_fob DESC LIMIT 10";
        $stmt = $this->db->prepare($empresasSql);
        $stmt->execute($params);
        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Top 10 Productos
        $productosSql = "SELECT producto, COUNT(*) as cantidad, SUM(valor_fob) as total_fob, SUM(peso_neto) as total_peso
                         FROM visar_exportaciones
                         WHERE producto IS NOT NULL AND producto != '' AND $whereClause
                         GROUP BY producto ORDER BY total_fob DESC LIMIT 10";
        $stmt = $this->db->prepare($productosSql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Datos del mapa mundial interactivo (Leaflet)
        $mapSql = "SELECT 
                       pais_destino as pais, 
                       SUM(valor_fob) as valor_fob, 
                       SUM(peso_neto) as peso, 
                       COUNT(*) as exportaciones,
                       GROUP_CONCAT(DISTINCT categoria_producto SEPARATOR '|') as categorias
                   FROM visar_exportaciones 
                   WHERE pais_destino IS NOT NULL AND pais_destino != '' AND $whereClause
                   GROUP BY pais_destino";
        $stmt = $this->db->prepare($mapSql);
        $stmt->execute($params);
        $mapData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enriquecer datos del mapa con Top 3 productos por país
        foreach ($mapData as &$paisData) {
            $paisName = $paisData['pais'];
            $topProdSql = "SELECT producto, COUNT(*) as cantidad 
                           FROM visar_exportaciones 
                           WHERE pais_destino = :pais 
                           AND producto IS NOT NULL AND producto != '' AND $whereClause
                           GROUP BY producto ORDER BY cantidad DESC LIMIT 3";
            $stmt = $this->db->prepare($topProdSql);
            $paisParams = array_merge([':pais' => $paisName], $params);
            $stmt->execute($paisParams);
            $paisData['top_productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $paisData['categorias'] = explode('|', $paisData['categorias']);
        }

        // 7. KPIs Generales
        $kpisSql = "SELECT 
                        COUNT(*) as total_exportaciones,
                        SUM(valor_fob) as valor_total,
                        SUM(peso_neto) as peso_total,
                        AVG(valor_fob) as promedio_fob,
                        COUNT(DISTINCT nombre_empresa) as total_empresas
                    FROM visar_exportaciones WHERE $whereClause";
        $stmt = $this->db->prepare($kpisSql);
        $stmt->execute($params);
        $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'por_mes' => $meses,
            'top_paises' => $paises,
            'top_categorias' => $categorias,
            'top_empresas' => $empresas,
            'top_productos' => $productos,
            'map_data' => $mapData,
            'kpis' => $kpis
        ];
    }

    public function createMany(array $records)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO visar_exportaciones (
                nombre_empresa, certificado, fecha_emision, categoria_producto, 
                pais_destino, producto, peso_neto, valor_fob, observaciones, 
                destinatario, aduana, emisor
            ) VALUES (
                :nombre_empresa, :certificado, :fecha_emision, :categoria_producto, 
                :pais_destino, :producto, :peso_neto, :valor_fob, :observaciones, 
                :destinatario, :aduana, :emisor
            )");

            foreach ($records as $record) {
                $stmt->execute([
                    ':nombre_empresa' => $record['nombre_empresa'] ?? null,
                    ':certificado' => $record['certificado'] ?? null,
                    ':fecha_emision' => $record['fecha_emision'] ?? null,
                    ':categoria_producto' => $record['categoria_producto'] ?? null,
                    ':pais_destino' => $record['pais_destino'] ?? null,
                    ':producto' => $record['producto'] ?? null,
                    ':peso_neto' => $record['peso_neto'] ?? 0,
                    ':valor_fob' => $record['valor_fob'] ?? 0,
                    ':observaciones' => $record['observaciones'] ?? null,
                    ':destinatario' => $record['destinatario'] ?? null,
                    ':aduana' => $record['aduana'] ?? null,
                    ':emisor' => $record['emisor'] ?? null
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
