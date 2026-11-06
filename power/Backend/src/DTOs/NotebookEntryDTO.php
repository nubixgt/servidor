<?php
namespace App\DTOs;

use App\Support\NotebookCategories;

/**
 * Create/patch payload for a coach-notebook entry.
 *
 * On create: `text` required, `category` optional, `status` optional
 * (defaults 'vigente' -- a manual note is the athlete's, born active).
 *
 * On patch: every field is optional; only the ones present are applied.
 * Use `has*()` to tell "absent" from "cleared".
 */
class NotebookEntryDTO
{
    private ?string $text = null;
    private ?string $category = null;
    private ?string $status = null;
    private ?string $retiredReason = null;

    private bool $hasText = false;
    private bool $hasCategory = false;
    private bool $hasStatus = false;
    private bool $hasRetiredReason = false;

    public static function fromRequest(array $d): self
    {
        $dto = new self();

        if (array_key_exists('text', $d)) {
            $dto->text = trim((string) $d['text']);
            $dto->hasText = true;
        }
        if (array_key_exists('category', $d)) {
            $dto->category = NotebookCategories::normalize((string) $d['category']);
            $dto->hasCategory = true;
        }
        if (array_key_exists('status', $d) && in_array($d['status'], NotebookCategories::STATUSES, true)) {
            $dto->status = $d['status'];
            $dto->hasStatus = true;
        }
        if (array_key_exists('retired_reason', $d)) {
            $dto->retiredReason = trim((string) $d['retired_reason']);
            $dto->hasRetiredReason = true;
        }

        return $dto;
    }

    public function text(): ?string { return $this->text; }
    public function category(): ?string { return $this->category; }
    public function status(): ?string { return $this->status; }
    public function retiredReason(): string { return $this->retiredReason ?? ''; }

    public function hasText(): bool { return $this->hasText; }
    public function hasCategory(): bool { return $this->hasCategory; }
    public function hasStatus(): bool { return $this->hasStatus; }
    public function hasRetiredReason(): bool { return $this->hasRetiredReason; }

    public function isEmptyPatch(): bool
    {
        return !$this->hasText && !$this->hasCategory && !$this->hasStatus && !$this->hasRetiredReason;
    }
}
