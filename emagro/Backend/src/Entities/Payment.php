<?php
namespace App\Entities;

class Payment
{
    public $id;
    public $payment_number;
    public $client_id;
    public $concept;
    public $due_date;
    public $amount;
    public $status;
}
