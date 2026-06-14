<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\RegistroVacunacionDTO;
use App\Services\RegistroVacunacionService;

class RegistroVacunacionController extends Controller
{
    #[Route('/registros-vacunacion', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $dto = RegistroVacunacionDTO::fromRequest($data);
            $service = new RegistroVacunacionService();
            
            if ($service->createRegistro($dto)) {
                $this->json(['status' => 'success', 'message' => 'Registro creado exitosamente.'], 201);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo crear el registro.'], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno del servidor.', 'details' => $e->getMessage()], 500);
        }
    }
}
