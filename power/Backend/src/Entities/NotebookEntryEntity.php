<?php
namespace App\Entities;

class NotebookEntryEntity
{
    public function __construct(
        public ?int $id,
        public string $text,
        public string $category,
        public ?string $quote,
        public string $source,
        public string $status,
        public ?string $retiredAt,
        public string $retiredReason,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'category' => $this->category,
            'quote' => $this->quote,
            'source' => $this->source,
            'status' => $this->status,
            'retired_at' => $this->retiredAt,
            'retired_reason' => $this->retiredReason,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
