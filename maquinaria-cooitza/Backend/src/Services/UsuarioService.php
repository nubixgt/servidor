<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Utils\JwtUtils;

class UsuarioService
{
    private $usuarioRepository;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
    }

    public function login($username, $password)
    {
        $usuario = $this->usuarioRepository->findByUsername($username);

        if (!$usuario || strcasecmp($usuario->status, 'activo') !== 0) {
            return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
        }

        if (!password_verify($password, $usuario->password_hash)) {
            return ['success' => false, 'message' => 'Contraseña incorrecta'];
        }

        // Update last access
        $this->usuarioRepository->updateLastAccess($usuario->id);

        $payload = [
            'id' => $usuario->id,
            'username' => $usuario->username,
            'role' => $usuario->role,
            'full_name' => $usuario->full_name,
            'exp' => time() + (86400 * 7) // 7 days expiration
        ];

        $token = JwtUtils::generate($payload);

        return [
            'success' => true, 
            'token' => $token,
            'user' => [
                'id' => $usuario->id,
                'username' => $usuario->username,
                'role' => $usuario->role,
                'full_name' => $usuario->full_name
            ]
        ];
    }

    public function getAll()
    {
        return $this->usuarioRepository->getAll();
    }

    public function create($data)
    {
        $raw_password = !empty($data['password']) ? $data['password'] : '123';
        $password_hash = password_hash($raw_password, PASSWORD_BCRYPT);
        
        $usuario = new \App\Entities\Usuario();
        $usuario->username = $data['username'] ?? '';
        $usuario->password_hash = $password_hash;
        $usuario->full_name = $data['full_name'] ?? '';
        $usuario->role = $data['role'] ?? 'tecnico_dashboard';
        $usuario->status = $data['status'] ?? 'activo';

        $id = $this->usuarioRepository->create($usuario);
        
        return $id ? ['success' => true, 'id' => $id] : ['success' => false, 'message' => 'Error al crear usuario'];
    }

    public function update($data)
    {
        $usuario = new \App\Entities\Usuario();
        $usuario->id = $data['id'];
        $usuario->username = $data['username'] ?? '';
        $usuario->full_name = $data['full_name'] ?? '';
        $usuario->role = $data['role'] ?? 'tecnico_dashboard';
        $usuario->status = $data['status'] ?? 'activo';

        $success = $this->usuarioRepository->update($usuario);
        
        return $success ? ['success' => true] : ['success' => false, 'message' => 'Error al actualizar usuario'];
    }

    public function updateStatus($id, $status)
    {
        $success = $this->usuarioRepository->updateStatus($id, $status);
        return $success ? ['success' => true] : ['success' => false, 'message' => 'Error al actualizar estado'];
    }

    public function delete($id)
    {
        $success = $this->usuarioRepository->delete($id);
        return $success ? ['success' => true] : ['success' => false, 'message' => 'Error al eliminar usuario'];
    }
}
