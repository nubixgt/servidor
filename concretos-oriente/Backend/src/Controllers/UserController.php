<?php

namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /users
    #[Route('/users', 'GET')]
    public function index()
    {
        try {
            // No seleccionamos la contraseña por seguridad
            $stmt = $this->db->query("SELECT id, nombre, usuario, rol, estado, foto, created_at, updated_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "success",
                "data" => $users
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al obtener los usuarios: " . $e->getMessage()
            ]);
        }
    }

    // POST /users
    #[Route('/users', 'POST')]
    public function store()
    {
        try {
            $nombre = $_POST['nombre'] ?? '';
            $usuario = $_POST['usuario'] ?? '';
            $passwordRaw = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? 'admin';
            $estado = $_POST['estado'] ?? 'Activo';

            if (empty($nombre) || empty($usuario) || empty($passwordRaw)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nombre, usuario y contraseña son obligatorios"]);
                return;
            }

            // Verificar si el usuario ya existe
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE usuario = :usuario");
            $checkStmt->execute([':usuario' => $usuario]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "El nombre de usuario ya está en uso"]);
                return;
            }

            // Encriptar la contraseña
            $passwordHashed = password_hash($passwordRaw, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (nombre, usuario, password, rol, estado) VALUES (:nombre, :usuario, :password, :rol, :estado)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':usuario' => $usuario,
                ':password' => $passwordHashed,
                ':rol' => $rol,
                ':estado' => $estado
            ]);

            $id = $this->db->lastInsertId();

            // Manejo de la foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $baseDir = __DIR__ . "/../../Uploads/Users/$id";
                if (!file_exists($baseDir)) {
                    mkdir($baseDir, 0777, true);
                }

                $fotoExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $fotoName = "foto_" . time() . ".$fotoExt";
                $fotoPath = "$baseDir/$fotoName";
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                    $fotoStmt = $this->db->prepare("UPDATE users SET foto = :foto WHERE id = :id");
                    $fotoStmt->execute([':foto' => "Uploads/Users/$id/$fotoName", ':id' => $id]);
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Usuario creado exitosamente"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al guardar: " . $e->getMessage()
            ]);
        }
    }

    // POST /users/{id} (Update - Usamos POST por FormData con archivos)
    #[Route('/users/{id}', 'POST')]
    public function update($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
                return;
            }

            $nombre = $_POST['nombre'] ?? $user['nombre'];
            $usuario = $_POST['usuario'] ?? $user['usuario'];
            $rol = $_POST['rol'] ?? $user['rol'];
            $estado = $_POST['estado'] ?? $user['estado'];
            $passwordRaw = $_POST['password'] ?? '';

            // Verificar si cambia el usuario y si ya existe
            if ($usuario !== $user['usuario']) {
                $checkStmt = $this->db->prepare("SELECT id FROM users WHERE usuario = :usuario");
                $checkStmt->execute([':usuario' => $usuario]);
                if ($checkStmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "El nombre de usuario ya está en uso"]);
                    return;
                }
            }

            // Preparar actualización
            $updates = ["nombre = :nombre", "usuario = :usuario", "rol = :rol", "estado = :estado"];
            $params = [
                ':nombre' => $nombre,
                ':usuario' => $usuario,
                ':rol' => $rol,
                ':estado' => $estado,
                ':id' => $id
            ];

            // Si se envió una nueva contraseña, la actualizamos
            if (!empty($passwordRaw)) {
                $updates[] = "password = :password";
                $params[':password'] = password_hash($passwordRaw, PASSWORD_DEFAULT);
            }

            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
            $updateStmt = $this->db->prepare($sql);
            $updateStmt->execute($params);

            // Actualizar foto si se envió una nueva
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $baseDir = __DIR__ . "/../../Uploads/Users/$id";
                if (!file_exists($baseDir)) {
                    mkdir($baseDir, 0777, true);
                }

                // Borrar foto vieja si existe
                if (!empty($user['foto']) && file_exists(__DIR__ . "/../../" . $user['foto'])) {
                    unlink(__DIR__ . "/../../" . $user['foto']);
                }

                $fotoExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $fotoName = "foto_" . time() . ".$fotoExt";
                $fotoPath = "$baseDir/$fotoName";
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                    $fotoStmt = $this->db->prepare("UPDATE users SET foto = :foto WHERE id = :id");
                    $fotoStmt->execute([':foto' => "Uploads/Users/$id/$fotoName", ':id' => $id]);
                }
            }

            // Forzar actualización de fecha
            $this->db->query("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = $id");

            echo json_encode([
                "status" => "success",
                "message" => "Usuario actualizado exitosamente"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al actualizar: " . $e->getMessage()
            ]);
        }
    }

    // DELETE /users/{id}
    #[Route('/users/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
                return;
            }

            $deleteStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->execute([$id]);

            // Borrar directorio físico de la foto si existe
            $dirPath = __DIR__ . "/../../Uploads/Users/$id";
            if (is_dir($dirPath)) {
                $this->deleteDirectory($dirPath);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Usuario eliminado exitosamente"
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Error al eliminar: " . $e->getMessage()
            ]);
        }
    }

    // Función auxiliar para borrar un directorio y su contenido
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        
        return rmdir($dir);
    }
}
