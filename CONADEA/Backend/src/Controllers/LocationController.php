<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\LocationService;

class LocationController extends Controller
{
    #[Route('/ubicacion/departamentos', 'GET')]
    public function departamentos()
    {
        $service = new LocationService();
        $this->json([
            'status' => 'success',
            'data' => $service->listDepartamentos(),
        ]);
    }

    #[Route('/ubicacion/municipios/{departamento_id}', 'GET')]
    public function municipios($departamentoId)
    {
        $service = new LocationService();

        try {
            $this->json([
                'status' => 'success',
                'data' => $service->listMunicipios((int) $departamentoId),
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
}
