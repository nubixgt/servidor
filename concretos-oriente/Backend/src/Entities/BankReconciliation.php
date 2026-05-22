<?php
namespace App\Entities;

class BankReconciliation
{
    public ?int $id = null;
    public int $bank_account_id;
    public string $periodo;
    public float $saldo_banco = 0.0;
    public string $partidas_conciliatorias = '[]';
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
