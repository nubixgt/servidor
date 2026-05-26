<?php
namespace App\Entities;

class Client
{
    public ?int $id = null;
    public string $company_name;
    public ?string $ruc = null;
    public string $status = 'active';
    public string $contact_name;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    
    // Virtual fields
    public int $projects_count = 0;
    public float $portfolio_value = 0.00;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
