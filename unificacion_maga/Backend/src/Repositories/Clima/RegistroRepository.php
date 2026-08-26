<?php
namespace App\Repositories\Clima;

use App\Utils\Database;
use PDO;

class RegistroRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($userId = null, $role = 'Administrador')
    {
        try {
            $sql = "
                SELECT r.*, u.NombreCompleto as usuario_nombre 
                FROM clima_registros r
                LEFT JOIN clima_usuarios u ON r.id_usuario = u.id
            ";
            
            // Si es Técnico, solo ve los suyos.
            if ($role === 'Técnico' && $userId !== null) {
                $sql .= " WHERE r.id_usuario = :user_id ";
            }
            
            $sql .= " ORDER BY r.fecha_registro DESC";

            $stmt = $this->db->prepare($sql);
            
            if ($role === 'Técnico' && $userId !== null) {
                $stmt->execute(['user_id' => $userId]);
            } else {
                $stmt->execute();
            }
            
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cargar fotografías para cada registro
            foreach ($registros as &$reg) {
                $reg['fotografias'] = $this->getFotografias($reg['id']);
            }
            
            return $registros;
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.NombreCompleto as usuario_nombre 
            FROM clima_registros r
            LEFT JOIN clima_usuarios u ON r.id_usuario = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registro) {
            $registro['fotografias'] = $this->getFotografias($id);
        }
        
        return $registro;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO clima_registros (id_usuario, fecha_registro, latitud, longitud, direccion, temperatura, humedad, precipitacion, viento, categoria, condicion_climatica, desastre_natural, observaciones) 
                VALUES (:id_usuario, :fecha_registro, :latitud, :longitud, :direccion, :temperatura, :humedad, :precipitacion, :viento, :categoria, :condicion_climatica, :desastre_natural, :observaciones)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM clima_registros WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getFotografias($registroId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clima_fotos WHERE id_registro = ? ORDER BY orden ASC");
            $stmt->execute([$registroId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function addFotografia(array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO clima_fotos (id_registro, nombre_archivo, ruta_archivo, orden) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['id_registro'],
            $data['nombre_archivo'],
            $data['ruta_archivo'],
            $data['orden'] ?? 0
        ]);
    }
}
