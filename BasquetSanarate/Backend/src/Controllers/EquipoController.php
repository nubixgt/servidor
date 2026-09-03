<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\EquipoDTO;
use App\Services\EquipoService;

class EquipoController extends Controller
{
    #[Route('/equipos', 'GET')]
    public function index()
    {
        $this->run(fn () => (new EquipoService())->list([
            'soloActivos' => $this->query('activo', '1') !== '0',
            'conferencia' => $this->query('conferencia'),
            'rama' => $this->query('rama'),
            'search' => $this->query('search'),
        ]));
    }

    #[Route('/equipos/{id}', 'GET')]
    public function show($id)
    {
        $this->run(fn () => (new EquipoService())->get((int) $id));
    }

    #[Route('/equipos', 'POST')]
    #[Authorize(['admin'])]
    public function store()
    {
        $dto = EquipoDTO::fromRequest($this->body());
        $this->run(fn () => (new EquipoService())->create($dto), 201);
    }

    #[Route('/equipos/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $dto = EquipoDTO::fromRequest($this->body());
        $this->run(fn () => (new EquipoService())->update((int) $id, $dto));
    }

    #[Route('/equipos/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function destroy($id)
    {
        $this->run(fn () => (new EquipoService())->delete((int) $id));
    }

    #[Route('/equipos/{id}/logo', 'POST')]
    #[Authorize(['admin'])]
    public function uploadLogo($id)
    {
        $this->run(fn () => (new EquipoService())->uploadLogo((int) $id, $_FILES['logo'] ?? ['error' => UPLOAD_ERR_NO_FILE]));
    }
}
