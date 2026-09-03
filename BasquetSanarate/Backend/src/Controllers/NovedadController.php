<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\NovedadDTO;
use App\Services\NovedadService;

class NovedadController extends Controller
{
    #[Route('/novedades', 'GET')]
    public function index()
    {
        $categoria = $this->query('categoria');
        if ($this->isAdmin()) {
            $filters = ['categoria' => $categoria];
            $estado = $this->query('estado');
            if ($estado) {
                $filters['estado'] = $estado;
            }
            $this->run(fn () => (new NovedadService())->listAdmin($filters));
            return;
        }
        $this->run(fn () => (new NovedadService())->listPublicas($categoria));
    }

    #[Route('/novedades/{id}', 'GET')]
    public function show($id)
    {
        $this->run(fn () => (new NovedadService())->get((int) $id));
    }

    #[Route('/novedades', 'POST')]
    #[Authorize(['admin'])]
    public function store()
    {
        $dto = NovedadDTO::fromRequest($this->body());
        $autor = $this->userId();
        $this->run(fn () => (new NovedadService())->create($dto, $autor), 201);
    }

    #[Route('/novedades/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $dto = NovedadDTO::fromRequest($this->body());
        $this->run(fn () => (new NovedadService())->update((int) $id, $dto));
    }

    #[Route('/novedades/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function destroy($id)
    {
        $this->run(fn () => (new NovedadService())->delete((int) $id));
    }

    #[Route('/novedades/{id}/portada', 'POST')]
    #[Authorize(['admin'])]
    public function uploadPortada($id)
    {
        $this->run(fn () => (new NovedadService())->uploadPortada((int) $id, $_FILES['portada'] ?? ['error' => UPLOAD_ERR_NO_FILE]));
    }

    #[Route('/novedades/{id}/pdf', 'POST')]
    #[Authorize(['admin'])]
    public function uploadPdf($id)
    {
        $this->run(fn () => (new NovedadService())->uploadPdf((int) $id, $_FILES['pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE]));
    }
}
