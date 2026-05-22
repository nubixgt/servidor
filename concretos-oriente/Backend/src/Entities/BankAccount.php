<?php
namespace App\Entities;

class BankAccount
{
    public ?int $id = null;
    public string $nombre_banco;
    public string $numero_cuenta;
    public string $tipo_cuenta;
    public string $moneda = 'GTQ';
    public int $activa = 1;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
