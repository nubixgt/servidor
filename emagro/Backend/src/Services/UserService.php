<?php
namespace App\Services;

use App\Repositories\UserRepository;

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
}
