<?php
namespace App\Entities;

class Vote
{
    public $id;
    public $optionId;
    public $createdAt;

    public function __construct($id, $optionId, $createdAt)
    {
        $this->id = $id;
        $this->optionId = $optionId;
        $this->createdAt = $createdAt;
    }
}
