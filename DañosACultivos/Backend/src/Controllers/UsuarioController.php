<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Utils\Database;

/** Administración de usuarios: solo rol admin. */
class UsuarioController extends Controller
{
    private const ROLES = ['admin', 'supervisor', 'tecnico'];

    private function pdo(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    #[Route('/usuarios', 'GET')]
    #[Authorize(['admin'])]
    public function index()
    {
        $rows = $this->pdo()->query('SELECT id, usuario, rol, activo, created_at FROM usuarios ORDER BY id')->fetchAll();
        $this->json(['usuarios' => array_map([$this, 'format'], $rows)]);
    }

    #[Route('/usuarios', 'POST')]
    #[Authorize(['admin'])]
    public function create()
    {
        $d = $this->input();
        $usuario = $this->validarUsuario($d);
        $rol = $this->validarRol($d);
        $password = (string)($d['password'] ?? '');
        if (strlen($password) < 6) {
            $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres.'], 400);
        }
        $this->assertUsuarioLibre($usuario);

        $stmt = $this->pdo()->prepare('INSERT INTO usuarios (usuario, password, rol, activo) VALUES (:u, :p, :r, :a)');
        $stmt->execute([
            'u' => $usuario,
            'p' => password_hash($password, PASSWORD_BCRYPT),
            'r' => $rol,
            'a' => ($d['activo'] ?? true) ? 1 : 0,
        ]);
        $this->json(['usuario' => $this->find((int)$this->pdo()->lastInsertId())], 201);
    }

    #[Route('/usuarios/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $id = (int)$id;
        $actual = $this->find($id) ?? $this->json(['error' => 'Usuario no encontrado.'], 404);
        $d = $this->input();
        $usuario = $this->validarUsuario($d);
        $rol = $this->validarRol($d);
        $activo = ($d['activo'] ?? true) ? 1 : 0;

        if ($id === $this->yo() && ($rol !== 'admin' || !$activo)) {
            $this->json(['error' => 'No puedes quitarte el rol admin ni desactivar tu propio usuario.'], 400);
        }
        $this->assertUsuarioLibre($usuario, $id);

        $params = ['u' => $usuario, 'r' => $rol, 'a' => $activo, 'id' => $id];
        $sql = 'UPDATE usuarios SET usuario = :u, rol = :r, activo = :a';
        $password = (string)($d['password'] ?? '');
        if ($password !== '') {
            if (strlen($password) < 6) {
                $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres.'], 400);
            }
            $sql .= ', password = :p';
            $params['p'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $this->pdo()->prepare($sql . ' WHERE id = :id')->execute($params);
        $this->json(['usuario' => $this->find($id)]);
    }

    #[Route('/usuarios/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function delete($id)
    {
        $id = (int)$id;
        if ($id === $this->yo()) {
            $this->json(['error' => 'No puedes eliminar tu propio usuario.'], 400);
        }
        $this->find($id) ?? $this->json(['error' => 'Usuario no encontrado.'], 404);
        $this->pdo()->prepare('DELETE FROM usuarios WHERE id = :id')->execute(['id' => $id]);
        $this->json(['ok' => true]);
    }

    private function input(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function yo(): int
    {
        return (int)($this->authUser()['sub'] ?? 0);
    }

    private function validarUsuario(array $d): string
    {
        $u = trim((string)($d['usuario'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $u)) {
            $this->json(['error' => 'El usuario debe tener de 3 a 60 caracteres (letras, números, punto, guion).'], 400);
        }
        return $u;
    }

    private function validarRol(array $d): string
    {
        $r = (string)($d['rol'] ?? '');
        if (!in_array($r, self::ROLES, true)) {
            $this->json(['error' => 'Rol inválido.'], 400);
        }
        return $r;
    }

    private function assertUsuarioLibre(string $usuario, int $exceptId = 0): void
    {
        $stmt = $this->pdo()->prepare('SELECT id FROM usuarios WHERE usuario = :u AND id <> :id LIMIT 1');
        $stmt->execute(['u' => $usuario, 'id' => $exceptId]);
        if ($stmt->fetch()) {
            $this->json(['error' => 'Ya existe un usuario con ese nombre.'], 409);
        }
    }

    private function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT id, usuario, rol, activo, created_at FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->format($row) : null;
    }

    private function format(array $r): array
    {
        return [
            'id' => (int)$r['id'],
            'usuario' => $r['usuario'],
            'rol' => $r['rol'],
            'activo' => (bool)$r['activo'],
            'creadoEn' => $r['created_at'],
        ];
    }
}
