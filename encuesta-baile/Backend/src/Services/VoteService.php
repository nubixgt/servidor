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

        if ($dto->optionId === 'option-b') {
            $dto->singer = null;
        }

        if ($dto->optionId === 'option-a' && !in_array($dto->singer, ['Eddy Herrera', 'Wilfredo Vargas'])) {
            throw new \InvalidArgumentException('Invalid singer selected for Option A');
        }

        $this->repository->insert($dto->optionId, $dto->singer);
    }

    public function getVoteSummary(): array
    {
        return $this->repository->getCountsByOption();
    }
}
