<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\PadreDTO;
use App\Services\PadreService;

class PadreController extends Controller
{
    #[Route('/padres', 'POST')]
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = PadreDTO::fromRequest($data);
        $service = new PadreService();

        try {
            $id = $service->register($dto);
            $this->json([
                'status'  => 'success',
                'message' => '¡Registro enviado correctamente!',
                'id'      => $id,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 409);
        }
    }

    #[Route('/padres', 'GET')]
    public function getAll()
    {
        $service = new PadreService();
        $registros = $service->getAll();

        $data = array_map(fn($r) => [
            'id'              => $r->id,
            'nombre_completo' => $r->nombreCompleto,
            'telefono'        => $r->telefono,
            'correo'          => $r->correo,
            'direccion'       => $r->direccion,
            'departamento'    => $r->departamento,
            'fecha_registro'  => $r->fechaRegistro,
        ], $registros);

        $this->json(['status' => 'success', 'data' => $data]);
    }
}
