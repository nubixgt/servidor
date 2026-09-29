<?php
namespace App\DTOs;

class ExampleDTO
{
    public function __construct(
        public string $name,
        public string $description
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['description'] ?? ''
        );
    }
}
