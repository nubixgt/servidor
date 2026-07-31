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
}
