<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Maquina;
use PDO;

class MaquinaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM maquinas ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new Maquina($row);
        }
        return $results;
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM maquinas WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? new Maquina($row) : null;
    }

    public function create(Maquina $maquina)
    {
        $sql = "INSERT INTO maquinas (marca, tipo, identificador, estado, horas_acumuladas, proximo_servicio) 
                VALUES (:marca, :tipo, :identificador, :estado, :horas_acumuladas, :proximo_servicio)";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            'marca' => $maquina->marca,
            'tipo' => $maquina->tipo,
            'identificador' => $maquina->identificador,
            'estado' => $maquina->estado,
            'horas_acumuladas' => $maquina->horas_acumuladas,
            'proximo_servicio' => $maquina->proximo_servicio
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    public function update(Maquina $maquina)
    {
        $sql = "UPDATE maquinas 
                SET marca = :marca, tipo = :tipo, identificador = :identificador, 
                    estado = :estado, horas_acumuladas = :horas, proximo_servicio = :proximo 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'marca' => $maquina->marca,
            'tipo' => $maquina->tipo,
            'identificador' => $maquina->identificador,
            'estado' => $maquina->estado,
            'horas' => $maquina->horas_acumuladas,
            'proximo' => $maquina->proximo_servicio,
            'id' => $maquina->id
        ]);
    }

    public function updateFotoPath($id, $foto_path)
    {
        $sql = "UPDATE maquinas SET foto_path = :path WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['path' => $foto_path, 'id' => $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM maquinas WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
