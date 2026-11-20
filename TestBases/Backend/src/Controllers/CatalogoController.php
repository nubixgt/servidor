<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Controller;
use App\Services\BeneficiarioService;
use App\Utils\Response;

class CatalogoController extends Controller
{
    /** Listas para los filtros: comunidades y comunidades base. */
    #[Route('/catalogos', 'GET')]
    #[Authorize(['admin', 'tecnico'])]
    public function index()
    {
        Response::success((new BeneficiarioService())->catalogos());
    }
}
