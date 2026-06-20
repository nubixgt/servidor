<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\MechanicRecordService;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Utils\Response;
use Exception;

class MechanicRecordController extends Controller
{
    private MechanicRecordService $service;

    public function __construct()
    {
        $this->service = new MechanicRecordService();
    }

    #[Route('/mechanic-records', 'GET')]
    #[Authorize]
    public function index(): void
    {
        try {
            Response::json(['success' => true, 'data' => $this->service->getAll()]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/mechanic-records/plates', 'GET')]
    #[Authorize]
    public function plates(): void
    {
        try {
            Response::json(['success' => true, 'data' => $this->service->getAllPlates()]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/mechanic-records/{id}/items', 'GET')]
    #[Authorize]
    public function items(int $id): void
    {
        try {
            Response::json(['success' => true, 'data' => $this->service->getItems($id)]);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            Response::json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/mechanic-records', 'POST')]
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

    #[Route('/mechanic-records/update/{id}', 'POST')]
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

    #[Route('/mechanic-records/{id}', 'DELETE')]
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
