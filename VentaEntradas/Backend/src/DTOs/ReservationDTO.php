<?php
namespace App\DTOs;

class ReservationDTO
{
    public function __construct(
        public mixed $tableIds,
        public mixed $name,
        public mixed $phone = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self($data['tableIds'] ?? null, $data['name'] ?? null, $data['phone'] ?? null);
    }
}
