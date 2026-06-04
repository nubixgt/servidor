<?php
namespace App\DTOs;

class VoteDTO
{
    public $optionId;

    public function __construct($optionId)
    {
        $this->optionId = $optionId;
    }

    public static function fromRequest(array $data): self
    {
        return new self($data['option_id'] ?? null);
    }
}
