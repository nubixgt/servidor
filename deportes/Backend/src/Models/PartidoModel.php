<?php
namespace Models;

use Core\Database;
use PDO;

class PartidoModel {
    private $conn;
    private $table_name = "partidos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($fecha, $equipo_local_id, $equipo_visitante_id, $goles_local, $goles_visitante, $registrado_por) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (fecha, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, registrado_por) 
                  VALUES (:fecha, :equipo_local_id, :equipo_visitante_id, :goles_local, :goles_visitante, :registrado_por)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":fecha", $fecha);
        $stmt->bindParam(":equipo_local_id", $equipo_local_id);
        $stmt->bindParam(":equipo_visitante_id", $equipo_visitante_id);
        $stmt->bindParam(":goles_local", $goles_local);
        $stmt->bindParam(":goles_visitante", $goles_visitante);
        $stmt->bindParam(":registrado_por", $registrado_por);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAll() {
        $query = "SELECT p.*, 
                  el.nombre as equipo_local_nombre, el.foto_ruta as equipo_local_foto,
                  ev.nombre as equipo_visitante_nombre, ev.foto_ruta as equipo_visitante_foto
                  FROM " . $this->table_name . " p
                  JOIN equipos el ON p.equipo_local_id = el.id
                  JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  ORDER BY p.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, 
                  el.nombre as equipo_local_nombre, el.foto_ruta as equipo_local_foto,
                  ev.nombre as equipo_visitante_nombre, ev.foto_ruta as equipo_visitante_foto
                  FROM " . $this->table_name . " p
                  JOIN equipos el ON p.equipo_local_id = el.id
                  JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
