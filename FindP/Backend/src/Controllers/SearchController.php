<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\SearchService;

class SearchController extends Controller
{
    #[Route('/search', 'GET')]
    public function search()
    {
        $query = $_GET['q'] ?? '';
        
        try {
            $service = new SearchService();
            $data = $service->search($query);
            
            // Add CORS header if needed specifically, though index.php handles it globally
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
