<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Repositories\ParticipanteRepository;
use App\Utils\Response;

class ParticipanteController extends Controller
{
    #[Route('/participantes', 'GET')]
    #[Authorize(['admin'])]
    public function index()
    {
        $repository = new ParticipanteRepository();
        Response::success($repository->findAll());
    }
}
