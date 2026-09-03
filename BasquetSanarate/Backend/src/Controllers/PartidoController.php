<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\PartidoDTO;
use App\Services\PartidoService;

class PartidoController extends Controller
{
    #[Route('/partidos', 'GET')]
    public function index()
    {
        $this->run(fn () => (new PartidoService())->list([
            'estado' => $this->query('estado'),
            'jornada' => $this->query('jornada'),
            'fase' => $this->query('fase'),
            'equipo_id' => $this->query('equipo_id'),
        ]));
    }

    #[Route('/partidos/{id}', 'GET')]
    public function show($id)
    {
        $this->run(fn () => (new PartidoService())->get((int) $id));
    }

    #[Route('/partidos', 'POST')]
    #[Authorize(['admin'])]
    public function store()
    {
        $dto = PartidoDTO::fromRequest($this->body());
        $this->run(fn () => (new PartidoService())->create($dto), 201);
    }

    #[Route('/partidos/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $dto = PartidoDTO::fromRequest($this->body());
        $this->run(fn () => (new PartidoService())->update((int) $id, $dto));
    }

    #[Route('/partidos/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function destroy($id)
    {
        $this->run(fn () => (new PartidoService())->delete((int) $id));
    }
}
