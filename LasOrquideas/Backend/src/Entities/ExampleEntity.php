<?php
namespace App\Entities;

class ExampleEntity
{
    public function __construct(
        public ?int $id = null,
        public string $name,
        public string $description
    ) {
    }
}
