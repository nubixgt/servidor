<?php
namespace App\Repositories\VISAN;

use App\Utils\Database;
use PDO;

class DapcaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM visan_dapca ORDER BY departamento ASC, intervencion ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $structured = [];
        $totalMeta = 0;
        $totalAvance = 0;

        foreach ($rows as $row) {
            $dept = $row['departamento'];
            if (!isset($structured[$dept])) {
                $structured[$dept] = [
                    'departamento' => $dept,
                    'intervenciones' => []
                ];
            }
            $row['porcentaje'] = $row['meta'] > 0 ? ($row['avance'] / $row['meta']) * 100 : 0;
            $structured[$dept]['intervenciones'][] = $row;
            $totalMeta += $row['meta'];
            $totalAvance += $row['avance'];
        }

        // Progress bars (Summarized by intervention type if needed, or just categories)
        // For now, let's just group by intervention name across all depts
        $stmt2 = $this->db->prepare("
            SELECT intervencion as nombre, SUM(meta) as meta, SUM(avance) as avance 
            FROM visan_dapca 
            GROUP BY intervencion
        ");
        $stmt2->execute();
        $bars = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bars as &$bar) {
            $bar['porcentaje'] = $bar['meta'] > 0 ? round(($bar['avance'] / $bar['meta']) * 100, 1) : 0;
        }

        return [
            'porcentaje_avance' => $totalMeta > 0 ? ($totalAvance / $totalMeta) * 100 : 0,
            'total_productores' => $totalMeta,
            'productores_alcanzados' => $totalAvance,
            'productores_restantes' => max(0, $totalMeta - $totalAvance),
            'programas' => array_values($structured),
            'progress_bars' => $bars
        ];
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO visan_dapca (departamento, intervencion, meta, avance) VALUES (:departamento, :intervencion, :meta, :avance)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE visan_dapca SET departamento = :departamento, intervencion = :intervencion, meta = :meta, avance = :avance WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM visan_dapca WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
