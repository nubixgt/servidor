<?php
namespace App\Services;

use App\DTOs\VoteDTO;
use App\Repositories\VoteRepository;

class VoteService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new VoteRepository();
    }

    public function createVote(VoteDTO $dto): void
    {
        if (!$dto->optionId || !in_array($dto->optionId, ['option-a', 'option-b'])) {
            throw new \InvalidArgumentException('Invalid option_id');
        }

        $this->repository->insert($dto->optionId);
    }

    public function getVoteSummary(): array
    {
        return $this->repository->getCountsByOption();
    }
}
