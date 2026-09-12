<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Repositories\StatementRepository;
use Exception;

class StatementController extends Controller
{
    private StatementRepository $stmtRepo;

    public function __construct()
    {
        $this->stmtRepo = new StatementRepository();
    }

    #[Route('/statement', 'GET')]
    #[Authorize]
    public function index()
    {
        try {
            $data = $this->stmtRepo->getGeneralStatement();

            $this->json([
                'status'  => 'success',
                'success' => true,
                'data'    => $data
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
