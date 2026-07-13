<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\PadronService;

class QueryController extends Controller
{
    #[Route('/query', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function search()
    {
        $q = $_GET['q'] ?? '';
        $municipio = $_GET['municipio'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);

        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 50;
        if ($limit > 100) $limit = 100;

        $service = new PadronService();
        
        try {
            $data = $service->queryPadron($q, $municipio, $page, $limit);
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => 'Error al buscar en el padrón', 'details' => $e->getMessage()], 500);
        }
    }
}
