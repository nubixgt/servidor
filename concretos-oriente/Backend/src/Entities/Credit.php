<?php
namespace App\Entities;

class Credit
{
    public ?int $id = null;
    public int $supplier_id;
    public ?int $project_id = null;
    public ?string $invoice_number = null;
    public string $purchase_date;
    public float $amount = 0.0;
    public string $due_date;
    public ?string $observations = null;
    public string $status = 'Pendiente';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
