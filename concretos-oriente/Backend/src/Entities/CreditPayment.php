<?php
namespace App\Entities;

class CreditPayment
{
    public ?int $id = null;
    public int $credit_id;
    public float $amount = 0.0;
    public string $payment_date;
    public int $bank_account_id;
    public ?string $check_number = null;
    public ?string $receipt_path = null;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
