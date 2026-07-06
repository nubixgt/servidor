<?php
namespace App\Services\Users;

use App\Repositories\Auth\UserRepository;

class UserService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function getUsers()
    {
        return $this->userRepo->getAll();
    }

    public function getUser($id)
    {
        $user = $this->userRepo->findById($id);
        if ($user) {
            // Split nombre_completo for the frontend
            $parts = explode(' ', $user['nombre_completo'], 2);
            $user['nombres'] = $parts[0] ?? '';
            $user['apellidos'] = $parts[1] ?? '';
            
            // Decode permissions JSON
            if (isset($user['permisos']) && !empty($user['permisos'])) {
                $user['permissions'] = json_decode($user['permisos'], true);
            } else {
                $user['permissions'] = ['technical_sheet' => false, 'forms' => []];
            }
        }
        return $user;
    }

    public function createUser($data)
    {
        // Format names
        if (isset($data['nombres']) && isset($data['apellidos'])) {
            $data['nombre_completo'] = trim($data['nombres'] . ' ' . $data['apellidos']);
            unset($data['nombres'], $data['apellidos']);
        }

        // Format permissions
        if (isset($data['permissions'])) {
            $data['permisos'] = json_encode($data['permissions']);
            unset($data['permissions']);
        }

        // Fix role key mapping if from frontend
        if (isset($data['role'])) {
            $data['rol'] = $data['role'];
            unset($data['role']);
        }
        if (isset($data['status'])) {
            $data['activo'] = $data['status'];
            unset($data['status']);
        }

        // Encriptar contraseña
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->userRepo->create($data);
    }

    public function updateUser($id, $data)
    {
        // Format names
        if (isset($data['nombres']) && isset($data['apellidos'])) {
            $data['nombre_completo'] = trim($data['nombres'] . ' ' . $data['apellidos']);
            unset($data['nombres'], $data['apellidos']);
        }

        // Format permissions
        if (isset($data['permissions'])) {
            $data['permisos'] = json_encode($data['permissions']);
            unset($data['permissions']);
        }

        // Fix role mapping
        if (isset($data['role'])) {
            $data['rol'] = $data['role'];
            unset($data['role']);
        }
        if (isset($data['status'])) {
            $data['activo'] = $data['status'];
            unset($data['status']);
        }

        // Ensure we don't attempt to update id
        unset($data['id'], $data['created_at'], $data['ultimo_acceso']);

        // Encriptar contraseña si se envía una nueva
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        return $this->userRepo->update($id, $data);
    }

    public function deleteUser($id)
    {
        return $this->userRepo->delete($id);
    }
}
