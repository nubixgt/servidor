<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Usuario;
use PDO;

class UsuarioRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?Usuario
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.usuario = :usuario
             LIMIT 1"
        );
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findByTelefono(string $telefono): ?Usuario
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.telefono = :telefono
             LIMIT 1"
        );
        $stmt->execute(['telefono' => $telefono]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function create(Usuario $entity): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios
                (nombre_completo, usuario, password_hash, telefono, departamento_id, municipio_id, rol_id)
             VALUES
                (:nombre_completo, :usuario, :password_hash, :telefono, :departamento_id, :municipio_id, :rol_id)"
        );
        $stmt->execute([
            'nombre_completo' => $entity->nombreCompleto,
            'usuario' => $entity->usuario,
            'password_hash' => $entity->passwordHash,
            'telefono' => $entity->telefono,
            'departamento_id' => $entity->departamentoId,
            'municipio_id' => $entity->municipioId,
            'rol_id' => $entity->rolId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function hydrate(array $row): Usuario
    {
        return new Usuario(
            (int) $row['id'],
            $row['nombre_completo'],
            $row['usuario'],
            $row['password_hash'],
            $row['telefono'],
            (int) $row['departamento_id'],
            (int) $row['municipio_id'],
            (int) $row['rol_id'],
            $row['rol_nombre'],
            (bool) $row['activo']
        );
    }
}
