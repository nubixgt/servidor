<?php
namespace App\Entities;

class Usuario
{
    public $id;
    public $username;
    public $password_hash;
    public $full_name;
    public $role;
    public $status;
    public $created_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->password_hash = $data['password_hash'] ?? null;
        $this->full_name = $data['full_name'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
}
