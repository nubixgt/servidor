<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\VoteDTO;
use App\Services\VoteService;

class VoteController extends Controller
{
    #[Route('/votes', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = VoteDTO::fromRequest($data);
        $service = new VoteService();

        try {
            $service->createVote($dto);
            $this->json(['message' => 'Vote recorded successfully'], 201);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/votes', 'GET')]
    public function index()
    {
        $service = new VoteService();

        try {
            $summary = $service->getVoteSummary();
            $this->json([
                'status' => 'success',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
