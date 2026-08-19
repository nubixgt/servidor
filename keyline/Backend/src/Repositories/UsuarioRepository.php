<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Usuario;
use PDO;

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1');
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function actualizarUltimoAcceso(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array $criteria Puede incluir: role, regionAsignada (filtra técnicos de una región,
     *                        usado para que un supervisor vea solo su equipo).
     * @return Usuario[]
     */
    public function findAll(array $criteria = []): array
    {
        $where = [];
        $params = [];
        if (!empty($criteria['role'])) {
            $where[] = 'role = :role';
            $params['role'] = $criteria['role'];
        }
        if (!empty($criteria['regionAsignada'])) {
            $where[] = 'region_asignada = :region';
            $params['region'] = $criteria['regionAsignada'];
        }
        $sql = 'SELECT * FROM usuarios';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nombre ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function existeUsuario(string $usuario, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario';
        $params = ['usuario' => $usuario];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(Usuario $u): Usuario
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nombre, usuario, email, password_hash, role, region_asignada, telefono, activo)
             VALUES (:nombre, :usuario, :email, :password_hash, :role, :region_asignada, :telefono, :activo)'
        );
        $stmt->execute([
            'nombre' => $u->nombre,
            'usuario' => $u->usuario,
            'email' => $u->email ?: null,
            'password_hash' => $u->passwordHash,
            'role' => $u->role,
            'region_asignada' => $u->regionAsignada ?: null,
            'telefono' => $u->telefono ?: null,
            'activo' => $u->activo ? 1 : 0,
        ]);
        $u->id = (int)$this->pdo->lastInsertId();
        return $u;
    }

    /** @param array $fields Claves: nombre, role, regionAsignada, telefono, activo, passwordHash (todas opcionales) */
    public function update(int $id, array $fields): void
    {
        $map = [
            'nombre' => 'nombre', 'role' => 'role', 'regionAsignada' => 'region_asignada',
            'telefono' => 'telefono', 'activo' => 'activo', 'passwordHash' => 'password_hash',
            'email' => 'email',
        ];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $key => $value) {
            if (!isset($map[$key])) {
                continue;
            }
            $column = $map[$key];
            $set[] = "$column = :$key";
            if ($key === 'activo') {
                $value = $value ? 1 : 0;
            } elseif ($key === 'regionAsignada' || $key === 'telefono' || $key === 'email') {
                $value = $value ?: null;
            }
            $params[$key] = $value;
        }
        if (!$set) {
            return;
        }
        $sql = 'UPDATE usuarios SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Usuario
    {
        return new Usuario(
            id: (int)$row['id'],
            nombre: $row['nombre'],
            usuario: $row['usuario'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            role: $row['role'],
            regionAsignada: $row['region_asignada'],
            telefono: $row['telefono'],
            activo: (bool)$row['activo'],
            createdAt: $row['created_at'],
            ultimoAcceso: $row['ultimo_acceso'],
        );
    }
}
