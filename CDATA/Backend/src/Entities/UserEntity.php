<?php
namespace App\Entities;

class UserEntity
{
    public $id;
    public $username;
    public $password_hash;
    public $role;

    public function __construct($id, $username, $password_hash, $role = 'admin')
    {
        $this->id = $id;
        $this->username = $username;
        $this->password_hash = $password_hash;
        $this->role = $role;
    }
}
