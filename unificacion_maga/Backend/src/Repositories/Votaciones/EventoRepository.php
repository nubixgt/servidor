<?php
namespace App\Repositories\Votaciones;

use App\Utils\Database;
use PDO;

class EventoRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT e.id, e.numero_evento as evento, e.titulo, e.sesion_numero as sesion, 
                       DATE_FORMAT(e.fecha_hora, '%Y-%m-%d %H:%i:%s') as fecha,
                       IFNULL(r.votos_favor, 0) as favor,
                       IFNULL(r.votos_contra, 0) as contra,
                       IFNULL(r.votos_ausentes, 0) as ausencias,
                       IFNULL(r.resultado, 'PENDIENTE') as resultado
                FROM votaciones_eventos e 
                LEFT JOIN votaciones_resumen_eventos r ON e.id = r.evento_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.titulo LIKE :search OR e.sesion_numero LIKE :search OR e.numero_evento LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['resultado'])) {
            $sql .= " AND r.resultado = :resultado";
            $params['resultado'] = strtoupper($filters['resultado']);
        }

        $sql .= " ORDER BY e.fecha_hora DESC, e.id DESC";

        if (isset($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummary()
    {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM votaciones_eventos) as eventos,
                (SELECT COUNT(*) FROM votaciones_congresistas WHERE activo = 1) as congresistas,
                (SELECT COUNT(*) FROM votaciones_bloques WHERE activo = 1) as bloques,
                (SELECT COUNT(*) FROM votaciones_votos) as votos
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEstadisticas($filters = [])
    {
        $params = [];
        $whereVotos = "1=1";
        $whereEventos = "1=1";
        
        if (!empty($filters['congresista_id'])) {
            $whereVotos .= " AND v.congresista_id = :cong_id";
            $params['cong_id'] = $filters['congresista_id'];
        }
        if (!empty($filters['bloque_id'])) {
            $whereVotos .= " AND v.bloque_id = :blq_id";
            $params['blq_id'] = $filters['bloque_id'];
        }
        if (!empty($filters['evento_id'])) {
            $whereVotos .= " AND v.evento_id = (SELECT id FROM votaciones_eventos WHERE numero_evento = :ev_id LIMIT 1)";
            $params['ev_id'] = $filters['evento_id'];
        }

        // 1. Distribución Global
        $sqlDist = "SELECT voto, COUNT(*) as cantidad FROM votaciones_votos v WHERE $whereVotos GROUP BY voto";
        $stmtDist = $this->db->prepare($sqlDist);
        foreach($params as $k => $v) $stmtDist->bindValue($k, $v);
        $stmtDist->execute();
        $distribucionRaw = $stmtDist->fetchAll(PDO::FETCH_ASSOC);
        
        $distribucion = ['A FAVOR' => 0, 'EN CONTRA' => 0, 'AUSENTE' => 0, 'LICENCIA' => 0];
        foreach ($distribucionRaw as $row) {
            $voto = strtoupper(trim($row['voto']));
            if (isset($distribucion[$voto])) {
                $distribucion[$voto] += $row['cantidad'];
            } else {
                $distribucion[$voto] = $row['cantidad'];
            }
        }

        // 2. Top Bloques Activos (Bloques con más votos emitidos, excluyendo ausentes)
        $sqlBloques = "SELECT b.id, b.nombre_corto as siglas, b.nombre, COUNT(*) as participaciones 
                       FROM votaciones_votos v 
                       JOIN votaciones_bloques b ON v.bloque_id = b.id 
                       WHERE $whereVotos AND v.voto IN ('A FAVOR', 'EN CONTRA') 
                       GROUP BY b.id, b.nombre_corto, b.nombre 
                       ORDER BY participaciones DESC 
                       LIMIT 5";
        $stmtBloques = $this->db->prepare($sqlBloques);
        foreach($params as $k => $v) $stmtBloques->bindValue($k, $v);
        $stmtBloques->execute();
        $top_bloques = $stmtBloques->fetchAll(PDO::FETCH_ASSOC);

        // 3. Ranking Asistencia (Top Ausentes)
        $sqlAusentes = "SELECT c.id, c.nombre, COUNT(*) as ausencias 
                        FROM votaciones_votos v 
                        JOIN votaciones_congresistas c ON v.congresista_id = c.id 
                        WHERE v.voto = 'AUSENTE' 
                        GROUP BY c.id, c.nombre 
                        ORDER BY ausencias DESC 
                        LIMIT 5";
        $stmtAusentes = $this->db->query($sqlAusentes);
        $top_ausentes = $stmtAusentes->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Ranking Participativos (Top A Favor / En Contra)
        $sqlActivos = "SELECT c.id, c.nombre, COUNT(*) as participaciones 
                        FROM votaciones_votos v 
                        JOIN votaciones_congresistas c ON v.congresista_id = c.id 
                        WHERE v.voto IN ('A FAVOR', 'EN CONTRA') 
                        GROUP BY c.id, c.nombre 
                        ORDER BY participaciones DESC 
                        LIMIT 5";
        $stmtActivos = $this->db->query($sqlActivos);
        $top_activos = $stmtActivos->fetchAll(PDO::FETCH_ASSOC);

        return [
            'distribucion' => $distribucion,
            'top_bloques' => $top_bloques,
            'top_ausentes' => $top_ausentes,
            'top_activos' => $top_activos
        ];
    }

    public function createOrUpdateFromPdf(array $data)
    {
        $numero = $data['numero_evento'] ?? 'N/A';
        
        $stmt = $this->db->prepare("SELECT id FROM votaciones_eventos WHERE numero_evento = ? LIMIT 1");
        $stmt->execute([$numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $id = $row['id'];
            $stmtUpdate = $this->db->prepare("UPDATE votaciones_eventos SET titulo = ?, sesion_numero = ?, fecha_hora = ?, archivo_origen = ? WHERE id = ?");
            $stmtUpdate->execute([$data['titulo'], $data['sesion'], $data['fecha'], $data['acta_pdf'], $id]);
            return $id;
        } else {
            $stmtInsert = $this->db->prepare("INSERT INTO votaciones_eventos (numero_evento, titulo, sesion_numero, fecha_hora, archivo_origen) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$numero, $data['titulo'], $data['sesion'], $data['fecha'], $data['acta_pdf']]);
            return $this->db->lastInsertId();
        }
    }

    public function clearVotos($eventoId)
    {
        $stmt = $this->db->prepare("DELETE FROM votaciones_votos WHERE evento_id = ?");
        return $stmt->execute([$eventoId]);
    }

    public function addVoto(array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO votaciones_votos (evento_id, congresista_id, bloque_id, voto, numero_orden) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['evento_id'],
            $data['congresista_id'],
            $data['bloque_id'],
            $data['voto'],
            $data['numero_orden'] ?? null
        ]);
    }

    public function calcularResumen($eventoId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) total,
                   SUM(voto='A FAVOR') favor,
                   SUM(voto='EN CONTRA') contra,
                   SUM(voto='AUSENTE') aus,
                   SUM(voto='LICENCIA') lic,
                   SUM(voto IN ('ABSTENCION', 'ABSTENCIÓN')) abs
            FROM votaciones_votos WHERE evento_id=?
        ");
        $stmt->execute([$eventoId]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        $res = 'PENDIENTE';
        if ($c['favor'] > $c['contra']) $res = 'APROBADO';
        elseif ($c['contra'] > $c['favor']) $res = 'RECHAZADO';
        elseif ($c['favor'] == $c['contra'] && $c['favor'] > 0) $res = 'EMPATE';

        $stmtIns = $this->db->prepare("
            INSERT INTO votaciones_resumen_eventos (evento_id, total_votos, votos_favor, votos_contra, votos_ausentes, votos_licencia, votos_abstencion, resultado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_votos=VALUES(total_votos),
                votos_favor=VALUES(votos_favor),
                votos_contra=VALUES(votos_contra),
                votos_ausentes=VALUES(votos_ausentes),
                votos_licencia=VALUES(votos_licencia),
                votos_abstencion=VALUES(votos_abstencion),
                resultado=VALUES(resultado)
        ");
        return $stmtIns->execute([$eventoId, $c['total'], $c['favor'], $c['contra'], $c['aus'], $c['lic'], $c['abs'], $res]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM votaciones_eventos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
