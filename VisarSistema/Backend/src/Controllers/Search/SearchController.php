<?php
namespace App\Controllers\Search;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\Search\SearchRepository;

class SearchController extends Controller
{
    private $repository;

    public function __construct()
    {
        $this->repository = new SearchRepository();
    }

    #[Route('/search', 'GET')]
    public function search()
    {
        try {
            $query = trim($_GET['q'] ?? '');

            if (strlen($query) < 2) {
                $this->json(['status' => 'success', 'data' => []]);
                return;
            }

            $results = $this->repository->globalSearch($query);
            $this->json(['status' => 'success', 'data' => $results]);

        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
