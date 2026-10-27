<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\Services\CatalogoService;

class CatalogoController extends Controller
{
    #[Route('/catalogos', 'GET')]
    #[Authorize]
    public function index()
    {
        $this->json((new CatalogoService())->catalogos());
    }

    #[Route('/catalogos/calidad', 'GET')]
    #[HasPrivilege('calidad.ver')]
    public function calidad()
    {
        $this->json((new CatalogoService())->calidadExtra());
    }
}
