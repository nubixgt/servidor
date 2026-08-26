<?php
namespace App\Repositories\Presupuesto;

use App\Utils\Database;
use PDO;

class PresupuestoRepository
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
        if (!empty($filters['ejercicio'])) {
            $where .= " AND e.ejercicio_fiscal = :ej";
            $params['ej'] = $filters['ejercicio'];
        }

        // Primero, verifiquemos si hay registros de UNIDAD_EJECUTORA para este ejercicio
        $checkSql = "SELECT COUNT(*) FROM presupuesto_ejecucion e 
                     JOIN presupuesto_categorias c ON e.categoria_id = c.id 
                     WHERE c.tipo = 'UNIDAD_EJECUTORA'";
        if (!empty($filters['ejercicio'])) {
            $checkSql .= " AND e.ejercicio_fiscal = " . (int)$filters['ejercicio'];
        }
        
        $count = $this->db->query($checkSql)->fetchColumn();

        if ($count > 0) {
            // Si hay unidades ejecutoras, sumamos de las unidades ejecutoras
            $sql = "
                SELECT 
                    SUM(e.asignado) as total_asignado,
                    SUM(e.modificado) as total_modificado,
                    SUM(e.vigente) as total_vigente,
                    SUM(e.devengado) as total_devengado,
                    SUM(e.saldo) as total_saldo,
                    (SUM(e.devengado) / NULLIF(SUM(e.vigente), 0)) * 100 as pct_global
                FROM presupuesto_ejecucion e
                JOIN presupuesto_categorias c ON e.categoria_id = c.id
                $where AND c.tipo = 'UNIDAD_EJECUTORA'
            ";
        } else {
            // Si no hay unidades ejecutoras, buscamos el registro del MAGA en la tabla de MINISTERIOS
            $sql = "
                SELECT 
                    e.asignado as total_asignado,
                    e.modificado as total_modificado,
                    e.vigente as total_vigente,
                    e.devengado as total_devengado,
                    e.saldo as total_saldo,
                    e.pct_ejec as pct_global
                FROM presupuesto_ejecucion e
                JOIN presupuesto_categorias c ON e.categoria_id = c.id
                $where AND c.tipo = 'MINISTERIO' AND (c.codigo = 'MAGA' OR c.nombre LIKE '%AGRICULTURA%')
                LIMIT 1
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || $res['total_vigente'] === null) {
            return [
                'total_asignado' => 0.0,
                'total_modificado' => 0.0,
                'total_vigente' => 0.0,
                'total_devengado' => 0.0,
                'total_saldo' => 0.0,
                'pct_global' => 0.0
            ];
        }

        return $res;
    }

    public function getItems($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];
        
        if (!empty($filters['tipo'])) {
            $where .= " AND c.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        } elseif (!empty($filters['tipo_dashboard'])) {
            $where .= " AND c.tipo != 'MINISTERIO'";
        }

        if (!empty($filters['ejercicio'])) {
            $where .= " AND e.ejercicio_fiscal = :ej";
            $params['ej'] = $filters['ejercicio'];
        }

        $sql = "
            SELECT e.*, c.nombre, c.codigo, c.tipo
            FROM presupuesto_ejecucion e
            JOIN presupuesto_categorias c ON e.categoria_id = c.id
            $where
            ORDER BY c.codigo ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec, fecha_corte) 
                VALUES (:categoria_id, :ejercicio_fiscal, :asignado, :modificado, :vigente, :devengado, :saldo, :pct_ejec, :fecha_corte)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE presupuesto_ejecucion 
                SET asignado = :asignado, modificado = :modificado, vigente = :vigente, 
                    devengado = :devengado, saldo = :saldo, pct_ejec = :pct_ejec, 
                    fecha_corte = :fecha_corte 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM presupuesto_ejecucion WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function cleanEjecucion($ejercicio, $tipo)
    {
        if ($tipo === 'UNIDAD_EJECUTORA') {
            $sql = "DELETE e FROM presupuesto_ejecucion e
                    INNER JOIN presupuesto_categorias c ON e.categoria_id = c.id
                    WHERE e.ejercicio_fiscal = :ejercicio 
                      AND c.tipo IN ('UNIDAD_EJECUTORA', 'PROGRAMA', 'GRUPO_GASTO', 'FUENTE_FINANCIAMIENTO')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['ejercicio' => $ejercicio]);
        } else {
            $sql = "DELETE e FROM presupuesto_ejecucion e
                    INNER JOIN presupuesto_categorias c ON e.categoria_id = c.id
                    WHERE e.ejercicio_fiscal = :ejercicio AND c.tipo = :tipo";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'ejercicio' => $ejercicio,
                'tipo' => $tipo
            ]);
        }
    }

    public function getCategoriaId($tipo, $codigo, $nombre)
    {
        // Check if exists
        if ($tipo === 'MINISTERIO') {
            // Para ministerios, buscamos por nombre exacto o por coincidencia parcial del nombre
            // para evitar colisiones en las siglas de códigos (ej: ME es tanto para Educación como Economía)
            $sql = "SELECT id FROM presupuesto_categorias 
                    WHERE tipo = :tipo 
                      AND (nombre = :nombre 
                           OR nombre LIKE :nombre_like) 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tipo' => $tipo,
                'nombre' => $nombre,
                'nombre_like' => '%' . trim(str_ireplace('MINISTERIO DE', '', $nombre)) . '%'
            ]);
        } else {
            $sql = "SELECT id FROM presupuesto_categorias WHERE tipo = :tipo AND codigo = :codigo LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['tipo' => $tipo, 'codigo' => $codigo]);
        }
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['id'];
        }

        // Insert new category
        $sql = "INSERT INTO presupuesto_categorias (tipo, codigo, nombre) VALUES (:tipo, :codigo, :nombre)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tipo' => $tipo,
            'codigo' => $codigo,
            'nombre' => $nombre
        ]);
        
        return $this->db->lastInsertId();
    }

    public function log(array $data)
    {
        $sql = "INSERT INTO presupuesto_bitacora (usuario, accion, detalles) VALUES (:usuario, :accion, :detalles)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
