<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        // No seleccionamos la contraseña por seguridad
        $stmt = $this->pdo->query("SELECT id, nombre, usuario, rol, estado, foto, created_at, updated_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function findByUsuario(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function findByUsuarioForAuth(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE usuario = :usuario AND estado = 'Activo'");
        $stmt->execute(['usuario' => $usuario]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO users (nombre, usuario, password, rol, estado) VALUES (:nombre, :usuario, :password, :rol, :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre'   => $data['nombre'],
            'usuario'  => $data['usuario'],
            'password' => $data['password'],
            'rol'      => $data['rol'],
            'estado'   => $data['estado']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $updates = ["nombre = :nombre", "usuario = :usuario", "rol = :rol", "estado = :estado"];
        $params = [
            'nombre'  => $data['nombre'],
            'usuario' => $data['usuario'],
            'rol'     => $data['rol'],
            'estado'  => $data['estado'],
            'id'      => $id
        ];

        if (isset($data['password']) && !empty($data['password'])) {
            $updates[] = "password = :password";
            $params['password'] = $data['password'];
        }

        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);

        // Forzar actualización de fecha
        $this->pdo->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $id]);
    }

    public function updateFoto(int $id, string $fotoPath): void
    {
        $this->pdo->prepare("UPDATE users SET foto = :foto WHERE id = :id")
             ->execute(['foto' => $fotoPath, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);
    }
}
