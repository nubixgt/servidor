<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\DanoCultivoDTO;
use App\Services\DanoCultivoService;

class DanoCultivoController extends Controller
{
    #[Route('/danos-cultivos', 'GET')]
    public function index()
    {
        try {
            $danos = (new DanoCultivoService())->listar($_GET);
            $data = array_map(fn($d) => $d->toArray(), $danos);
            $this->json(['danos' => $data, 'total' => count($data)]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/danos-cultivos', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = DanoCultivoDTO::fromRequest($data);
        try {
            $dano = (new DanoCultivoService())->crear($dto);
            $this->json(['dano' => $dano->toArray()], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/danos-cultivos/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            (new DanoCultivoService())->eliminar((int)$id);
            $this->json(['ok' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/danos-cultivos/{id}/fotos', 'POST')]
    public function subirFotos($id)
    {
        try {
            $fotos = (new DanoCultivoService())->subirFotos((int)$id, $_FILES['fotos'] ?? []);
            $this->json(['fotos' => $fotos], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    private function statusFor(\Exception $e): int
    {
        $code = $e->getCode();
        return in_array($code, [400, 401, 403, 404, 409], true) ? $code : 500;
    }
}
