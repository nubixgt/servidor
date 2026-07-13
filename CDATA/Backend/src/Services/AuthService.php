<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function login($usuario, $password)
    {
        $user = $this->repository->findByUsername($usuario);
        
        if ($user && password_verify($password, $user->password_hash)) {
            $payload = [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'exp' => time() + (60 * 60 * 24) // 24 hours
            ];
            
            $token = JwtUtils::generate($payload);
            
            return [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role
                ]
            ];
        }
        
        return false;
    }
}
