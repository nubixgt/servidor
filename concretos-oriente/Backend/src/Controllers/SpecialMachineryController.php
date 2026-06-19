<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\SpecialMachineryService;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Utils\Response;
use Exception;

class SpecialMachineryController extends Controller
{
    private SpecialMachineryService $service;

    public function __construct()
    {
        $this->service = new SpecialMachineryService();
    }

    #[Route('/special-machinery', 'GET')]
    #[Authorize]
    public function index(): void
    {
        try {
            Response::json(['success' => true, 'data' => $this->service->getAll()]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/special-machinery', 'POST')]
    #[Authorize]
    public function store(): void
    {
        try {
            $result = $this->service->create($_POST, $_FILES);
            Response::json($result, 201);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            Response::json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/special-machinery/update/{id}', 'POST')]
    #[Authorize]
    public function update(int $id): void
    {
        try {
            $result = $this->service->update($id, $_POST, $_FILES);
            Response::json($result);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            Response::json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/special-machinery/{id}', 'DELETE')]
    #[Authorize]
    public function destroy(int $id): void
    {
        try {
            $result = $this->service->delete($id);
            Response::json($result);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            Response::json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }
}
