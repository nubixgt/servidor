<?php
namespace App\Repositories\Auth;

use App\Utils\Database;
use PDO;

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, password, email, nombre_completo, rol, activo, puesto_funcional, ubicacion_laboral, permisos, ultimo_acceso,
                IFNULL(intentos_fallidos, 0) as intentos_fallidos,
                bloqueado_hasta
                FROM maga_usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
        } catch (\PDOException $e) {
            // Fallback: columnas nuevas aún no existen (migración pendiente)
            $stmt = $this->db->prepare("SELECT id, username, password, email, nombre_completo, rol, activo, puesto_funcional, ubicacion_laboral, permisos, ultimo_acceso,
                0 as intentos_fallidos, NULL as bloqueado_hasta
                FROM maga_usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, nombre_completo, rol, activo, puesto_funcional, ubicacion_laboral, permisos,
                IFNULL(intentos_fallidos, 0) as intentos_fallidos,
                bloqueado_hasta, ultimo_acceso, created_at FROM maga_usuarios WHERE id = ?");
            $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $stmt = $this->db->prepare("SELECT id, username, email, nombre_completo, rol, activo, ultimo_acceso, created_at FROM maga_usuarios WHERE id = ?");
            $stmt->execute([$id]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT id, username, email, nombre_completo, rol, activo, puesto_funcional, ubicacion_laboral, permisos,
                IFNULL(intentos_fallidos, 0) as intentos_fallidos,
                bloqueado_hasta, ultimo_acceso, created_at FROM maga_usuarios ORDER BY created_at DESC");
        } catch (\PDOException $e) {
            $stmt = $this->db->query("SELECT id, username, email, nombre_completo, rol, activo, ultimo_acceso, created_at FROM maga_usuarios ORDER BY created_at DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        // Set defaults if missing
        $data['puesto_funcional'] = $data['puesto_funcional'] ?? null;
        $data['ubicacion_laboral'] = $data['ubicacion_laboral'] ?? null;
        $data['permisos'] = $data['permisos'] ?? null;

        $sql = "INSERT INTO maga_usuarios (username, password, email, nombre_completo, rol, activo, puesto_funcional, ubicacion_laboral, permisos) 
                VALUES (:username, :password, :email, :nombre_completo, :rol, :activo, :puesto_funcional, :ubicacion_laboral, :permisos)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE maga_usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateLoginTime($id)
    {
        $stmt = $this->db->prepare("UPDATE maga_usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM maga_usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function incrementLoginAttempts($id)
    {
        $stmt = $this->db->prepare("UPDATE maga_usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function resetLoginAttempts($id)
    {
        $stmt = $this->db->prepare("UPDATE maga_usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function lockUser($id, $minutes = 30)
    {
        $stmt = $this->db->prepare("UPDATE maga_usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
        return $stmt->execute([$minutes, $id]);
    }
}
