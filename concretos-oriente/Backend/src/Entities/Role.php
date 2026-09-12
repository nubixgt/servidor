<?php
namespace App\Entities;

class Role
{
    public ?int $id;
    public string $name;
    public ?string $description;
    public array $permissions;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        
        if (isset($data['permissions'])) {
            $this->permissions = is_string($data['permissions']) 
                ? json_decode($data['permissions'], true) 
                : $data['permissions'];
        } else {
            $this->permissions = [];
        }

        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
