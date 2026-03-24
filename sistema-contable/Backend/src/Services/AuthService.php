<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login($username, $password)
    {
        $user = $this->userRepo->findByUsername($username);
        
        if (!$user) {
            throw new \Exception("Credenciales inválidas o usuario inactivo");
        }

        // Verify password hash
        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception("Credenciales inválidas");
        }

        // Registrar tiempo de último acceso
        $this->userRepo->updateLastLogin($user['id']);

        // Fetch DB Privileges mapped to the Role
        $privileges = $this->userRepo->getPrivilegesByRole($user['role']);

        // Generate JWT payload
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name'],
            'location_id' => $user['location_id'],
            'privileges' => $privileges,
            'exp' => time() + (60 * 60 * 24) // 1 day
        ];

        return [
            'token' => JwtUtils::generate($payload),
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'location_id' => $user['location_id'],
                'status' => $user['status']
            ]
        ];
    }
}
