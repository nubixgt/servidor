<?php
namespace App\DTOs;

class VoteDTO
{
    public $optionId;
    public $singer;

    public function __construct($optionId, $singer = null)
    {
        $this->optionId = $optionId;
        $this->singer = $singer;
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['option_id'] ?? null,
            $data['singer'] ?? null
        );
    }
}
