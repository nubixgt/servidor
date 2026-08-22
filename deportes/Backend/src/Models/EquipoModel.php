<?php
namespace Models;

use Core\Database;
use PDO;

class EquipoModel {
    private $conn;
    private $table_name = "equipos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($nombre, $representante, $telefono, $dpi, $foto_ruta, $foto_representante_ruta = null, $usuario = null, $password_hash = null, $rol = 'encargado') {
        $query = "INSERT INTO " . $this->table_name . " (nombre, representante, telefono, dpi, foto_ruta, foto_representante_ruta, usuario, password_hash, rol) VALUES (:nombre, :representante, :telefono, :dpi, :foto_ruta, :foto_representante_ruta, :usuario, :password_hash, :rol)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":representante", $representante);
        $stmt->bindParam(":telefono", $telefono);
        $stmt->bindParam(":dpi", $dpi);
        $stmt->bindParam(":foto_ruta", $foto_ruta);
        $stmt->bindParam(":foto_representante_ruta", $foto_representante_ruta);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":rol", $rol);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updateFoto($id, $foto_ruta) {
        $query = "UPDATE " . $this->table_name . " SET foto_ruta = :foto_ruta WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":foto_ruta", $foto_ruta);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function updateFotoRepresentante($id, $foto_ruta) {
        $query = "UPDATE " . $this->table_name . " SET foto_representante_ruta = :foto_ruta WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":foto_ruta", $foto_ruta);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function updateSubRepresentante($id, $nombre, $dpi, $telefono, $foto_ruta = null) {
        if ($foto_ruta) {
            $query = "UPDATE " . $this->table_name . " SET sub_representante_nombre = :nombre, sub_representante_dpi = :dpi, sub_representante_telefono = :telefono, sub_representante_foto_ruta = :foto_ruta WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " SET sub_representante_nombre = :nombre, sub_representante_dpi = :dpi, sub_representante_telefono = :telefono WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":dpi", $dpi);
        $stmt->bindParam(":telefono", $telefono);
        if ($foto_ruta) {
            $stmt->bindParam(":foto_ruta", $foto_ruta);
        }
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE rol = 'encargado' ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsuario($usuario) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE usuario = :usuario LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countEquipos() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE rol = 'encargado'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getEquiposIncompletos() {
        $query = "SELECT e.id, e.nombre, e.foto_ruta, COUNT(j.id) as cantidad_jugadores 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN jugadores j ON e.id = j.equipo_id AND j.estado = 'activo'
                  WHERE e.rol = 'encargado' 
                  GROUP BY e.id 
                  HAVING cantidad_jugadores < 10";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEncargados() {
        $query = "SELECT e.id as equipo_id, e.nombre as equipo_nombre, e.representante, e.telefono, e.foto_representante_ruta,
                  (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.estado = 'activo') as cantidad_jugadores
                  FROM " . $this->table_name . " e
                  WHERE e.rol = 'encargado'
                  ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
