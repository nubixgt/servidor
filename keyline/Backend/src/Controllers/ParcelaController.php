<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\ParcelaDTO;
use App\Services\ParcelaService;
use App\Services\FotoService;
use App\Services\AuthService;

class ParcelaController extends Controller
{
    #[Route('/parcelas', 'GET')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function index()
    {
        $currentUser = AuthService::currentPayload();
        $service = new ParcelaService();
        try {
            $parcelas = $service->listar($_GET, $currentUser);
            $data = array_map(fn($p) => $p->toArray(), $parcelas);
            $this->json(['parcelas' => $data, 'total' => count($data)]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}', 'GET')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function show($id)
    {
        $currentUser = AuthService::currentPayload();
        $service = new ParcelaService();
        try {
            $parcela = $service->obtener((int)$id, $currentUser);
            $this->json(['parcela' => $parcela->toArray()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas', 'POST')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function create()
    {
        $currentUser = AuthService::currentPayload();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = ParcelaDTO::fromRequest($data);
        $service = new ParcelaService();
        try {
            $parcela = $service->crear($dto, $currentUser);
            $this->json(['parcela' => $parcela->toArray()], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}', 'PUT')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function update($id)
    {
        $currentUser = AuthService::currentPayload();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = ParcelaDTO::fromRequest($data);
        $service = new ParcelaService();
        try {
            $parcela = $service->actualizar((int)$id, $dto, $currentUser);
            $this->json(['parcela' => $parcela->toArray()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}/revision', 'POST')]
    #[Authorize(['supervisor', 'administrador'])]
    public function revisar($id)
    {
        $currentUser = AuthService::currentPayload();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new ParcelaService();
        try {
            $parcela = $service->revisar(
                (int)$id,
                $data['estadoValidacion'] ?? '',
                $data['comentario'] ?? '',
                $currentUser
            );
            $this->json(['parcela' => $parcela->toArray()]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}', 'DELETE')]
    #[Authorize(['administrador'])]
    public function delete($id)
    {
        $currentUser = AuthService::currentPayload();
        $service = new ParcelaService();
        try {
            $service->eliminar((int)$id, $currentUser);
            (new FotoService())->eliminarCarpetaParcela((int)$id);
            $this->json(['ok' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}/fotos', 'POST')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function subirFotos($id)
    {
        $currentUser = AuthService::currentPayload();
        $service = new FotoService();
        try {
            $fotos = $service->subir(
                (int)$id,
                $_FILES['fotos'] ?? [],
                $currentUser['nombre'] ?? '',
                $_POST['caption'] ?? '',
                $currentUser
            );
            $this->json(['fotos' => $fotos], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], $this->statusFor($e));
        }
    }

    #[Route('/parcelas/{id}/fotos/{fotoId}', 'DELETE')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function eliminarFoto($id, $fotoId)
    {
        $currentUser = AuthService::currentPayload();
        $service = new FotoService();
        try {
            $service->eliminar((int)$id, (int)$fotoId, $currentUser);
            $this->json(['ok' => true]);
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
