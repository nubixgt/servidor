<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\EstadisticaService;

class EstadisticaController extends Controller
{
    #[Route('/estadisticas/jugadores', 'GET')]
    public function jugadores()
    {
        $this->run(fn () => (new EstadisticaService())->jugadores());
    }

    #[Route('/estadisticas/jugadores/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function updateJugador($id)
    {
        $body = $this->body();
        $this->run(fn () => (new EstadisticaService())->updateJugador((int) $id, $body));
    }

    #[Route('/estadisticas/clasificacion', 'GET')]
    public function clasificacion()
    {
        $this->run(fn () => (new EstadisticaService())->clasificacion());
    }

    #[Route('/estadisticas/clasificacion/{equipoId}', 'PUT')]
    #[Authorize(['admin'])]
    public function updateClasificacion($equipoId)
    {
        $body = $this->body();
        $this->run(fn () => (new EstadisticaService())->updateClasificacion((int) $equipoId, $body));
    }

    #[Route('/estadisticas/recalcular', 'POST')]
    #[Authorize(['admin'])]
    public function recalcular()
    {
        $this->run(fn () => (new EstadisticaService())->recalcular());
    }
}
