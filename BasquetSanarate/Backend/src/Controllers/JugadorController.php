<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\JugadorDTO;
use App\Services\JugadorService;

class JugadorController extends Controller
{
    #[Route('/jugadores', 'GET')]
    public function index()
    {
        $this->run(fn () => (new JugadorService())->list([
            'soloActivos' => $this->query('activo', '1') !== '0',
            'equipo_id' => $this->query('equipo_id'),
            'posicion' => $this->query('posicion'),
            'estado' => $this->query('estado'),
            'search' => $this->query('search'),
            'page' => (int) $this->query('page', 1),
            'perPage' => (int) $this->query('perPage', 0),
        ]));
    }

    #[Route('/jugadores/{id}', 'GET')]
    public function show($id)
    {
        $this->run(fn () => (new JugadorService())->get((int) $id));
    }

    #[Route('/jugadores', 'POST')]
    #[Authorize(['admin'])]
    public function store()
    {
        $dto = JugadorDTO::fromRequest($this->body());
        $this->run(fn () => (new JugadorService())->create($dto), 201);
    }

    #[Route('/jugadores/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $dto = JugadorDTO::fromRequest($this->body());
        $this->run(fn () => (new JugadorService())->update((int) $id, $dto));
    }

    #[Route('/jugadores/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function destroy($id)
    {
        $this->run(fn () => (new JugadorService())->delete((int) $id));
    }

    #[Route('/jugadores/{id}/foto', 'POST')]
    #[Authorize(['admin'])]
    public function uploadFoto($id)
    {
        $this->run(fn () => (new JugadorService())->uploadFoto((int) $id, $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE]));
    }
}
