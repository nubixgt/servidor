<?php
namespace App\DTOs;

class CambioClaveDTO
{
    public function __construct(
        public string $actual,
        public string $nueva
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            (string) ($data['actual'] ?? ''),
            (string) ($data['nueva'] ?? '')
        );
    }
}
