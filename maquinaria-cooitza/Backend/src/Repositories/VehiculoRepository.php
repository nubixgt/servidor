<?php
namespace App\Repositories;

use App\Core\Database;
use App\Entities\Vehiculo;

class VehiculoRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM vehiculos ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new Vehiculo($row);
        }
        return $results;
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM vehiculos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? new Vehiculo($row) : null;
    }

    public function create(Vehiculo $vehiculo)
    {
        $sql = "INSERT INTO vehiculos (marca, placa, tipo, modelo, kilometraje_registro, piloto_asignado, foto, status) 
                VALUES (:marca, :placa, :tipo, :modelo, :kilometraje_registro, :piloto_asignado, :foto, :status)";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            'marca' => $vehiculo->marca,
            'placa' => $vehiculo->placa,
            'tipo' => $vehiculo->tipo,
            'modelo' => $vehiculo->modelo,
            'kilometraje_registro' => $vehiculo->kilometraje_registro,
            'piloto_asignado' => $vehiculo->piloto_asignado,
            'foto' => $vehiculo->foto,
            'status' => $vehiculo->status
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    public function update(Vehiculo $vehiculo)
    {
        $sql = "UPDATE vehiculos 
                SET marca = :marca, placa = :placa, tipo = :tipo, modelo = :modelo, 
                    kilometraje_registro = :kilometraje_registro, piloto_asignado = :piloto_asignado, status = :status";
        
        $params = [
            'marca' => $vehiculo->marca,
            'placa' => $vehiculo->placa,
            'tipo' => $vehiculo->tipo,
            'modelo' => $vehiculo->modelo,
            'kilometraje_registro' => $vehiculo->kilometraje_registro,
            'piloto_asignado' => $vehiculo->piloto_asignado,
            'status' => $vehiculo->status,
            'id' => $vehiculo->id
        ];

        if ($vehiculo->foto !== null) {
            $sql .= ", foto = :foto";
            $params['foto'] = $vehiculo->foto;
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateFotoPath($id, $path)
    {
        $stmt = $this->db->prepare("UPDATE vehiculos SET foto = :foto WHERE id = :id");
        return $stmt->execute(['foto' => $path, 'id' => $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM vehiculos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
