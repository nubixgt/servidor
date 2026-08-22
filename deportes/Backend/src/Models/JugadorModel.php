<?php
namespace Models;

use Core\Database;
use PDO;

class JugadorModel {
    private $conn;
    private $table_name = "jugadores";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($equipo_id, $nombre, $dpi, $foto_ruta, $telefono, $posicion) {
        $query = "INSERT INTO " . $this->table_name . " (equipo_id, nombre, dpi, foto_ruta, telefono, posicion) VALUES (:equipo_id, :nombre, :dpi, :foto_ruta, :telefono, :posicion)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":equipo_id", $equipo_id);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":dpi", $dpi);
        $stmt->bindParam(":foto_ruta", $foto_ruta);
        $stmt->bindParam(":telefono", $telefono);
        $stmt->bindParam(":posicion", $posicion);

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

    public function updateJugador($id, $nombre, $dpi, $foto_ruta = null) {
        if ($foto_ruta) {
            $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, dpi = :dpi, foto_ruta = :foto_ruta WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, dpi = :dpi WHERE id = :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":dpi", $dpi);
        if ($foto_ruta) {
            $stmt->bindParam(":foto_ruta", $foto_ruta);
        }
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function getByEquipo($equipo_id, $estado = 'activo') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE equipo_id = :equipo_id AND estado = :estado ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equipo_id", $equipo_id);
        $stmt->bindParam(":estado", $estado);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function darDeBaja($id, $razon) {
        $query = "UPDATE " . $this->table_name . " SET estado = 'inactivo', razon_baja = :razon, fecha_baja = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":razon", $razon);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function countActivos() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE estado = 'activo'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
