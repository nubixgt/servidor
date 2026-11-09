<?php
namespace App\Entities;

class Reservation
{
    public function __construct(
        public int $tableId,
        public string $bookingId,
        public string $name,
        public string $createdAt, // UTC, formato ISO 8601
        public ?int $userId = null,
        public ?string $userName = null, // nombre completo de quien registró
        public ?string $category = null,
        public ?float $price = null // quetzales
    ) {
    }
}
