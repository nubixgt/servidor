<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Entities\User;
use Exception;

class UserService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function getAllUsers()
    {
        return $this->userRepo->findAll();
    }

    public function createUser(array $data)
    {
        if (empty($data['nombre']) || empty($data['usuario']) || empty($data['contrasena'])) {
            throw new Exception("Faltan campos obligatorios para crear el usuario");
        }

        // Check if user already exists
        $existing = $this->userRepo->findByUsername($data['usuario']);
        if ($existing) {
            throw new Exception("El nombre de usuario ya está en uso");
        }

        $user = new User();
        $user->nombre = $data['nombre'];
        $user->usuario = $data['usuario'];
        $user->contrasena = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        $user->rol = $data['rol'] ?? 'vendedor';
        $user->estado = $data['estado'] ?? 'activo';

        $id = $this->userRepo->create($user);
        $user->id = $id;

        // Don't return password hash
        unset($user->contrasena);

        return $user;
    }

    public function updateUser($id, array $data)
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        if (empty($data['nombre']) || empty($data['usuario'])) {
            throw new Exception("Nombre y usuario son obligatorios");
        }

        // Si se cambia el nombre de usuario, verificar que no exista ya para otro id
        $existing = $this->userRepo->findByUsername($data['usuario']);
        if ($existing && $existing->id != $id) {
            throw new Exception("El nombre de usuario ya está en uso por otra cuenta");
        }

        $user->nombre = $data['nombre'];
        $user->usuario = $data['usuario'];
        $user->rol = $data['rol'] ?? $user->rol;
        $user->estado = $data['estado'] ?? $user->estado;

        if (!empty($data['contrasena'])) {
            $user->contrasena = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        } else {
            $user->contrasena = null; // null => no password change in Repo logic
        }

        $this->userRepo->update($user);

        unset($user->contrasena);
        return $user;
    }

    public function toggleUserStatus($id)
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }

        $newStatus = ($user->estado === 'activo') ? 'De Baja' : 'activo';

        $this->userRepo->updateStatus($id, $newStatus);

        return $newStatus;
    }
}
